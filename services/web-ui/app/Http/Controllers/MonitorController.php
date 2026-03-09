<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MonitorController extends Controller
{
    public function show(Request $request)
    {
        $baseUrl = rtrim(config('services.parking.base_url'), '/');
        $response = Http::get("{$baseUrl}/api/occupancy", ['include_spots' => true]);

        $payload = $response->ok() ? $response->json() : null;

        return view('monitor', [
            'summary' => $payload,
            'error' => $response->ok() ? null : 'Impossibile recuperare lo stato del parcheggio',
        ]);
    }
}
