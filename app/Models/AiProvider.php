<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'enabled',
        'priority',
        'default_model',
        'model',
        'timeout',
        'base_url',
        'status',
        'is_default',
        'is_fallback',
        'last_health_check_at',
        'last_health_status',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'is_default' => 'boolean',
        'is_fallback' => 'boolean',
        'last_health_check_at' => 'datetime',
    ];
}
