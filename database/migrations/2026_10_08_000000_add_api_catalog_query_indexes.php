<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(
                ['status', 'is_approved', 'deleted_at', 'created_at', 'id'],
                'products_api_catalog_listing_index'
            );
            $table->index(
                ['status', 'is_approved', 'type', 'deleted_at', 'created_at', 'id'],
                'products_api_catalog_type_listing_index'
            );
        });

        Schema::table('course_category_translations', function (Blueprint $table) {
            $table->index(
                ['course_category_id', 'lang_code'],
                'course_category_translations_lookup_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('course_category_translations', function (Blueprint $table) {
            $table->dropIndex('course_category_translations_lookup_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_api_catalog_type_listing_index');
            $table->dropIndex('products_api_catalog_listing_index');
        });
    }
};
