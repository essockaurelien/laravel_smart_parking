<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChargeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_role',
        'spot_id',
        'target_percent',
        'current_percent',
        'initial_percent',
        'battery_kwh',
        'status',
        'queue_position',
        'estimated_minutes',
        'started_at',
        'completed_at',
        'notify_on_complete',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'notify_on_complete' => 'boolean',
    ];
}
