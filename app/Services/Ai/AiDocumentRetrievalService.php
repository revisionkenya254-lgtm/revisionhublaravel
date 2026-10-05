<?php

namespace App\Services\Ai;

use App\Models\AiDocumentChunk;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AiDocumentRetrievalService
{
    public function search(int $instructorId, string $query, array $options = []): array
    {
        $normalizedQuery = trim(preg_replace('/\s+/', ' ', mb_strtolower($query)) ?? $query);

        if ($normalizedQuery === '') {
            return [
                'query' => $query,
                'results' => [],
                'context' => '',
            ];
        }

        $terms = $this->extractTerms($normalizedQuery);
        $documentIds = array_values(array_filter(array_map('intval', $options['document_ids'] ?? [])));
        $limit = max(1, (int) ($options['limit'] ?? 5));

        $chunks = AiDocumentChunk::query()
            ->select('ai_document_chunks.*')
            ->join('ai_documents', 'ai_documents.id', '=', 'ai_document_chunks.ai_document_id')
            ->where('ai_documents.instructor_id', $instructorId)
            ->where('ai_documents.status', 'processed')
            ->when(! empty($documentIds), fn ($queryBuilder) => $queryBuilder->whereIn('ai_document_chunks.ai_document_id', $documentIds))
            ->with(['document:id,instructor_id,source_name,source_type,product_id,product_note_id,original_path,file_extension,status'])
            ->where(function ($queryBuilder) use ($terms, $normalizedQuery) {
                $queryBuilder->whereRaw('LOWER(ai_document_chunks.content) LIKE ?', ['%' . $normalizedQuery . '%'])
                    ->orWhereRaw("LOWER(COALESCE(ai_document_chunks.heading, '')) LIKE ?", ['%' . $normalizedQuery . '%'])
                    ->orWhereRaw('LOWER(ai_documents.source_name) LIKE ?', ['%' . $normalizedQuery . '%']);

                foreach ($terms as $term) {
                    $queryBuilder->orWhereRaw('LOWER(ai_document_chunks.content) LIKE ?', ['%' . $term . '%'])
                        ->orWhereRaw("LOWER(COALESCE(ai_document_chunks.heading, '')) LIKE ?", ['%' . $term . '%'])
                        ->orWhereRaw('LOWER(ai_documents.source_name) LIKE ?', ['%' . $term . '%']);
                }
            })
            ->orderByDesc('ai_document_chunks.id')
            ->limit(200)
            ->get();

        $ranked = $chunks
            ->map(function (AiDocumentChunk $chunk) use ($terms, $normalizedQuery) {
                return $this->rankChunk($chunk, $normalizedQuery, $terms);
            })
            ->sortByDesc('score')
            ->take($limit)
            ->values();

        return [
            'query' => $query,
            'results' => $ranked,
            'context' => $this->buildContext($ranked),
        ];
    }

    public function buildContext(Collection|array $results, int $maxCharacters = 8000): string
    {
        $results = collect($results);
        $context = '';

        foreach ($results as $result) {
            $document = $result['document'] ?? [];
            $header = sprintf(
                "[Source: %s | Chunk: %s%s]",
                $document['source_name'] ?? 'Unknown',
                $result['chunk_index'] ?? 0,
                isset($result['page_start'], $result['page_end']) && $result['page_start'] && $result['page_end']
                    ? " | Pages: {$result['page_start']}-{$result['page_end']}"
                    : ''
            );

            $block = $header . "\n" . trim((string) ($result['snippet'] ?? '')) . "\n\n";

            if (mb_strlen($context . $block) > $maxCharacters) {
                break;
            }

            $context .= $block;
        }

        return trim($context);
    }

    private function extractTerms(string $query): array
    {
        $stopWords = [
            'the', 'a', 'an', 'is', 'are', 'was', 'were', 'to', 'of', 'and', 'or', 'in',
            'on', 'for', 'with', 'what', 'why', 'how', 'when', 'where', 'which', 'who',
            'explain', 'tell', 'me', 'about', 'please',
        ];

        return collect(preg_split('/[^\p{L}\p{N}]+/u', $query) ?: [])
            ->map(fn ($term) => trim($term))
            ->filter()
            ->reject(fn ($term) => in_array($term, $stopWords, true) || mb_strlen($term) < 3)
            ->unique()
            ->values()
            ->all();
    }

    private function buildSnippet(string $content, array $terms, int $length = 320): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $content) ?? $content);

        if ($normalized === '') {
            return '';
        }

        $lower = mb_strtolower($normalized);
        $position = false;

        foreach ($terms as $term) {
            $termPosition = mb_stripos($lower, $term);
            if ($termPosition !== false) {
                $position = $position === false ? $termPosition : min($position, $termPosition);
            }
        }

        if ($position === false) {
            return Str::limit($normalized, $length);
        }

        $start = max(0, $position - (int) floor($length / 3));
        return Str::limit(mb_substr($normalized, $start), $length);
    }

    private function rankChunk(AiDocumentChunk $chunk, string $normalizedQuery, array $terms): array
    {
        $content = mb_strtolower(trim((string) $chunk->content));
        $heading = mb_strtolower(trim((string) $chunk->heading));
        $sourceName = mb_strtolower(trim((string) $chunk->document?->source_name));
        $contentWords = max(1, str_word_count(strip_tags($content)));

        $score = 0;
        $matchedTerms = [];
        if ($content !== '' && str_contains($content, $normalizedQuery)) {
            $score += 18;
        }

        if ($heading !== '' && str_contains($heading, $normalizedQuery)) {
            $score += 12;
        }

        if ($sourceName !== '' && str_contains($sourceName, $normalizedQuery)) {
            $score += 8;
        }

        foreach ($terms as $term) {
            $contentHits = substr_count($content, $term);
            $headingHits = substr_count($heading, $term);
            $sourceHits = substr_count($sourceName, $term);

            if ($contentHits > 0 || $headingHits > 0 || $sourceHits > 0) {
                $matchedTerms[] = $term;
            }

            $score += ($contentHits * 2);
            $score += ($headingHits * 4);
            $score += ($sourceHits * 3);
        }

        $coverageRatio = count($terms) > 0 ? count($matchedTerms) / count($terms) : 0;
        $score += (int) round($coverageRatio * 14);

        if ($content !== '') {
            $score += $this->earlyOccurrenceBoost($content, $terms);
        }

        $pageSpan = $this->pageSpan($chunk->page_start, $chunk->page_end);
        if ($pageSpan <= 1) {
            $score += 3;
        } elseif ($pageSpan <= 3) {
            $score += 2;
        } elseif ($pageSpan <= 6) {
            $score += 1;
        }

        $score -= max(0, (int) floor(($contentWords - 220) / 80));

        return [
            'score' => max(0, $score),
            'chunk_id' => $chunk->id,
            'chunk_index' => $chunk->chunk_index,
            'heading' => $chunk->heading,
            'snippet' => $this->buildSnippet($chunk->content, $terms),
            'page_start' => $chunk->page_start,
            'page_end' => $chunk->page_end,
            'document' => [
                'id' => $chunk->document?->id,
                'source_name' => $chunk->document?->source_name,
                'source_type' => $chunk->document?->source_type,
                'status' => $chunk->document?->status,
                'product_id' => $chunk->document?->product_id,
                'product_note_id' => $chunk->document?->product_note_id,
            ],
            'signals' => [
                'matched_terms' => $matchedTerms,
                'coverage_ratio' => $coverageRatio,
                'page_span' => $pageSpan,
            ],
        ];
    }

    private function earlyOccurrenceBoost(string $content, array $terms): int
    {
        $boost = 0;
        $sample = mb_substr($content, 0, 220);

        foreach ($terms as $term) {
            if (str_contains($sample, $term)) {
                $boost += 1;
            }
        }

        return min(6, $boost);
    }

    private function pageSpan(?int $pageStart, ?int $pageEnd): int
    {
        if (! $pageStart || ! $pageEnd) {
            return 0;
        }

        return max(1, $pageEnd - $pageStart + 1);
    }
}
