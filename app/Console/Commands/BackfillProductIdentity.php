<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\CatalogCacheClear;
use App\Services\ProductIdentityService;
use Illuminate\Console\Command;

class BackfillProductIdentity extends Command
{
    protected $signature = 'products:backfill-identity
        {--dry-run : Preview the changes without saving anything}
        {--limit=0 : Limit the number of products processed; 0 means all}
        {--ids= : Comma-separated product IDs to update}
        {--main-category= : Catalog main category slug, for example lower-primary}
        {--category= : Catalog category slug, for example grade-1}
        {--subject= : Catalog subject, for example Mathematics}
        {--education-level= : Display education level, for example Lower Primary}
        {--class-grade= : Display class grade, for example Grade 1}';

    protected $description = 'Backfill catalog identity metadata for non-course products.';

    public function __construct(private readonly ProductIdentityService $identityService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));
        $ids = collect(explode(',', (string) $this->option('ids')))
            ->map(fn (string $id) => (int) trim($id))
            ->filter()
            ->values();
        $explicitMetadata = array_filter([
            'education_level' => $this->option('education-level'),
            'class_grade' => $this->option('class-grade'),
            'subject' => $this->option('subject'),
        ], fn ($value) => $value !== null && $value !== '');
        $explicitIdentity = array_filter([
            'main_category' => $this->option('main-category'),
            'category' => $this->option('category'),
            'subject' => $this->option('subject'),
        ], fn ($value) => $value !== null && $value !== '');

        $products = Product::query()
            ->whereIn('type', [
                Product::TYPE_PAST_PAPER,
                Product::TYPE_PREDICTION,
                Product::TYPE_NOTE,
                Product::TYPE_QUIZ,
            ])
            ->orderBy('id')
            ->when($ids->isNotEmpty(), fn ($query) => $query->whereIn('id', $ids))
            ->when($limit > 0, fn ($query) => $query->limit($limit))
            ->get();

        if ($products->isEmpty()) {
            $this->info('No products were found.');

            return self::SUCCESS;
        }

        $updated = 0;
        $examples = [];

        foreach ($products as $product) {
            $metadata = $product->metadata ?? [];
            $metadata = array_replace($metadata, $explicitMetadata);
            $stampedMetadata = $this->identityService->stampMetadata($metadata, [
                'type' => $product->type,
                'education_level' => data_get($metadata, 'education_level'),
                'class_grade' => data_get($metadata, 'class_grade'),
                'main_category' => $explicitIdentity['main_category'] ?? null,
                'category' => $explicitIdentity['category'] ?? null,
                'exam_category' => data_get($metadata, 'exam_category'),
                'subject' => data_get($metadata, 'subject'),
                'course' => data_get($metadata, 'course'),
                'topic' => data_get($metadata, 'topic'),
                'paper' => data_get($metadata, 'paper'),
                'year' => data_get($metadata, 'year'),
                'language' => data_get($metadata, 'language'),
            ], $product);
            $stampedMetadata = array_replace($stampedMetadata, $explicitMetadata);

            if ($stampedMetadata === $metadata) {
                continue;
            }

            $examples[] = [
                'id' => $product->id,
                'title' => $product->title,
                'type' => $product->type,
                'main_category' => data_get($stampedMetadata, 'catalog_identity.main_category', '-'),
                'category' => data_get($stampedMetadata, 'catalog_identity.category', '-'),
                'subject' => data_get($stampedMetadata, 'catalog_identity.subject', '-'),
            ];

            if ($dryRun) {
                $this->line(sprintf(
                    '[dry-run] #%d %s => %s / %s / %s',
                    $product->id,
                    $product->title,
                    data_get($stampedMetadata, 'catalog_identity.main_category', '-'),
                    data_get($stampedMetadata, 'catalog_identity.category', '-'),
                    data_get($stampedMetadata, 'catalog_identity.subject', '-')
                ));
                $updated++;
                continue;
            }

            $product->forceFill(['metadata' => $stampedMetadata])->saveQuietly();
            $updated++;
        }

        if (! $dryRun) {
            CatalogCacheClear::clear();
        }

        $this->newLine();
        $this->info(sprintf(
            '%s %d product(s).',
            $dryRun ? 'Would update' : 'Updated',
            $updated
        ));

        if (! empty($examples)) {
            $this->table(['Product ID', 'Title', 'Type', 'Main category', 'Category', 'Subject'], $examples);
        }

        return self::SUCCESS;
    }
}
