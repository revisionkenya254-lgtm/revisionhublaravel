<?php

namespace App\Services\Ai;

use App\Services\Ai\DTO\ChatRequest;
use App\Services\Ai\Providers\OpenAIProvider as BaseOpenAIProvider;

class OpenAiChatProvider extends BaseOpenAIProvider implements AiProviderInterface
{
    public function answer(array $messages, array $options = []): array
    {
        $chatResponse = $this->chat(ChatRequest::fromArray([
            'messages' => $messages,
            'prompt' => data_get(end($messages) ?: [], 'content'),
            'temperature' => $options['temperature'] ?? 0.2,
            'max_tokens' => $options['max_tokens'] ?? 900,
            'provider' => 'openai',
        ]));

        return [
            'provider' => $chatResponse->provider,
            'model' => $chatResponse->model,
            'content' => $chatResponse->answer,
            'input_tokens' => $chatResponse->inputTokens,
            'output_tokens' => $chatResponse->outputTokens,
            'estimated_cost' => $chatResponse->estimatedCost,
            'credits_used' => $chatResponse->creditsUsed,
            'latency' => $chatResponse->latency,
        ];
    }

    public function streamAnswer(array $messages, array $options, callable $onToken): array
    {
        $chatResponse = $this->stream(ChatRequest::fromArray([
            'messages' => $messages,
            'prompt' => data_get(end($messages) ?: [], 'content'),
            'temperature' => $options['temperature'] ?? 0.2,
            'max_tokens' => $options['max_tokens'] ?? 900,
            'provider' => 'openai',
        ]), $onToken);

        return [
            'provider' => $chatResponse->provider,
            'model' => $chatResponse->model,
            'content' => $chatResponse->answer,
            'input_tokens' => $chatResponse->inputTokens,
            'output_tokens' => $chatResponse->outputTokens,
            'estimated_cost' => $chatResponse->estimatedCost,
            'credits_used' => $chatResponse->creditsUsed,
            'latency' => $chatResponse->latency,
        ];
    }
}

