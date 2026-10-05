<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\AiChatConversation;

class AiRequest extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'conversation_id',
        'provider',
        'model',
        'input_tokens',
        'output_tokens',
        'credits_used',
        'estimated_cost',
        'latency',
        'status',
        'mode',
        'metadata',
        'error_message',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'estimated_cost' => 'decimal:6',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiChatConversation::class, 'conversation_id');
    }
}
