<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'default_provider',
        'fallback_provider',
        'routing_mode',
        'document_queue_mode',
    ];
}
