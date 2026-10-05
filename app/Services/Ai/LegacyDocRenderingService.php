<?php

namespace App\Services\Ai;

use RuntimeException;

class LegacyDocRenderingService
{
    private const PAGE_WIDTH = 1654;
    private const PAGE_HEIGHT = 2339;
    private const MARGIN_X = 140;
    private const MARGIN_Y = 140;
    private const FONT_SIZE = 28;
    private const BITMAP_FONT = 5;

    public function renderFromExtractionResult(array $extractionResult, int $maxPages = 10): array
    {
        $maxPages = max(1, $maxPages);
        $tempDir = $this->createTempDirectory();
        $items = [];

        $embeddedImages = array_values(array_filter((array) data_get($extractionResult, 'embedded_images.items', [])));
        $renderableText = trim((string) data_get($extractionResult, 'renderable_text', data_get($extractionResult, 'text', '')));

        if (! empty($embeddedImages)) {
            $items = $this->renderImagePages($embeddedImages, $tempDir, $maxPages);
        } elseif ($renderableText !== '') {
            $items = $this->renderTextPages($renderableText, $tempDir, $maxPages);
        }

        return [
            'temp_dir' => $tempDir,
            'items' => $items,
            'method' => ! empty($embeddedImages) ? 'legacy-doc-render-images' : 'legacy-doc-render-text',
        ];
    }

    private function renderImagePages(array $images, string $tempDir, int $maxPages): array
    {
        $items = [];

        foreach (array_slice($images, 0, $maxPages) as $index => $image) {
            $sourcePath = (string) ($image['path'] ?? '');
            if ($sourcePath === '' || ! is_file($sourcePath)) {
                continue;
            }

            $binary = @file_get_contents($sourcePath);
            if (! is_string($binary) || $binary === '') {
                continue;
            }

            $sourceImage = @imagecreatefromstring($binary);
            if (! is_resource($sourceImage) && ! ($sourceImage instanceof \GdImage)) {
                continue;
            }

            $canvas = $this->createCanvas();
            $canvasWidth = imagesx($canvas);
            $canvasHeight = imagesy($canvas);
            $sourceWidth = imagesx($sourceImage);
            $sourceHeight = imagesy($sourceImage);
            $maxWidth = $canvasWidth - (self::MARGIN_X * 2);
            $maxHeight = $canvasHeight - (self::MARGIN_Y * 2);
            $scale = min($maxWidth / max(1, $sourceWidth), $maxHeight / max(1, $sourceHeight), 1);
            $targetWidth = max(1, (int) round($sourceWidth * $scale));
            $targetHeight = max(1, (int) round($sourceHeight * $scale));
            $targetX = max(0, (int) round(($canvasWidth - $targetWidth) / 2));
            $targetY = max(0, (int) round(($canvasHeight - $targetHeight) / 2));

            imagecopyresampled(
                $canvas,
                $sourceImage,
                $targetX,
                $targetY,
                0,
                0,
                $targetWidth,
                $targetHeight,
                $sourceWidth,
                $sourceHeight
            );

            $outputPath = $this->writeCanvas($canvas, $tempDir, $index + 1);
            $items[] = [
                'path' => $outputPath,
                'source' => $sourcePath,
                'kind' => 'image',
                'page_number' => $index + 1,
                'width' => $canvasWidth,
                'height' => $canvasHeight,
            ];

            imagedestroy($canvas);
            imagedestroy($sourceImage);
        }

        return $items;
    }

    private function renderTextPages(string $text, string $tempDir, int $maxPages): array
    {
        $fontPath = $this->resolveFontPath();
        $paragraphs = preg_split("/\R{2,}/u", $this->normalizeText($text)) ?: [];
        $lines = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim((string) $paragraph);
            if ($paragraph === '') {
                continue;
            }

            foreach ($this->wrapParagraph($paragraph, $fontPath) as $line) {
                $lines[] = $line;
            }

            $lines[] = '';
        }

        $filteredLines = [];
        foreach ($lines as $index => $line) {
            if ($line === '' && ($index === 0 || ($lines[$index - 1] ?? '') === '')) {
                continue;
            }

            $filteredLines[] = $line;
        }

        $lines = $filteredLines;

        if (empty($lines)) {
            return [];
        }

        $lineHeight = $this->lineHeight($fontPath);
        $maxLinesPerPage = max(20, (int) floor((self::PAGE_HEIGHT - (self::MARGIN_Y * 2)) / $lineHeight));
        $pages = array_chunk($lines, $maxLinesPerPage);
        $items = [];

        foreach (array_slice($pages, 0, $maxPages) as $index => $pageLines) {
            $canvas = $this->createCanvas();
            $this->drawTextLines($canvas, $pageLines, $fontPath, $lineHeight);
            $outputPath = $this->writeCanvas($canvas, $tempDir, $index + 1);

            $items[] = [
                'path' => $outputPath,
                'source' => 'rendered-text',
                'kind' => 'text',
                'page_number' => $index + 1,
                'width' => self::PAGE_WIDTH,
                'height' => self::PAGE_HEIGHT,
            ];

            imagedestroy($canvas);
        }

        return $items;
    }

    private function drawTextLines($canvas, array $lines, ?string $fontPath, int $lineHeight): void
    {
        $ink = imagecolorallocate($canvas, 28, 32, 44);
        $y = self::MARGIN_Y;

        foreach ($lines as $line) {
            $line = (string) $line;

            if ($line === '') {
                $y += (int) round($lineHeight * 0.6);
                continue;
            }

            if ($fontPath) {
                imagettftext($canvas, self::FONT_SIZE, 0, self::MARGIN_X, $y, $ink, $fontPath, $line);
                $y += $lineHeight;
            } else {
                imagestring($canvas, self::BITMAP_FONT, self::MARGIN_X, $y, $line, $ink);
                $y += imagefontheight(self::BITMAP_FONT) + 8;
            }

            if ($y > (self::PAGE_HEIGHT - self::MARGIN_Y)) {
                break;
            }
        }
    }

    private function wrapParagraph(string $paragraph, ?string $fontPath): array
    {
        $maxWidth = self::PAGE_WIDTH - (self::MARGIN_X * 2);
        $words = preg_split('/\s+/u', trim($paragraph)) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $word = trim((string) $word);
            if ($word === '') {
                continue;
            }

            $candidate = $current === '' ? $word : $current . ' ' . $word;

            if ($this->measureTextWidth($candidate, $fontPath) <= $maxWidth) {
                $current = $candidate;
                continue;
            }

            if ($current !== '') {
                $lines[] = $current;
            }

            if ($this->measureTextWidth($word, $fontPath) <= $maxWidth) {
                $current = $word;
                continue;
            }

            foreach ($this->splitLongWord($word, $fontPath, $maxWidth) as $fragment) {
                $lines[] = $fragment;
            }

            $current = '';
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    private function splitLongWord(string $word, ?string $fontPath, int $maxWidth): array
    {
        $parts = [];
        $current = '';
        $characters = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($characters as $character) {
            $candidate = $current . $character;
            if ($current !== '' && $this->measureTextWidth($candidate, $fontPath) > $maxWidth) {
                $parts[] = $current;
                $current = $character;
                continue;
            }

            $current = $candidate;
        }

        if ($current !== '') {
            $parts[] = $current;
        }

        return $parts;
    }

    private function measureTextWidth(string $text, ?string $fontPath): int
    {
        if ($fontPath && function_exists('imagettfbbox')) {
            $box = imagettfbbox(self::FONT_SIZE, 0, $fontPath, $text);
            if ($box !== false) {
                return (int) abs($box[4] - $box[0]);
            }
        }

        return max(1, (int) ceil(mb_strlen($text) * imagefontwidth(self::BITMAP_FONT)));
    }

    private function lineHeight(?string $fontPath): int
    {
        if ($fontPath && function_exists('imagettfbbox')) {
            $box = imagettfbbox(self::FONT_SIZE, 0, $fontPath, 'Ag');
            if ($box !== false) {
                return max(36, (int) abs($box[5] - $box[1]) + 12);
            }
        }

        return imagefontheight(self::BITMAP_FONT) + 8;
    }

    private function createCanvas()
    {
        $canvas = imagecreatetruecolor(self::PAGE_WIDTH, self::PAGE_HEIGHT);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        return $canvas;
    }

    private function writeCanvas($canvas, string $tempDir, int $pageNumber): string
    {
        $path = rtrim($tempDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . sprintf('page-%03d.png', $pageNumber);
        imagepng($canvas, $path, 6);

        return $path;
    }

    private function createTempDirectory(): string
    {
        $tempDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'ai-doc-render-' . uniqid();

        if (! @mkdir($tempDir, 0777, true) && ! is_dir($tempDir)) {
            throw new RuntimeException(__('Unable to create a temporary rendering directory.'));
        }

        return $tempDir;
    }

    private function resolveFontPath(): ?string
    {
        $candidates = array_filter([
            'C:\\Windows\\Fonts\\arial.ttf',
            'C:\\Windows\\Fonts\\segoeui.ttf',
            'C:\\Windows\\Fonts\\tahoma.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation2/LiberationSans-Regular.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            '/usr/share/fonts/dejavu/DejaVuSans.ttf',
        ]);

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace("\0", '', $text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
