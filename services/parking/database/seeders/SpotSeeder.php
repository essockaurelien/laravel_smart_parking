<?php

namespace Database\Seeders;

use App\Models\Spot;
use Illuminate\Database\Seeder;

class SpotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $spots = [];
        for ($i = 1; $i <= 10; $i++) {
            $spots[] = [
                'code' => sprintf('A-%02d', $i),
                'is_occupied' => false,
                'is_reserved' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Spot::upsert($spots, ['code'], ['is_occupied', 'is_reserved', 'updated_at']);
    }
}
