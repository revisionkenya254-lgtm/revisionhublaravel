<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\StandaloneAiChatConversation;
use App\Services\Ai\StandaloneAiChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiChatController extends Controller
{
    public function __construct(
        private readonly StandaloneAiChatService $chatService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $conversation = $request->query('conversation');
        $data = $this->chatService->buildIndexData($user, is_string($conversation) ? $conversation : null);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ], 200);
    }

    public function credits(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->chatService->buildCreditsData(auth()->user()),
        ], 200);
    }

    public function conversations(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->chatService->listConversations(auth()->user()),
        ], 200);
    }

    public function storeConversation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $this->chatService->createConversation(auth()->user(), $validated['title'] ?? null),
        ], 201);
    }

    public function conversationMessages(Request $request, StandaloneAiChatConversation $conversation): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->chatService->conversationMessages(auth()->user(), $conversation),
        ], 200);
    }

    public function stream(Request $request, StandaloneAiChatConversation $conversation): StreamedResponse|JsonResponse
    {
        return $this->chatService->stream($request, $conversation, auth()->user());
    }

    public function generateImage(Request $request, StandaloneAiChatConversation $conversation): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->chatService->generateImage($request, $conversation, auth()->user()),
        ], 200);
    }

    public function destroy(StandaloneAiChatConversation $conversation): JsonResponse
    {
        return response()->json(
            $this->chatService->destroy($conversation, auth()->user()),
            200
        );
    }
}

