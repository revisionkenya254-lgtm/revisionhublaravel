<?php

namespace App\Services\Ai;

use App\Models\AiDocument;
use App\Models\AiDocumentQuestionRegion;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AiDocumentQuestionRegionService
{
    public function rebuild(AiDocument $document, array $pages): array
    {
        AiDocumentQuestionRegion::where('ai_document_id', $document->id)->delete();

        $regions = [];

        foreach ($pages as $page) {
            $regions = array_merge($regions, $this->extractRegionsFromPage($document, $page));
        }

        foreach ($regions as $region) {
            AiDocumentQuestionRegion::create($region);
        }

        return $regions;
    }

    private function extractRegionsFromPage(AiDocument $document, array $page): array
    {
        $pageNumber = (int) ($page['page_number'] ?? 0);
        $pageWidth = (float) Arr::get($page, 'page_width', 1) ?: 1;
        $pageHeight = (float) Arr::get($page, 'page_height', 1) ?: 1;
        $lines = collect($page['lines'] ?? [])->values();

        if ($pageNumber <= 0 || $lines->isEmpty()) {
            return [];
        }

        $regions = [];
        $current = null;

        foreach ($lines as $lineIndex => $line) {
            $text = trim((string) ($line['text'] ?? ''));
            $box = $this->normalizedBox($line['bbox'] ?? [], $pageWidth, $pageHeight);

            if ($text === '' || $box === null) {
                continue;
            }

            $match = $this->questionStartMatch($text);

            if ($match !== null) {
                if ($current !== null) {
                    $regions[] = $this->finalizeRegion($document, $pageNumber, $current);
                }

                $current = [
                    'question_number' => $match['number'],
                    'question_label' => $match['label'],
                    'content' => $text,
                    'line_payload' => [
                        [
                            'text' => $text,
                            'bbox' => $box,
                            'line_index' => $lineIndex,
                        ],
                    ],
                    'bbox' => $box,
                ];
                continue;
            }

            if ($current === null) {
                continue;
            }

            $current['content'] .= "\n" . $text;
            $current['line_payload'][] = [
                'text' => $text,
                'bbox' => $box,
                'line_index' => $lineIndex,
            ];
            $current['bbox'] = $this->mergeBoxes($current['bbox'], $box);
        }

        if ($current !== null) {
            $regions[] = $this->finalizeRegion($document, $pageNumber, $current);
        }

        return $regions;
    }

    private function finalizeRegion(AiDocument $document, int $pageNumber, array $current): array
    {
        $bbox = $current['bbox'];

        return [
            'ai_document_id' => $document->id,
            'question_number' => $current['question_number'],
            'question_label' => $current['question_label'],
            'page_number' => $pageNumber,
            'x' => $bbox['x'],
            'y' => $bbox['y'],
            'width' => $bbox['width'],
            'height' => $bbox['height'],
            'content' => trim($current['content']),
            'line_payload' => $current['line_payload'],
            'metadata' => [
                'source_name' => $document->source_name,
                'source_type' => $document->source_type,
            ],
        ];
    }

    private function questionStartMatch(string $text): ?array
    {
        if (preg_match('/^(?:question\s*)?(?<number>\d{1,3})(?:[\.\)\:-]|\s|$)/i', $text, $matches)) {
            $number = (int) $matches['number'];

            return [
                'number' => $number,
                'label' => 'Question ' . $number,
            ];
        }

        return null;
    }

    private function normalizedBox(array $bbox, float $pageWidth, float $pageHeight): ?array
    {
        $x = (float) ($bbox['x'] ?? 0);
        $y = (float) ($bbox['y'] ?? 0);
        $width = (float) ($bbox['width'] ?? 0);
        $height = (float) ($bbox['height'] ?? 0);

        if ($width <= 0 || $height <= 0) {
            return null;
        }

        return [
            'x' => max(0.0, min(1.0, $x / $pageWidth)),
            'y' => max(0.0, min(1.0, $y / $pageHeight)),
            'width' => max(0.0, min(1.0, $width / $pageWidth)),
            'height' => max(0.0, min(1.0, $height / $pageHeight)),
        ];
    }

    private function mergeBoxes(array $left, array $right): array
    {
        $leftX = (float) ($left['x'] ?? 0);
        $leftY = (float) ($left['y'] ?? 0);
        $leftW = (float) ($left['width'] ?? 0);
        $leftH = (float) ($left['height'] ?? 0);
        $rightX = (float) ($right['x'] ?? 0);
        $rightY = (float) ($right['y'] ?? 0);
        $rightW = (float) ($right['width'] ?? 0);
        $rightH = (float) ($right['height'] ?? 0);

        $x1 = min($leftX, $rightX);
        $y1 = min($leftY, $rightY);
        $x2 = max($leftX + $leftW, $rightX + $rightW);
        $y2 = max($leftY + $leftH, $rightY + $rightH);

        return [
            'x' => $x1,
            'y' => $y1,
            'width' => max(0.0, $x2 - $x1),
            'height' => max(0.0, $y2 - $y1),
        ];
    }
}
