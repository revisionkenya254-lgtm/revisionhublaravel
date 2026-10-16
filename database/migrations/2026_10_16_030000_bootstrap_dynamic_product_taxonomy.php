<?php

use App\Services\Taxonomy\TaxonomyBootstrapSynchronizer;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(TaxonomyBootstrapSynchronizer::class)->sync(false, true);
    }

    public function down(): void
    {
        // Deliberately irreversible: canonical nodes may be referenced after
        // creation and legacy node classifications are valid taxonomy data.
    }
};
