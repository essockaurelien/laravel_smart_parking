<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Spot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        $role = $authUser['role'] ?? 'base';

        $query = Reservation::query()->orderByDesc('created_at');
        if ($role !== 'admin') {
            $query->where('user_id', $authUser['id'] ?? '');
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $this->expireStaleReservations($request);

        $data = $request->validate([
            'spot_id' => ['required', 'integer'],
            'arrival_eta' => ['required', 'date', 'after_or_equal:now'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'card_number' => ['required', 'regex:/^\d{12,19}$/'],
            'card_holder' => ['required', 'string', 'max:255'],
            'card_exp_month' => ['required', 'integer', 'min:1', 'max:12'],
            'card_exp_year' => ['required', 'integer', 'min:2020'],
        ]);

        $authUser = $request->attributes->get('auth_user');
        if (!$this->isPremiumUser($authUser)) {
            return response()->json(['message' => 'Premium access required'], 403);
        }

        $startAt = Carbon::parse($data['arrival_eta']);
        $endAt = (clone $startAt)->addMinutes($data['duration_minutes']);

        $spot = Spot::findOrFail($data['spot_id']);
        if ($spot->is_reserved || $spot->is_occupied) {
            return response()->json(['message' => 'Spot not available'], 409);
        }

        $overlap = Reservation::where('spot_id', $spot->id)
            ->whereIn('status', ['pending', 'active'])
            ->where(function ($query) use ($startAt, $endAt) {
                $query->whereBetween('start_at', [$startAt, $endAt])
                    ->orWhereBetween('end_at', [$startAt, $endAt])
                    ->orWhere(function ($inner) use ($startAt, $endAt) {
                        $inner->where('start_at', '<=', $startAt)
                            ->where('end_at', '>=', $endAt);
                    });
            })
            ->exists();

        if ($overlap) {
            return response()->json(['message' => 'Spot already reserved for this time window'], 409);
        }

        $reservation = Reservation::create([
            'spot_id' => $data['spot_id'],
            'user_id' => $authUser['id'] ?? 'unknown',
            'status' => 'pending',
            'arrival_eta' => Carbon::parse($data['arrival_eta']),
            'duration_minutes' => $data['duration_minutes'],
            'start_at' => $startAt,
            'end_at' => $endAt,
            'card_last4' => $this->cardLast4($data['card_number']),
            'card_holder' => $data['card_holder'],
            'card_exp_month' => $data['card_exp_month'],
            'card_exp_year' => $data['card_exp_year'],
        ]);

        $spot->update(['is_reserved' => true]);

        return response()->json($reservation, 201);
    }

    public function cancel(Request $request, Reservation $reservation)
    {
        $authUser = $request->attributes->get('auth_user');
        $role = $authUser['role'] ?? 'base';
        if ($role !== 'admin' && ($authUser['id'] ?? null) !== $reservation->user_id) {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        if ($reservation->status === 'cancelled') {
            return response()->json($reservation);
        }

        $reservation->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        Spot::where('id', $reservation->spot_id)->update(['is_reserved' => false]);

        return response()->json($reservation);
    }

    private function expireStaleReservations(Request $request): void
    {
        $tolerance = (int) config('parking.reservation_tolerance_minutes');
        $penalty = (float) config('parking.no_show_penalty');

        $expired = Reservation::where('status', 'pending')
            ->where('arrival_eta', '<', now()->subMinutes($tolerance))
            ->get();

        foreach ($expired as $reservation) {
            $reservation->update([
                'status' => 'no_show',
                'cancelled_at' => now(),
                'penalty_amount' => $penalty,
            ]);

            Spot::where('id', $reservation->spot_id)->update(['is_reserved' => false]);
            $this->createPenaltyPayment($request, $reservation->user_id, $reservation->id, $penalty);
        }
    }

    private function createPenaltyPayment(Request $request, string $userId, int $reservationId, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $token = $request->bearerToken();
        $baseUrl = rtrim(config('payments-service.base_url'), '/');
        if (!$token || !$baseUrl) {
            return;
        }

        $internalToken = config('payments-service.internal_token');

        $client = Http::withToken($token);
        if ($internalToken) {
            $client = $client->withHeaders(['X-Internal-Token' => $internalToken]);
        }

        $client->post("{$baseUrl}/api/payments", [
            'user_id' => $userId,
            'type' => 'no_show',
            'reference_id' => (string) $reservationId,
            'amount' => $amount,
        ]);
    }

    private function cardLast4(string $cardNumber): string
    {
        return substr($cardNumber, -4);
    }

    private function isPremiumUser(?array $authUser): bool
    {
        if (!$authUser) {
            return false;
        }

        $role = $authUser['role'] ?? 'base';
        if ($role === 'admin') {
            return true;
        }
        if ($role !== 'premium') {
            return false;
        }

        if (!empty($authUser['premium_until'])) {
            $premiumUntil = strtotime($authUser['premium_until']);
            if ($premiumUntil !== false && $premiumUntil < time()) {
                return false;
            }
        }

        return true;
    }
}
