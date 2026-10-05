<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiChatConversation;
use App\Models\AiRequest;
use App\Models\User;
use App\Services\Ai\AiCreditLedgerService;
use App\Services\Ai\AiCreditPurchaseService;
use App\Services\Ai\AIChatService;
use App\Services\Ai\DTO\ChatRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentAiChatController extends Controller
{
    public function __construct(
        private readonly AIChatService $chatService,
        private readonly AiCreditLedgerService $creditLedger,
        private readonly AiCreditPurchaseService $purchaseService
    ) {
    }

    public function index(Request $request, ?string $conversation = null): View
    {
        $user = userAuth();
        $returnToUrl = $this->resolveReturnToUrl($request);
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
        $conversations = $this->chatService->listConversations($user);
        $activeConversation = null;

        if ($conversation) {
            $activeConversation = $this->chatService->getConversation($user, $conversation);
        }

        $messages = $activeConversation
            ? $activeConversation->messages()->orderBy('id')->get()
            : collect();

        $view = $request->boolean('embedded') || $request->routeIs('ai-chat')
            ? 'frontend.ai-chat.index'
            : 'frontend.student-dashboard.ai-chat.index';

        return view($view, compact(
            'currentBalance',
            'monthlyAllowance',
            'usageThisMonth',
            'usagePercent',
            'conversations',
            'activeConversation',
            'messages',
            'returnToUrl'
        ));
    }

    public function credits(): View
    {
        $user = userAuth();
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
                ?: $request->conversation?->title
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

        return view('frontend.ai-chat.credits', compact(
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
        ));
    }

    public function storeConversation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $returnToUrl = $this->resolveReturnToUrl($request);

        $conversation = $this->chatService->createConversation(
            userAuth(),
            $validated['title'] ?? null
        );

        return response()->json([
            'status' => 'success',
            'conversation' => $this->formatConversation($conversation),
            'redirect' => $this->appendReturnTo(
                $this->appendModalState(
                    route('student.ai-chat.index', ['conversation' => $conversation->public_id], false),
                    $request
                ),
                $returnToUrl
            ),
        ]);
    }

    public function conversationMessages(Request $request, AiChatConversation $conversation): JsonResponse
    {
        abort_unless($conversation->user_id === userAuth()->id, 403);

        $messages = $conversation->messages()->orderBy('id')->get()->map(fn ($message) => [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'provider' => $message->provider,
            'model' => $message->model,
            'created_at' => optional($message->created_at)->toDateTimeString(),
        ]);

        return response()->json([
            'status' => 'success',
            'messages' => $messages,
        ]);
    }

    public function stream(Request $request, AiChatConversation $conversation): StreamedResponse|JsonResponse
    {
        abort_unless($conversation->user_id === userAuth()->id, 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'type' => ['nullable', 'in:text,image'],
            'style' => ['nullable', 'in:default,simple,detailed'],
            'size' => ['nullable', 'in:auto,1024x1024,1536x1024,1024x1536'],
        ]);

        $prompt = trim($validated['message']);

        if (($validated['type'] ?? 'text') === 'image') {
            $image = $this->chatService->generateImage(ChatRequest::fromArray([
                'conversation_id' => $conversation->id,
                'user_id' => userAuth()->id,
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

            return response()->json([
                'status' => 'success',
                'image' => $image,
                'conversation' => $this->formatConversation($conversation->fresh()),
            ]);
        }

        $chatRequest = ChatRequest::fromArray([
            'conversation_id' => $conversation->id,
            'user_id' => userAuth()->id,
            'prompt' => $prompt,
            'provider' => 'openai',
            'mode' => 'general',
            'style' => $validated['style'] ?? 'default',
            'max_tokens' => 900,
            'temperature' => 0.2,
        ]);

        return response()->stream(function () use ($conversation, $chatRequest) {
            ignore_user_abort(true);
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', '0');

            while (ob_get_level() > 0) {
                @ob_end_flush();
            }

            ob_implicit_flush(true);

            try {
                $answer = $this->chatService->chat(
                    $chatRequest,
                    function (string $token) {
                        echo $this->streamEvent('token', $token);
                        @flush();
                    }
                );

                echo $this->streamEvent('meta', [
                    'provider' => $answer->provider,
                    'model' => $answer->model,
                    'conversation_title' => $conversation->fresh()->title,
                ]);

                echo $this->streamEvent('done', [
                    'content' => $answer->answer,
                ]);
            } catch (\Throwable $throwable) {
                Log::error('AI chat stream handler failed.', [
                    'conversation_id' => $conversation->id,
                    'user_id' => userAuth()->id,
                    'prompt' => mb_substr($chatRequest->prompt ?? '', 0, 300),
                    'provider' => $chatRequest->provider,
                    'mode' => $chatRequest->mode,
                    'style' => $chatRequest->style,
                    'message' => $throwable->getMessage(),
                    'exception' => $throwable,
                ]);

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

    public function destroy(AiChatConversation $conversation): JsonResponse
    {
        abort_unless($conversation->user_id === userAuth()->id, 403);

        $conversation->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('Conversation deleted successfully'),
        ]);
    }

    private function streamEvent(string $event, mixed $data): string
    {
        return "event: {$event}\n" . 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
    }

    private function formatConversation(AiChatConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'public_id' => $conversation->public_id,
            'title' => $conversation->title ?: __('New chat'),
            'last_message_at' => optional($conversation->last_message_at)->diffForHumans(),
        ];
    }

    private function resolveReturnToUrl(Request $request): ?string
    {
        $candidate = $request->query('return_to');

        if (! is_string($candidate) || trim($candidate) === '') {
            return null;
        }

        $candidate = trim($candidate);
        $origin = parse_url(rtrim(url('/'), '/'));
        $candidateParts = parse_url($candidate);

        if (! is_array($candidateParts)) {
            return null;
        }

        if (($candidateParts['scheme'] ?? null) === null || ($candidateParts['host'] ?? null) === null) {
            return str_starts_with($candidate, '/')
                ? url($candidate)
                : null;
        }

        if (
            ($origin['scheme'] ?? null) === ($candidateParts['scheme'] ?? null)
            && ($origin['host'] ?? null) === ($candidateParts['host'] ?? null)
            && (($origin['port'] ?? null) === ($candidateParts['port'] ?? null))
        ) {
            return $candidate;
        }

        return null;
    }

    private function appendReturnTo(string $url, ?string $returnToUrl): string
    {
        if (! $returnToUrl) {
            return $url;
        }

        return str_contains($url, '?')
            ? $url . '&return_to=' . urlencode($returnToUrl)
            : $url . '?return_to=' . urlencode($returnToUrl);
    }

    private function appendModalState(string $url, Request $request): string
    {
        if (! $request->boolean('embedded')) {
            return $url;
        }

        return str_contains($url, '?')
            ? $url . '&embedded=1'
            : $url . '?embedded=1';
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

