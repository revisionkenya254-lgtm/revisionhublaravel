<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefreshToken extends Model
{
    use HasUuids;

    protected $fillable = ['device_session_id', 'token_hash', 'issued_at', 'expires_at', 'used_at', 'revoked_at', 'revocation_reason', 'replaced_by_id', 'ip_address', 'user_agent'];
    protected $casts = ['issued_at' => 'datetime', 'expires_at' => 'datetime', 'used_at' => 'datetime', 'revoked_at' => 'datetime'];

    public function deviceSession(): BelongsTo { return $this->belongsTo(DeviceSession::class); }
}
