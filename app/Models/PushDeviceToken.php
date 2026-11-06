<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PushDeviceToken extends Model
{
    protected $fillable = [
        'user_id',
        'token_hash',
        'token',
        'platform',
        'last_registered_at',
    ];

    protected $casts = [
        'last_registered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(PushNotificationDelivery::class);
    }
}
