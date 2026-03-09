<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spot extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'is_occupied',
        'is_reserved',
        'current_user_id',
    ];

    protected $casts = [
        'is_occupied' => 'boolean',
        'is_reserved' => 'boolean',
    ];
}
