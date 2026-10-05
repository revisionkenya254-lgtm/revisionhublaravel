<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_note_id')->nullable()->constrained('product_notes')->nullOnDelete();
            $table->string('source_type');
            $table->string('source_name');
            $table->string('original_path');
            $table->string('storage_disk')->default('bunny');
            $table->string('bunny_folder_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('file_extension', 32)->nullable();
            $table->string('file_hash', 64)->index();
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('page_count')->nullable();
            $table->unsignedInteger('character_count')->nullable();
            $table->longText('extracted_text_path')->nullable();
            $table->text('extracted_text_excerpt')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->longText('failure_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_documents');
    }
};
