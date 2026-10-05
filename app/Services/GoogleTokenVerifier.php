<?php

namespace App\Services;

use Google\Client;

class GoogleTokenVerifier
{
    /** @return array<string, mixed>|null */
    public function verify(string $idToken): ?array
    {
        $client = new Client([
            'client_id' => config('services.google.client_id'),
        ]);

        $payload = $client->verifyIdToken($idToken);

        return is_array($payload) ? $payload : null;
    }
}
