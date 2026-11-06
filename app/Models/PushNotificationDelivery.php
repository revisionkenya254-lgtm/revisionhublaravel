<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushNotificationDelivery extends Model
{
    protected $fillable = [
        'campaign_id',
        'push_device_token_id',
        'token_hash',
        'status',
        'message_id',
        'error_code',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PushNotificationCampaign::class, 'campaign_id');
    }

    public function deviceToken(): BelongsTo
    {
        return $this->belongsTo(PushDeviceToken::class, 'push_device_token_id');
    }
}
