<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Course\app\Models\CourseCategory;

class ProgrammeUnitAssignment extends Model
{
    public const GROUP_COMMON = 'common';
    public const GROUP_MAJOR = 'major';
    public const GROUP_ELECTIVE = 'elective';
    public const GROUPS = [self::GROUP_COMMON, self::GROUP_MAJOR, self::GROUP_ELECTIVE];

    protected $fillable = [
        'programme_node_id',
        'curriculum_unit_id',
        'group_type',
        'semester',
        'credit_hours',
        'is_mandatory',
        'display_order',
        'status',
    ];

    protected $casts = [
        'credit_hours' => 'decimal:2',
        'is_mandatory' => 'boolean',
        'status' => 'boolean',
    ];

    public function programme(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'programme_node_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(CurriculumUnit::class, 'curriculum_unit_id');
    }

    public function resourceContexts(): HasMany
    {
        return $this->hasMany(ProductCurriculumContext::class);
    }
}
