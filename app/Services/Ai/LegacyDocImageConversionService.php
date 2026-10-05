<?php

namespace App\Services\Ai;

use PhpOffice\PhpWord\Element\Image;
use RuntimeException;
use Throwable;

class LegacyDocImageConversionService
{
    public function extractFromDocument(object $document): array
    {
        if (! method_exists($document, 'getSections')) {
            return [
                'temp_dir' => null,
                'items' => [],
            ];
        }

        $tempDir = $this->createTempDirectory();
        $items = [];
        $seen = [];

        foreach ($document->getSections() ?? [] as $section) {
            $this->collectContainerImages($section, $items, $seen, $tempDir);

            if (method_exists($section, 'getHeaders')) {
                foreach ($section->getHeaders() ?? [] as $header) {
                    $this->collectContainerImages($header, $items, $seen, $tempDir);
                }
            }

            if (method_exists($section, 'getFooters')) {
                foreach ($section->getFooters() ?? [] as $footer) {
                    $this->collectContainerImages($footer, $items, $seen, $tempDir);
                }
            }
        }

        return [
            'temp_dir' => $tempDir,
            'items' => $items,
        ];
    }

    private function createTempDirectory(): string
    {
        $tempDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'ai-doc-images-' . uniqid();

        if (! @mkdir($tempDir, 0777, true) && ! is_dir($tempDir)) {
            throw new RuntimeException(__('Unable to create a temporary image conversion directory.'));
        }

        return $tempDir;
    }

    private function collectContainerImages(mixed $container, array &$items, array &$seen, string $tempDir): void
    {
        if (! is_object($container)) {
            return;
        }

        if (method_exists($container, 'getRows')) {
            foreach ($container->getRows() ?? [] as $row) {
                if (! method_exists($row, 'getCells')) {
                    continue;
                }

                foreach ($row->getCells() ?? [] as $cell) {
                    $this->collectContainerImages($cell, $items, $seen, $tempDir);
                }
            }
        }

        if (method_exists($container, 'getElements')) {
            foreach ($container->getElements() ?? [] as $element) {
                if ($element instanceof Image) {
                    $this->captureImage($element, $items, $seen, $tempDir);
                    continue;
                }

                $this->collectContainerImages($element, $items, $seen, $tempDir);
            }
        }
    }

    private function captureImage(Image $image, array &$items, array &$seen, string $tempDir): void
    {
        $source = trim((string) $image->getSource());
        $sourceKey = $source !== '' ? $source : spl_object_hash($image);
        $sourceHash = sha1($sourceKey . '|' . (string) $image->getName() . '|' . (string) $image->getImageExtension());

        if (isset($seen[$sourceHash])) {
            return;
        }

        $binary = '';
        $extension = strtolower((string) $image->getImageExtension());
        if ($extension === '') {
            $extension = $this->extensionFromMimeType((string) $image->getImageType());
        }
        if ($extension === '') {
            $extension = 'png';
        }

        try {
            if ($source !== '' && is_file($source)) {
                $binary = (string) @file_get_contents($source);
            } elseif (method_exists($image, 'getImageString')) {
                $binary = (string) $image->getImageString();
            }
        } catch (Throwable) {
            $binary = '';
        }

        if ($binary === '') {
            return;
        }

        $target = $tempDir . DIRECTORY_SEPARATOR . 'image-' . (count($items) + 1) . '.' . $extension;
        if (@file_put_contents($target, $binary) === false) {
            return;
        }

        $seen[$sourceHash] = true;
        $size = @getimagesize($target) ?: [1, 1];

        $items[] = [
            'path' => $target,
            'source' => $source !== '' ? $source : null,
            'name' => $image->getName(),
            'extension' => $extension,
            'mime_type' => $image->getImageType() ?: $this->mimeTypeFromExtension($extension),
            'width' => max(1, (int) ($size[0] ?? 1)),
            'height' => max(1, (int) ($size[1] ?? 1)),
        ];
    }

    private function extensionFromMimeType(string $mimeType): string
    {
        return match (strtolower($mimeType)) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/bmp', 'image/x-ms-bmp' => 'bmp',
            'image/webp' => 'webp',
            default => '',
        };
    }

    private function mimeTypeFromExtension(string $extension): string
    {
        return match (strtolower($extension)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }
}
