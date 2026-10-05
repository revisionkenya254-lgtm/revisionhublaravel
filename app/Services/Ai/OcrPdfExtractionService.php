<?php

namespace App\Services\Ai;

use RuntimeException;

class OcrPdfExtractionService
{
    public function canRun(): bool
    {
        return $this->commandExists('tesseract') && $this->commandExists('pdftoppm');
    }

    public function extractFromPdf(string $pdfPath, ?int $pageLimit = null): array
    {
        if (! is_file($pdfPath)) {
            throw new RuntimeException(__('The PDF file could not be found.'));
        }

        if (! $this->canRun()) {
            throw new RuntimeException(__('OCR tools are not installed on this server.'));
        }

        $pageCount = $this->countPages($pdfPath);
        $limit = $pageLimit && $pageLimit > 0 ? min($pageLimit, $pageCount ?: $pageLimit) : ($pageCount ?: 10);
        $tempDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'ai-ocr-' . uniqid();

        if (! @mkdir($tempDir, 0777, true) && ! is_dir($tempDir)) {
            throw new RuntimeException(__('Unable to create a temporary OCR directory.'));
        }

        $prefix = $tempDir . DIRECTORY_SEPARATOR . 'page';
        $renderCommand = sprintf(
            '%s -png -r 220 -f %d -l %d %s %s',
            escapeshellarg($this->commandPath('pdftoppm')),
            1,
            max(1, $limit),
            escapeshellarg($pdfPath),
            escapeshellarg($prefix)
        );

        @exec($renderCommand, $renderOutput, $renderExitCode);
        if ((int) $renderExitCode !== 0) {
            $this->deleteDirectory($tempDir);
            throw new RuntimeException(__('Unable to render PDF pages for OCR.'));
        }

        $images = glob($tempDir . DIRECTORY_SEPARATOR . 'page-*.png') ?: [];
        sort($images);

        $pages = [];
        $pageIndex = 1;
        foreach ($images as $image) {
            $pagePayload = $this->extractPagePayload($image, $pageIndex);
            if ($pagePayload['text'] !== '') {
                $pages[] = $pagePayload;
            }

            $pageIndex++;
        }

        $this->deleteDirectory($tempDir);

        $text = trim(implode("\n\n", array_map(
            fn (array $page) => sprintf("[Page %d]\n%s", $page['page_number'], trim((string) $page['text'])),
            $pages
        )));

        return [
            'pages' => $pages,
            'text' => $text,
            'excerpt' => mb_substr($text, 0, 1000),
            'page_count' => $pageCount,
            'character_count' => mb_strlen($text),
            'requires_ocr' => false,
            'method' => 'tesseract',
        ];
    }

    private function extractPagePayload(string $imagePath, int $pageNumber): array
    {
        $imageSize = @getimagesize($imagePath) ?: [1, 1];
        $imageWidth = max(1, (int) ($imageSize[0] ?? 1));
        $imageHeight = max(1, (int) ($imageSize[1] ?? 1));

        $tsvCommand = sprintf(
            '%s %s stdout --psm 6 tsv',
            escapeshellarg($this->commandPath('tesseract')),
            escapeshellarg($imagePath)
        );

        $tsvOutput = trim((string) shell_exec($tsvCommand));
        if ($tsvOutput === '') {
            return [
                'page_number' => $pageNumber,
                'text' => '',
                'page_width' => $imageWidth,
                'page_height' => $imageHeight,
                'lines' => [],
            ];
        }

        $rows = preg_split("/\R/u", $tsvOutput) ?: [];
        $header = null;
        $lines = [];

        foreach ($rows as $row) {
            $columns = array_map('trim', explode("\t", $row));
            if ($header === null) {
                $header = $columns;
                continue;
            }

            $data = array_combine($header, array_pad($columns, count($header), '')) ?: [];
            if (($data['level'] ?? null) !== '5') {
                continue;
            }

            $text = trim((string) ($data['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $lineKey = implode('-', [
                $data['page_num'] ?? $pageNumber,
                $data['block_num'] ?? 0,
                $data['par_num'] ?? 0,
                $data['line_num'] ?? 0,
            ]);

            if (! isset($lines[$lineKey])) {
                $lines[$lineKey] = [
                    'text_parts' => [],
                    'boxes' => [],
                ];
            }

            $lines[$lineKey]['text_parts'][] = $text;
            $lines[$lineKey]['boxes'][] = [
                'x' => (float) ($data['left'] ?? 0),
                'y' => (float) ($data['top'] ?? 0),
                'width' => (float) ($data['width'] ?? 0),
                'height' => (float) ($data['height'] ?? 0),
            ];
        }

        $normalizedLines = [];
        foreach (array_values($lines) as $lineIndex => $line) {
            $bbox = $this->mergeBoxes($line['boxes']);
            $normalizedLines[] = [
                'text' => trim(implode(' ', $line['text_parts'])),
                'bbox' => $bbox,
                'line_index' => $lineIndex,
            ];
        }

        $pageText = trim(implode("\n", array_column($normalizedLines, 'text')));

        return [
            'page_number' => $pageNumber,
            'text' => $pageText,
            'page_width' => $imageWidth,
            'page_height' => $imageHeight,
            'lines' => $normalizedLines,
        ];
    }

    private function mergeBoxes(array $boxes): array
    {
        if (empty($boxes)) {
            return ['x' => 0, 'y' => 0, 'width' => 0, 'height' => 0];
        }

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

    private function commandExists(string $name): bool
    {
        $result = PHP_OS_FAMILY === 'Windows'
            ? shell_exec('where ' . $name . ' 2>NUL')
            : shell_exec('command -v ' . $name . ' 2>/dev/null');

        return trim((string) $result) !== '';
    }

    private function commandPath(string $name): string
    {
        $result = PHP_OS_FAMILY === 'Windows'
            ? shell_exec('where ' . $name . ' 2>NUL')
            : shell_exec('command -v ' . $name . ' 2>/dev/null');

        $path = trim((string) $result);

        if ($path === '') {
            throw new RuntimeException(__('Unable to locate the OCR binary: :name', ['name' => $name]));
        }

        return strtok($path, "\r\n") ?: $path;
    }

    private function countPages(string $path): int
    {
        $content = file_get_contents($path);
        if ($content === false) {
            return 0;
        }

        if (preg_match_all('/\/Type\s*\/Page\b/', $content, $matches)) {
            return max(1, count($matches[0]));
        }

        return 0;
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            if (is_dir($file)) {
                $this->deleteDirectory($file);
                continue;
            }

            @unlink($file);
        }

        @rmdir($directory);
    }
}
