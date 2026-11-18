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
    public const GROUP_UNIVERSITY_COMMON = 'university_common';
    public const GROUP_FACULTY_COMMON = 'faculty_common';
    public const GROUP_CORE = 'core';
    public const GROUP_SPECIALIZATION = 'specialization';
    public const GROUP_RESEARCH = 'research';
    public const GROUP_PAPER = 'paper';
    public const GROUPS = [
        self::GROUP_COMMON,
        self::GROUP_MAJOR,
        self::GROUP_ELECTIVE,
        self::GROUP_UNIVERSITY_COMMON,
        self::GROUP_FACULTY_COMMON,
        self::GROUP_CORE,
        self::GROUP_SPECIALIZATION,
        self::GROUP_RESEARCH,
        self::GROUP_PAPER,
    ];

    protected $fillable = [
        'programme_node_id',
        'curriculum_unit_id',
        'group_type',
        'study_year',
        'semester',
        'module',
        'stage',
        'section',
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
