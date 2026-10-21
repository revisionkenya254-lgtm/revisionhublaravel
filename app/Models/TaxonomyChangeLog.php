<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxonomyChangeLog extends Model
{
    protected $fillable = [
        'admin_id',
        'category_id',
        'batch_uuid',
        'action',
        'before',
        'after',
        'context',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'context' => 'array',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
