<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnDelete();
            $table->text('excerpt')->nullable();
            $table->longText('intro_html')->nullable();
            $table->unsignedInteger('estimated_read_minutes')->nullable();
            $table->string('difficulty')->nullable();
            $table->boolean('show_resources')->default(true);
            $table->boolean('show_discussion')->default(true);
            $table->json('prerequisites')->nullable();
            $table->timestamps();
        });

        Schema::create('product_note_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_note_id')->constrained('product_notes')->cascadeOnDelete();
            $table->foreignId('parent_topic_id')->nullable()->constrained('product_note_topics')->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('summary')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('estimated_read_minutes')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->unique(['product_note_id', 'slug']);
        });

        Schema::create('product_note_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained('product_note_topics')->cascadeOnDelete();
            $table->string('block_type', 50);
            $table->json('content_json')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_note_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_note_id')->constrained('product_notes')->cascadeOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained('product_note_topics')->nullOnDelete();
            $table->string('title');
            $table->string('resource_type', 50)->default('link');
            $table->string('url_or_path', 2048);
            $table->json('meta_json')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_note_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained('product_note_topics')->nullOnDelete();
            $table->foreignId('last_block_id')->nullable()->constrained('product_note_blocks')->nullOnDelete();
            $table->decimal('completion_percent', 5, 2)->default(0);
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });

        Schema::create('product_note_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained('product_note_topics')->nullOnDelete();
            $table->foreignId('block_id')->nullable()->constrained('product_note_blocks')->nullOnDelete();
            $table->string('label')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_note_bookmarks');
        Schema::dropIfExists('product_note_progress');
        Schema::dropIfExists('product_note_resources');
        Schema::dropIfExists('product_note_blocks');
        Schema::dropIfExists('product_note_topics');
        Schema::dropIfExists('product_notes');
    }
};
