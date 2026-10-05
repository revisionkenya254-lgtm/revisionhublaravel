<?php

namespace App\Services\Ai\Router;

use App\Enums\AiRoutingMode;
use App\Services\Ai\DTO\ChatRequest;
use App\Models\AiSetting;
use Illuminate\Support\Facades\Schema;

class AIRouter
{
    public function route(ChatRequest $request): array
    {
        $settings = $this->settings();
        $defaultProvider = (string) ($settings['default_provider'] ?? config('ai.default', 'openai'));
        $fallbackProvider = (string) ($settings['fallback_provider'] ?? config('ai.fallback', $defaultProvider));
        $globalMode = AiRoutingMode::tryFrom((string) ($settings['routing_mode'] ?? config('ai.routing_mode', AiRoutingMode::Auto->value))) ?? AiRoutingMode::Auto;
        $requestedProvider = strtolower(trim($request->provider));

        if (in_array($requestedProvider, $this->providerKeys(), true) && $requestedProvider !== 'auto') {
            return $this->unique([$requestedProvider, $fallbackProvider]);
        }

        if ($globalMode === AiRoutingMode::OpenAiOnly) {
            return $this->unique(['openai', $fallbackProvider]);
        }

        $detectedProvider = $this->detectProvider($request);

        return $this->unique([$detectedProvider ?? $defaultProvider, $fallbackProvider]);
    }

    private function detectProvider(ChatRequest $request): ?string
    {
        $mode = strtolower(trim($request->mode));
        $prompt = mb_strtolower(trim((string) $request->prompt));
        $rules = (array) config('ai.routing.rules', []);

        foreach ($rules as $ruleName => $rule) {
            $provider = (string) ($rule['provider'] ?? '');
            $keywords = (array) ($rule['keywords'] ?? []);

            if ($mode !== '' && $mode === $ruleName) {
                return $provider ?: null;
            }

            foreach ($keywords as $keyword) {
                if ($keyword !== '' && str_contains($prompt, mb_strtolower((string) $keyword))) {
                    return $provider ?: null;
                }
            }
        }

        return null;
    }

    private function providerKeys(): array
    {
        return array_keys((array) config('ai.providers', []));
    }

    private function settings(): array
    {
        if (! Schema::hasTable('ai_settings')) {
            return [];
        }

        $settings = AiSetting::query()->first();

        return $settings ? [
            'default_provider' => $settings->default_provider,
            'fallback_provider' => $settings->fallback_provider,
            'routing_mode' => $settings->routing_mode,
        ] : [];
    }

    private function unique(array $providers): array
    {
        return array_values(array_filter(array_unique(array_filter(array_map(
            static fn ($provider) => is_string($provider) ? strtolower(trim($provider)) : null,
            $providers
        )))));
    }
}

