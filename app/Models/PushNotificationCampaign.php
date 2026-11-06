<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PushNotificationCampaign extends Model
{
    protected $fillable = [
        'admin_id',
        'title',
        'body',
        'target_type',
        'education_level',
        'target_url',
        'status',
        'recipient_count',
        'sent_count',
        'failed_count',
        'sent_at',
        'failure_reason',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(PushNotificationDelivery::class, 'campaign_id');
    }
}
