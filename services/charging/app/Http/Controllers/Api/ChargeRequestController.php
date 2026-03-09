<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChargeRequest;
use Bluerhinos\phpMQTT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ChargeRequestController extends Controller
{
    public function index()
    {
        return ChargeRequest::orderByDesc('created_at')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'spot_id' => ['required', 'integer'],
            'target_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'current_percent' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'battery_kwh' => ['nullable', 'numeric', 'min:1'],
            'notify_on_complete' => ['sometimes', 'boolean'],
        ]);

        $authUser = $request->attributes->get('auth_user');
        $userId = $authUser['id'] ?? null;
        if (!$userId) {
            return response()->json(['message' => 'Missing user'], 401);
        }

        $currentPercent = $data['current_percent'] ?? 0;
        $minutesPerPercent = (int) config('charging.minutes_per_percent');
        $estimatedMinutes = max(0, ($data['target_percent'] - $currentPercent) * $minutesPerPercent);

        $queuePosition = (int) ChargeRequest::whereIn('status', ['queued', 'charging'])
            ->max('queue_position') + 1;

        $status = $queuePosition === 1 && !$this->hasActiveCharge()
            ? 'charging'
            : 'queued';

        $requestModel = ChargeRequest::create([
            'user_id' => $userId,
            'user_role' => $authUser['role'] ?? null,
            'spot_id' => $data['spot_id'],
            'target_percent' => $data['target_percent'],
            'current_percent' => $currentPercent,
            'initial_percent' => $currentPercent,
            'battery_kwh' => $data['battery_kwh'] ?? null,
            'status' => $status,
            'queue_position' => $queuePosition,
            'estimated_minutes' => $estimatedMinutes,
            'started_at' => $status === 'charging' ? now() : null,
            'notify_on_complete' => (bool) ($data['notify_on_complete'] ?? false),
        ]);

        if ($status === 'charging') {
            $requestModel->update(['status' => 'charging']);
            $this->publishMwbotStatus('charging');
            $this->publish('mwbot/assignment', json_encode([
                'request_id' => $requestModel->id,
                'spot_id' => $requestModel->spot_id,
                'user_id' => $requestModel->user_id,
            ]));
        }

        return response()->json($requestModel, 201);
    }

    public function quote(Request $request)
    {
        $data = $request->validate([
            'spot_id' => ['required', 'integer'],
            'target_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'current_percent' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'battery_kwh' => ['nullable', 'numeric', 'min:1'],
        ]);

        $currentPercent = $data['current_percent'] ?? 0;
        $minutesPerPercent = (int) config('charging.minutes_per_percent');
        $estimatedMinutes = max(0, ($data['target_percent'] - $currentPercent) * $minutesPerPercent);

        $queuePosition = (int) ChargeRequest::whereIn('status', ['queued', 'charging'])
            ->max('queue_position') + 1;

        return response()->json([
            'queue_position' => $queuePosition,
            'cars_before' => max(0, $queuePosition - 1),
            'estimated_minutes' => $estimatedMinutes,
        ]);
    }

    public function show(ChargeRequest $chargeRequest)
    {
        return response()->json($chargeRequest);
    }

    public function cancel(ChargeRequest $chargeRequest)
    {
        if (in_array($chargeRequest->status, ['cancelled', 'completed'], true)) {
            return response()->json($chargeRequest);
        }

        $chargeRequest->update(['status' => 'cancelled']);

        $this->recalculateQueue();
        $this->assignNext();

        return response()->json($chargeRequest);
    }

    public function progress(Request $request, ChargeRequest $chargeRequest)
    {
        $data = $request->validate([
            'current_percent' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $chargeRequest->update([
            'current_percent' => $data['current_percent'],
            'status' => $chargeRequest->status === 'queued' ? 'charging' : $chargeRequest->status,
            'started_at' => $chargeRequest->status === 'queued' ? now() : $chargeRequest->started_at,
        ]);

        $chargeRequest = $chargeRequest->fresh();

        $this->publish("charging/progress/{$chargeRequest->id}", json_encode([
            'request_id' => $chargeRequest->id,
            'user_id' => $chargeRequest->user_id,
            'spot_id' => $chargeRequest->spot_id,
            'current_percent' => $chargeRequest->current_percent,
            'target_percent' => $chargeRequest->target_percent,
        ]));

        if ($data['current_percent'] >= $chargeRequest->target_percent) {
            $chargeRequest->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $this->publish("charging/complete/{$chargeRequest->id}", json_encode([
                'request_id' => $chargeRequest->id,
                'user_id' => $chargeRequest->user_id,
                'spot_id' => $chargeRequest->spot_id,
            ]));

            if ($chargeRequest->notify_on_complete) {
                $this->publish("notify/user/{$chargeRequest->user_id}", 'Charge complete');
            }

            $this->publishMwbotStatus('idle');

            $this->createChargingPayment($request, $chargeRequest);
            $this->recalculateQueue();
            $this->assignNext();
        }

        return response()->json($chargeRequest->fresh());
    }

    private function publish(string $topic, string $message): void
    {
        $config = $this->mqttConfig();
        $clientId = $config['client_id'] . '-' . uniqid();
        $mqtt = new phpMQTT($config['host'], $config['port'], $clientId);

        $connected = $mqtt->connect(true, null, $config['username'], $config['password']);
        if (!$connected) {
            throw new RuntimeException('Unable to connect to MQTT broker');
        }

        $mqtt->publish($topic, $message, 0);
        $mqtt->close();
    }

    private function mqttConfig(): array
    {
        return [
            'host' => getenv('MQTT_HOST') ?: (config('mqtt.host') ?? 'localhost'),
            'port' => (int) (getenv('MQTT_PORT') ?: (config('mqtt.port') ?? 1883)),
            'client_id' => getenv('MQTT_CLIENT_ID') ?: (config('mqtt.client_id') ?? 'charging-service'),
            'username' => getenv('MQTT_USERNAME') ?: config('mqtt.username'),
            'password' => getenv('MQTT_PASSWORD') ?: config('mqtt.password'),
        ];
    }

    private function hasActiveCharge(): bool
    {
        return ChargeRequest::where('status', 'charging')->exists();
    }

    private function assignNext(): void
    {
        if ($this->hasActiveCharge()) {
            return;
        }

        $next = ChargeRequest::where('status', 'queued')
            ->orderBy('queue_position')
            ->first();

        if (!$next) {
            return;
        }

        $next->update([
            'status' => 'charging',
            'started_at' => now(),
        ]);

        $this->publishMwbotStatus('charging');
        $this->publish('mwbot/assignment', json_encode([
            'request_id' => $next->id,
            'spot_id' => $next->spot_id,
            'user_id' => $next->user_id,
        ]));
    }

    private function recalculateQueue(): void
    {
        $queued = ChargeRequest::whereIn('status', ['queued', 'charging'])
            ->orderBy('created_at')
            ->get();

        $position = 1;
        foreach ($queued as $request) {
            $request->update(['queue_position' => $position]);
            $position++;
        }
    }

    private function createChargingPayment(Request $request, ChargeRequest $chargeRequest): void
    {
        $token = $request->bearerToken();
        $baseUrl = rtrim(config('payments-service.base_url'), '/');
        if (!$token || !$baseUrl) {
            return;
        }

        $batteryKwh = $chargeRequest->battery_kwh ?? (float) config('charging.default_battery_kwh');
        $deltaPercent = max(0, $chargeRequest->target_percent - ($chargeRequest->initial_percent ?? 0));
        $energyKwh = ($deltaPercent / 100) * $batteryKwh;
        $costPerKw = $this->getChargingCostPerKw($token, $baseUrl);
        $amount = round($energyKwh * $costPerKw, 2);

        if ($amount <= 0) {
            return;
        }

        $internalToken = config('payments-service.internal_token');

        $client = Http::withToken($token);
        if ($internalToken) {
            $client = $client->withHeaders(['X-Internal-Token' => $internalToken]);
        }

        $client->post("{$baseUrl}/api/payments", [
            'user_id' => $chargeRequest->user_id,
            'user_role' => $chargeRequest->user_role,
            'type' => 'charging',
            'reference_id' => (string) $chargeRequest->id,
            'amount' => $amount,
        ]);
    }

    private function publishMwbotStatus(string $status): void
    {
        $this->publish('mwbot/status', json_encode([
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
        ]));
    }

    private function getChargingCostPerKw(string $token, string $baseUrl): float
    {
        $response = Http::withToken($token)->get("{$baseUrl}/api/pricing");
        if (!$response->ok()) {
            return (float) config('charging.cost_per_kw');
        }

        $cost = $response->json('charging_cost_per_kw');
        return is_numeric($cost) ? (float) $cost : (float) config('charging.cost_per_kw');
    }
}
