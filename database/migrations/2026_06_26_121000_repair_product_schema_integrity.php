<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql'
            && Schema::hasTable('products')
            && Schema::hasColumn('products', 'instructor_id')
            && ! $this->hasForeignKey('products', 'instructor_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreign('instructor_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products') && $this->hasForeignKey('products', 'instructor_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropForeign(['instructor_id']);
            });
        }
    }

    private function hasForeignKey(string $table, string $column): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();
    }
};
