<?php

namespace Modules\PageBuilder\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomPageTranslation extends Model {
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['custom_page_id','name', 'content', 'lang_code','seo_title','seo_description'];

    public function custom_page(): ?BelongsTo {
        return $this->belongsTo(CustomPage::class, 'custom_page_id');
    }

}
