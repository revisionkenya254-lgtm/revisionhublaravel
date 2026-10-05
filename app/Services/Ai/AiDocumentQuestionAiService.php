<?php

namespace App\Services\Ai;

use App\Models\AiDocumentQuestionRegion;
use RuntimeException;

class AiDocumentQuestionAiService
{
    public function __construct(
        private readonly AiProviderRouterService $routerService,
        private readonly OpenAiChatProvider $openAiProvider
    ) {
    }

    public function answer(AiDocumentQuestionRegion $region, string $studentPrompt = ''): array
    {
        return $this->generateAnswer($region, $studentPrompt, false, null);
    }

    public function streamAnswer(AiDocumentQuestionRegion $region, string $studentPrompt, callable $onToken): array
    {
        return $this->generateAnswer($region, $studentPrompt, true, $onToken);
    }

    private function resolveProvider(string $providerName): AiProviderInterface
    {
        return $this->openAiProvider;
    }

    private function generateAnswer(AiDocumentQuestionRegion $region, string $studentPrompt, bool $stream, ?callable $onToken): array
    {
        $region->loadMissing('document');

        $prompt = trim($studentPrompt) !== '' ? trim($studentPrompt) : $region->question_label;
        $questionText = trim(implode("\n\n", array_filter([
            $region->question_label,
            $region->content,
            $studentPrompt !== '' ? 'Student request: ' . $studentPrompt : '',
        ])));

        $messages = [
            [
                'role' => 'system',
                'content' => implode("\n", [
                    'You are a helpful tutor answering a selected question region from a PDF.',
                    'Use the selected region text as the primary source.',
                    'Explain clearly and directly.',
                    'If the selected region is incomplete, say so briefly and do not invent missing content.',
                    'Do not ask the user to provide the question again when the region text is already available.',
                    'Answer the question using the visible region content first, then the student request if relevant.',
                ]),
            ],
            [
                'role' => 'user',
                'content' => trim(implode("\n\n", [
                    'Selected question region: ' . $region->question_label,
                    'Region text: ' . $region->content,
                    $studentPrompt !== '' ? 'Student prompt: ' . $studentPrompt : '',
                ])),
            ],
        ];

        $providerOrder = $this->routerService->orderedProviders($questionText, [
            'results' => [
                ['score' => 100],
            ],
        ]);

        foreach ($providerOrder as $providerName) {
            $provider = $this->resolveProvider($providerName);

            try {
                $result = $stream
                    ? $provider->streamAnswer($messages, [
                        'temperature' => 0.2,
                        'max_tokens' => 700,
                    ], $onToken ?? static fn (string $token) => null)
                    : $provider->answer($messages, [
                        'temperature' => 0.2,
                        'max_tokens' => 700,
                    ]);

                $content = trim((string) ($result['content'] ?? ''));
                if ($content === '') {
                    continue;
                }

                return [
                    'provider' => $result['provider'] ?? $providerName,
                    'model' => $result['model'] ?? null,
                    'content' => $content,
                    'input_tokens' => (int) ($result['input_tokens'] ?? 0),
                    'output_tokens' => (int) ($result['output_tokens'] ?? 0),
                    'estimated_cost' => (float) ($result['estimated_cost'] ?? 0),
                    'credits_used' => (int) ($result['credits_used'] ?? 0),
                    'latency' => (int) ($result['latency'] ?? 0),
                ];
            } catch (RuntimeException) {
                continue;
            }
        }

        throw new RuntimeException(__('Unable to generate an answer for the selected region.'));
    }
}
