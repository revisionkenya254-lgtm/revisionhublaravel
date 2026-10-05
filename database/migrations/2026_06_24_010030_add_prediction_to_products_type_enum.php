<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE products MODIFY type ENUM('course', 'past_paper', 'prediction', 'note', 'quiz') DEFAULT 'past_paper'");
        }
    }

    public function down(): void
    {
        DB::table('products')->where('type', 'prediction')->update(['type' => 'past_paper']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE products MODIFY type ENUM('course', 'past_paper', 'note', 'quiz') DEFAULT 'past_paper'");
        }
    }
};
