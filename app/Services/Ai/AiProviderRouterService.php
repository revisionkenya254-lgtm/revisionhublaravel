<?php

namespace App\Services\Ai;

use App\Enums\AiRoutingMode;
use App\Services\Ai\DTO\ChatRequest;
use App\Services\Ai\Router\AIRouter;

class AiProviderRouterService
{
    public function __construct(private readonly AIRouter $router)
    {
    }

    public function shouldUseOpenAi(string $questionText, array $retrieval = []): bool
    {
        return $this->orderedProviders($questionText, $retrieval)[0] === 'openai';
    }

    public function orderedProviders(string $questionText, array $retrieval = []): array
    {
        $mode = $this->resolveMode($questionText, $retrieval);

        return $this->router->route(ChatRequest::fromArray([
            'prompt' => $questionText,
            'mode' => $mode,
            'provider' => 'auto',
        ]));
    }

    private function resolveMode(string $questionText, array $retrieval = []): string
    {
        $text = mb_strtolower(trim($questionText));
        $length = mb_strlen($text);
        $topScore = (int) data_get($retrieval, 'results.0.score', 0);

        if ($length > 500 || $topScore >= 14) {
            return 'essay';
        }

        return AiRoutingMode::Auto->value;
    }
}

