<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_document_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_document_id')->constrained('ai_documents')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('section_label')->nullable();
            $table->unsignedInteger('question_number')->nullable()->index();
            $table->string('question_label');
            $table->string('part_label')->nullable();
            $table->unsignedInteger('marks')->nullable();
            $table->string('marks_label')->nullable();
            $table->longText('content');
            $table->longText('raw_text')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['ai_document_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_document_questions');
    }
};
