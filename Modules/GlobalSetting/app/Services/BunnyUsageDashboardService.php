<?php

namespace Modules\GlobalSetting\app\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BunnyUsageDashboardService
{
    private const BASE_URL = 'https://api.bunny.net';
    private const CACHE_KEY = 'bunny.usage.dashboard.v2';

    public function dashboard(): array
    {
        Cache::forget('bunny.usage.dashboard');

        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function () {
            $coreApiKey = trim((string) config('bunny.core_api_key'));
            $storageAccessKey = trim((string) config('bunny.storage_access_key'));
            $streamApiKey = trim((string) config('bunny.stream_api_key'));

            $cdnZones = $this->fetchList('/pullzone', $coreApiKey);
            $cdnBillingSummary = $this->fetchList('/billing/summary', $coreApiKey);
            $storageZones = $this->fetchList('/storagezone', $coreApiKey);
            $streamLibraries = $this->fetchList('/videolibrary', $coreApiKey);
            $pendingPaymentRequests = $this->fetchList('/billing/pending-payment-requests', $coreApiKey);
            $billingDetails = $this->fetchJson('/billing/details', $coreApiKey);
            $billingAmountDue = $this->extractAmount($billingDetails);

            $cdnRows = $this->buildCdnRows($cdnZones, $cdnBillingSummary);
            $storageRows = $this->buildStorageRows($storageZones);
            $streamRows = $this->buildStreamRows($streamLibraries);
            $pendingRows = $this->buildPendingRows($pendingPaymentRequests);
            $cdnChart = $this->buildCdnChart($cdnRows);

            return [
                'generated_at' => Carbon::now(),
                'credentials_ready' => filled($coreApiKey),
                'summary' => [
                    'cdn_count' => count($cdnRows),
                    'storage_count' => count($storageRows),
                    'stream_count' => count($streamRows),
                    'pending_requests_count' => count($pendingRows),
                    'estimated_monthly_due' => $this->sumUsageAmount($cdnRows),
                    'billing_balance' => $billingAmountDue,
                    'billing_balance_human' => $this->formatMoney($billingAmountDue ?? 0),
                    'billing_status' => data_get($billingDetails, 'Status') ?? data_get($billingDetails, 'status'),
                ],
                'cdn' => $cdnRows,
                'cdn_chart' => $cdnChart,
                'storage' => $storageRows,
                'stream' => $streamRows,
                'pending_requests' => $pendingRows,
                'credentials' => [
                    'core_api_key' => filled($coreApiKey),
                    'storage_access_key' => filled($storageAccessKey),
                    'stream_api_key' => filled($streamApiKey),
                ],
                'bunny_errors' => [
                    'cdn' => $this->isUnavailable($cdnZones),
                    'storage' => $this->isUnavailable($storageZones),
                    'stream' => $this->isUnavailable($streamLibraries),
                    'billing' => $this->isUnavailable($cdnBillingSummary),
                ],
            ];
        });
    }

    public function testConnection(string $type): array
    {
        return match ($type) {
            'core' => $this->testCoreConnection(),
            'cdn' => $this->testCdnConnection(),
            'storage' => $this->testStorageConnection(),
            'storage_public' => $this->testStoragePublicUrlConnection(),
            'stream' => $this->testStreamConnection(),
            default => [
                'status' => false,
                'message' => __('Unknown Bunny test type.'),
                'tested_at' => Carbon::now()->toDateTimeString(),
            ],
        };
    }

    private function fetchJson(string $path, string $accessKey): array|null
    {
        if ($accessKey === '') {
            return null;
        }

        $response = Http::baseUrl(self::BASE_URL)
            ->withHeaders(['AccessKey' => $accessKey])
            ->acceptJson()
            ->timeout(20)
            ->get($path);

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }

    private function fetchList(string $path, string $accessKey): array
    {
        $response = $this->fetchJson($path, $accessKey);

        if (! is_array($response)) {
            return [];
        }

        if (array_key_exists('Items', $response) && is_array($response['Items'])) {
            return $response['Items'];
        }

        return array_is_list($response) ? $response : [$response];
    }

    private function buildCdnRows(array $cdnZones, array $billingSummary): array
    {
        $billingByPullZoneId = collect($billingSummary)->keyBy('PullZoneId');

        return collect($cdnZones)->map(function ($zone) use ($billingByPullZoneId) {
            $zoneId = data_get($zone, 'Id');
            $billing = $billingByPullZoneId->get($zoneId, []);
            $bandwidthUsed = (int) (data_get($billing, 'MonthlyBandwidthUsed') ?? data_get($zone, 'MonthlyBandwidthUsed') ?? 0);
            $monthlyUsage = (float) (data_get($billing, 'MonthlyUsage') ?? 0);

            return [
                'id' => $zoneId,
                'name' => data_get($zone, 'Name', __('Unknown Pull Zone')),
                'monthly_bandwidth_used' => $bandwidthUsed,
                'monthly_bandwidth_used_human' => $this->formatBytes($bandwidthUsed),
                'monthly_usage' => $monthlyUsage,
                'monthly_usage_human' => $this->formatMoney($monthlyUsage),
                'monthly_bandwidth_limit' => (int) (data_get($zone, 'MonthlyBandwidthLimit') ?? 0),
                'monthly_bandwidth_limit_human' => $this->formatBytes((int) (data_get($zone, 'MonthlyBandwidthLimit') ?? 0)),
                'hostnames' => collect(data_get($zone, 'Hostnames', []))
                    ->map(fn ($hostname) => is_array($hostname) ? (data_get($hostname, 'Value') ?? data_get($hostname, 'Hostname') ?? null) : $hostname)
                    ->filter()
                    ->values()
                    ->all(),
                'raw' => $zone,
            ];
        })->values()->all();
    }

    private function buildStorageRows(array $storageZones): array
    {
        return collect($storageZones)->map(function ($zone) {
            $storageUsed = (int) (data_get($zone, 'StorageUsed') ?? 0);

            return [
                'id' => data_get($zone, 'Id'),
                'name' => data_get($zone, 'Name', __('Unknown Storage Zone')),
                'storage_used' => $storageUsed,
                'storage_used_human' => $this->formatBytes($storageUsed),
                'files_stored' => (int) (data_get($zone, 'FilesStored') ?? 0),
                'region' => data_get($zone, 'Region'),
                'replication_regions' => collect(data_get($zone, 'ReplicationRegions', []))->filter()->values()->all(),
                'raw' => $zone,
            ];
        })->values()->all();
    }

    private function buildStreamRows(array $streamLibraries): array
    {
        return collect($streamLibraries)->map(function ($library) {
            $trafficUsage = (int) (data_get($library, 'TrafficUsage') ?? 0);
            $storageUsage = (int) (data_get($library, 'StorageUsage') ?? 0);

            return [
                'id' => data_get($library, 'Id'),
                'name' => data_get($library, 'Name', __('Unknown Library')),
                'video_count' => (int) (data_get($library, 'VideoCount') ?? 0),
                'traffic_usage' => $trafficUsage,
                'traffic_usage_human' => $this->formatBytes($trafficUsage),
                'storage_usage' => $storageUsage,
                'storage_usage_human' => $this->formatBytes($storageUsage),
                'date_modified' => data_get($library, 'DateModified'),
                'raw' => $library,
            ];
        })->values()->all();
    }

    private function buildPendingRows(array $pendingRequests): array
    {
        return collect($pendingRequests)->map(function ($request) {
            $amount = data_get($request, 'Amount')
                ?? data_get($request, 'amount')
                ?? data_get($request, 'TotalAmount')
                ?? data_get($request, 'totalAmount')
                ?? 0;

            return [
                'id' => data_get($request, 'Id') ?? data_get($request, 'id'),
                'title' => data_get($request, 'Title')
                    ?? data_get($request, 'title')
                    ?? data_get($request, 'Description')
                    ?? data_get($request, 'description')
                    ?? __('Payment Request'),
                'amount' => (float) $amount,
                'amount_human' => $this->formatMoney((float) $amount),
                'status' => data_get($request, 'Status') ?? data_get($request, 'status') ?? __('Pending'),
                'due_date' => data_get($request, 'DueDate') ?? data_get($request, 'dueDate') ?? data_get($request, 'CreatedAt') ?? data_get($request, 'createdAt'),
                'raw' => $request,
            ];
        })->values()->all();
    }

    private function buildCdnChart(array $cdnRows): array
    {
        $topRows = collect($cdnRows)
            ->sortByDesc('monthly_usage')
            ->take(8)
            ->values();

        return [
            'labels' => $topRows->map(fn ($row) => $row['name'] ?? __('Unknown'))->all(),
            'values' => $topRows->map(fn ($row) => (float) ($row['monthly_usage'] ?? 0))->all(),
        ];
    }

    private function sumUsageAmount(array $cdnRows): string
    {
        $sum = collect($cdnRows)->sum('monthly_usage');

        return $this->formatMoney((float) $sum);
    }

    private function isUnavailable(array|null $response): bool
    {
        return $response === null;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return number_format($value, $value >= 10 ? 1 : 2) . ' ' . $units[$power];
    }

    private function formatMoney(float $amount): string
    {
        return '$' . number_format($amount, 2);
    }

    private function extractAmount(array|null $response): float|null
    {
        if (! is_array($response)) {
            return null;
        }

        $candidates = [
            'AmountDue',
            'amountDue',
            'Balance',
            'balance',
            'TotalDue',
            'totalDue',
            'OutstandingBalance',
            'outstandingBalance',
            'Amount',
            'amount',
        ];

        foreach ($candidates as $candidate) {
            $value = data_get($response, $candidate);

            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }

    private function testCoreConnection(): array
    {
        $coreApiKey = trim((string) config('bunny.core_api_key'));

        if ($coreApiKey === '') {
            return $this->connectionFailure(__('Core API key is missing.'));
        }

        $response = $this->requestBunny(self::BASE_URL, '/pullzone/count', $coreApiKey);

        if (! $response->successful()) {
            return $this->connectionFailure(
                __('Core API test failed with status :status.', ['status' => $response->status()]),
                $this->responseSnippet($response->body())
            );
        }

        return $this->connectionSuccess(
            __('Core API connection is working.'),
            [
                'count' => $this->extractNumericResponse($response->json() ?? $response->body()),
            ]
        );
    }

    private function testCdnConnection(): array
    {
        $coreApiKey = trim((string) config('bunny.core_api_key'));
        $pullZoneName = trim((string) config('bunny.cdn_pull_zone_name'));
        $hostname = trim((string) config('bunny.cdn_hostname'));

        if ($coreApiKey === '' || $pullZoneName === '') {
            return $this->connectionFailure(__('CDN test needs the Bunny core API key and pull zone name.'));
        }

        $response = $this->requestBunny(self::BASE_URL, '/pullzone?perPage=1000', $coreApiKey);

        if (! $response->successful()) {
            return $this->connectionFailure(
                __('CDN pull zone test failed with status :status.', ['status' => $response->status()]),
                $this->responseSnippet($response->body())
            );
        }

        $zones = $this->normalizeListResponse($response->json());
        $matchedZone = collect($zones)->first(function ($zone) use ($pullZoneName) {
            return strcasecmp((string) data_get($zone, 'Name'), $pullZoneName) === 0;
        });

        if (! $matchedZone) {
            return $this->connectionFailure(
                __('Pull zone ":zone" was not found in Bunny.', ['zone' => $pullZoneName]),
                $this->responseSnippet($response->body())
            );
        }

        $details = [
            'pull_zone_id' => data_get($matchedZone, 'Id'),
            'pull_zone_name' => data_get($matchedZone, 'Name'),
            'hostname' => data_get($matchedZone, 'HostName') ?? data_get($matchedZone, 'Hostname'),
        ];

        if ($hostname !== '') {
            $deliveryCheck = $this->probePublicEndpoint($hostname);

            if (! $deliveryCheck['status']) {
                return $this->connectionFailure(
                    $deliveryCheck['message'] ?? __('CDN hostname probe failed.'),
                    $deliveryCheck['details'] ?? []
                );
            }

            $details = array_merge($details, $deliveryCheck['details'] ?? []);
        }

        return $this->connectionSuccess(
            __('CDN connection is working and the pull zone was found.'),
            $details
        );
    }

    private function testStorageConnection(): array
    {
        $storageZoneName = trim((string) config('bunny.storage_zone_name'));
        $storageAccessKey = trim((string) config('bunny.storage_access_key'));
        $storageEndpoint = trim((string) config('bunny.storage_api_endpoint'));

        if ($storageZoneName === '' || $storageAccessKey === '') {
            return $this->connectionFailure(__('Storage test needs the storage zone name and access key.'));
        }

        $baseUrl = $storageEndpoint !== '' ? rtrim($storageEndpoint, '/') : 'https://storage.bunnycdn.com';
        $response = $this->requestBunny(
            $baseUrl,
            '/' . rawurlencode($storageZoneName) . '/',
            $storageAccessKey
        );

        if (! $response->successful()) {
            if ($response->status() === 401) {
                return $this->connectionFailure(
                    __('Storage authentication failed. Please verify the storage zone password and regional API endpoint.'),
                    $this->responseSnippet($response->body())
                );
            }

            return $this->connectionFailure(
                __('Storage API test failed with status :status.', ['status' => $response->status()]),
                $this->responseSnippet($response->body())
            );
        }

        return $this->connectionSuccess(
            __('Storage API connection is working.'),
            [
                'storage_zone_name' => $storageZoneName,
                'items_returned' => is_array($response->json()) ? count($response->json()) : null,
            ]
        );
    }

    private function testStoragePublicUrlConnection(): array
    {
        $cdnUrl = trim((string) config('bunny.storage_cdn_url'));

        if ($cdnUrl === '') {
            return $this->connectionFailure(__('Storage CDN URL is missing.'));
        }

        $probe = $this->probePublicEndpoint($cdnUrl);

        if (! ($probe['status'] ?? false)) {
            return $probe;
        }

        return $this->connectionSuccess(
            __('Public file URL is reachable.'),
            $probe['details'] ?? []
        );
    }

    private function testStreamConnection(): array
    {
        $streamLibraryId = trim((string) config('bunny.stream_library_id'));
        $streamApiKey = trim((string) config('bunny.stream_api_key'));

        if ($streamLibraryId === '' || $streamApiKey === '') {
            return $this->connectionFailure(__('Stream test needs the library ID and API key.'));
        }

        $response = $this->requestBunny(
            'https://video.bunnycdn.com',
            '/library/' . rawurlencode($streamLibraryId) . '/videos?perPage=1',
            $streamApiKey
        );

        if (! $response->successful()) {
            return $this->connectionFailure(
                __('Stream API test failed with status :status.', ['status' => $response->status()]),
                $this->responseSnippet($response->body())
            );
        }

        $payload = $response->json();

        return $this->connectionSuccess(
            __('Stream API connection is working.'),
            [
                'library_id' => $streamLibraryId,
                'videos_returned' => data_get($payload, 'TotalItems')
                    ?? data_get($payload, 'totalItems')
                    ?? (is_array(data_get($payload, 'Items')) ? count(data_get($payload, 'Items')) : null),
            ]
        );
    }

    private function requestBunny(string $baseUrl, string $path, string $accessKey)
    {
        return Http::baseUrl($baseUrl)
            ->withHeaders(['AccessKey' => $accessKey])
            ->acceptJson()
            ->timeout(20)
            ->get($path);
    }

    private function probePublicEndpoint(string $hostname): array
    {
        $url = $this->normalizePublicUrl($hostname);

        try {
            $response = Http::timeout(15)->get($url);
        } catch (\Throwable $throwable) {
            return $this->connectionFailure(
                __('Unable to reach the CDN hostname :url.', ['url' => $url]),
                [
                    'url' => $url,
                    'error' => $throwable->getMessage(),
                ]
            );
        }

        $statusCode = $response->status();

        if ($statusCode >= 500) {
            return $this->connectionFailure(
                __('The CDN hostname responded with HTTP :status.', ['status' => $statusCode]),
                [
                    'url' => $url,
                    'http_status' => $statusCode,
                    'body' => $this->responseSnippet($response->body()),
                ]
            );
        }

        return $this->connectionSuccess(
            __('CDN hostname is reachable.'),
            [
                'url' => $url,
                'http_status' => $statusCode,
                'body' => $this->responseSnippet($response->body()),
            ]
        );
    }

    private function normalizePublicUrl(string $hostname): string
    {
        $hostname = trim($hostname);

        if ($hostname === '') {
            return '';
        }

        if (str_starts_with($hostname, 'http://') || str_starts_with($hostname, 'https://')) {
            return rtrim($hostname, '/');
        }

        return 'https://' . trim($hostname, '/');
    }

    private function normalizeListResponse(mixed $response): array
    {
        if (! is_array($response)) {
            return [];
        }

        if (array_key_exists('Items', $response) && is_array($response['Items'])) {
            return $response['Items'];
        }

        return array_is_list($response) ? $response : [$response];
    }

    private function extractNumericResponse(mixed $response): int|float|string|null
    {
        if (is_numeric($response)) {
            return $response + 0;
        }

        if (is_array($response)) {
            foreach (['Count', 'count', 'TotalItems', 'totalItems', 'Amount', 'amount'] as $key) {
                $value = data_get($response, $key);
                if (is_numeric($value)) {
                    return $value + 0;
                }
            }
        }

        return null;
    }

    private function responseSnippet(string $body, int $length = 180): string
    {
        $body = trim($body);

        return str($body)->limit($length)->toString();
    }

    private function connectionSuccess(string $message, array $details = []): array
    {
        return [
            'status' => true,
            'message' => $message,
            'details' => $details,
            'tested_at' => Carbon::now()->toDateTimeString(),
        ];
    }

    private function connectionFailure(string $message, string|null $details = null): array
    {
        return [
            'status' => false,
            'message' => $message,
            'details' => $details,
            'tested_at' => Carbon::now()->toDateTimeString(),
        ];
    }
}
