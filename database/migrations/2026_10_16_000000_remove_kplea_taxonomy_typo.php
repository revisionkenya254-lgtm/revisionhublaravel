<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Course\app\Service\CourseCategoryCacheClear;

return new class extends Migration
{
    private const REMOVED_SLUG = 'kplea';

    public function up(): void
    {
        if (! Schema::hasTable('course_categories')) {
            return;
        }

        DB::transaction(function (): void {
            $categoryIds = $this->categoryIdsToRemove();

            if ($categoryIds !== []) {
                $this->removeCategoryReferences($categoryIds);
                DB::table('course_categories')->whereIn('id', $categoryIds)->delete();
            }

            $this->removeDeprecatedProductMetadata();
        });

        CourseCategoryCacheClear::clear();
    }

    public function down(): void
    {
        // Deliberately irreversible: rolling back must not recreate a typo or
        // restore invalid category references.
    }

    /** @return array<int, int> */
    private function categoryIdsToRemove(): array
    {
        $ids = DB::table('course_categories')
            ->whereRaw('LOWER(slug) = ?', [self::REMOVED_SLUG])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $pendingParentIds = $ids;

        while ($pendingParentIds !== []) {
            $childIds = DB::table('course_categories')
                ->whereIn('parent_id', $pendingParentIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->reject(fn (int $id) => in_array($id, $ids, true))
                ->values()
                ->all();

            $ids = array_values(array_unique([...$ids, ...$childIds]));
            $pendingParentIds = $childIds;
        }

        return $ids;
    }

    /** @param array<int, int> $categoryIds */
    private function removeCategoryReferences(array $categoryIds): void
    {
        foreach (['products', 'courses'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'category_id')) {
                DB::table($table)
                    ->whereIn('category_id', $categoryIds)
                    ->update(['category_id' => null]);
            }
        }

        if (Schema::hasTable('course_category_translations')) {
            DB::table('course_category_translations')
                ->whereIn('course_category_id', $categoryIds)
                ->delete();
        }

        if (Schema::hasTable('user_skill_topics')) {
            DB::table('user_skill_topics')
                ->whereIn('category_id', $categoryIds)
                ->delete();
        }
    }

    private function removeDeprecatedProductMetadata(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'metadata')) {
            return;
        }

        DB::table('products')
            ->select(['id', 'metadata'])
            ->where('metadata', 'like', '%kplea%')
            ->orderBy('id')
            ->chunkById(200, function ($products): void {
                foreach ($products as $product) {
                    $metadata = json_decode((string) $product->metadata, true);

                    if (! is_array($metadata)) {
                        continue;
                    }

                    $cleanedMetadata = $this->removeDeprecatedMetadataValues($metadata);

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update([
                            'metadata' => json_encode($cleanedMetadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    private function removeDeprecatedMetadataValues(array $values): array
    {
        $isList = array_is_list($values);
        $cleaned = [];

        foreach ($values as $key => $value) {
            if (is_string($key) && strtolower(trim($key)) === self::REMOVED_SLUG) {
                continue;
            }

            if (is_string($value) && strtolower(trim($value)) === self::REMOVED_SLUG) {
                continue;
            }

            $cleaned[$key] = is_array($value)
                ? $this->removeDeprecatedMetadataValues($value)
                : $value;
        }

        return $isList ? array_values($cleaned) : $cleaned;
    }
};
