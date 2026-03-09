<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Spot;
use Illuminate\Http\Request;

class SpotController extends Controller
{
    public function occupancy(Request $request)
    {
        $total = Spot::count();
        $occupied = Spot::where('is_occupied', true)->count();
        $reserved = Spot::where('is_reserved', true)->count();

        $payload = [
            'total_spots' => $total,
            'occupied_spots' => $occupied,
            'reserved_spots' => $reserved,
            'available_spots' => max(0, $total - $occupied - $reserved),
        ];

        if ($request->boolean('include_spots')) {
            $payload['spots'] = Spot::orderBy('code')->get();
        }

        return response()->json($payload);
    }

    public function index()
    {
        return Spot::orderBy('code')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'is_occupied' => ['sometimes', 'boolean'],
            'is_reserved' => ['sometimes', 'boolean'],
            'current_user_id' => ['nullable', 'string', 'max:255'],
        ]);

        $spot = Spot::create($data);

        return response()->json($spot, 201);
    }

    public function update(Request $request, Spot $spot)
    {
        $data = $request->validate([
            'is_occupied' => ['sometimes', 'boolean'],
            'is_reserved' => ['sometimes', 'boolean'],
            'current_user_id' => ['nullable', 'string', 'max:255'],
        ]);

        $spot->update($data);

        return $spot->fresh();
    }
}
