<?php

namespace App\Services\Ai\DTO;

final readonly class ChatResponse
{
    public function __construct(
        public string $provider,
        public ?string $model,
        public string $answer,
        public int $inputTokens = 0,
        public int $outputTokens = 0,
        public ?string $finishReason = null,
        public int $latency = 0,
        public float $estimatedCost = 0.0,
        public int $creditsUsed = 0,
    ) {
    }

    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'model' => $this->model,
            'answer' => $this->answer,
            'input_tokens' => $this->inputTokens,
            'output_tokens' => $this->outputTokens,
            'finish_reason' => $this->finishReason,
            'latency' => $this->latency,
            'estimated_cost' => $this->estimatedCost,
            'credits_used' => $this->creditsUsed,
        ];
    }
}

