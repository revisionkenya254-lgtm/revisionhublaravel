<?php

namespace App\Services\Ai;

use Illuminate\Support\Str;

class AiDocumentChunkingService
{
    public function buildChunks(string|array $input, array $context = []): array
    {
        if (is_array($input)) {
            return $this->buildChunksFromPages($input, $context);
        }

        $text = trim($input);

        if ($text === '') {
            return [];
        }

        $paragraphs = preg_split("/\R{2,}/u", $text) ?: [];
        $chunks = [];
        $current = [];
        $currentLength = 0;
        $chunkIndex = 0;
        $maxLength = 3000;

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim(preg_replace('/[ \t]+/', ' ', $paragraph) ?? $paragraph);

            if ($paragraph === '') {
                continue;
            }

            $paragraphParts = mb_strlen($paragraph) > $maxLength
                ? $this->splitLongParagraph($paragraph, $maxLength)
                : [$paragraph];

            foreach ($paragraphParts as $part) {
                $partLength = mb_strlen($part);

                if ($currentLength > 0 && ($currentLength + $partLength + 2) > $maxLength) {
                    $chunks[] = $this->makeChunk($chunkIndex++, implode("\n\n", $current), $context);
                    $current = [];
                    $currentLength = 0;
                }

                $current[] = $part;
                $currentLength += $partLength + 2;
            }
        }

        if (! empty($current)) {
            $chunks[] = $this->makeChunk($chunkIndex, implode("\n\n", $current), $context);
        }

        return $chunks;
    }

    private function buildChunksFromPages(array $pages, array $context = []): array
    {
        $chunks = [];
        $current = [];
        $currentLength = 0;
        $chunkIndex = 0;
        $maxLength = 3000;
        $currentPageStart = null;
        $currentPageEnd = null;

        foreach ($pages as $page) {
            $pageNumber = (int) ($page['page_number'] ?? 0);
            $pageText = trim(preg_replace('/[ \t]+/', ' ', (string) ($page['text'] ?? '')) ?? '');

            if ($pageNumber <= 0 || $pageText === '') {
                continue;
            }

            $paragraphs = preg_split("/\R{2,}/u", $pageText) ?: [];

            foreach ($paragraphs as $paragraph) {
                $paragraph = trim($paragraph);

                if ($paragraph === '') {
                    continue;
                }

                $paragraphParts = mb_strlen($paragraph) > $maxLength
                    ? $this->splitLongParagraph($paragraph, $maxLength)
                    : [$paragraph];

                foreach ($paragraphParts as $part) {
                    $partLength = mb_strlen($part);

                    if ($currentLength > 0 && ($currentLength + $partLength + 2) > $maxLength) {
                        $chunks[] = $this->makeChunk($chunkIndex++, implode("\n\n", $current), array_merge($context, [
                            'page_start' => $currentPageStart,
                            'page_end' => $currentPageEnd,
                        ]));
                        $current = [];
                        $currentLength = 0;
                        $currentPageStart = null;
                        $currentPageEnd = null;
                    }

                    $currentPageStart ??= $pageNumber;
                    $currentPageEnd = $pageNumber;
                    $current[] = $part;
                    $currentLength += $partLength + 2;
                }
            }
        }

        if (! empty($current)) {
            $chunks[] = $this->makeChunk($chunkIndex, implode("\n\n", $current), array_merge($context, [
                'page_start' => $currentPageStart,
                'page_end' => $currentPageEnd,
            ]));
        }

        return $chunks;
    }

    private function splitLongParagraph(string $paragraph, int $maxLength): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/u', $paragraph) ?: [$paragraph];
        $parts = [];
        $current = '';

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);

            if ($sentence === '') {
                continue;
            }

            if ($current !== '' && mb_strlen($current . ' ' . $sentence) > $maxLength) {
                $parts[] = $current;
                $current = $sentence;
                continue;
            }

            $current = $current === '' ? $sentence : $current . ' ' . $sentence;
        }

        if ($current !== '') {
            $parts[] = $current;
        }

        return $parts;
    }

    private function makeChunk(int $index, string $content, array $context = []): array
    {
        return [
            'chunk_index' => $index,
            'heading' => $context['heading'] ?? null,
            'page_start' => $context['page_start'] ?? null,
            'page_end' => $context['page_end'] ?? null,
            'content' => trim($content),
            'content_hash' => hash('sha256', trim($content)),
            'token_count' => max(1, (int) ceil(mb_strlen($content) / 4)),
            'metadata' => array_filter([
                'source_type' => $context['source_type'] ?? null,
                'source_name' => $context['source_name'] ?? null,
                'chunk_label' => $context['chunk_label'] ?? null,
            ]),
        ];
    }
}
