<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('approval_note')->nullable()->after('is_approved');
            $table->timestamp('taxonomy_verified_at')->nullable()->after('approval_note');
            $table->unsignedBigInteger('taxonomy_verified_by')->nullable()->after('taxonomy_verified_at');
            $table->index('taxonomy_verified_by', 'products_taxonomy_verified_by_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_taxonomy_verified_by_index');
            $table->dropColumn(['approval_note', 'taxonomy_verified_at', 'taxonomy_verified_by']);
        });
    }
};
