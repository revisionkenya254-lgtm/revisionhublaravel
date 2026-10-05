<?php

namespace App\Services\Ai;

use PhpOffice\PhpWord\Shared\OLERead;
use RuntimeException;
use Throwable;

class LegacyDocTextExtractionService
{
    private const STYLE_NOISE_PHRASES = [
        'default paragraph font',
        'table normal',
        'normal',
        'normal table',
        'times new roman',
        'cambria math',
        'heading 1',
        'heading 2',
        'heading 3',
        'heading 4',
        'heading 5',
        'heading 6',
        'heading 7',
        'heading 8',
        'heading 9',
        'title',
        'subtitle',
        'no spacing',
    ];

    public function extractFromPath(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException(__('The Word file could not be found.'));
        }

        $analysis = $this->extractBinaryText($path);
        $text = $this->normalizeText((string) ($analysis['text'] ?? ''));
        $qualityScore = (int) ($analysis['quality_score'] ?? 0);
        $styleNoiseRatio = (float) ($analysis['style_noise_ratio'] ?? 0);
        $wordCount = (int) ($analysis['word_count'] ?? 0);
        $requiresTranscription = $text === '' || $qualityScore < 35 || $styleNoiseRatio >= 0.25 || $wordCount < 8;

        if ($requiresTranscription) {
            $text = '';
        }

        $pageCount = $text !== '' ? 1 : 0;

        return [
            'text' => $text,
            'excerpt' => mb_substr($text, 0, 1000),
            'page_count' => $pageCount,
            'character_count' => mb_strlen($text),
            'requires_transcription' => $requiresTranscription,
            'quality_score' => $qualityScore,
            'style_noise_ratio' => $styleNoiseRatio,
            'word_count' => $wordCount,
            'method' => $text !== '' ? 'legacy-ole' : 'legacy-ole-low-confidence',
            'renderable_text' => (string) ($analysis['renderable_text'] ?? ''),
            'pages' => $text !== '' ? [[
                'page_number' => 1,
                'text' => $text,
                'page_width' => 1,
                'page_height' => 1,
                'lines' => [],
            ]] : [],
        ];
    }

    private function extractBinaryText(string $path): array
    {
        try {
            $ole = new OLERead();
            $ole->read($path);
        } catch (Throwable) {
            return [
                'text' => '',
                'quality_score' => 0,
            ];
        }

        $streams = array_filter([
            $this->safeGetStream($ole, 'wrkdocument'),
            $this->safeGetStream($ole, 'wrk1Table'),
            $this->safeGetStream($ole, 'wrkData'),
            $this->safeGetStream($ole, 'wrkObjectPool'),
        ]);

        $candidates = [];
        foreach ($streams as $stream) {
            $candidates = array_merge($candidates, $this->extractCandidatesFromStream($stream));
        }

        $rankedCandidates = $this->rankCandidates($candidates);
        $filtered = array_filter($rankedCandidates, fn (array $candidate) => $candidate['score'] >= 18);

        if (empty($filtered)) {
            return [
                'text' => '',
                'quality_score' => 0,
                'style_noise_ratio' => 1,
            ];
        }

        usort($filtered, fn (array $left, array $right) => $right['score'] <=> $left['score']);

        $selectedCandidates = array_slice($filtered, 0, 40);
        $selected = array_map(fn (array $candidate) => $candidate['text'], $selectedCandidates);
        $styleNoiseCount = 0;
        foreach ($selected as $candidateText) {
            if ($this->isStyleNoiseCandidate($candidateText)) {
                $styleNoiseCount++;
            }
        }

        $filteredSelected = array_values(array_filter($selected, fn (string $candidateText) => ! $this->isStyleNoiseCandidate($candidateText)));

        if (empty($filteredSelected)) {
            return [
                'text' => '',
                'quality_score' => 0,
                'style_noise_ratio' => 1,
                'word_count' => 0,
                'renderable_text' => '',
            ];
        }

        $combinedText = trim(implode("\n", $filteredSelected));
        $wordCount = preg_match_all('/\b[\pL\pN]{2,}\b/u', $combinedText) ?: 0;
        $digitCount = preg_match_all('/\d/', $combinedText) ?: 0;
        $letterCount = preg_match_all('/\pL/u', $combinedText) ?: 0;

        if ($wordCount < 8 || mb_strlen($combinedText) < 50 || $digitCount > $letterCount) {
            return [
                'text' => '',
                'quality_score' => 0,
                'style_noise_ratio' => 1,
                'word_count' => $wordCount,
                'renderable_text' => $combinedText,
            ];
        }

        return [
            'text' => $combinedText,
            'quality_score' => (int) round(array_sum(array_column($filtered, 'score')) / max(1, count($filtered))),
            'style_noise_ratio' => $styleNoiseCount / max(1, count($selected)),
            'word_count' => $wordCount,
            'renderable_text' => $combinedText,
        ];
    }

    private function safeGetStream(OLERead $ole, string $property): string
    {
        $streamIndex = $ole->{$property} ?? null;

        if ($streamIndex === null) {
            return '';
        }

        try {
            $stream = $ole->getStream($streamIndex);

            return is_string($stream) ? $stream : '';
        } catch (Throwable) {
            return '';
        }
    }

    private function extractCandidatesFromStream(string $stream): array
    {
        $candidates = [];

        if (preg_match_all('/(?:[\x20-\x7E]\x00){4,}/', $stream, $matches)) {
            foreach ($matches[0] as $match) {
                $decoded = @mb_convert_encoding($match, 'UTF-8', 'UTF-16LE');
                if (is_string($decoded) && trim($decoded) !== '') {
                    $candidates[] = $decoded;
                }
            }
        }

        if (preg_match_all('/[A-Za-z0-9][A-Za-z0-9 \t\-\.,;:!?\(\)\[\]\/\\\\+&%#\'"]{5,}/', $stream, $matches)) {
            foreach ($matches[0] as $match) {
                if (is_string($match) && trim($match) !== '') {
                    $candidates[] = $match;
                }
            }
        }

        return $candidates;
    }

    private function rankCandidates(array $candidates): array
    {
        $ranked = [];

        foreach ($candidates as $candidate) {
            $candidate = $this->normalizeText($candidate);

            if ($candidate === '') {
                continue;
            }

            if ($this->isStyleNoiseCandidate($candidate)) {
                continue;
            }

            $score = $this->scoreCandidate($candidate);
            if ($score <= 0) {
                continue;
            }

            $ranked[] = [
                'text' => $candidate,
                'score' => $score,
            ];
        }

        return array_values(array_unique($ranked, SORT_REGULAR));
    }

    private function scoreCandidate(string $candidate): int
    {
        $candidate = trim($candidate);
        $length = mb_strlen($candidate);
        if ($length < 12) {
            return 0;
        }

        $wordCount = preg_match_all('/\b[\pL\pN]{2,}\b/u', $candidate) ?: 0;
        $letterCount = preg_match_all('/\pL/u', $candidate) ?: 0;
        $vowelCount = preg_match_all('/[aeiouAEIOU]/', $candidate) ?: 0;
        $digitCount = preg_match_all('/\d/', $candidate) ?: 0;
        $symbolCount = preg_match_all('/[^[:alnum:]\s\.,;:!?\-\'"\/\(\)\[\]]/', $candidate) ?: 0;
        $punctuationBoost = preg_match('/[\.!?]$/', $candidate) ? 3 : 0;
        $sentenceBoost = preg_match('/\s/', $candidate) ? 2 : 0;

        $score = ($wordCount * 6) + (int) min(20, $letterCount / 3) + min(6, $vowelCount) + $punctuationBoost + $sentenceBoost;
        $score -= (int) round($digitCount * 0.7);
        $score -= (int) round($symbolCount * 1.5);

        return max(0, $score);
    }

    private function normalizeCandidate(string $candidate): string
    {
        $candidate = str_replace("\0", '', $candidate);
        $candidate = preg_replace('/[ \t]+/', ' ', $candidate) ?? $candidate;
        $candidate = preg_replace("/\n{3,}/", "\n\n", $candidate) ?? $candidate;
        $candidate = trim($candidate);

        return $candidate;
    }

    private function filterReadableCandidates(array $candidates): array
    {
        return array_values(array_filter(array_map(function (string $candidate) {
            $candidate = trim(preg_replace('/[ \t]+/', ' ', $candidate) ?? '');
            $candidate = preg_replace("/\n{3,}/", "\n\n", $candidate) ?? $candidate;

            if ($candidate === '') {
                return null;
            }

            $letters = preg_match_all('/[A-Za-z]/', $candidate);
            if ($letters < 3) {
                return null;
            }

            $printable = preg_match_all('/[[:print:]]/', $candidate);
            if ($printable === 0) {
                return null;
            }

            return $candidate;
        }, $candidates)));
    }

    private function normalizeText(string $text): string
    {
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function isStyleNoiseCandidate(string $candidate): bool
    {
        $normalized = strtolower(trim(preg_replace('/[ \t]+/', ' ', $candidate) ?? ''));
        if ($normalized === '') {
            return true;
        }

        if (in_array($normalized, self::STYLE_NOISE_PHRASES, true)) {
            return true;
        }

        if (preg_match('/^(?:[a-z]+\s+){0,4}(font|normal|table|style|heading|subtitle|title)(?:\s+[a-z]+){0,4}$/i', $normalized)) {
            return true;
        }

        if (preg_match('/^(?:default\s+)?(?:paragraph\s+)?font$/i', $normalized)) {
            return true;
        }

        if (preg_match('/^times\s+new\s+roman$/i', $normalized)) {
            return true;
        }

        if (preg_match('/^cambria\s+math$/i', $normalized)) {
            return true;
        }

        if (preg_match('/^[a-z][a-z0-9\s\-]{0,32}$/i', $normalized) && preg_match('/\b(normal|font|table|style)\b/i', $normalized)) {
            return true;
        }

        return false;
    }
}
