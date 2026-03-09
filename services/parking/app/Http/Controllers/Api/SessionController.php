<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParkingSession;
use App\Models\Reservation;
use App\Models\Spot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SessionController extends Controller
{
    public function index(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        $role = $authUser['role'] ?? 'base';

        $query = ParkingSession::query()->orderByDesc('check_in_at');
        if ($role !== 'admin') {
            $query->where('user_id', $authUser['id'] ?? '');
        }

        return $query->get();
    }

    public function checkin(Request $request)
    {
        $this->expireStaleReservations($request);

        $data = $request->validate([
            'spot_id' => ['required', 'integer'],
        ]);

        $authUser = $request->attributes->get('auth_user');
        $userId = $authUser['id'] ?? null;
        if (!$userId) {
            return response()->json(['message' => 'Missing user'], 401);
        }

        $spot = Spot::findOrFail($data['spot_id']);
        if ($spot->is_occupied) {
            return response()->json(['message' => 'Spot already occupied'], 409);
        }

        if ($spot->is_reserved) {
            $tolerance = (int) config('parking.reservation_tolerance_minutes');
            $reservedByOther = Reservation::where('spot_id', $spot->id)
                ->where('status', 'pending')
                ->where('user_id', '!=', $userId)
                ->where('arrival_eta', '>=', now()->subMinutes($tolerance))
                ->exists();

            if ($reservedByOther) {
                return response()->json(['message' => 'Spot reserved for another user'], 409);
            }
        }

        $openSession = ParkingSession::where('user_id', $userId)
            ->whereNull('check_out_at')
            ->first();
        if ($openSession) {
            return response()->json(['message' => 'Active session already open'], 409);
        }

        $session = ParkingSession::create([
            'spot_id' => $spot->id,
            'user_id' => $userId,
            'check_in_at' => now(),
        ]);

        $spot->update([
            'is_occupied' => true,
            'is_reserved' => false,
            'current_user_id' => $userId,
        ]);

        Reservation::where('spot_id', $spot->id)
            ->where('user_id', $userId)
            ->whereIn('status', ['pending'])
            ->update(['status' => 'active']);

        $this->notifyOccupancy($spot->id, true);

        return response()->json($session, 201);
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'session_id' => ['required', 'integer'],
        ]);

        $authUser = $request->attributes->get('auth_user');
        $role = $authUser['role'] ?? 'base';

        $session = ParkingSession::findOrFail($data['session_id']);
        if ($role !== 'admin' && ($authUser['id'] ?? null) !== $session->user_id) {
            return response()->json(['message' => 'Not authorized'], 403);
        }
        if ($session->check_out_at) {
            return response()->json(['message' => 'Session already closed'], 409);
        }

        $checkoutAt = now();
        $totalMinutes = Carbon::parse($session->check_in_at)->diffInMinutes($checkoutAt);
        $ratePerHour = $this->getRatePerHour($request);
        $fee = round(($totalMinutes / 60) * $ratePerHour, 2);

        $session->update([
            'check_out_at' => $checkoutAt,
            'total_minutes' => $totalMinutes,
            'parking_fee' => $fee,
        ]);

        Spot::where('id', $session->spot_id)->update([
            'is_occupied' => false,
            'current_user_id' => null,
        ]);

        Reservation::where('spot_id', $session->spot_id)
            ->where('user_id', $session->user_id)
            ->whereIn('status', ['active'])
            ->update(['status' => 'completed']);

        $this->createParkingPayment($request, $session->user_id, $session->id, $fee);
        $this->notifyOccupancy($session->spot_id, false);

        return response()->json($session);
    }

    private function createParkingPayment(Request $request, string $userId, int $sessionId, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $token = $request->bearerToken();
        $baseUrl = rtrim(config('payments-service.base_url'), '/');
        if (!$token || !$baseUrl) {
            return;
        }

        $authUser = $request->attributes->get('auth_user');

        $internalToken = config('payments-service.internal_token');

        $client = Http::withToken($token);
        if ($internalToken) {
            $client = $client->withHeaders(['X-Internal-Token' => $internalToken]);
        }

        $client->post("{$baseUrl}/api/payments", [
            'user_id' => $userId,
            'user_role' => $authUser['role'] ?? null,
            'type' => 'parking',
            'reference_id' => (string) $sessionId,
            'amount' => $amount,
        ]);
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

    private function getRatePerHour(Request $request): float
    {
        $token = $request->bearerToken();
        $baseUrl = rtrim(config('payments-service.base_url'), '/');
        if (!$token || !$baseUrl) {
            return (float) config('parking.rate_per_hour');
        }

        $response = Http::withToken($token)->get("{$baseUrl}/api/pricing");
        if (!$response->ok()) {
            return (float) config('parking.rate_per_hour');
        }

        $rate = $response->json('parking_rate_per_hour');
        return is_numeric($rate) ? (float) $rate : (float) config('parking.rate_per_hour');
    }

    private function notifyOccupancy(int $spotId, bool $occupied): void
    {
        $baseUrl = rtrim(config('iot.base_url'), '/');
        $internalToken = config('iot.internal_token');
        if (!$baseUrl || !$internalToken) {
            return;
        }

        Http::withHeaders(['X-Internal-Token' => $internalToken])->post("{$baseUrl}/api/internal/spot/occupancy", [
            'spot_id' => $spotId,
            'occupied' => $occupied,
        ]);
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
}
