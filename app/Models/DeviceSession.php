<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceSession extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'family_id', 'installation_id', 'device_name', 'platform', 'app_version', 'ip_address', 'user_agent', 'last_seen_at', 'expires_at', 'revoked_at', 'revocation_reason'];
    protected $casts = ['last_seen_at' => 'datetime', 'expires_at' => 'datetime', 'revoked_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function refreshTokens(): HasMany { return $this->hasMany(RefreshToken::class); }
}
