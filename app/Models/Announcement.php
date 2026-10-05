<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function course() {
       return $this->belongsTo(Course::class); 
    }
    public function instructor(): BelongsTo {
        return $this->belongsTo(User::class, 'instructor_id', 'id');
    }
}
