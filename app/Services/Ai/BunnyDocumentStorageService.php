<?php

namespace App\Services\Ai;

use App\Services\Storage\StoragePathResolverService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class BunnyDocumentStorageService
{
    public function __construct(
        private readonly AiDocumentHashService $hashService,
        private readonly StoragePathResolverService $pathResolver
    ) {
    }

    public function uploadInstructorFile(UploadedFile $file, int $instructorId, string $folder = 'ai-documents'): array
    {
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $directory = $this->buildInstructorFolderPath($instructorId, $folder);

        return $this->storeFile($file, $directory, $originalName, $extension);
    }

    public function uploadAsset(UploadedFile $file, array $context): array
    {
        $actorRole = $this->pathResolver->normalizeActorRole((string) ($context['actor_role'] ?? 'instructor'));
        $actorId = (int) ($context['actor_id'] ?? 0);

        if ($actorId <= 0) {
            throw new RuntimeException(__('Actor ID is required for Bunny uploads.'));
        }

        $productType = $this->pathResolver->normalizeProductType((string) ($context['product_type'] ?? ''));
        $productId = isset($context['product_id']) ? (int) $context['product_id'] : null;
        $assetKind = $this->pathResolver->resolveAssetKind((string) ($context['asset_kind'] ?? 'asset'));
        $directory = $this->pathResolver->buildFolderPrefix($actorRole, $actorId, $productType, $productId, $assetKind);

        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');

        return $this->storeFile($file, $directory, $originalName, $extension, $context);
    }

    public function uploadStreamAsset(UploadedFile $file, array $context): array
    {
        $context['asset_kind'] = $context['asset_kind'] ?? 'video';

        return $this->uploadAsset($file, $context);
    }

    public function deletePath(string $path): bool
    {
        $path = $this->normalizeRelativePath($path);

        if ($path === '') {
            return false;
        }

        if ($this->shouldUseLocalFileStorage()) {
            $absolutePath = public_path($path);

            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }

            return true;
        }

        $response = Http::withHeaders([
            'AccessKey' => $this->storageAccessKey(),
        ])
            ->timeout(60)
            ->delete($this->storageUploadUrl($path));

        return $response->successful() || $response->status() === 404;
    }

    public function downloadToTemporaryPath(string $path): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'ai-doc-');

        if ($tempPath === false) {
            throw new RuntimeException(__('Unable to create a temporary file.'));
        }

        $relativePath = $this->normalizeRelativePath($path);
        if ($this->shouldUseLocalFileStorage()) {
            $absolutePath = public_path($relativePath);

            if (! is_file($absolutePath)) {
                throw new RuntimeException(__('Unable to download the file from local storage.'));
            }

            if (! copy($absolutePath, $tempPath)) {
                throw new RuntimeException(__('Unable to download the file from local storage.'));
            }

            return $tempPath;
        }

        $extension = strtolower(pathinfo(parse_url($relativePath, PHP_URL_PATH) ?: $relativePath, PATHINFO_EXTENSION));
        $zone = trim((string) config('bunny.storage_zone_name'));

        if ($extension !== '') {
            $extensionPath = $tempPath . '.' . $extension;

            if (@rename($tempPath, $extensionPath)) {
                $tempPath = $extensionPath;
            }
        }

        $downloadTargets = [];
        $publicUrl = trim($this->publicUrl($relativePath));

        if ($this->shouldUsePublicDownloadUrl($publicUrl, $relativePath)) {
            $downloadTargets[] = [
                'url' => $publicUrl,
                'headers' => [],
            ];
        }

        try {
            $downloadTargets[] = [
                'url' => $this->storageDownloadUrl($relativePath),
                'headers' => [
                    'AccessKey' => $this->storageAccessKey(),
                ],
            ];

            $legacyRelativePath = $zone !== '' ? trim($zone . '/' . ltrim($relativePath, '/'), '/') : '';
            if ($legacyRelativePath !== '' && $legacyRelativePath !== $relativePath) {
                $downloadTargets[] = [
                    'url' => $this->storageDownloadUrl($legacyRelativePath),
                    'headers' => [
                        'AccessKey' => $this->storageAccessKey(),
                    ],
                ];
            }
        } catch (Throwable) {
            // Ignore storage API fallback when no key is configured.
        }

        $lastError = null;

        foreach ($downloadTargets as $target) {
            $response = Http::withHeaders($target['headers'])
                ->connectTimeout(30)
                ->timeout(300)
                ->retry(3, 2000, function ($exception, $request) {
                    return true;
                })
                ->withOptions([
                    'sink' => $tempPath,
                    'read_timeout' => 300,
                ])
                ->get($target['url']);

            if ($response->successful()) {
                return $tempPath;
            }

            $lastError = $response->body();

            if (is_file($tempPath) && filesize($tempPath) === 0) {
                @unlink($tempPath);
                $tempPath = tempnam(sys_get_temp_dir(), 'ai-doc-') ?: $tempPath;

                if ($extension !== '') {
                    $extensionPath = $tempPath . '.' . $extension;

                    if (@rename($tempPath, $extensionPath)) {
                        $tempPath = $extensionPath;
                    }
                }
            }
        }

        @unlink($tempPath);
        throw new RuntimeException(__('Unable to download the file from Bunny.').' '.($lastError ?: __('Unknown error.')));

        return $tempPath;
    }

    public function buildInstructorFolderPath(int $instructorId, string $folder = 'ai-documents', ?Carbon $date = null): string
    {
        $date ??= now();

        return trim(sprintf('instructors/%d/%s/%s/%s', $instructorId, trim($folder, '/'), $date->format('Y'), $date->format('m')), '/');
    }

    public function publicUrl(string $path): string
    {
        $baseUrl = trim((string) config('bunny.storage_cdn_url'));

        if ($baseUrl === '') {
            return ltrim($path, '/');
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    private function storeFile(UploadedFile $file, string $directory, string $originalName, string $extension, array $context = []): array
    {
        $baseName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'document';
        $fileName = $baseName . '_' . Str::random(10) . '.' . $extension;
        $relativePath = trim(rtrim($directory, '/') . '/' . $fileName, '/');
        $absolutePath = null;
        if ($this->shouldUseLocalFileStorage()) {
            $absoluteDirectory = public_path($directory);
            File::ensureDirectoryExists($absoluteDirectory);
            $file->move($absoluteDirectory, $fileName);
            $absolutePath = $absoluteDirectory . DIRECTORY_SEPARATOR . $fileName;
        } else {
            $contents = file_get_contents($file->getRealPath());

            if ($contents === false) {
                throw new RuntimeException(__('Unable to read the uploaded file.'));
            }

            $response = Http::withHeaders([
                'AccessKey' => $this->storageAccessKey(),
            ])
                ->timeout(120)
                ->withBody($contents, $file->getMimeType() ?: 'application/octet-stream')
                ->put($this->storageUploadUrl($relativePath));

            if (! $response->successful()) {
                throw new RuntimeException(__('Unable to store the file on Bunny.').' '.$response->body());
            }
        }

        return [
            'path' => $relativePath,
            'folder_path' => rtrim($directory, '/'),
            'url' => $this->publicUrl($relativePath),
            'original_name' => $originalName,
            'mime_type' => $file->getMimeType(),
            'extension' => $extension,
            'size' => (int) $file->getSize(),
            'hash' => $this->hashService->hashFile($absolutePath ?? $file->getRealPath()),
            'context' => $context,
        ];
    }

    private function normalizeRelativePath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return '';
        }

        $cdnBase = trim((string) config('bunny.storage_cdn_url'));
        if ($cdnBase !== '' && str_starts_with($path, $cdnBase)) {
            $path = substr($path, strlen($cdnBase));
        }

        return ltrim($path, '/');
    }

    private function storageUploadUrl(string $path): string
    {
        $zone = trim((string) config('bunny.storage_zone_name'));
        $endpoint = $this->normalizedStorageApiEndpoint($zone);

        if ($zone === '') {
            throw new RuntimeException(__('Bunny storage zone name is not configured.'));
        }

        return $endpoint . '/' . rawurlencode($zone) . '/' . ltrim($path, '/');
    }

    private function storageDownloadUrl(string $path): string
    {
        return $this->storageUploadUrl($path);
    }

    private function shouldUsePublicDownloadUrl(string $publicUrl, string $relativePath): bool
    {
        if ($publicUrl === '' || $publicUrl === $relativePath) {
            return false;
        }

        $publicHost = parse_url($publicUrl, PHP_URL_HOST);
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! is_string($publicHost) || $publicHost === '') {
            return false;
        }

        if (is_string($appHost) && $appHost !== '' && strcasecmp($publicHost, $appHost) === 0) {
            return false;
        }

        return true;
    }

    private function storageAccessKey(): string
    {
        $accessKey = trim((string) config('bunny.storage_access_key'));

        if ($accessKey === '') {
            throw new RuntimeException(__('Bunny storage access key is not configured.'));
        }

        return $accessKey;
    }

    private function shouldUseLocalFileStorage(): bool
    {
        return app()->environment(['local', 'testing'])
            && trim((string) config('bunny.storage_status', 'inactive')) !== 'active';
    }

    private function normalizedStorageApiEndpoint(string $zone): string
    {
        $endpoint = trim((string) config('bunny.storage_api_endpoint', 'https://storage.bunnycdn.com'));

        if ($endpoint === '') {
            return 'https://storage.bunnycdn.com';
        }

        $endpoint = rtrim($endpoint, '/');
        $parsedPath = parse_url($endpoint, PHP_URL_PATH);
        $parsedPath = is_string($parsedPath) ? trim($parsedPath, '/') : '';

        if ($zone !== '' && $parsedPath !== '') {
            $segments = explode('/', $parsedPath);
            $lastSegment = end($segments);

            if ($lastSegment === $zone) {
                $endpoint = preg_replace('#/' . preg_quote($zone, '#') . '$#', '', $endpoint) ?: $endpoint;
            }
        }

        return rtrim($endpoint, '/');
    }
}
