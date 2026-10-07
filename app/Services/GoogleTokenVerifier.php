<?php

namespace App\Services;

use Google\Client;
use Illuminate\Support\Facades\Cache;

class GoogleTokenVerifier
{
    /** @return array<string, mixed>|null */
    public function verify(string $idToken): ?array
    {
        $clientId = config('services.google.client_id') ?: data_get(Cache::get('setting'), 'gmail_client_id');

        if (! is_string($clientId) || $clientId === '' || $clientId === 'gmail_client_id') {
            return null;
        }

        $client = new Client([
            'client_id' => $clientId,
        ]);

        $payload = $client->verifyIdToken($idToken);

        return is_array($payload) ? $payload : null;
    }
}
