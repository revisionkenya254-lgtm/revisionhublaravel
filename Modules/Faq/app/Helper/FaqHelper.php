<?php

namespace Modules\Faq\app\Helper;

use Illuminate\Support\Facades\Cache;
use Modules\Faq\app\Models\Faq;

class FaqHelper {
    public static function getAll($hours = 24) {
        $lang = getSessionLanguage();
        $cacheKey = "faqs_{$lang}";

        return Cache::remember($cacheKey, now()->addHours($hours), function () use ($lang) {
            return Faq::selectRaw("faqs.id, t.question as translation_question, t.answer as translation_answer")
                ->leftJoin('faq_translations as t', function ($q) use ($lang) {
                    $q->on('t.faq_id', '=', 'faqs.id')
                        ->where('t.lang_code', $lang);
                })->where('status', 1)->get();
        });
    }
}