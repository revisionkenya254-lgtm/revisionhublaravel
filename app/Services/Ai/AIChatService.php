<?php

namespace App\Services\Ai;

use App\Enums\AiChatMode;
use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Models\AiProvider as AiProviderModel;
use App\Models\AiRequest;
use App\Models\AiSetting;
use App\Models\User;
use App\Services\Ai\AiCreditLedgerService;
use App\Services\Ai\DTO\ChatRequest;
use App\Services\Ai\DTO\ChatResponse;
use App\Services\Ai\Exceptions\AIException;
use App\Services\Ai\Providers\OpenAIProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AIChatService
{
    public function __construct(
        private readonly AiCreditLedgerService $creditLedger,
        private readonly OpenAIProvider $openAiProvider
    ) {
    }

    public function listConversations(User $user, int $limit = 12): Collection
    {
        return AiChatConversation::query()
            ->where('user_id', $user->id)
            ->withCount('messages')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function createConversation(User $user, ?string $title = null): AiChatConversation
    {
        return AiChatConversation::create([
            'user_id' => $user->id,
            'title' => $title,
            'last_message_at' => now(),
        ]);
    }

    public function getConversation(User $user, int|string $conversationIdentifier): AiChatConversation
    {
        $query = AiChatConversation::query()->where('user_id', $user->id);

        if (is_int($conversationIdentifier) || ctype_digit((string) $conversationIdentifier)) {
            return $query->where(function ($builder) use ($conversationIdentifier): void {
                $builder->whereKey($conversationIdentifier)
                    ->orWhere('public_id', (string) $conversationIdentifier);
            })->firstOrFail();
        }

        return $query->where('public_id', (string) $conversationIdentifier)->firstOrFail();
    }

    public function getConversationByPublicId(User $user, string $publicId): AiChatConversation
    {
        return AiChatConversation::query()
            ->where('user_id', $user->id)
            ->where('public_id', $publicId)
            ->firstOrFail();
    }

    public function appendUserMessage(AiChatConversation $conversation, User $user, string $content): AiChatMessage
    {
        $message = $conversation->messages()->create([
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $content,
        ]);

        $conversation->forceFill(['last_message_at' => now()])->save();

        return $message;
    }

    public function storeAssistantMessage(
        AiChatConversation $conversation,
        ChatResponse $response,
        ?array $metadata = []
    ): AiChatMessage {
        $message = $conversation->messages()->create([
            'user_id' => null,
            'role' => 'assistant',
            'content' => $response->answer,
            'provider' => $response->provider,
            'model' => $response->model,
            'metadata' => $metadata ?: null,
        ]);

        $conversation->forceFill(['last_message_at' => now()])->save();

        return $message;
    }

    public function storeSystemMessage(AiChatConversation $conversation, string $content): AiChatMessage
    {
        return $conversation->messages()->create([
            'role' => 'system',
            'content' => $content,
        ]);
    }

    public function renameFromPrompt(AiChatConversation $conversation, string $prompt): void
    {
        if (filled($conversation->title)) {
            return;
        }

        $title = trim(mb_substr($prompt, 0, 48));
        $conversation->forceFill([
            'title' => $title !== '' ? $title : __('New chat'),
        ])->save();
    }

    public function chat(ChatRequest $request, ?callable $onToken = null): ChatResponse
    {
        $user = $this->resolveUser($request->userId);
        $conversation = $request->conversationId
            ? $this->getConversation($user, $request->conversationId)
            : $this->createConversation($user);

        $prompt = trim((string) $request->prompt);

        if ($prompt === '') {
            throw AIException::providerUnavailable(__('A prompt is required.'));
        }

        $this->appendUserMessage($conversation, $user, $prompt);
        $this->renameFromPrompt($conversation, $prompt);

        $conversation->load([
            'messages' => fn ($query) => $query->orderByDesc('id')->limit((int) config('ai.history.max_messages', 16)),
        ]);

        $payloadRequest = $request->with([
            'conversation_id' => $conversation->id,
            'messages' => $this->buildMessages($conversation, $request),
            'prompt' => $prompt,
            'provider' => 'openai',
        ]);

        try {
            $response = $onToken
                ? $this->openAiProvider->stream($payloadRequest, $onToken)
                : $this->openAiProvider->chat($payloadRequest);

            if (blank($response->answer)) {
                throw AIException::providerUnavailable(__('OpenAI returned an empty response.'));
            }

            $providerOrder = ['openai'];
            $this->logChatResponse($user, $conversation, $payloadRequest, $response, 'openai', $providerOrder);

            $this->storeAssistantMessage($conversation, $response, [
                'provider_order' => $providerOrder,
                'mode' => $payloadRequest->mode,
                'style' => $payloadRequest->style,
                'conversation_title' => $conversation->title,
            ]);

            $requestLog = $this->storeRequestLog($user, $conversation, $response, 'success', $payloadRequest, $providerOrder);
            $this->creditLedger->recordUsage($user, $requestLog);

            return $response;
        } catch (Throwable $throwable) {
            Log::error('OpenAI chat request failed.', [
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'provider' => 'openai',
                'message' => $throwable->getMessage(),
                'exception' => $throwable,
            ]);

            $this->storeFailedRequestLog($user, $conversation, $payloadRequest, 'openai', ['openai'], $throwable);

            throw $throwable instanceof AIException
                ? $throwable
                : AIException::providerUnavailable(__('Unable to generate a response right now.'));
        }
    }

    public function generateImage(ChatRequest $request): array
    {
        $user = $this->resolveUser($request->userId);
        $conversation = $request->conversationId
            ? $this->getConversation($user, $request->conversationId)
            : $this->createConversation($user);

        $prompt = trim((string) $request->prompt);

        if ($prompt === '') {
            throw AIException::providerUnavailable(__('A prompt is required.'));
        }

        $this->appendUserMessage($conversation, $user, $prompt);
        $this->renameFromPrompt($conversation, $prompt);

        $payloadRequest = $request->with([
            'conversation_id' => $conversation->id,
            'prompt' => $prompt,
            'provider' => 'openai',
            'mode' => 'image_generation',
        ]);

        $image = $this->openAiProvider->generateImage($payloadRequest);

        $this->storeImageMessage($conversation, $image, $request);
        $requestLog = $this->storeImageRequestLog($user, $conversation, $payloadRequest, $image);
        $this->creditLedger->recordUsage($user, $requestLog);

        return array_merge($image, [
            'conversation_id' => $conversation->id,
            'conversation_title' => $conversation->fresh()->title,
        ]);
    }

    private function providerDefinition(string $providerName): array
    {
        $config = (array) data_get(config('ai.providers', []), $providerName, []);

        if ($config === [] || ! Schema::hasTable('ai_providers')) {
            return $config;
        }

        $provider = AiProviderModel::query()->where('name', $providerName)->first();

        if (! $provider) {
            return $config;
        }

        return array_merge($config, [
            'enabled' => (bool) $provider->enabled,
            'model' => $provider->model ?: $provider->default_model ?: ($config['model'] ?? null),
            'timeout' => $provider->timeout ?: ($config['timeout'] ?? 45),
            'base_url' => $provider->base_url ?: ($config['base_url'] ?? null),
            'status' => $provider->status,
        ]);
    }

    private function resolveUser(?int $userId): User
    {
        $user = $userId ? User::query()->find($userId) : auth()->user();

        if (! $user instanceof User) {
            throw AIException::providerUnavailable(__('Authentication is required.'));
        }

        return $user;
    }

    private function buildMessages(AiChatConversation $conversation, ChatRequest $request): array
    {
        $historyLimit = max(0, (int) config('ai.history.max_messages', 16) - 1);
        $messages = $conversation->messages
            ->sortBy('id')
            ->take(max($conversation->messages->count() - 1, 0))
            ->slice(max($conversation->messages->count() - 1 - $historyLimit, 0), $historyLimit)
            ->map(fn (AiChatMessage $message) => [
                'role' => $message->role === 'assistant' ? 'assistant' : 'user',
                'content' => $message->content,
            ])
            ->values()
            ->all();

        return array_merge(
            [[
                'role' => 'system',
                'content' => $request->systemPrompt ?: $this->systemPrompt($request),
            ]],
            $messages,
            [[
                'role' => 'user',
                'content' => $request->prompt,
            ]]
        );
    }

    private function systemPrompt(ChatRequest $request): string
    {
        $mode = AiChatMode::tryFrom(strtolower($request->mode)) ?? AiChatMode::General;
        $lines = [
            'You are a friendly AI study assistant for Revision Hub Kenya.',
            'Answer clearly, accurately, and in a way that helps the student learn.',
        ];

        $lines[] = match (strtolower((string) $request->style)) {
            'simple' => 'Keep the answer short, plain, and easy to understand.',
            'detailed' => 'Give a thorough, structured answer with examples when helpful.',
            default => match ($mode) {
                AiChatMode::Essay => 'Write in a structured, polished style with a strong introduction, body, and conclusion.',
                AiChatMode::Homework => 'Help the student understand the work step by step without skipping reasoning.',
                AiChatMode::MathReasoning => 'Show the reasoning one step at a time and keep notation clean.',
                AiChatMode::ScienceReasoning => 'Explain the scientific reasoning clearly and avoid unsupported claims.',
                AiChatMode::Pdf => 'Use document-style reasoning and summarize key points carefully.',
                AiChatMode::ImageAnalysis => 'Describe visible details carefully and avoid guessing what is not shown.',
                AiChatMode::Definition => 'Give crisp definitions first, then a short example if helpful.',
                default => 'Keep answers concise but educational.',
            },
        };

        $lines[] = 'If the question is ambiguous, ask one short clarifying question.';
        $lines[] = 'If you use assumptions, state them briefly.';

        return implode("\n", $lines);
    }

    private function aiSettings(): array
    {
        if (! Schema::hasTable('ai_settings')) {
            return [
                'default_provider' => config('ai.default'),
                'fallback_provider' => config('ai.fallback'),
                'routing_mode' => config('ai.routing_mode'),
            ];
        }

        $settings = AiSetting::query()->first();

        return [
            'default_provider' => $settings?->default_provider ?: config('ai.default'),
            'fallback_provider' => $settings?->fallback_provider ?: config('ai.fallback'),
            'routing_mode' => $settings?->routing_mode ?: config('ai.routing_mode'),
        ];
    }

    private function storeRequestLog(
        User $user,
        AiChatConversation $conversation,
        ChatResponse $response,
        string $status,
        ChatRequest $request,
        array $providerOrder
    ): AiRequest {
        if (! Schema::hasTable('ai_requests')) {
            throw new AIException(__('AI request logging is unavailable.'));
        }

        return AiRequest::create([
            'user_id' => $user->id,
            'conversation_id' => $conversation->id,
            'provider' => $response->provider,
            'model' => $response->model,
            'input_tokens' => $response->inputTokens,
            'output_tokens' => $response->outputTokens,
            'credits_used' => $response->creditsUsed,
            'estimated_cost' => $response->estimatedCost,
            'latency' => $response->latency,
            'status' => $status,
            'mode' => $request->mode,
            'metadata' => [
                'provider_order' => $providerOrder,
                'temperature' => $request->temperature,
                'max_tokens' => $request->maxTokens,
                'style' => $request->style,
                'prompt' => $request->prompt,
                'conversation_title' => $conversation->title,
            ],
        ]);
    }

    private function storeFailedRequestLog(
        User $user,
        AiChatConversation $conversation,
        ChatRequest $request,
        string $providerName,
        array $providerOrder,
        Throwable $throwable
    ): AiRequest {
        if (! Schema::hasTable('ai_requests')) {
            throw new AIException(__('AI request logging is unavailable.'));
        }

        return AiRequest::create([
            'user_id' => $user->id,
            'conversation_id' => $conversation->id,
            'provider' => $providerName,
            'model' => data_get($this->providerDefinition($providerName), 'model'),
            'input_tokens' => 0,
            'output_tokens' => 0,
            'credits_used' => 0,
            'estimated_cost' => 0,
            'latency' => 0,
            'status' => 'failed',
            'mode' => $request->mode,
            'error_message' => $throwable->getMessage(),
            'metadata' => [
                'provider_order' => $providerOrder,
                'temperature' => $request->temperature,
                'max_tokens' => $request->maxTokens,
                'style' => $request->style,
                'prompt' => $request->prompt,
                'conversation_title' => $conversation->title,
            ],
        ]);
    }

    private function logChatResponse(
        User $user,
        AiChatConversation $conversation,
        ChatRequest $request,
        ChatResponse $response,
        string $providerName,
        array $providerOrder
    ): void {
        Log::info('AI chat response completed.', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'provider' => $response->provider ?: $providerName,
            'model' => $response->model,
            'mode' => $request->mode,
            'style' => $request->style,
            'latency_ms' => $response->latency,
            'input_tokens' => $response->inputTokens,
            'output_tokens' => $response->outputTokens,
            'credits_used' => $response->creditsUsed,
            'estimated_cost' => $response->estimatedCost,
            'provider_order' => $providerOrder,
            'prompt' => $this->truncateLogText($request->prompt ?? ''),
            'answer_preview' => $this->truncateLogText($response->answer, 1200),
        ]);
    }

    private function truncateLogText(string $text, int $limit = 300): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $text) ?: '');

        return mb_strlen($clean) > $limit
            ? rtrim(mb_substr($clean, 0, $limit - 1)) . '...'
            : $clean;
    }

    private function storeImageMessage(AiChatConversation $conversation, array $image, ChatRequest $request): AiChatMessage
    {
        $message = $conversation->messages()->create([
            'user_id' => null,
            'role' => 'assistant',
            'content' => __('Image generated'),
            'provider' => $image['provider'] ?? 'openai',
            'model' => $image['model'] ?? null,
            'metadata' => [
                'type' => 'image',
                'image_url' => $image['image_url'] ?? null,
                'image_path' => $image['image_path'] ?? null,
                'prompt' => $image['prompt'] ?? null,
                'revised_prompt' => $image['revised_prompt'] ?? null,
                'image_size' => data_get($request->metadata, 'image_size'),
                'image_style' => $request->style,
                'latency' => $image['latency'] ?? null,
            ],
        ]);

        $conversation->forceFill(['last_message_at' => now()])->save();

        return $message;
    }

    private function storeImageRequestLog(
        User $user,
        AiChatConversation $conversation,
        ChatRequest $request,
        array $image
    ): AiRequest {
        if (! Schema::hasTable('ai_requests')) {
            throw new AIException(__('AI request logging is unavailable.'));
        }

        return AiRequest::create([
            'user_id' => $user->id,
            'conversation_id' => $conversation->id,
            'provider' => $image['provider'] ?? 'openai',
            'model' => $image['model'] ?? null,
            'input_tokens' => 0,
            'output_tokens' => 0,
            'credits_used' => 0,
            'estimated_cost' => 0,
            'latency' => (int) ($image['latency'] ?? 0),
            'status' => 'success',
            'mode' => $request->mode,
            'metadata' => [
                'type' => 'image',
                'prompt' => $request->prompt,
                'image_url' => $image['image_url'] ?? null,
                'image_path' => $image['image_path'] ?? null,
                'revised_prompt' => $image['revised_prompt'] ?? null,
                'image_size' => data_get($request->metadata, 'image_size'),
                'image_style' => $request->style,
                'conversation_title' => $conversation->title,
            ],
        ]);
    }

    public function syncProviderCatalog(): void
    {
        if (! Schema::hasTable('ai_providers')) {
            return;
        }

        $settings = $this->aiSettings();
        $providerNames = array_keys((array) config('ai.providers', []));

        AiProviderModel::query()
            ->whereNotIn('name', $providerNames)
            ->delete();

        foreach ((array) config('ai.providers', []) as $name => $definition) {
            AiProviderModel::firstOrCreate(
                ['name' => $name],
                [
                    'enabled' => (bool) ($definition['enabled'] ?? false),
                    'priority' => $name === $settings['default_provider'] ? 0 : 10,
                    'default_model' => $definition['model'] ?? null,
                    'model' => $definition['model'] ?? null,
                    'timeout' => (int) ($definition['timeout'] ?? 45),
                    'base_url' => $definition['base_url'] ?? null,
                    'status' => 'configured',
                    'is_default' => $name === $settings['default_provider'],
                    'is_fallback' => $name === $settings['fallback_provider'],
                ]
            );
        }
    }

    public function syncGlobalSettings(): void
    {
        if (! Schema::hasTable('ai_settings')) {
            return;
        }

        AiSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'default_provider' => config('ai.default'),
                'fallback_provider' => config('ai.fallback'),
                'routing_mode' => config('ai.routing_mode'),
            ]
        );
    }
}

