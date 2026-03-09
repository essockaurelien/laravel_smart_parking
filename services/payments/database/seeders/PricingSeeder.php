<?php

namespace Database\Seeders;

use App\Models\Pricing;
use Illuminate\Database\Seeder;

class PricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Pricing::create([
            'parking_rate_per_hour' => 2.0,
            'charging_cost_per_kw' => 0.4,
            'updated_by' => 'system',
            'effective_at' => now(),
        ]);
    }
}
