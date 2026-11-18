<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCurriculumContext extends Model
{
    protected $fillable = [
        'product_id',
        'curriculum_unit_id',
        'programme_unit_assignment_id',
        'resource_type',
        'assessment_series',
        'assessment_year',
        'paper_number',
    ];

    protected $casts = ['assessment_year' => 'integer'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(CurriculumUnit::class, 'curriculum_unit_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(ProgrammeUnitAssignment::class, 'programme_unit_assignment_id');
    }
}
