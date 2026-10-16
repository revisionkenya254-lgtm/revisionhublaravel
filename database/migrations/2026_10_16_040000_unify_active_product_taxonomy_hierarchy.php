<?php

use App\Services\Taxonomy\TaxonomyHierarchyUnifier;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(TaxonomyHierarchyUnifier::class)->unify(false, true);
    }

    public function down(): void
    {
        // Deliberately irreversible: references may point at canonical leaves
        // after legacy branches have been archived.
    }
};
