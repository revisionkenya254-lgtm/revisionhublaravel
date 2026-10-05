<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('priority')->default(0);
            $table->string('default_model')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('timeout')->default(45);
            $table->string('base_url')->nullable();
            $table->string('status')->default('configured');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_fallback')->default(false);
            $table->timestamp('last_health_check_at')->nullable();
            $table->string('last_health_status')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};
