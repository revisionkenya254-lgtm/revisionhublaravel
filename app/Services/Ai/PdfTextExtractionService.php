<?php

namespace App\Services\Ai;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\LineGroupingStrategy\TextOverlapStrategy;
use PrinsFrank\PdfParser\PdfParser;
use RuntimeException;

class PdfTextExtractionService
{
    public function extractFromPath(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException(__('The PDF file could not be found.'));
        }

        $pages = [];
        $pageTexts = [];
        $text = '';
        $method = 'prinsfrank';

        try {
            $document = (new PdfParser())->parseFile($path);
            $pages = $document->getPages();

            foreach ($pages as $index => $page) {
                $pageNumber = $index + 1;
                $pageLines = $this->extractPageLines($page, $document);
                $pageText = trim(implode("\n", array_column($pageLines, 'text')));

                $pageTexts[] = [
                    'page_number' => $pageNumber,
                    'text' => $pageText,
                    'page_width' => $page->getMediaBox()?->getWidth() ?? 1,
                    'page_height' => $page->getMediaBox()?->getHeight() ?? 1,
                    'lines' => $pageLines,
                ];
            }

            $pageSegments = array_values(array_filter(array_map(
                fn ($page) => trim((string) ($page['text'] ?? '')),
                $pageTexts
            )));

            $text = trim(implode("\n\n", $pageSegments));

            if ($text === '' && ! empty($pageTexts)) {
                $text = trim(implode("\n\n", array_map(
                    fn ($page) => sprintf("[Page %d]\n%s", $page['page_number'], trim((string) ($page['text'] ?? ''))),
                    $pageTexts
                )));
            }
        } catch (\Throwable $throwable) {
            $text = '';
            $pages = [];
        }

        $text = $this->normalizeText($text);
        $pageCount = count($pages);
        $characterCount = mb_strlen($text);
        $requiresOcr = $characterCount < 120;

        return [
            'text' => $text,
            'excerpt' => mb_substr($text, 0, 1000),
            'page_count' => $pageCount,
            'character_count' => $characterCount,
            'requires_transcription' => $requiresOcr,
            'method' => $method,
            'pages' => $pageTexts,
        ];
    }

    private function normalizeText(string $text): string
    {
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function extractPageLines($page, $document): array
    {
        try {
            $positionedElements = $page->getPositionedTextElements();
        } catch (\Throwable) {
            return [];
        }

        $lines = [];
        foreach ((new TextOverlapStrategy())->group($positionedElements) as $lineIndex => $lineElements) {
            $textParts = [];
            $boxes = [];

            foreach ($lineElements as $element) {
                try {
                    $text = trim((string) $element->getText($document, $page));
                } catch (\Throwable) {
                    $text = '';
                }

                if ($text !== '') {
                    $textParts[] = $text;
                }

                $fontWidth = 0.0;
                try {
                    $fontWidth = (float) $element->getFont($document, $page)->getWidthForChars($element->getCodePoints(), $element->textState, $element->absoluteMatrix);
                } catch (\Throwable) {
                    $fontWidth = max(1.0, mb_strlen($text) * (($element->textState->fontSize ?? 10) * 0.5));
                }

                $boxes[] = [
                    'x' => $element->absoluteMatrix->offsetX,
                    'y' => $element->absoluteMatrix->offsetY,
                    'width' => max(1.0, $fontWidth),
                    'height' => max(1.0, $element->getHeight()),
                ];
            }

            if (empty($textParts) || empty($boxes)) {
                continue;
            }

            $bbox = $this->mergeBoxes($boxes);
            $lines[] = [
                'text' => trim(implode(' ', $textParts)),
                'bbox' => $bbox,
                'line_index' => $lineIndex,
            ];
        }

        return $lines;
    }

    private function mergeBoxes(array $boxes): array
    {
        $x1 = min(array_column($boxes, 'x'));
        $y1 = min(array_column($boxes, 'y'));
        $x2 = max(array_map(fn ($box) => $box['x'] + $box['width'], $boxes));
        $y2 = max(array_map(fn ($box) => $box['y'] + $box['height'], $boxes));

        return [
            'x' => $x1,
            'y' => $y1,
            'width' => max(1.0, $x2 - $x1),
            'height' => max(1.0, $y2 - $y1),
        ];
    }
}
