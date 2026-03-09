<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pricing extends Model
{
    use HasFactory;

    protected $fillable = [
        'parking_rate_per_hour',
        'charging_cost_per_kw',
        'updated_by',
        'effective_at',
    ];

    protected $casts = [
        'effective_at' => 'datetime',
    ];
}
