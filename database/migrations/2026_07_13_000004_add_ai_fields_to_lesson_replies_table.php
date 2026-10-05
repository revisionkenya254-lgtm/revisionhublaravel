<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_replies', function (Blueprint $table) {
            $table->boolean('is_ai')->default(false)->after('reply');
            $table->string('ai_provider')->nullable()->after('is_ai');
            $table->string('ai_model')->nullable()->after('ai_provider');
            $table->longText('ai_context')->nullable()->after('ai_model');
            $table->json('metadata')->nullable()->after('ai_context');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_replies', function (Blueprint $table) {
            $table->dropColumn([
                'is_ai',
                'ai_provider',
                'ai_model',
                'ai_context',
                'metadata',
            ]);
        });
    }
};
