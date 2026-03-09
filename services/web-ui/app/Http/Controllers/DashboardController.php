<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $token = $request->session()->get('access_token');
        $user = $request->session()->get('user');
        $role = $this->resolveRole($user);
        $view = $this->resolveView($role);

        $summary = [
            'user' => $user,
            'role' => $role,
            'spots' => [],
            'requests' => [],
            'reservations' => [],
            'sessions' => [],
            'pricing' => null,
            'payments' => [],
            'hue_lights' => [],
            'charge_quote' => $request->session()->get('charge_quote'),
            'charge_draft' => $request->session()->get('charge_draft'),
        ];

        if ($token) {
            $summary['spots'] = $this->getData(config('services.parking.base_url') . '/api/spots', $token);
            $summary['requests'] = $this->getData(config('services.charging.base_url') . '/api/charge-requests', $token);
            $summary['reservations'] = $this->getData(config('services.parking.base_url') . '/api/reservations', $token);
            $summary['sessions'] = $this->getData(config('services.parking.base_url') . '/api/sessions', $token);
            $summary['pricing'] = $this->getData(config('services.payments.base_url') . '/api/pricing', $token, true);
            $summary['payments'] = $this->getPayments($request, $token);

            if ($role === 'admin') {
                $summary['hue_lights'] = $this->getData(config('services.iot.base_url') . '/api/hue/lights', $token, true) ?? [];
            }
        }

        return view($view, $summary);
    }

    public function reserve(Request $request)
    {
        $data = $request->validate([
            'spot_id' => ['required', 'integer'],
            'arrival_eta' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'card_number' => ['required', 'regex:/^\d{12,19}$/'],
            'card_holder' => ['required', 'string', 'max:255'],
            'card_exp_month' => ['required', 'integer', 'min:1', 'max:12'],
            'card_exp_year' => ['required', 'integer', 'min:2020'],
        ]);

        $token = $request->session()->get('access_token');

        try {
            $response = Http::withToken($token)->post(config('services.parking.base_url') . '/api/reservations', $data);
        } catch (\Throwable $exception) {
            return redirect('/')->withErrors([
                'reservation' => 'Prenotazione non riuscita: errore di connessione al servizio parcheggio',
            ]);
        }

        if ($response->successful()) {
            $request->session()->forget('errors');
            return redirect('/')->with('status', 'Prenotazione creata');
        }

        $message = $response->json('message')
            ?? $response->json('error')
            ?? $this->firstValidationError($response->json('errors') ?? null)
            ?? 'Prenotazione non riuscita';

        return redirect('/')->withErrors([
            'reservation' => $message . ' (HTTP ' . $response->status() . ')',
        ]);
    }

    private function firstValidationError(?array $errors): ?string
    {
        if (!$errors) {
            return null;
        }

        foreach ($errors as $messages) {
            if (is_array($messages) && isset($messages[0])) {
                return $messages[0];
            }
        }

        return null;
    }

    public function cancelReservation(Request $request, int $reservationId)
    {
        $token = $request->session()->get('access_token');
        $response = Http::withToken($token)->delete(
            config('services.parking.base_url') . "/api/reservations/{$reservationId}"
        );

        return $response->successful()
            ? redirect('/')->with('status', 'Prenotazione annullata')
            : redirect('/')->withErrors(['reservation' => 'Annullamento non riuscito']);
    }

    public function checkin(Request $request)
    {
        $data = $request->validate([
            'spot_id' => ['required', 'integer'],
        ]);

        $token = $request->session()->get('access_token');
        $response = Http::withToken($token)->post(config('services.parking.base_url') . '/api/checkin', $data);

        return $response->successful()
            ? redirect('/')->with('status', 'Check-in completato')
            : redirect('/')->withErrors(['session' => 'Check-in non riuscito']);
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'session_id' => ['required', 'integer'],
        ]);

        $token = $request->session()->get('access_token');
        $response = Http::withToken($token)->post(config('services.parking.base_url') . '/api/checkout', $data);

        return $response->successful()
            ? redirect('/')->with('status', 'Check-out completato')
            : redirect('/')->withErrors(['session' => 'Check-out non riuscito']);
    }

    public function charge(Request $request)
    {
        $data = $request->validate([
            'spot_id' => ['required', 'integer'],
            'target_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'current_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'battery_kwh' => ['nullable', 'numeric', 'min:1'],
            'notify_on_complete' => ['sometimes', 'boolean'],
        ]);

        $token = $request->session()->get('access_token');
        $response = Http::withToken($token)->post(config('services.charging.base_url') . '/api/charge-requests', $data);

        $request->session()->forget(['charge_quote', 'charge_draft']);

        return $response->successful()
            ? redirect('/')->with('status', 'Richiesta di ricarica inviata')
            : redirect('/')->withErrors(['charge' => 'Richiesta di ricarica non riuscita']);
    }

    public function quoteCharge(Request $request)
    {
        $data = $request->validate([
            'spot_id' => ['required', 'integer'],
            'target_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'current_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'battery_kwh' => ['nullable', 'numeric', 'min:1'],
            'notify_on_complete' => ['sometimes', 'boolean'],
        ]);

        $token = $request->session()->get('access_token');
        $payload = Arr::only($data, ['spot_id', 'target_percent', 'current_percent', 'battery_kwh']);
        $response = Http::withToken($token)->post(config('services.charging.base_url') . '/api/charge-requests/quote', $payload);
        if (!$response->ok()) {
            return redirect('/')->withErrors(['charge' => 'Impossibile ottenere il preventivo']);
        }

        $request->session()->put('charge_quote', $response->json());
        $request->session()->put('charge_draft', $data);

        return redirect('/')->with('status', 'Preventivo ricarica pronto');
    }

    public function updatePricing(Request $request)
    {
        $data = $request->validate([
            'parking_rate_per_hour' => ['required', 'numeric', 'min:0'],
            'charging_cost_per_kw' => ['required', 'numeric', 'min:0'],
        ]);

        $token = $request->session()->get('access_token');
        $user = $request->session()->get('user');

        $payload = array_merge($data, ['updated_by' => $user['email'] ?? 'admin']);
        $response = Http::withToken($token)->post(config('services.payments.base_url') . '/api/pricing', $payload);

        return $response->successful()
            ? redirect('/')->with('status', 'Tariffe aggiornate')
            : redirect('/')->withErrors(['pricing' => 'Aggiornamento tariffe non riuscito']);
    }

    private function getData(string $url, string $token, bool $allowEmpty = false)
    {
        $response = Http::withToken($token)->get($url);
        if (!$response->ok()) {
            return $allowEmpty ? null : [];
        }

        return $response->json();
    }

    private function getPayments(Request $request, string $token): array
    {
        $query = array_filter([
            'type' => $request->query('payment_type'),
            'user_role' => $request->query('payment_role'),
            'from' => $request->query('payment_from'),
            'to' => $request->query('payment_to'),
        ], fn ($value) => $value !== null && $value !== '');

        $response = Http::withToken($token)->get(config('services.payments.base_url') . '/api/payments', $query);
        if (!$response->ok()) {
            return [];
        }

        return $response->json() ?? [];
    }

    private function resolveRole(?array $user): string
    {
        $role = $user['role'] ?? 'base';
        if ($role === 'premium' && !empty($user['premium_until'])) {
            $premiumUntil = strtotime($user['premium_until']);
            if ($premiumUntil !== false && $premiumUntil < time()) {
                return 'base';
            }
        }

        return $role;
    }

    private function resolveView(string $role): string
    {
        return match ($role) {
            'admin' => 'dashboard-admin',
            'premium' => 'dashboard-premium',
            default => 'dashboard-base',
        };
    }
}
