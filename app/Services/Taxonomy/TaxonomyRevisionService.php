<?php

namespace App\Services\Taxonomy;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TaxonomyRevisionService
{
    private const CACHE_KEY = 'product_taxonomy_revision_v1';

    public function current(): int
    {
        if (! Schema::hasTable('taxonomy_revisions')) {
            return 1;
        }

        return (int) Cache::rememberForever(
            self::CACHE_KEY,
            fn () => DB::table('taxonomy_revisions')->where('id', 1)->value('revision') ?: 1
        );
    }

    public function touch(): int
    {
        if (! Schema::hasTable('taxonomy_revisions')) {
            return 1;
        }

        $updated = DB::table('taxonomy_revisions')->where('id', 1)->increment('revision', 1, [
            'updated_at' => now(),
        ]);
        if (! $updated) {
            DB::table('taxonomy_revisions')->insert([
                'id' => 1,
                'revision' => 1,
                'updated_at' => now(),
            ]);
        }
        Cache::forget(self::CACHE_KEY);

        return $this->current();
    }
}
