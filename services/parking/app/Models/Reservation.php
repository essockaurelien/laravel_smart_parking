<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'spot_id',
        'user_id',
        'status',
        'arrival_eta',
        'duration_minutes',
        'start_at',
        'end_at',
        'cancelled_at',
        'penalty_amount',
        'card_last4',
        'card_holder',
        'card_exp_month',
        'card_exp_year',
    ];

    protected $casts = [
        'arrival_eta' => 'datetime',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];
}
