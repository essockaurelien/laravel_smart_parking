<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthUiController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $response = Http::acceptJson()->asJson()->post(
            config('services.auth.base_url') . '/api/login',
            $data
        );
        if (!$response->ok()) {
            $message = $response->json('message') ?? 'Invalid credentials';
            return back()->withErrors(['login' => $message])->withInput();
        }

        $payload = $response->json();
        if (!is_array($payload) || !isset($payload['access_token'], $payload['user'])) {
            return back()->withErrors(['login' => 'Unexpected auth response'])->withInput();
        }

        $request->session()->put('access_token', $payload['access_token']);
        $request->session()->put('user', $payload['user']);

        return redirect('/');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:base,premium'],
        ]);

        $response = Http::acceptJson()->asJson()->post(
            config('services.auth.base_url') . '/api/register',
            $data
        );
        if (!$response->ok()) {
            $message = $response->json('message') ?? 'Unable to register';
            return back()->withErrors(['register' => $message])->withInput();
        }

        $payload = $response->json();
        if (!is_array($payload) || !isset($payload['access_token'], $payload['user'])) {
            return back()->withErrors(['register' => 'Unexpected auth response'])->withInput();
        }

        $request->session()->put('access_token', $payload['access_token']);
        $request->session()->put('user', $payload['user']);

        return redirect('/');
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
