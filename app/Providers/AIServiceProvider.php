<?php

namespace App\Providers;

use App\Models\AiProvider;
use App\Models\AiSetting;
use App\Services\Ai\AIChatService;
use App\Services\Ai\Providers\OpenAIProvider;
use App\Services\Ai\Router\AIRouter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AIRouter::class);
        $this->app->singleton(OpenAIProvider::class);
        $this->app->singleton(AIChatService::class);
    }

    public function boot(): void
    {
        try {
            if ($this->tableExists('ai_providers')) {
                app(AIChatService::class)->syncProviderCatalog();
            }

            if ($this->tableExists('ai_settings')) {
                app(AIChatService::class)->syncGlobalSettings();
            }
        } catch (Throwable $throwable) {
            Log::warning('AI service bootstrap skipped database sync.', [
                'message' => $throwable->getMessage(),
            ]);
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }
}

