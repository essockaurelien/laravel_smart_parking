<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Models\Spot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ExpireReservationsCommand extends Command
{
    protected $signature = 'spark:expire-reservations';
    protected $description = 'Expire overdue reservations and apply no-show penalties.';

    public function handle(): int
    {
        $tolerance = (int) config('parking.reservation_tolerance_minutes');
        $penalty = (float) config('parking.no_show_penalty');

        $expired = Reservation::where('status', 'pending')
            ->where('arrival_eta', '<', now()->subMinutes($tolerance))
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No reservations to expire.');
            return Command::SUCCESS;
        }

        $token = $this->getSystemToken();
        $baseUrl = rtrim(config('payments-service.base_url'), '/');

        foreach ($expired as $reservation) {
            $reservation->update([
                'status' => 'no_show',
                'cancelled_at' => now(),
                'penalty_amount' => $penalty,
            ]);

            Spot::where('id', $reservation->spot_id)->update(['is_reserved' => false]);

            if ($token && $baseUrl && $penalty > 0) {
                $internalToken = config('payments-service.internal_token');
                $client = Http::withToken($token);
                if ($internalToken) {
                    $client = $client->withHeaders(['X-Internal-Token' => $internalToken]);
                }

                $client->post("{$baseUrl}/api/payments", [
                    'user_id' => $reservation->user_id,
                    'type' => 'no_show',
                    'reference_id' => (string) $reservation->id,
                    'amount' => $penalty,
                ]);
            }
        }

        $this->info("Expired {$expired->count()} reservations.");

        return Command::SUCCESS;
    }

    private function getSystemToken(): ?string
    {
        $email = config('parking.system_email');
        $password = config('parking.system_password');
        if (!$email || !$password) {
            return null;
        }

        $authBaseUrl = rtrim(config('auth-service.base_url'), '/');
        if (!$authBaseUrl) {
            return null;
        }

        $response = Http::acceptJson()->asJson()->post("{$authBaseUrl}/api/login", [
            'email' => $email,
            'password' => $password,
        ]);

        if (!$response->ok()) {
            return null;
        }

        return $response->json('access_token');
    }
}
