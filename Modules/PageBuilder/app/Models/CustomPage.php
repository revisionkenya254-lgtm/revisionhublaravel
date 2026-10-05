<?php

namespace Modules\PageBuilder\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CustomPage extends Model {
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['slug', 'fixed_header', 'status'];

    public function getNameAttribute(): ?string {
        return $this->translation->name;
    }
    public function getContentAttribute(): ?string {
        return $this->translation->content;
    }
    public function translation(): ?HasOne {
        return $this->hasOne(CustomPageTranslation::class)->where('lang_code', getSessionLanguage());
    }

    public function getTranslation($code): ?CustomPageTranslation {
        return $this->hasOne(CustomPageTranslation::class)->where('lang_code', $code)->first();
    }

    public function translations(): ?HasMany {
        return $this->hasMany(CustomPageTranslation::class, 'custom_page_id');
    }

    public function scopeActive($query) {
        return $query->where('status', 1);
    }
    public function getSeoTitleAttribute(): ?string {
        return $this->translation->seo_title;
    }

    public function getSeoDescriptionAttribute(): ?string {
        return $this->translation->seo_description;
    }

}
