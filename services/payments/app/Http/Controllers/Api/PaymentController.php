<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'type' => ['sometimes', 'string'],
            'user_id' => ['sometimes', 'string'],
            'user_role' => ['sometimes', 'string'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ]);

        $authUser = $request->attributes->get('auth_user');
        $role = $authUser['role'] ?? 'base';
        if ($role !== 'admin') {
            $data['user_id'] = $authUser['id'] ?? null;
            unset($data['user_role']);
        }

        $query = Payment::query();
        if (!empty($data['type'])) {
            $query->where('type', $data['type']);
        }
        if (!empty($data['user_id'])) {
            $query->where('user_id', $data['user_id']);
        }
        if (!empty($data['user_role'])) {
            $query->where('user_role', $data['user_role']);
        }
        if (!empty($data['from'])) {
            $query->where('paid_at', '>=', $data['from']);
        }
        if (!empty($data['to'])) {
            $query->where('paid_at', '<=', $data['to']);
        }

        return $query->orderByDesc('paid_at')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'string', 'max:255'],
            'user_role' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'string', 'max:50'],
            'reference_id' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $payment = Payment::create([
            'user_id' => $data['user_id'],
            'user_role' => $data['user_role'] ?? null,
            'type' => $data['type'],
            'reference_id' => $data['reference_id'] ?? null,
            'amount' => $data['amount'],
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return response()->json($payment, 201);
    }
}
