<?php

namespace App\Services\Ai;

use PhpOffice\PhpWord\IOFactory;
use RuntimeException;
use ZipArchive;
use Throwable;

class WordTextExtractionService
{
    public function __construct(
        private readonly LegacyDocTextExtractionService $legacyDocExtractionService,
        private readonly LegacyDocImageConversionService $legacyDocImageConversionService
    ) {
    }

    public function extractFromPath(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException(__('The Word file could not be found.'));
        }

        $document = null;
        $text = '';
        $renderableText = '';
        $method = $this->readerNameForPath($path);
        $isLegacyDoc = $method === 'MsDoc';

        try {
            $document = $this->loadDocument($path);
            $text = $this->normalizeText($this->extractDocumentText($document));
            $renderableText = $text;
        } catch (Throwable $throwable) {
            if ($isLegacyDoc) {
                $legacyResult = $this->legacyDocExtractionService->extractFromPath($path);
                $legacyResult['embedded_images'] = [
                    'temp_dir' => null,
                    'items' => [],
                ];
                $legacyResult['renderable_text'] = $legacyResult['renderable_text'] ?? '';

                return $legacyResult;
            }

            throw $throwable;
        }

        if ($text === '' && $method === 'Word2007') {
            $text = $this->normalizeText($this->extractDocxXmlText($path));
            if ($text !== '') {
                $method = 'Word2007-XML';
            }
        }

        $embeddedImages = $method === 'MsDoc'
            ? $this->extractEmbeddedImages($document)
            : [
                'temp_dir' => null,
                'items' => [],
            ];

        if ($text === '' && $method === 'MsDoc') {
            $legacyResult = $this->legacyDocExtractionService->extractFromPath($path);
            $legacyResult['embedded_images'] = $embeddedImages;
            $legacyResult['renderable_text'] = $legacyResult['renderable_text'] ?? $renderableText;

            if (trim((string) ($legacyResult['text'] ?? '')) !== '' || ! empty($legacyResult['requires_transcription'])) {
                return $legacyResult;
            }
        }

        $pageCount = $text !== '' ? 1 : 0;
        $pages = $text !== '' ? [[
            'page_number' => 1,
            'text' => $text,
            'page_width' => 1,
            'page_height' => 1,
            'lines' => [],
        ]] : [];

        return [
            'text' => $text,
            'excerpt' => mb_substr($text, 0, 1000),
            'page_count' => $pageCount,
            'character_count' => mb_strlen($text),
            'requires_transcription' => false,
            'method' => $method,
            'pages' => $pages,
            'embedded_images' => $embeddedImages,
            'renderable_text' => $renderableText,
        ];
    }

    private function loadDocument(string $path): object
    {
        $candidates = $this->readerCandidatesForPath($path);
        $lastThrowable = null;

        foreach ($candidates as $readerName) {
            try {
                return IOFactory::load($path, $readerName);
            } catch (Throwable $throwable) {
                $lastThrowable = $throwable;
            }
        }

        throw new RuntimeException(__('Unable to read the Word document.') . ($lastThrowable ? ' ' . $lastThrowable->getMessage() : ''));
    }

    private function readerCandidatesForPath(string $path): array
    {
        return match ($this->readerNameForPath($path)) {
            'MsDoc' => ['MsDoc', 'Word2007'],
            default => ['Word2007', 'MsDoc'],
        };
    }

    private function readerNameForPath(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'doc' => 'MsDoc',
            'docx' => 'Word2007',
            default => 'Word2007',
        };
    }

    private function extractDocumentText(object $document): string
    {
        if (! method_exists($document, 'getSections')) {
            return '';
        }

        $sections = [];
        foreach ($document->getSections() as $section) {
            $sectionText = trim(implode("\n", $this->extractElements($section->getElements() ?? [])));
            if ($sectionText !== '') {
                $sections[] = $sectionText;
            }
        }

        return trim(implode("\n\n", $sections));
    }

    private function extractEmbeddedImages(object $document): array
    {
        if ($this->legacyDocImageConversionService === null || ! method_exists($document, 'getSections')) {
            return [
                'temp_dir' => null,
                'items' => [],
            ];
        }

        try {
            $conversion = $this->legacyDocImageConversionService->extractFromDocument($document);

            return [
                'temp_dir' => $conversion['temp_dir'] ?? null,
                'items' => $conversion['items'] ?? [],
            ];
        } catch (Throwable) {
            return [
                'temp_dir' => null,
                'items' => [],
            ];
        }
    }

    private function extractDocxXmlText(string $path): string
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return '';
        }

        $parts = [];

        foreach ($this->docxTextFiles() as $fileName) {
            $xml = $zip->getFromName($fileName);
            if (! is_string($xml) || trim($xml) === '') {
                continue;
            }

            $parts[] = $this->extractTextFromXml($xml);
        }

        $zip->close();

        return trim(implode("\n\n", array_filter($parts)));
    }

    private function docxTextFiles(): array
    {
        return [
            'word/document.xml',
            'word/footnotes.xml',
            'word/endnotes.xml',
            'word/comments.xml',
        ];
    }

    private function extractTextFromXml(string $xml): string
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $dom = new \DOMDocument();
            if (! $dom->loadXML($xml, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET)) {
                return '';
            }

            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

            $paragraphs = [];
            foreach ($xpath->query('//w:p') as $paragraph) {
                $segments = [];
                foreach ($paragraph->childNodes as $child) {
                    $segments[] = $this->extractTextFromXmlNode($child);
                }

                $paragraphText = trim(preg_replace("/[ \t]+/", ' ', implode('', $segments)) ?? '');
                if ($paragraphText !== '') {
                    $paragraphs[] = $paragraphText;
                }
            }

            if (! empty($paragraphs)) {
                return trim(implode("\n", $paragraphs));
            }

            $nodes = $xpath->query('//w:t|//w:tab|//w:br|//w:cr');
            $segments = [];
            foreach ($nodes as $node) {
                $segments[] = $this->extractTextFromXmlNode($node);
            }

            return trim(preg_replace("/[ \t]+/", ' ', implode('', $segments)) ?? '');
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function extractTextFromXmlNode(\DOMNode $node): string
    {
        return match ($node->localName) {
            'tab' => "\t",
            'br', 'cr' => "\n",
            default => trim((string) $node->textContent),
        };
    }

    private function extractElements(array $elements): array
    {
        $blocks = [];

        foreach ($elements as $element) {
            $text = trim($this->extractElementText($element));

            if ($text !== '') {
                $blocks[] = $text;
            }
        }

        return $blocks;
    }

    private function extractElementText(mixed $element): string
    {
        if (is_string($element) || is_numeric($element)) {
            return trim((string) $element);
        }

        if (! is_object($element)) {
            return '';
        }

        if (method_exists($element, 'getRows')) {
            $rows = [];

            foreach ($element->getRows() ?? [] as $row) {
                $cells = [];

                if (method_exists($row, 'getCells')) {
                    foreach ($row->getCells() ?? [] as $cell) {
                        $cellText = trim($this->extractElementText($cell));
                        if ($cellText !== '') {
                            $cells[] = $cellText;
                        }
                    }
                }

                if (! empty($cells)) {
                    $rows[] = implode("\t", $cells);
                }
            }

            return implode("\n", $rows);
        }

        if (method_exists($element, 'getElements')) {
            return trim(implode("\n", $this->extractElements($element->getElements() ?? [])));
        }

        foreach (['getText', 'getContent', 'getValue'] as $method) {
            if (! method_exists($element, $method)) {
                continue;
            }

            try {
                $value = trim((string) $element->{$method}());
                if ($value !== '') {
                    return $value;
                }
            } catch (Throwable) {
                // Ignore extractors that cannot read the element payload.
            }
        }

        if (method_exists($element, 'getTextObject')) {
            try {
                $textObject = $element->getTextObject();
                if ($textObject) {
                    return trim($this->extractElementText($textObject));
                }
            } catch (Throwable) {
                // Ignore and continue with the fallback below.
            }
        }

        if ($element instanceof \PhpOffice\PhpWord\Element\TextBreak || $element instanceof \PhpOffice\PhpWord\Element\PageBreak) {
            return "\n";
        }

        return '';
    }

    private function normalizeText(string $text): string
    {
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
