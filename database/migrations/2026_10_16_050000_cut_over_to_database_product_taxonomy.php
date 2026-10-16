<?php

use App\Services\Taxonomy\TaxonomyBootstrapSynchronizer;
use App\Services\Taxonomy\TaxonomyCatalogRepository;
use App\Services\Taxonomy\TaxonomyHierarchyUnifier;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(TaxonomyBootstrapSynchronizer::class)->sync(false, true);
        app(TaxonomyHierarchyUnifier::class)->unify(false, true);
        app(TaxonomyCatalogRepository::class)->clearCache();
    }

    public function down(): void
    {
        // Runtime readers retain a configuration fallback, so rolling code back
        // does not require deleting or rewriting valid taxonomy rows.
    }
};
