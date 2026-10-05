<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::put('setting', (object) [
            'app_name' => 'RevisionHub',
            'logo' => 'test-logo.png',
            'preloader' => 'test-preloader.png',
            'breadcrumb_image' => 'test-breadcrumb.png',
            'recaptcha_status' => 'inactive',
            'google_login_status' => 'inactive',
            'header_topbar_status' => 'inactive',
            'header_social_status' => 'inactive',
            'cursor_dot_status' => 'inactive',
            'site_theme' => 'theme-one',
            'copyright_text' => 'Test',
            'pusher_status' => 'inactive',
        ]);
    }

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_custom_reset_password_screen_can_be_rendered(): void
    {
        $user = User::factory()->create([
            'forget_password_token' => 'reset-token',
        ]);

        $response = $this->get('/reset-password-page/'.$user->forget_password_token);

        $response->assertStatus(200);
    }

    public function test_password_can_be_reset_with_a_valid_custom_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'forget_password_token' => 'reset-token',
        ]);

        $response = $this->post('/reset-password-store/'.$user->forget_password_token, [
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertNull($user->fresh()->forget_password_token);
    }
}
