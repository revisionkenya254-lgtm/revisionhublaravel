<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$database = DB::getDatabaseName();

$hasCategoryFk = DB::table('information_schema.KEY_COLUMN_USAGE')
    ->where('TABLE_SCHEMA', $database)
    ->where('TABLE_NAME', 'products')
    ->where('COLUMN_NAME', 'category_id')
    ->where('REFERENCED_TABLE_NAME', 'course_categories')
    ->exists();

if (Schema::hasTable('products') && ! $hasCategoryFk) {
    DB::statement('ALTER TABLE `products` ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `course_categories` (`id`) ON DELETE SET NULL');
}

$migration = '2026_06_24_010000_create_products_table';

if (! DB::table('migrations')->where('migration', $migration)->exists()) {
    $batch = (int) DB::table('migrations')->max('batch');

    DB::table('migrations')->insert([
        'migration' => $migration,
        'batch' => $batch > 0 ? $batch : 1,
    ]);
}

echo "products migration repaired\n";
