<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ImageTranscriptionService
{
    public function transcribeImagePaths(array $imagePaths, int $maxImages = 10): array
    {
        $apiKey = trim((string) config('services.openai_vision.api_key', config('services.openai.api_key')));
        $baseUrl = rtrim((string) config('services.openai_vision.base_url', config('services.openai.base_url', 'https://api.openai.com/v1')), '/');
        $model = (string) config('services.openai_vision.model', config('services.openai.model', 'gpt-4o-mini'));
        $limit = max(1, $maxImages);

        if ($apiKey === '') {
            throw new RuntimeException(__('OpenAI API key is not configured for image transcription.'));
        }

        $pages = [];
        $images = array_values(array_filter($imagePaths, fn (string $path) => is_file($path)));

        foreach (array_slice($images, 0, $limit) as $index => $imagePath) {
            $imageBytes = (string) file_get_contents($imagePath);
            if ($imageBytes === '') {
                continue;
            }

            $mimeType = $this->mimeTypeFromExtension(pathinfo($imagePath, PATHINFO_EXTENSION));
            $dataUri = 'data:' . $mimeType . ';base64,' . base64_encode($imageBytes);

            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(90)
                ->post($baseUrl . '/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You transcribe text from document images. Return only the transcription. Preserve headings, line breaks, bullets, question numbers, and simple math as plain text so each question can be extracted question by question later. Do not combine multiple questions into one item when the text is later parsed.',
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Transcribe this document image. If it contains no readable text, return an empty string. Keep question numbers, section labels, and answer instructions intact so later extraction can split each question separately. Keep each visible question clearly separated in the transcription.',
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => ['url' => $dataUri],
                                ],
                            ],
                        ],
                    ],
                    'temperature' => 0.0,
                    'max_tokens' => 1200,
                ]);

            if (! $response->successful()) {
                Log::error('Image transcription failed', [
                    'image_path' => $imagePath,
                    'status' => $response->status(),
                    'content_type' => $response->header('content-type'),
                    'response_body' => $response->body(),
                    'response_preview' => $this->truncateText((string) $response->body(), 4000),
                ]);

                throw new RuntimeException($response->json('error.message') ?: __('Image transcription failed.'));
            }

            $text = trim((string) data_get($response->json(), 'choices.0.message.content', ''));
            if ($text === '') {
                continue;
            }

            $pages[] = [
                'page_number' => count($pages) + 1,
                'text' => $text,
                'page_width' => 1,
                'page_height' => 1,
                'lines' => [],
                'image_path' => $imagePath,
            ];
        }

        $text = trim(implode("\n\n", array_map(
            fn (array $page) => sprintf("[Page %d]\n%s", $page['page_number'], trim((string) $page['text'])),
            $pages
        )));

        return [
            'pages' => $pages,
            'text' => $text,
            'excerpt' => mb_substr($text, 0, 1000),
            'page_count' => count($pages),
            'character_count' => mb_strlen($text),
            'method' => 'openai_vision_image',
        ];
    }

    private function mimeTypeFromExtension(?string $extension): string
    {
        return match (strtolower((string) $extension)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }

    private function truncateText(string $text, int $limit = 4000): string
    {
        $text = trim($text);

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return mb_substr($text, 0, $limit) . '...[truncated]';
    }
}
