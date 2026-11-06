<?php

namespace App\Services\Push;

use App\Models\PushNotificationCampaign;
use Google\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirebaseMessagingService
{
    public function isConfigured(): bool
    {
        return filled(config('services.firebase.project_id'))
            && filled(config('services.firebase.credentials'))
            && is_file(config('services.firebase.credentials'));
    }

    /**
     * @return array{status: 'sent'|'unregistered', message_id: ?string, error_code: ?string}
     */
    public function send(string $deviceToken, PushNotificationCampaign $campaign): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Firebase Cloud Messaging is not configured.');
        }

        $projectId = (string) config('services.firebase.project_id');
        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->timeout(15)
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => [
                        'title' => $campaign->title,
                        'body' => $campaign->body,
                    ],
                    'data' => array_filter([
                        'campaign_id' => (string) $campaign->id,
                        'target_url' => $campaign->target_url,
                    ], static fn (?string $value): bool => $value !== null),
                ],
            ]);

        $responseBody = $response->json();
        $errorStatus = data_get($responseBody, 'error.status');
        $errorCode = data_get($responseBody, 'error.details.0.errorCode') ?? $errorStatus;

        if ($errorCode === 'UNREGISTERED') {
            return ['status' => 'unregistered', 'message_id' => null, 'error_code' => 'UNREGISTERED'];
        }

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'FCM returned HTTP %d (%s).',
                $response->status(),
                is_string($errorCode) ? $errorCode : 'unknown_error'
            ));
        }

        return [
            'status' => 'sent',
            'message_id' => data_get($responseBody, 'name'),
            'error_code' => null,
        ];
    }

    private function accessToken(): string
    {
        return Cache::remember('firebase_fcm_access_token', now()->addMinutes(50), function (): string {
            $client = new Client;
            $client->setAuthConfig((string) config('services.firebase.credentials'));
            $client->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);
            $token = $client->fetchAccessTokenWithAssertion();
            $accessToken = $token['access_token'] ?? null;

            if (! is_string($accessToken) || $accessToken === '') {
                throw new RuntimeException('Google did not return an FCM access token.');
            }

            return $accessToken;
        });
    }
}
