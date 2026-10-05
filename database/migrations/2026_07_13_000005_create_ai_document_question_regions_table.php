<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_document_question_regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_document_id')->constrained('ai_documents')->cascadeOnDelete();
            $table->unsignedInteger('question_number')->nullable()->index();
            $table->string('question_label');
            $table->unsignedInteger('page_number')->index();
            $table->decimal('x', 10, 6);
            $table->decimal('y', 10, 6);
            $table->decimal('width', 10, 6);
            $table->decimal('height', 10, 6);
            $table->longText('content');
            $table->json('line_payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_document_question_regions');
    }
};
