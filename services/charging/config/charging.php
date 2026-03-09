<?php

return [
    'minutes_per_percent' => env('CHARGING_MINUTES_PER_PERCENT', 2),
    'cost_per_kw' => env('CHARGING_COST_PER_KW', 0.4),
    'default_battery_kwh' => env('CHARGING_DEFAULT_BATTERY_KWH', 60),
];
