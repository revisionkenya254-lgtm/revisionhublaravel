<?php

namespace App\Services\Taxonomy;

use App\Models\TaxonomyChangeLog;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TaxonomyAuditService
{
    public function __construct(private readonly TaxonomyRevisionService $revision)
    {
    }

    public function record(
        string $action,
        ?int $categoryId,
        mixed $before,
        mixed $after,
        array $context = []
    ): void {
        if (Schema::hasTable('taxonomy_change_logs')) {
            TaxonomyChangeLog::create([
                'admin_id' => auth('admin')->id(),
                'category_id' => $categoryId,
                'batch_uuid' => (string) Str::uuid(),
                'action' => $action,
                'before' => $before,
                'after' => $after,
                'context' => $context,
            ]);
        }

        $this->revision->touch();
    }
}
