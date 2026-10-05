<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_document_processing_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_document_id')->constrained('ai_documents')->cascadeOnDelete();
            $table->string('stage');
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_document_processing_logs');
    }
};
