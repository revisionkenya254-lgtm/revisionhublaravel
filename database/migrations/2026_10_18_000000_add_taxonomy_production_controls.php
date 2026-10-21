<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxonomy_revisions', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('revision')->default(1);
            $table->timestamp('updated_at')->nullable();
        });

        DB::table('taxonomy_revisions')->insert([
            'id' => 1,
            'revision' => 1,
            'updated_at' => now(),
        ]);

        Schema::create('taxonomy_change_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->uuid('batch_uuid')->index();
            $table->string('action', 60)->index();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'created_at'], 'taxonomy_logs_category_created_index');
            $table->index(['admin_id', 'created_at'], 'taxonomy_logs_admin_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxonomy_change_logs');
        Schema::dropIfExists('taxonomy_revisions');
    }
};
