<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'auth' => [
        'base_url' => env('AUTH_BASE_URL', 'http://auth:8000'),
    ],
    'parking' => [
        'base_url' => env('PARKING_BASE_URL', 'http://parking:8000'),
    ],
    'charging' => [
        'base_url' => env('CHARGING_BASE_URL', 'http://charging:8000'),
    ],
    'payments' => [
        'base_url' => env('PAYMENTS_BASE_URL', 'http://payments:8000'),
    ],
    'iot' => [
        'base_url' => env('IOT_BASE_URL', 'http://iot-gateway:8000'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
