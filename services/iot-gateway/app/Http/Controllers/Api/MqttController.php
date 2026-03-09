<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Bluerhinos\phpMQTT;
use Illuminate\Http\Request;
use RuntimeException;

class MqttController extends Controller
{
    public function publish(Request $request)
    {
        $data = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'qos' => ['sometimes', 'integer', 'min:0', 'max:2'],
        ]);

        $mqtt = $this->connect();
        $mqtt->publish($data['topic'], $data['message'], $data['qos'] ?? 0);
        $mqtt->close();

        return response()->json(['status' => 'published']);
    }

    public function status()
    {
        $mqtt = $this->connect();
        $mqtt->close();

        return response()->json(['status' => 'connected']);
    }

    private function connect(): phpMQTT
    {
        $config = $this->mqttConfig();
        $clientId = $config['client_id'] . '-' . uniqid();

        $mqtt = new phpMQTT($config['host'], $config['port'], $clientId);
        $connected = $mqtt->connect(true, null, $config['username'], $config['password']);
        if (!$connected) {
            throw new RuntimeException('Unable to connect to MQTT broker');
        }

        return $mqtt;
    }

    private function mqttConfig(): array
    {
        return [
            'host' => getenv('MQTT_HOST') ?: (config('mqtt.host') ?? 'localhost'),
            'port' => (int) (getenv('MQTT_PORT') ?: (config('mqtt.port') ?? 1883)),
            'client_id' => getenv('MQTT_CLIENT_ID') ?: (config('mqtt.client_id') ?? 'iot-gateway'),
            'username' => getenv('MQTT_USERNAME') ?: config('mqtt.username'),
            'password' => getenv('MQTT_PASSWORD') ?: config('mqtt.password'),
        ];
    }
}
