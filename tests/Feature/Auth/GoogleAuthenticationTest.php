<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\GoogleTokenVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_google_login_creates_a_user_and_returns_a_token(): void
    {
        $this->app->instance(GoogleTokenVerifier::class, new class extends GoogleTokenVerifier {
            public function verify(string $idToken): ?array
            {
                return [
                    'sub' => 'google-user-123',
                    'email' => 'google@example.com',
                    'email_verified' => true,
                    'name' => 'Google User',
                ];
            }
        });

        $response = $this->postJson('/api/auth/google', [
            'id_token' => 'test-token',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['status', 'message', 'bearer_token', 'user_id']);

        $this->assertDatabaseHas('users', [
            'email' => 'google@example.com',
            'google_id' => 'google-user-123',
        ]);
    }

    public function test_google_login_links_an_existing_user_by_verified_email(): void
    {
        $user = User::factory()->create([
            'email' => 'existing@example.com',
            'google_id' => null,
        ]);

        $this->app->instance(GoogleTokenVerifier::class, new class extends GoogleTokenVerifier {
            public function verify(string $idToken): ?array
            {
                return [
                    'sub' => 'google-existing-123',
                    'email' => 'existing@example.com',
                    'email_verified' => true,
                ];
            }
        });

        $this->postJson('/api/auth/google', ['id_token' => 'test-token'])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'google_id' => 'google-existing-123',
        ]);
    }

    public function test_invalid_google_tokens_are_rejected(): void
    {
        $this->app->instance(GoogleTokenVerifier::class, new class extends GoogleTokenVerifier {
            public function verify(string $idToken): ?array
            {
                return null;
            }
        });

        $this->postJson('/api/auth/google', ['id_token' => 'invalid-token'])
            ->assertUnauthorized();
    }

    public function test_a_different_google_identity_cannot_use_an_existing_email(): void
    {
        User::factory()->create([
            'email' => 'linked@example.com',
            'google_id' => 'another-google-id',
        ]);

        $this->app->instance(GoogleTokenVerifier::class, new class extends GoogleTokenVerifier {
            public function verify(string $idToken): ?array
            {
                return [
                    'sub' => 'unrelated-google-id',
                    'email' => 'linked@example.com',
                    'email_verified' => true,
                ];
            }
        });

        $this->postJson('/api/auth/google', ['id_token' => 'test-token'])
            ->assertStatus(409);
    }
}
