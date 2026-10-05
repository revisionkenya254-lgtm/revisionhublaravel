<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\StandaloneAiChatConversation;
use App\Services\Ai\StandaloneAiChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiChatController extends Controller
{
    public function __construct(
        private readonly StandaloneAiChatService $chatService
    ) {
    }

    public function index(Request $request, ?string $conversation = null): View
    {
        $data = $this->chatService->buildIndexData(userAuth(), $conversation);
        $data['returnToUrl'] = $this->resolveReturnToUrl($request);

        return view('frontend.ai-chat.index', $data);
    }

    public function credits(): View
    {
        return view('frontend.ai-chat.credits', $this->chatService->buildCreditsData(userAuth()));
    }

    public function storeConversation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $this->chatService->storeConversation(userAuth(), $validated['title'] ?? null);

        return response()->json(
            array_merge(
                $result,
                ['redirect' => $this->appendModalState(
                    $this->appendReturnTo($result['redirect'] ?? '', $this->resolveReturnToUrl($request)),
                    $request
                )]
            )
        );
    }

    public function conversationMessages(Request $request, StandaloneAiChatConversation $conversation): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'messages' => $this->chatService->conversationMessages(userAuth(), $conversation),
        ]);
    }

    public function stream(Request $request, StandaloneAiChatConversation $conversation): StreamedResponse|JsonResponse
    {
        return $this->chatService->stream($request, $conversation, userAuth());
    }

    public function generateImage(Request $request, StandaloneAiChatConversation $conversation): JsonResponse
    {
        return response()->json($this->chatService->generateImage($request, $conversation, userAuth()));
    }

    public function destroy(StandaloneAiChatConversation $conversation): JsonResponse
    {
        return response()->json($this->chatService->destroy($conversation, userAuth()));
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
}

