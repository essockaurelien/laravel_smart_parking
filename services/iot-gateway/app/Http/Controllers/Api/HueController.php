<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Bluerhinos\phpMQTT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HueController extends Controller
{
    public function lights()
    {
        $baseUrl = rtrim(config('hue.base_url'), '/');
        $username = config('hue.username');

        $response = Http::get("{$baseUrl}/api/{$username}/lights");
        if (!$response->ok()) {
            return response()->json(['message' => 'Unable to reach Hue emulator'], 502);
        }

        return response()->json($response->json());
    }

    public function setSpotOccupancy(Request $request)
    {
        $data = $request->validate([
            'spot_id' => ['required', 'integer', 'min:1'],
            'occupied' => ['required', 'boolean'],
        ]);

        $lightId = (int) $data['spot_id'] + (int) config('hue.spot_offset');

        $topic = $data['occupied']
            ? "parking/spot/{$data['spot_id']}/occupied"
            : "parking/spot/{$data['spot_id']}/freed";

        $this->publish($topic, json_encode([
            'spot_id' => $data['spot_id'],
            'occupied' => $data['occupied'],
        ]));

        return $this->setLightState($lightId, [
            'on' => $data['occupied'],
            'bri' => $data['occupied'] ? 254 : 100,
        ]);
    }

    public function setLight(Request $request, int $lightId)
    {
        $data = $request->validate([
            'on' => ['required', 'boolean'],
            'bri' => ['sometimes', 'integer', 'min:1', 'max:254'],
        ]);

        return $this->setLightState($lightId, $data);
    }

    private function setLightState(int $lightId, array $payload)
    {
        $baseUrl = rtrim(config('hue.base_url'), '/');
        $username = config('hue.username');

        $response = Http::put("{$baseUrl}/api/{$username}/lights/{$lightId}/state", $payload);
        if (!$response->ok()) {
            return response()->json(['message' => 'Unable to update Hue light'], 502);
        }

        return response()->json(['status' => 'ok']);
    }

    private function publish(string $topic, string $message): void
    {
        $config = config('mqtt');
        $clientId = $config['client_id'] . '-' . uniqid();
        $mqtt = new phpMQTT($config['host'], $config['port'], $clientId);

        $connected = $mqtt->connect(true, null, $config['username'], $config['password']);
        if (!$connected) {
            throw new RuntimeException('Unable to connect to MQTT broker');
        }

        $mqtt->publish($topic, $message, 0);
        $mqtt->close();
    }
}
