<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->text('token');
            $table->string('platform', 20)->default('android');
            $table->timestamp('last_registered_at');
            $table->timestamps();
            $table->index(['user_id', 'platform']);
        });

        Schema::create('push_notification_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Admin::class)->nullable()->constrained()->nullOnDelete();
            $table->string('title', 100);
            $table->string('body', 500);
            $table->string('target_type', 30);
            $table->string('education_level')->nullable();
            $table->string('target_url', 2048)->nullable();
            $table->string('status', 30)->default('preparing');
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('push_notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('push_notification_campaigns')->cascadeOnDelete();
            $table->foreignId('push_device_token_id')->nullable()->constrained('push_device_tokens')->nullOnDelete();
            $table->string('token_hash', 64);
            $table->string('status', 20)->default('pending');
            $table->string('message_id', 500)->nullable();
            $table->string('error_code', 100)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'token_hash']);
            $table->index(['campaign_id', 'status', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notification_deliveries');
        Schema::dropIfExists('push_notification_campaigns');
        Schema::dropIfExists('push_device_tokens');
    }
};
