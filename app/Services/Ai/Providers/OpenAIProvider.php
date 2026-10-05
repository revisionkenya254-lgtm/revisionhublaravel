<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Contracts\AIProvider;
use App\Services\Ai\DTO\ChatRequest;
use App\Services\Ai\DTO\ChatResponse;
use App\Services\Ai\Exceptions\AIException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class OpenAIProvider implements AIProvider
{
    private const NAME = 'openai';

    public function chat(ChatRequest $request): ChatResponse
    {
        $startedAt = microtime(true);
        $config = $this->config();
        $response = $this->sendChatRequest($request, $config, false);
        $payload = $response->json();
        $answer = trim((string) data_get($payload, 'choices.0.message.content', ''));
        $usage = (array) data_get($payload, 'usage', []);

        return new ChatResponse(
            provider: self::NAME,
            model: (string) ($config['model'] ?? null),
            answer: $answer,
            inputTokens: (int) ($usage['prompt_tokens'] ?? $this->countTokens($request->messages)),
            outputTokens: (int) ($usage['completion_tokens'] ?? $this->countTokens($answer)),
            finishReason: data_get($payload, 'choices.0.finish_reason'),
            latency: (int) round((microtime(true) - $startedAt) * 1000),
            estimatedCost: $this->estimateCost(
                (int) ($usage['prompt_tokens'] ?? $this->countTokens($request->messages)),
                (int) ($usage['completion_tokens'] ?? $this->countTokens($answer))
            ),
            creditsUsed: $this->estimateCredits(
                (int) ($usage['prompt_tokens'] ?? $this->countTokens($request->messages)),
                (int) ($usage['completion_tokens'] ?? $this->countTokens($answer))
            ),
        );
    }

    public function stream(ChatRequest $request, callable $onToken): ChatResponse
    {
        $startedAt = microtime(true);
        $config = $this->config();
        $response = $this->sendChatRequest($request, $config, true);
        $body = $response->toPsrResponse()->getBody();
        $buffer = '';
        $content = '';
        $finishReason = null;

        while (! $body->eof()) {
            $buffer .= $body->read(1024);

            while (($position = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $position));
                $buffer = substr($buffer, $position + 1);

                if ($line === '' || ! str_starts_with($line, 'data:')) {
                    continue;
                }

                $data = trim(substr($line, 5));

                if ($data === '' || $data === '[DONE]') {
                    continue;
                }

                $payload = json_decode($data, true);
                $delta = (string) data_get($payload, 'choices.0.delta.content', '');
                $finishReason = data_get($payload, 'choices.0.finish_reason', $finishReason);

                if ($delta === '') {
                    continue;
                }

                $content .= $delta;
                $onToken($delta);
            }
        }

        $inputTokens = $this->countTokens($request->messages);
        $outputTokens = $this->countTokens($content);

        return new ChatResponse(
            provider: self::NAME,
            model: (string) ($config['model'] ?? null),
            answer: trim($content),
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            finishReason: $finishReason,
            latency: (int) round((microtime(true) - $startedAt) * 1000),
            estimatedCost: $this->estimateCost($inputTokens, $outputTokens),
            creditsUsed: $this->estimateCredits($inputTokens, $outputTokens),
        );
    }

    public function embeddings(string|array $input): array
    {
        $config = $this->config();
        $model = $config['embedding_model'] ?? $config['model'] ?? null;

        if (! $model) {
            throw AIException::unsupportedFeature('embeddings', self::NAME);
        }

        $response = Http::withToken($this->apiKey())
            ->acceptJson()
            ->asJson()
            ->timeout((int) $config['timeout'])
            ->post(rtrim((string) $config['base_url'], '/') . '/embeddings', [
                'model' => $model,
                'input' => $input,
            ]);

        if (! $response->successful()) {
            throw AIException::providerUnavailable($response->json('error.message') ?: __('OpenAI request failed.'));
        }

        return $response->json();
    }

    public function vision(ChatRequest $request): ChatResponse
    {
        if ($request->attachments === []) {
            return $this->chat($request);
        }

        $startedAt = microtime(true);
        $config = $this->config();
        $messages = $request->messages;
        $messages[] = [
            'role' => 'user',
            'content' => $this->buildVisionContent($request),
        ];

        $response = $this->sendPayload([
            'model' => $config['model'],
            'messages' => $messages,
            'temperature' => $request->temperature,
            'max_tokens' => $request->maxTokens,
        ], $config);

        $payload = $response->json();
        $answer = trim((string) data_get($payload, 'choices.0.message.content', ''));
        $usage = (array) data_get($payload, 'usage', []);
        $inputTokens = (int) ($usage['prompt_tokens'] ?? $this->countTokens($messages));
        $outputTokens = (int) ($usage['completion_tokens'] ?? $this->countTokens($answer));

        return new ChatResponse(
            provider: self::NAME,
            model: (string) ($config['model'] ?? null),
            answer: $answer,
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            finishReason: data_get($payload, 'choices.0.finish_reason'),
            latency: (int) round((microtime(true) - $startedAt) * 1000),
            estimatedCost: $this->estimateCost($inputTokens, $outputTokens),
            creditsUsed: $this->estimateCredits($inputTokens, $outputTokens),
        );
    }

    public function generateImage(ChatRequest $request): array
    {
        $startedAt = microtime(true);
        $config = $this->config();
        $prompt = trim((string) $request->prompt);

        if ($prompt === '') {
            throw AIException::providerUnavailable(__('A prompt is required.'));
        }

        $payload = [
            'model' => $config['image_model'] ?? 'gpt-image-2',
            'prompt' => $prompt,
            'size' => data_get($request->metadata, 'image_size', $config['image_size'] ?? '1024x1024'),
            'quality' => $config['image_quality'] ?? 'auto',
            'output_format' => $config['image_output_format'] ?? 'png',
        ];

        $style = strtolower(trim((string) $request->style));
        if (in_array($style, ['vivid', 'natural'], true)) {
            $payload['style'] = $style;
        }

        $response = Http::withToken($this->apiKey())
            ->acceptJson()
            ->asJson()
            ->timeout((int) $config['timeout'])
            ->post(rtrim((string) $config['base_url'], '/') . '/images/generations', $payload);

        if (! $response->successful()) {
            throw AIException::providerUnavailable($response->json('error.message') ?: __('OpenAI image request failed.'));
        }

        $body = $response->json();
        $b64Image = (string) data_get($body, 'data.0.b64_json', '');

        if ($b64Image === '') {
            throw AIException::providerUnavailable(__('OpenAI did not return an image.'));
        }

        $binary = base64_decode($b64Image, true);

        if ($binary === false) {
            throw AIException::providerUnavailable(__('Unable to decode the generated image.'));
        }

        $outputFormat = strtolower((string) data_get($body, 'data.0.output_format', $config['image_output_format'] ?? 'png'));
        $extension = match ($outputFormat) {
            'jpeg', 'jpg' => 'jpg',
            'webp' => 'webp',
            default => 'png',
        };
        $path = 'ai-images/' . now()->format('Y/m') . '/' . Str::uuid() . '.' . $extension;
        Storage::disk('public')->put($path, $binary);

        return [
            'provider' => self::NAME,
            'model' => (string) ($config['image_model'] ?? 'gpt-image-2'),
            'prompt' => $prompt,
            'image_url' => Storage::disk('public')->url($path),
            'image_path' => $path,
            'revised_prompt' => data_get($body, 'data.0.revised_prompt'),
            'image_size' => data_get($request->metadata, 'image_size', $config['image_size'] ?? '1024x1024'),
            'image_style' => in_array($style, ['vivid', 'natural'], true) ? $style : 'auto',
            'latency' => (int) round((microtime(true) - $startedAt) * 1000),
        ];
    }

    public function health(): array
    {
        $startedAt = microtime(true);
        try {
            $response = Http::withToken($this->apiKey())
                ->acceptJson()
                ->timeout((int) $this->config()['timeout'])
                ->get(rtrim((string) $this->config()['base_url'], '/') . '/models');

            return [
                'provider' => self::NAME,
                'status' => $response->successful() ? 'healthy' : 'unhealthy',
                'latency' => (int) round((microtime(true) - $startedAt) * 1000),
                'message' => $response->successful() ? __('Connection successful.') : ($response->json('error.message') ?: __('OpenAI health check failed.')),
            ];
        } catch (\Throwable $throwable) {
            return [
                'provider' => self::NAME,
                'status' => 'unhealthy',
                'latency' => (int) round((microtime(true) - $startedAt) * 1000),
                'message' => $throwable->getMessage(),
            ];
        }
    }

    public function countTokens(string|array $input): int
    {
        $text = is_array($input) ? $this->flatten($input) : (string) $input;

        return max(1, (int) ceil(mb_strlen($text) / 4));
    }

    private function config(): array
    {
        $config = (array) config('ai.providers.openai', []);

        if (! (bool) ($config['enabled'] ?? false)) {
            throw AIException::providerDisabled(self::NAME);
        }

        if (blank($config['api_key'] ?? null)) {
            throw AIException::providerUnavailable(__('OpenAI API key is not configured.'));
        }

        return $config;
    }

    private function apiKey(): string
    {
        return trim((string) $this->config()['api_key']);
    }

    private function sendChatRequest(ChatRequest $request, array $config, bool $stream): \Illuminate\Http\Client\Response
    {
        return $this->sendPayload([
            'model' => $config['model'],
            'messages' => $request->messages,
            'temperature' => $request->temperature,
            'max_tokens' => $request->maxTokens,
            'stream' => $stream,
        ], $config, $stream);
    }

    private function sendPayload(array $payload, array $config, bool $stream = false): \Illuminate\Http\Client\Response
    {
        $uri = rtrim((string) $config['base_url'], '/') . '/chat/completions';

        $request = Http::withToken($this->apiKey())
            ->accept($stream ? 'text/event-stream' : 'application/json')
            ->asJson()
            ->timeout((int) $config['timeout']);

        if ($stream) {
            $request = $request->withOptions(['stream' => true]);
        }

        try {
            $response = $request->post($uri, $payload);
        } catch (Throwable $throwable) {
            Log::error('OpenAI chat request connection failed.', [
                'provider' => self::NAME,
                'model' => $config['model'] ?? null,
                'stream' => $stream,
                'uri' => $uri,
                'message' => $throwable->getMessage(),
                'exception' => $throwable,
            ]);

            throw AIException::providerUnavailable($this->normalizeTransportMessage($throwable, $uri));
        }

        if (! $response->successful()) {
            Log::error('OpenAI chat request failed.', [
                'provider' => self::NAME,
                'model' => $config['model'] ?? null,
                'stream' => $stream,
                'status' => $response->status(),
                'message' => data_get($response->json(), 'error.message') ?: $response->body(),
                'response_preview' => $this->truncateLogText($response->body(), 1200),
            ]);

            throw AIException::providerUnavailable($response->json('error.message') ?: __('OpenAI request failed.'));
        }

        return $response;
    }

    private function normalizeTransportMessage(Throwable $throwable, string $uri): string
    {
        $message = trim($throwable->getMessage());
        $prefix = __('Internet connection lost.');

        if ($message === '') {
            return $prefix;
        }

        if (Str::contains($message, $uri) || Str::contains($message, ['Connection refused', 'Failed to connect', 'cURL error', 'timed out'])) {
            return $prefix . ' ' . $message;
        }

        return $message;
    }

    private function truncateLogText(string $text, int $limit = 300): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $text) ?: '');

        return mb_strlen($clean) > $limit
            ? rtrim(mb_substr($clean, 0, $limit - 1)) . '...'
            : $clean;
    }

    private function buildVisionContent(ChatRequest $request): array
    {
        $content = [];

        if (filled($request->prompt)) {
            $content[] = ['type' => 'text', 'text' => $request->prompt];
        }

        foreach ($request->attachments as $attachment) {
            $url = is_array($attachment) ? ($attachment['url'] ?? $attachment['path'] ?? null) : $attachment;
            if (! $url) {
                continue;
            }

            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $url,
                ],
            ];
        }

        return $content;
    }

    private function estimateCost(int $inputTokens, int $outputTokens): float
    {
        $pricing = (array) data_get($this->config(), 'pricing', []);
        $inputRate = (float) ($pricing['input_per_1k'] ?? 0);
        $outputRate = (float) ($pricing['output_per_1k'] ?? 0);

        return round((($inputTokens / 1000) * $inputRate) + (($outputTokens / 1000) * $outputRate), 6);
    }

    private function estimateCredits(int $inputTokens, int $outputTokens): int
    {
        $tokensPerCredit = max(1, (int) config('ai.credits.tokens_per_credit', 800));

        return max(1, (int) ceil(($inputTokens + $outputTokens) / $tokensPerCredit));
    }

    private function flatten(array $input): string
    {
        return collect($input)
            ->flatten()
            ->filter(fn ($value) => is_scalar($value) || $value instanceof \Stringable)
            ->map(fn ($value) => (string) $value)
            ->implode("\n");
    }
}

