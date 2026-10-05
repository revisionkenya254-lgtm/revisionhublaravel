<?php

namespace App\Services\Ai;

use App\Enums\AiChatMode;
use App\Models\AiCreditLedger;
use App\Models\AiRequest;
use App\Models\StandaloneAiChatConversation;
use App\Models\StandaloneAiChatMessage;
use App\Models\User;
use App\Services\Ai\DTO\ChatRequest;
use App\Services\Ai\DTO\ChatResponse;
use App\Services\Ai\Exceptions\AIException;
use App\Services\Ai\Providers\OpenAIProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class StandaloneAiChatService
{
    public function __construct(
        private readonly OpenAIProvider $openAiProvider,
        private readonly AiCreditLedgerService $creditLedger,
        private readonly AiCreditPurchaseService $purchaseService
    ) {
    }

    public function listConversations(User $user, int $limit = 12): Collection
    {
        return StandaloneAiChatConversation::query()
            ->where('user_id', $user->id)
            ->withCount('messages')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function createConversation(User $user, ?string $title = null): StandaloneAiChatConversation
    {
        return StandaloneAiChatConversation::create([
            'user_id' => $user->id,
            'title' => $title,
            'last_message_at' => now(),
        ]);
    }

    public function getConversation(User $user, int|string $conversationIdentifier): StandaloneAiChatConversation
    {
        $query = StandaloneAiChatConversation::query()->where('user_id', $user->id);

        if (is_int($conversationIdentifier) || ctype_digit((string) $conversationIdentifier)) {
            return $query->where(function ($builder) use ($conversationIdentifier): void {
                $builder->whereKey($conversationIdentifier)
                    ->orWhere('public_id', (string) $conversationIdentifier);
            })->firstOrFail();
        }

        return $query->where('public_id', (string) $conversationIdentifier)->firstOrFail();
    }

    public function buildIndexData(User $user, ?string $conversation = null): array
    {
        $this->creditLedger->ensureMonthlyGrant($user);
        $currentBalance = $this->creditLedger->currentBalance($user);
        $monthlyAllowance = $this->creditLedger->monthlyAllowance($user);
        $usageThisMonth = (int) AiRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->whereBetween('created_at', [now()->startOfMonth(), now()])
            ->sum('credits_used');
        $usagePercent = $monthlyAllowance > 0
            ? (int) min(100, round(($usageThisMonth / $monthlyAllowance) * 100))
            : 0;
        $conversations = $this->listConversations($user);
        $activeConversation = null;

        if ($conversation) {
            $activeConversation = $this->getConversation($user, $conversation);
        }

        $messages = $activeConversation
            ? $activeConversation->messages()->orderBy('id')->get()
            : collect();

        return compact(
            'currentBalance',
            'monthlyAllowance',
            'usageThisMonth',
            'usagePercent',
            'conversations',
            'activeConversation',
            'messages'
        );
    }

    public function buildCreditsData(User $user): array
    {
        $this->creditLedger->ensureMonthlyGrant($user);
        $monthlyAllowance = $this->creditLedger->monthlyAllowance($user);

        $baseQuery = AiRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'success');

        $thisMonthStart = now()->startOfMonth();
        $lastMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = now()->subMonthNoOverflow()->endOfMonth();
        $yearStart = now()->startOfYear();

        $monthQuery = (clone $baseQuery)->whereBetween('created_at', [$thisMonthStart, now()]);
        $lastMonthQuery = (clone $baseQuery)->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd]);
        $yearQuery = (clone $baseQuery)->whereBetween('created_at', [$yearStart, now()]);

        $recentRequests = (clone $baseQuery)
            ->with(['conversation:id,title'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $usageThisMonth = (int) $monthQuery->sum('credits_used');
        $usageTotal = (int) (clone $baseQuery)->sum('credits_used');
        $usageLastMonth = (int) $lastMonthQuery->sum('credits_used');
        $usagePercent = $monthlyAllowance > 0
            ? (int) min(100, round(($usageThisMonth / $monthlyAllowance) * 100))
            : 0;
        $currentBalance = $this->creditLedger->currentBalance($user);
        $estimatedSpendThisYear = (float) $yearQuery->sum('estimated_cost');
        $estimatedSpendLastMonth = (float) $lastMonthQuery->sum('estimated_cost');
        $monthChangePercent = $usageLastMonth > 0
            ? (int) round((($usageThisMonth - $usageLastMonth) / $usageLastMonth) * 100)
            : 0;

        $usageBreakdownRaw = (clone $monthQuery)
            ->selectRaw('COALESCE(NULLIF(mode, \'\'), provider) as bucket, SUM(credits_used) as credits')
            ->groupBy('bucket')
            ->orderByDesc('credits')
            ->limit(5)
            ->get();

        $usageBreakdown = $usageBreakdownRaw->map(function ($row) use ($usageThisMonth) {
            $credits = (int) $row->credits;

            return [
                'label' => $this->formatAiBucketLabel((string) $row->bucket),
                'credits' => $credits,
                'share' => $usageThisMonth > 0 ? (int) round(($credits / $usageThisMonth) * 100) : 0,
            ];
        });

        $recentActivity = $recentRequests->map(function (AiRequest $request) {
            $bucket = $request->mode ?: $request->provider;
            $label = $this->formatAiBucketLabel((string) $bucket);
            $prompt = data_get($request->metadata, 'prompt')
                ?: data_get($request->metadata, 'revised_prompt')
                ?: data_get($request->metadata, 'conversation_title')
                ?: __('AI request completed successfully');

            return [
                'title' => $label,
                'message' => $this->truncateAiText((string) $prompt, 64),
                'credits' => '-' . number_format((int) $request->credits_used) . ' credits',
                'time' => $request->created_at?->format('M d, g:i A') ?: __('Just now'),
                'icon' => match (true) {
                    str_contains(strtolower((string) $bucket), 'image') => 'fa-image',
                    str_contains(strtolower((string) $bucket), 'pdf') => 'fa-file-alt',
                    default => 'fa-comments',
                },
                'tone' => match (true) {
                    str_contains(strtolower((string) $bucket), 'image') => 'is-warning',
                    str_contains(strtolower((string) $bucket), 'pdf') => 'is-success',
                    default => 'is-primary',
                },
            ];
        });

        $topUpPackages = $this->purchaseService->packages();
        $paymentGateways = app(\Modules\BasicPayment\app\Services\PaymentMethodService::class)->getActiveGatewaysWithDetails();

        $paymentMethods = [
            ['name' => 'M-Pesa', 'description' => 'Pay via M-Pesa STK Push', 'icon' => 'fa-mobile-screen-button'],
            ['name' => 'Visa / Mastercard', 'description' => 'Pay using card', 'icon' => 'fa-credit-card'],
            ['name' => 'PayPal', 'description' => 'Pay with PayPal account', 'icon' => 'fa-paypal'],
        ];

        $howItWorks = [
            ['icon' => 'fa-coins', 'title' => 'Pay as you go', 'text' => 'Spend credits only when you use AI features.'],
            ['icon' => 'fa-layer-group', 'title' => 'Different actions', 'text' => 'Consume different amounts of credits.'],
            ['icon' => 'fa-infinity', 'title' => 'Credits never expire', 'text' => 'Your credits are valid until you use them.'],
            ['icon' => 'fa-shield-halved', 'title' => 'Secure & private', 'text' => 'Your data is protected and never shared.'],
        ];

        return compact(
            'user',
            'usageThisMonth',
            'usageTotal',
            'usageLastMonth',
            'monthlyAllowance',
            'usagePercent',
            'currentBalance',
            'estimatedSpendThisYear',
            'estimatedSpendLastMonth',
            'monthChangePercent',
            'usageBreakdown',
            'recentActivity',
            'topUpPackages',
            'paymentGateways',
            'paymentMethods',
            'howItWorks'
        );
    }

    public function storeConversation(User $user, ?string $title = null): array
    {
        $conversation = $this->createConversation($user, $title);

        return [
            'status' => 'success',
            'conversation' => $this->formatConversation($conversation),
            'redirect' => route('ai-chat', ['conversation' => $conversation->public_id], false),
        ];
    }

    public function conversationMessages(User $user, StandaloneAiChatConversation $conversation): array
    {
        abort_unless($conversation->user_id === $user->id, 403);

        return $conversation->messages()->orderBy('id')->get()->map(fn ($message) => [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'provider' => $message->provider,
            'model' => $message->model,
            'created_at' => optional($message->created_at)->toDateTimeString(),
        ])->all();
    }

    public function stream(Request $request, StandaloneAiChatConversation $conversation, User $user): StreamedResponse|JsonResponse
    {
        abort_unless($conversation->user_id === $user->id, 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'type' => ['nullable', 'in:text,image'],
            'style' => ['nullable', 'in:default,simple,detailed'],
            'size' => ['nullable', 'in:auto,1024x1024,1536x1024,1024x1536'],
        ]);

        $prompt = trim($validated['message']);

        if (($validated['type'] ?? 'text') === 'image') {
            return response()->json($this->generateImagePayload($conversation, $user, $prompt, $validated));
        }

        $this->appendUserMessage($conversation, $user, $prompt);
        $this->renameFromPrompt($conversation, $prompt);

        $conversation->load([
            'messages' => fn ($query) => $query->orderByDesc('id')->limit((int) config('ai.history.max_messages', 16)),
        ]);

        $chatRequest = ChatRequest::fromArray([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'prompt' => $prompt,
            'provider' => 'openai',
            'mode' => 'general',
            'style' => $validated['style'] ?? 'default',
            'max_tokens' => 900,
            'temperature' => 0.2,
            'messages' => $this->buildMessages($conversation, $prompt, $validated['style'] ?? 'default'),
        ]);

        return response()->stream(function () use ($conversation, $chatRequest, $user) {
            ignore_user_abort(true);
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', '0');

            while (ob_get_level() > 0) {
                @ob_end_flush();
            }

            ob_implicit_flush(true);

            try {
                $answer = $this->openAiProvider->stream($chatRequest, function (string $token) {
                    echo $this->streamEvent('token', $token);
                    @flush();
                });

                $assistantMessage = $this->storeAssistantMessage($conversation, $answer, [
                    'provider_order' => ['openai'],
                    'mode' => $chatRequest->mode,
                    'style' => $chatRequest->style,
                    'conversation_title' => $conversation->title,
                    'conversation_public_id' => $conversation->public_id,
                ]);

                $requestLog = $this->storeRequestLog($user, $conversation, $answer, 'success', $chatRequest, ['openai']);
                $this->creditLedger->recordUsage($user, $requestLog);

                Log::info('Standalone AI chat response completed.', [
                    'conversation_id' => $conversation->id,
                    'message_id' => $assistantMessage->id,
                    'user_id' => $user->id,
                    'provider' => $answer->provider,
                    'model' => $answer->model,
                    'mode' => $chatRequest->mode,
                    'style' => $chatRequest->style,
                    'latency_ms' => $answer->latency,
                    'input_tokens' => $answer->inputTokens,
                    'output_tokens' => $answer->outputTokens,
                    'credits_used' => $answer->creditsUsed,
                ]);

                echo $this->streamEvent('meta', [
                    'provider' => $answer->provider,
                    'model' => $answer->model,
                    'conversation_title' => $conversation->fresh()->title,
                ]);

                echo $this->streamEvent('done', [
                    'content' => $answer->answer,
                ]);
            } catch (Throwable $throwable) {
                Log::error('Standalone AI chat stream handler failed.', [
                    'conversation_id' => $conversation->id,
                    'user_id' => $user->id,
                    'prompt' => mb_substr($chatRequest->prompt ?? '', 0, 300),
                    'provider' => $chatRequest->provider,
                    'mode' => $chatRequest->mode,
                    'style' => $chatRequest->style,
                    'message' => $throwable->getMessage(),
                    'exception' => $throwable,
                ]);

                $this->storeFailedRequestLog($user, $conversation, $chatRequest, 'openai', ['openai'], $throwable);

                echo $this->streamEvent('error', [
                    'message' => $throwable->getMessage(),
                ]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function generateImage(Request $request, StandaloneAiChatConversation $conversation, User $user): array
    {
        abort_unless($conversation->user_id === $user->id, 403);

        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:4000'],
            'style' => ['nullable', 'in:default,simple,detailed,auto'],
            'size' => ['nullable', 'in:auto,1024x1024,1536x1024,1024x1536'],
        ]);

        return $this->generateImagePayload(
            $conversation,
            $user,
            trim($validated['prompt']),
            $validated
        );
    }

    public function destroy(StandaloneAiChatConversation $conversation, User $user): array
    {
        abort_unless($conversation->user_id === $user->id, 403);

        $conversation->delete();

        return [
            'status' => 'success',
            'message' => __('Conversation deleted successfully'),
        ];
    }

    private function appendUserMessage(StandaloneAiChatConversation $conversation, User $user, string $content): StandaloneAiChatMessage
    {
        $message = $conversation->messages()->create([
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $content,
        ]);

        $conversation->forceFill(['last_message_at' => now()])->save();

        return $message;
    }

    private function storeAssistantMessage(
        StandaloneAiChatConversation $conversation,
        ChatResponse $response,
        ?array $metadata = []
    ): StandaloneAiChatMessage {
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

    private function renameFromPrompt(StandaloneAiChatConversation $conversation, string $prompt): void
    {
        if (filled($conversation->title)) {
            return;
        }

        $title = trim(mb_substr($prompt, 0, 48));

        $conversation->forceFill([
            'title' => $title !== '' ? $title : __('New chat'),
        ])->save();
    }

    private function buildMessages(StandaloneAiChatConversation $conversation, string $prompt, string $style): array
    {
        $historyLimit = max(0, (int) config('ai.history.max_messages', 16) - 1);
        $messages = $conversation->messages
            ->sortBy('id')
            ->take(max($conversation->messages->count() - 1, 0))
            ->slice(max($conversation->messages->count() - 1 - $historyLimit, 0), $historyLimit)
            ->map(fn (StandaloneAiChatMessage $message) => [
                'role' => $message->role === 'assistant' ? 'assistant' : 'user',
                'content' => $message->content,
            ])
            ->values()
            ->all();

        return array_merge(
            [[
                'role' => 'system',
                'content' => $this->systemPrompt($style),
            ]],
            $messages,
            [[
                'role' => 'user',
                'content' => $prompt,
            ]]
        );
    }

    private function systemPrompt(string $style): string
    {
        $lines = [
            'You are a friendly AI study assistant for Revision Hub Kenya.',
            'Answer clearly, accurately, and in a way that helps the student learn.',
        ];

        $lines[] = match (strtolower($style)) {
            'simple' => 'Keep the answer short, plain, and easy to understand.',
            'detailed' => 'Give a thorough, structured answer with examples when helpful.',
            default => 'Keep answers concise but educational.',
        };

        $lines[] = 'If the question is ambiguous, ask one short clarifying question.';
        $lines[] = 'If you use assumptions, state them briefly.';

        return implode("\n", $lines);
    }

    private function streamEvent(string $event, mixed $data): string
    {
        return "event: {$event}\n" . 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
    }

    private function generateImagePayload(
        StandaloneAiChatConversation $conversation,
        User $user,
        string $prompt,
        array $validated
    ): array {
        $this->appendUserMessage($conversation, $user, $prompt);
        $this->renameFromPrompt($conversation, $prompt);

        $image = $this->openAiProvider->generateImage(ChatRequest::fromArray([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'prompt' => $prompt,
            'provider' => 'openai',
            'mode' => 'image_generation',
            'max_tokens' => 0,
            'temperature' => 0,
            'style' => $validated['style'] ?? 'auto',
            'metadata' => [
                'image_size' => $validated['size'] ?? '1024x1024',
            ],
        ]));

        $assistantMessage = $conversation->messages()->create([
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
                'image_size' => data_get($validated, 'size', '1024x1024'),
                'image_style' => data_get($validated, 'style', 'auto'),
                'latency' => $image['latency'] ?? null,
            ],
        ]);

        $conversation->forceFill(['last_message_at' => now()])->save();

        $requestLog = $this->storeImageRequestLog($user, $conversation, $validated, $image);
        $this->creditLedger->recordUsage($user, $requestLog);

        Log::info('Standalone AI image response completed.', [
            'conversation_id' => $conversation->id,
            'message_id' => $assistantMessage->id,
            'user_id' => $user->id,
            'provider' => $image['provider'] ?? 'openai',
            'model' => $image['model'] ?? null,
            'image_size' => data_get($validated, 'size', '1024x1024'),
            'image_style' => data_get($validated, 'style', 'auto'),
        ]);

        return array_merge($image, [
            'conversation_id' => $conversation->id,
            'conversation_title' => $conversation->fresh()->title,
        ]);
    }

    private function storeRequestLog(
        User $user,
        StandaloneAiChatConversation $conversation,
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
            'conversation_id' => null,
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
                'conversation_public_id' => $conversation->public_id,
                'conversation_title' => $conversation->title,
            ],
        ]);
    }

    private function storeFailedRequestLog(
        User $user,
        StandaloneAiChatConversation $conversation,
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
            'conversation_id' => null,
            'provider' => $providerName,
            'model' => data_get(config('ai.providers.' . $providerName, []), 'model'),
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
                'conversation_public_id' => $conversation->public_id,
                'conversation_title' => $conversation->title,
            ],
        ]);
    }

    private function storeImageRequestLog(
        User $user,
        StandaloneAiChatConversation $conversation,
        array $validated,
        array $image
    ): AiRequest {
        if (! Schema::hasTable('ai_requests')) {
            throw new AIException(__('AI request logging is unavailable.'));
        }

        return AiRequest::create([
            'user_id' => $user->id,
            'conversation_id' => null,
            'provider' => $image['provider'] ?? 'openai',
            'model' => $image['model'] ?? null,
            'input_tokens' => 0,
            'output_tokens' => 0,
            'credits_used' => 0,
            'estimated_cost' => 0,
            'latency' => (int) ($image['latency'] ?? 0),
            'status' => 'success',
            'mode' => 'image_generation',
            'metadata' => [
                'type' => 'image',
                'prompt' => $image['prompt'] ?? data_get($validated, 'prompt'),
                'image_url' => $image['image_url'] ?? null,
                'image_path' => $image['image_path'] ?? null,
                'revised_prompt' => $image['revised_prompt'] ?? null,
                'image_size' => data_get($validated, 'size', '1024x1024'),
                'image_style' => data_get($validated, 'style', 'auto'),
                'conversation_public_id' => $conversation->public_id,
                'conversation_title' => $conversation->title,
            ],
        ]);
    }

    private function formatConversation(StandaloneAiChatConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'public_id' => $conversation->public_id,
            'title' => $conversation->title ?: __('New chat'),
            'last_message_at' => optional($conversation->last_message_at)->diffForHumans(),
        ];
    }

    private function formatAiBucketLabel(string $bucket): string
    {
        $label = trim($bucket);

        return match (strtolower($label)) {
            'general', 'ai chat', 'chat' => __('AI Chat (General)'),
            'image_generation', 'image analysis', 'image' => __('Image Analysis'),
            'pdf', 'document', 'document ai' => __('PDF / Document AI'),
            'question_solving', 'question solving', 'homework' => __('Question Solving'),
            'voice_to_text', 'voice to text' => __('Voice to Text'),
            default => ucwords(str_replace(['_', '-'], ' ', $label)),
        };
    }

    private function truncateAiText(string $text, int $limit = 64): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $text) ?: '');

        return mb_strlen($clean) > $limit
            ? rtrim(mb_substr($clean, 0, $limit - 1)) . '...'
            : $clean;
    }
}

