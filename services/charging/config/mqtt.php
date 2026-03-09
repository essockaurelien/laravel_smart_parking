<?php

return [
    'host' => getenv('MQTT_HOST') ?: env('MQTT_HOST', 'mosquitto'),
    'port' => (int) (getenv('MQTT_PORT') ?: env('MQTT_PORT', 1883)),
    'client_id' => getenv('MQTT_CLIENT_ID') ?: env('MQTT_CLIENT_ID', 'charging-service'),
    'username' => getenv('MQTT_USERNAME') ?: env('MQTT_USERNAME'),
    'password' => getenv('MQTT_PASSWORD') ?: env('MQTT_PASSWORD'),
];
