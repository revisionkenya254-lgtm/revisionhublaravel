<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\AuthOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class RegistrationTest extends TestCase
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
            'google_tagmanager_status' => 'inactive',
            'google_login_status' => 'inactive',
            'header_topbar_status' => 'inactive',
            'header_social_status' => 'inactive',
            'cursor_dot_status' => 'inactive',
            'site_theme' => 'theme-one',
            'copyright_text' => 'Test',
            'pusher_status' => 'inactive',
        ]);
        Cache::put('marketing_setting', (object) [
            'register' => false,
        ]);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_are_redirected_to_the_otp_screen_after_registering(): void
    {
        $countryId = DB::table('countries')->insertGetId([
            'name' => 'Test Country',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $mock = Mockery::mock(AuthOtpService::class);
        $mock->shouldReceive('send')->once()->andReturn(true);
        $this->app->instance(AuthOtpService::class, $mock);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'country_id' => $countryId,
            'phone' => '0123456789',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertGuest();
        $this->assertNull($user->email_verified_at);
        $response->assertRedirect(route('auth.otp.notice', [
            'email' => $user->email,
            'purpose' => AuthOtpService::PURPOSE_REGISTER,
        ]));
    }
}
