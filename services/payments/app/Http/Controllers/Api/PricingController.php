<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pricing;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function show()
    {
        $latest = Pricing::orderByDesc('effective_at')->first();

        return response()->json($latest);
    }

    public function store(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        $role = $authUser['role'] ?? 'base';
        if ($role !== 'admin') {
            return response()->json(['message' => 'Admin access required'], 403);
        }

        $data = $request->validate([
            'parking_rate_per_hour' => ['required', 'numeric', 'min:0'],
            'charging_cost_per_kw' => ['required', 'numeric', 'min:0'],
            'updated_by' => ['nullable', 'string', 'max:255'],
            'effective_at' => ['sometimes', 'date'],
        ]);

        $pricing = Pricing::create([
            'parking_rate_per_hour' => $data['parking_rate_per_hour'],
            'charging_cost_per_kw' => $data['charging_cost_per_kw'],
            'updated_by' => $data['updated_by'] ?? null,
            'effective_at' => $data['effective_at'] ?? now(),
        ]);

        return response()->json($pricing, 201);
    }
}
