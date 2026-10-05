<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            if (!Schema::hasColumn('carts', 'item_type')) {
                $table->enum('item_type', ['course', 'product'])->default('course')->after('qty');
            }
            if (!Schema::hasColumn('carts', 'product_id')) {
                $table->foreignId('product_id')->nullable()->after('course_id')->constrained('products')->cascadeOnDelete();
            }
            $table->unsignedBigInteger('course_id')->nullable()->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->nullable(false)->change();
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->nullable(false)->change();
        });

        Schema::table('carts', function (Blueprint $table) {
            if (Schema::hasColumn('carts', 'product_id')) {
                $table->dropConstrainedForeignId('product_id');
            }
            if (Schema::hasColumn('carts', 'item_type')) {
                $table->dropColumn('item_type');
            }
        });
    }
};
