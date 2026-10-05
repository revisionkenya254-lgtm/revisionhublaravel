<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('chapter_id')->constrained('course_chapters')->onDelete('cascade');
            $table->foreignId('chapter_item_id')->constrained('course_chapter_items')->onDelete('cascade');
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->longText('description')->nullable();
            $table->longText('instructions')->nullable();
            $table->enum('submission_type', ['file', 'text', 'link'])->default('file');
            $table->dateTime('due_date')->nullable();
            $table->boolean('late_submission_allowed')->default(false);
            $table->string('late_penalty')->nullable(); // e.g., "10%" or "5"
            $table->decimal('max_marks', 8, 2)->default(100);
            $table->enum('status', ['draft', 'published'])->default('published');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
