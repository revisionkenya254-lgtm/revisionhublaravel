<?php

namespace App\Services\Storage;

use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class BunnyStreamService
{
    public function uploadVideo(UploadedFile $file, string $title, array $context = []): array
    {
        $libraryId = $this->libraryId();
        $apiKey = $this->apiKey();
        $videoId = $this->createVideoSlot($libraryId, $apiKey, $title, $context);
        $mimeType = $file->getMimeType() ?: 'application/octet-stream';
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            throw new RuntimeException(__('Unable to read the uploaded video file.'));
        }

        $stream = Utils::streamFor($handle);

        try {
            $response = Http::withHeaders([
                'AccessKey' => $apiKey,
                'Content-Type' => $mimeType,
            ])
                ->timeout(180)
                ->withBody($stream, $mimeType)
                ->put($this->baseUrl() . '/library/' . rawurlencode($libraryId) . '/videos/' . rawurlencode($videoId));
        } finally {
            $stream->close();
        }

        if (! $response->successful()) {
            throw new RuntimeException(__('Unable to upload the video to Bunny Stream.') . ' ' . $response->body());
        }

        return [
            'video_id' => $videoId,
            'library_id' => $libraryId,
            'storage' => 'bunny_stream',
            'title' => $title,
            'embed_url' => $this->buildEmbedUrl($videoId, $libraryId),
            'play_url' => $this->buildPlayUrl($videoId, $libraryId),
            'context' => $context,
        ];
    }

    public function deleteVideo(string $videoId): bool
    {
        $videoId = trim($videoId);

        if ($videoId === '') {
            return false;
        }

        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey(),
        ])
            ->timeout(120)
            ->delete($this->baseUrl() . '/library/' . rawurlencode($this->libraryId()) . '/videos/' . rawurlencode($videoId));

        return $response->successful() || $response->status() === 404;
    }

    public function buildEmbedUrl(string $videoId, ?string $libraryId = null): string
    {
        $libraryId ??= $this->libraryId();

        return $this->playerBaseUrl() . '/embed/' . rawurlencode($libraryId) . '/' . rawurlencode(trim($videoId));
    }

    public function buildPlayUrl(string $videoId, ?string $libraryId = null): string
    {
        $libraryId ??= $this->libraryId();

        return $this->playerBaseUrl() . '/play/' . rawurlencode($libraryId) . '/' . rawurlencode(trim($videoId));
    }

    private function createVideoSlot(string $libraryId, string $apiKey, string $title, array $context = []): string
    {
        $payload = array_filter([
            'title' => $title,
            'collectionId' => $context['collection_id'] ?? null,
            'thumbnailTime' => $context['thumbnail_time'] ?? null,
        ], static fn ($value) => $value !== null && $value !== '');

        $response = Http::withHeaders([
            'AccessKey' => $apiKey,
            'Content-Type' => 'application/json',
        ])
            ->timeout(60)
            ->post($this->baseUrl() . '/library/' . rawurlencode($libraryId) . '/videos', $payload);

        if (! $response->successful()) {
            throw new RuntimeException(__('Unable to create a Bunny Stream video slot.') . ' ' . $response->body());
        }

        $videoId = data_get($response->json(), 'guid');

        if (blank($videoId)) {
            throw new RuntimeException(__('Bunny Stream did not return a video ID.'));
        }

        return (string) $videoId;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('bunny.stream_api_base_url', 'https://video.bunnycdn.com'), '/');
    }

    private function playerBaseUrl(): string
    {
        $hostname = trim((string) config('bunny.stream_cdn_hostname', 'player.mediadelivery.net'));

        if ($hostname === '') {
            $hostname = 'player.mediadelivery.net';
        }

        if (! Str::startsWith($hostname, ['http://', 'https://'])) {
            $hostname = 'https://' . ltrim($hostname, '/');
        }

        return rtrim($hostname, '/');
    }

    private function libraryId(): string
    {
        $libraryId = trim((string) config('bunny.stream_library_id'));

        if ($libraryId === '') {
            throw new RuntimeException(__('Bunny Stream library ID is not configured.'));
        }

        return $libraryId;
    }

    private function apiKey(): string
    {
        $apiKey = trim((string) config('bunny.stream_api_key'));

        if ($apiKey === '') {
            throw new RuntimeException(__('Bunny Stream API key is not configured.'));
        }

        return $apiKey;
    }
}
