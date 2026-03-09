<?php

return [
    'rate_per_hour' => env('PARKING_RATE_PER_HOUR', 2.0),
    'reservation_tolerance_minutes' => env('RESERVATION_TOLERANCE_MINUTES', 20),
    'no_show_penalty' => env('RESERVATION_NO_SHOW_PENALTY', 5.0),
    'system_email' => env('PARKING_SYSTEM_EMAIL'),
    'system_password' => env('PARKING_SYSTEM_PASSWORD'),
];
