<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_credit_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_request_id')->nullable()->constrained('ai_requests')->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('ai_chat_conversations')->nullOnDelete();
            $table->string('type', 40);
            $table->integer('amount');
            $table->integer('balance_after')->default(0);
            $table->string('period_key', 20)->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'type', 'period_key']);
            $table->unique(['user_id', 'type', 'period_key']);
            $table->index('ai_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_credit_ledger');
    }
};
