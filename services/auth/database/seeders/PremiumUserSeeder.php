<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PremiumUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'premium@local'],
            [
                'name' => 'Premium User',
                'password' => Hash::make('premium12345'),
                'role' => 'premium',
                'premium_until' => now()->addDays(30),
            ]
        );
    }
}
