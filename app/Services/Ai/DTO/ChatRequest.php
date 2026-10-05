<?php

namespace App\Services\Ai\DTO;

use App\Enums\AiChatMode;

final readonly class ChatRequest
{
    public function __construct(
        public array $messages = [],
        public ?string $prompt = null,
        public ?int $conversationId = null,
        public ?int $userId = null,
        public ?int $productId = null,
        public array $attachments = [],
        public string $mode = AiChatMode::General->value,
        public float $temperature = 0.2,
        public int $maxTokens = 900,
        public string $provider = 'auto',
        public ?string $systemPrompt = null,
        public array $metadata = [],
        public ?string $style = null,
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            messages: $payload['messages'] ?? [],
            prompt: $payload['prompt'] ?? null,
            conversationId: isset($payload['conversation_id']) ? (int) $payload['conversation_id'] : null,
            userId: isset($payload['user_id']) ? (int) $payload['user_id'] : null,
            productId: isset($payload['product_id']) ? (int) $payload['product_id'] : null,
            attachments: $payload['attachments'] ?? [],
            mode: (string) ($payload['mode'] ?? AiChatMode::General->value),
            temperature: (float) ($payload['temperature'] ?? 0.2),
            maxTokens: (int) ($payload['max_tokens'] ?? 900),
            provider: (string) ($payload['provider'] ?? 'auto'),
            systemPrompt: $payload['system_prompt'] ?? null,
            metadata: $payload['metadata'] ?? [],
            style: $payload['style'] ?? null,
        );
    }

    public function with(array $overrides): self
    {
        return new self(
            messages: $overrides['messages'] ?? $this->messages,
            prompt: $overrides['prompt'] ?? $this->prompt,
            conversationId: $overrides['conversation_id'] ?? $this->conversationId,
            userId: $overrides['user_id'] ?? $this->userId,
            productId: $overrides['product_id'] ?? $this->productId,
            attachments: $overrides['attachments'] ?? $this->attachments,
            mode: $overrides['mode'] ?? $this->mode,
            temperature: $overrides['temperature'] ?? $this->temperature,
            maxTokens: $overrides['max_tokens'] ?? $this->maxTokens,
            provider: $overrides['provider'] ?? $this->provider,
            systemPrompt: $overrides['system_prompt'] ?? $this->systemPrompt,
            metadata: $overrides['metadata'] ?? $this->metadata,
            style: $overrides['style'] ?? $this->style,
        );
    }
}

