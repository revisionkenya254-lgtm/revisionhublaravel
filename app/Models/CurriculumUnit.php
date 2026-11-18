<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Course\app\Models\CourseCategory;

class CurriculumUnit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'awarding_body_id',
        'code',
        'name',
        'slug',
        'curriculum_version',
        'description',
        'status',
    ];

    protected $casts = ['status' => 'boolean'];

    public function awardingBody(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'awarding_body_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProgrammeUnitAssignment::class);
    }

    public function resourceContexts(): HasMany
    {
        return $this->hasMany(ProductCurriculumContext::class);
    }
}
