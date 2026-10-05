<?php

namespace App\Services\Storage;

use Carbon\Carbon;
use Illuminate\Support\Str;

class StoragePathResolverService
{
    public function buildAssetPath(
        string $actorRole,
        int $actorId,
        string $productType,
        ?int $productId,
        string $assetKind,
        string $extension,
        ?Carbon $date = null
    ): string {
        $date ??= now();
        $actorRole = $this->normalizeActorRole($actorRole);
        $productType = $this->normalizeProductType($productType);
        $assetKind = $this->resolveAssetKind($assetKind);
        $extension = ltrim(strtolower($extension), '.');

        $segments = [
            $actorRole . 's',
            (string) $actorId,
            'products',
        ];

        if ($productType !== '') {
            $segments[] = $productType;
        }

        if ($productId !== null) {
            $segments[] = (string) $productId;
        }

        $segments[] = $assetKind;
        $segments[] = $date->format('Y');
        $segments[] = $date->format('m');

        $path = implode('/', array_filter($segments, static fn ($segment) => $segment !== ''));

        return $path . '/';
    }

    public function buildFolderPrefix(
        string $actorRole,
        int $actorId,
        string $productType,
        ?int $productId,
        string $assetKind,
        ?Carbon $date = null
    ): string {
        return rtrim($this->buildAssetPath(
            $actorRole,
            $actorId,
            $productType,
            $productId,
            $assetKind,
            '',
            $date
        ), '/') . '/';
    }

    public function normalizeActorRole(string $actorRole): string
    {
        $actorRole = strtolower(trim($actorRole));

        return in_array($actorRole, ['admin', 'instructor'], true) ? $actorRole : 'instructor';
    }

    public function normalizeProductType(string $productType): string
    {
        $productType = strtolower(trim($productType));
        $map = [
            'course' => 'course',
            'pastpaper' => 'past_paper',
            'past-paper' => 'past_paper',
            'past_paper' => 'past_paper',
            'prediction' => 'prediction',
            'note' => 'note',
            'notes' => 'notes',
            'quiz' => 'quiz',
        ];

        return $map[$productType] ?? Str::slug($productType, '_');
    }

    public function resolveAssetKind(string $assetKind): string
    {
        $assetKind = strtolower(trim($assetKind));

        return match ($assetKind) {
            'thumbnail', 'thumbnails' => 'thumbnail',
            'source', 'source_pdf', 'pdf', 'document' => 'source',
            'attachment', 'attachments', 'resource', 'resources' => 'attachments',
            'video', 'videos', 'stream', 'demo_video' => 'video',
            'import', 'imports' => 'imports',
            default => Str::slug($assetKind, '_') ?: 'asset',
        };
    }

    public function isStreamAsset(string $assetKind): bool
    {
        return in_array($this->resolveAssetKind($assetKind), ['video'], true);
    }
}
