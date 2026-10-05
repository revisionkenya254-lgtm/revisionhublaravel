<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnDelete();
            $table->enum('tier', ['short', 'long']);
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced']);
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedInteger('attempt_limit')->default(1);
            $table->unsignedInteger('pass_mark')->default(0);
            $table->unsignedInteger('question_count_cache')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('product_quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_quiz_id')->constrained('product_quizzes')->cascadeOnDelete();
            $table->text('prompt');
            $table->enum('question_type', ['single_choice', 'short_answer']);
            $table->unsignedInteger('marks')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('correct_text_answer')->nullable();
            $table->timestamps();

            $table->index(['product_quiz_id', 'sort_order']);
        });

        Schema::create('product_quiz_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('product_quiz_questions')->cascadeOnDelete();
            $table->string('option_text');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['question_id', 'sort_order']);
        });

        Schema::create('product_quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_quiz_id')->constrained('product_quizzes')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('total_marks')->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->enum('status', ['pass', 'fail']);
            $table->timestamp('submitted_at');
            $table->json('quiz_snapshot')->nullable();
            $table->timestamps();

            $table->unique(['product_quiz_id', 'user_id', 'attempt_number'], 'product_quiz_attempt_number_unique');
        });

        Schema::create('product_quiz_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('product_quiz_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained('product_quiz_questions')->nullOnDelete();
            $table->foreignId('selected_option_id')->nullable()->constrained('product_quiz_options')->nullOnDelete();
            $table->text('typed_answer')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('awarded_marks')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_quiz_attempt_answers');
        Schema::dropIfExists('product_quiz_attempts');
        Schema::dropIfExists('product_quiz_options');
        Schema::dropIfExists('product_quiz_questions');
        Schema::dropIfExists('product_quizzes');
    }
};
