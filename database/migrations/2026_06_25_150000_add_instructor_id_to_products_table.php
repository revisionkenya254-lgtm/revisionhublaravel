<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'instructor_id')) {
                $table->foreignId('instructor_id')->nullable()->after('course_id')->constrained('users')->nullOnDelete();
            }
        });

        if (DB::getDriverName() === 'mysql'
            && Schema::hasColumn('products', 'instructor_id')
            && ! $this->hasForeignKey('products', 'instructor_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreign('instructor_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'instructor_id')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if ($this->hasForeignKey('products', 'instructor_id')) {
                $table->dropForeign(['instructor_id']);
            }

            $table->dropColumn('instructor_id');
        });
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
