<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\GoogleTokenVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_android_google_login_creates_a_user_and_returns_a_token(): void
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

        $response = $this->postJson('/auth/google', [
            'id_token' => 'test-token',
            'platform' => 'android',
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

        $this->postJson('/auth/google', ['id_token' => 'test-token'])->assertOk();

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

        $this->postJson('/auth/google', ['id_token' => 'invalid-token'])
            ->assertUnauthorized();
    }

    public function test_android_google_login_validates_the_credential_manager_nonce(): void
    {
        $this->app->instance(GoogleTokenVerifier::class, new class extends GoogleTokenVerifier {
            public function verify(string $idToken): ?array
            {
                return [
                    'sub' => 'google-user-with-nonce',
                    'email' => 'nonce@example.com',
                    'email_verified' => true,
                    'nonce' => 'signed-nonce',
                ];
            }
        });

        $this->postJson('/auth/google', [
            'id_token' => 'test-token',
            'nonce' => 'different-nonce',
            'platform' => 'android',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid Google sign-in nonce.');

        $this->assertDatabaseMissing('users', ['email' => 'nonce@example.com']);
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

        $this->postJson('/auth/google', ['id_token' => 'test-token'])
            ->assertStatus(409);
    }

    public function test_web_google_login_creates_a_session_using_gis_credential_and_csrf_token(): void
    {
        $this->app->instance(GoogleTokenVerifier::class, new class extends GoogleTokenVerifier {
            public function verify(string $idToken): ?array
            {
                return [
                    'sub' => 'google-web-123',
                    'email' => 'web@example.com',
                    'email_verified' => true,
                    'name' => 'Web User',
                ];
            }
        });

        $response = $this
            ->withUnencryptedCookie('g_csrf_token', 'google-csrf-token')
            ->post('/auth/google', [
                'credential' => 'test-token',
                'g_csrf_token' => 'google-csrf-token',
            ]);

        $response->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticated('web');
    }

    public function test_web_google_login_rejects_a_mismatched_gis_csrf_token(): void
    {
        $response = $this
            ->withUnencryptedCookie('g_csrf_token', 'cookie-token')
            ->post('/auth/google', [
                'credential' => 'test-token',
                'g_csrf_token' => 'body-token',
            ]);

        $response->assertRedirect(route('login'));
        $this->assertGuest('web');
    }

    public function test_google_login_rejects_ambiguous_token_fields(): void
    {
        $this->postJson('/auth/google', [
            'credential' => 'web-token',
            'id_token' => 'android-token',
        ])->assertUnprocessable();
    }
}
