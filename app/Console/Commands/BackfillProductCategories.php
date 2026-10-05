<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Course\app\Models\CourseCategory;

class BackfillProductCategories extends Command
{
    protected $signature = 'products:backfill-categories
        {--dry-run : Preview the changes without saving anything}
        {--limit=0 : Limit the number of products processed; 0 means all}';

    protected $description = 'Backfill missing product category_id values for past papers and prediction packs from metadata and title keywords.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));

        $products = Product::query()
            ->whereIn('type', [
                Product::TYPE_PAST_PAPER,
                Product::TYPE_PREDICTION,
            ])
            ->where(function ($query) {
                $query->whereNull('category_id')->orWhere('category_id', 0);
            })
            ->orderBy('id')
            ->when($limit > 0, fn ($query) => $query->limit($limit))
            ->get();

        if ($products->isEmpty()) {
            $this->info('No uncategorized products were found.');

            return self::SUCCESS;
        }

        $categories = $this->buildCategoryIndex();
        $updated = 0;
        $skipped = 0;
        $examples = [];

        foreach ($products as $product) {
            $matchedCategory = $this->resolveCategoryForProduct($product, $categories);

            if (! $matchedCategory) {
                $skipped++;
                continue;
            }

            $examples[] = [
                'id' => $product->id,
                'title' => $product->title,
                'category' => $matchedCategory['translation_name'] ?: $matchedCategory['slug'],
            ];

            if ($dryRun) {
                $this->line(sprintf(
                    '[dry-run] #%d %s => %s',
                    $product->id,
                    $product->title,
                    $matchedCategory['translation_name'] ?: $matchedCategory['slug']
                ));
                $updated++;
                continue;
            }

            $product->category_id = $matchedCategory['id'];
            $product->save();
            $updated++;
        }

        $this->newLine();
        $this->info(sprintf(
            '%s %d product(s), skipped %d, %s.',
            $dryRun ? 'Would update' : 'Updated',
            $updated,
            $skipped,
            $dryRun ? 'no records were saved' : 'changes were saved'
        ));

        if (! empty($examples)) {
            $this->table(['Product ID', 'Title', 'Matched Category'], $examples);
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{id:int, slug:string, translation_name:string}>
     */
    private function buildCategoryIndex(): array
    {
        $categories = CourseCategory::query()
            ->select('course_categories.*')
            ->selectRaw('t.name as translation_name')
            ->leftJoin('course_category_translations as t', function ($join) {
                $join->on('t.course_category_id', '=', 'course_categories.id')
                    ->where('t.lang_code', app()->getLocale());
            })
            ->where('status', 1)
            ->get();

        $parentIds = $categories
            ->pluck('parent_id')
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->all();

        return $categories
            ->filter(fn ($category) => ! in_array((int) $category->id, $parentIds, true))
            ->map(fn ($category) => [
                'id' => (int) $category->id,
                'slug' => (string) $category->slug,
                'translation_name' => (string) ($category->translation_name ?? ''),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{id:int, slug:string, translation_name:string}>  $categories
     */
    private function resolveCategoryForProduct(Product $product, array $categories): ?array
    {
        $metadata = $product->metadata ?? [];
        $haystack = $this->normalizeText(implode(' ', array_filter([
            $product->title,
            $product->slug,
            data_get($metadata, 'education_level'),
            data_get($metadata, 'class_grade'),
            data_get($metadata, 'exam_category'),
            data_get($metadata, 'subject'),
            data_get($metadata, 'course'),
            data_get($metadata, 'paper'),
            data_get($metadata, 'year'),
            is_array(data_get($metadata, 'tags')) ? implode(' ', data_get($metadata, 'tags', [])) : data_get($metadata, 'tags'),
        ])));

        $rules = $this->categoryKeywordRules();

        foreach ($rules as $slug => $keywords) {
            $category = collect($categories)->firstWhere('slug', $slug);

            if (! $category) {
                continue;
            }

            foreach ($keywords as $keyword) {
                if (str_contains($haystack, $this->normalizeText($keyword))) {
                    return $category;
                }
            }
        }

        return collect($categories)
            ->sortByDesc(fn ($category) => strlen((string) $category['translation_name']) + strlen((string) $category['slug']))
            ->first(function (array $category) use ($haystack) {
                $slug = $this->normalizeText($category['slug']);
                $label = $this->normalizeText($category['translation_name']);

                return ($slug !== '' && str_contains($haystack, $slug))
                    || ($label !== '' && str_contains($haystack, $label));
            });
    }

    /**
     * A small synonym map for the most common paper/resource categories.
     *
     * @return array<string, array<int, string>>
     */
    private function categoryKeywordRules(): array
    {
        return [
            'accounting' => [
                'accounting',
                'auditing',
                'bookkeeping',
                'financial accounting',
                'cost accounting',
                'taxation',
                'ledger',
                'trial balance',
            ],
            'business-management' => [
                'business management',
                'business studies',
                'business administration',
                'management',
            ],
            'human-resource-management' => [
                'human resource',
                'hr management',
                'personnel management',
                'human resource management',
            ],
            'procurement-supply-chain' => [
                'procurement',
                'supply chain',
                'supply-chain',
            ],
            'banking-finance' => [
                'banking',
                'banking finance',
                'finance',
                'financial services',
            ],
            'marketing' => [
                'marketing',
            ],
            'secretarial-studies' => [
                'secretarial',
                'secretarial studies',
            ],
            'office-administration' => [
                'office administration',
                'office practice',
            ],
            'entrepreneurship' => [
                'entrepreneurship',
            ],
            'ict' => [
                'ict',
                'information communication technology',
                'computer packages',
                'computer science',
                'software development',
                'web development',
                'cyber security',
                'networking',
            ],
            'building-technology' => [
                'building technology',
                'construction technology',
            ],
            'plumbing' => [
                'plumbing',
            ],
            'welding-fabrication' => [
                'welding',
                'fabrication',
            ],
        ];
    }

    private function normalizeText(?string $value): string
    {
        $value = Str::lower((string) $value);
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }
}
