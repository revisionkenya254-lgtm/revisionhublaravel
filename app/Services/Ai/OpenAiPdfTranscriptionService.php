<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use PrinsFrank\PdfParser\PdfParser;

class OpenAiPdfTranscriptionService
{
    public function transcribe(string $path, int $maxPages = 10): array
    {
        if (! is_file($path)) {
            throw new RuntimeException(__('The PDF file could not be found.'));
        }

        $apiKey = trim((string) config('services.openai_vision.api_key', config('services.openai.api_key')));
        $baseUrl = rtrim((string) config('services.openai_vision.base_url', config('services.openai.base_url', 'https://api.openai.com/v1')), '/');
        $model = (string) config('services.openai_vision.model', config('services.openai.model', 'gpt-4o-mini'));
        $maxPages = max(1, $maxPages);

        if ($apiKey === '') {
            throw new RuntimeException(__('OpenAI API key is not configured for scanned PDF transcription.'));
        }

        $document = (new PdfParser())->parseFile($path);
        $pages = array_slice($document->getPages(), 0, $maxPages);

        $pageTexts = [];
        foreach ($pages as $index => $page) {
            $pageNumber = $index + 1;
            $image = $this->largestPageImage($page);

            if (! $image) {
                continue;
            }

            $imageBytes = (string) $image->getContent();
            if ($imageBytes === '') {
                continue;
            }

            $mimeType = $this->guessMimeType($image->getImageType()?->getFileExtension());
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
                            'content' => 'You transcribe text from scanned PDF page images. Return only the transcription. Preserve headings, line breaks, bullets, question numbers, and simple math as plain text so each question can be extracted separately later. Do not combine multiple questions into one item.',
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Transcribe this PDF page image. If it contains no readable text, return an empty string. Keep visible question numbers and section labels intact, and separate each question clearly in the transcription.',
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
                throw new RuntimeException($response->json('error.message') ?: __('OpenAI scanned PDF transcription failed.'));
            }

            $text = trim((string) data_get($response->json(), 'choices.0.message.content', ''));
            if ($text === '') {
                continue;
            }

            $pageTexts[] = [
                'page_number' => $pageNumber,
                'text' => $text,
            ];
        }

        return [
            'pages' => $pageTexts,
            'text' => trim(implode("\n\n", array_map(
                fn (array $page) => sprintf("[Page %d]\n%s", $page['page_number'], trim((string) $page['text'])),
                $pageTexts
            ))),
            'method' => 'openai_vision',
        ];
    }

    private function largestPageImage($page): mixed
    {
        try {
            $images = $page->getImages();
        } catch (\Throwable) {
            return null;
        }

        if (empty($images)) {
            return null;
        }

        usort($images, function ($left, $right) {
            $leftSize = method_exists($left, 'getLength') ? (int) $left->getLength() : strlen((string) $left->getContent());
            $rightSize = method_exists($right, 'getLength') ? (int) $right->getLength() : strlen((string) $right->getContent());

            return $rightSize <=> $leftSize;
        });

        return $images[0] ?? null;
    }

    private function guessMimeType(?string $extension): string
    {
        return match (strtolower((string) $extension)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }
}
