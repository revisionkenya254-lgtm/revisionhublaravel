<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\AuthOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class AuthenticationTest extends TestCase
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

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_verified_users_can_request_a_login_otp(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $mock = Mockery::mock(AuthOtpService::class);
        $mock->shouldReceive('send')->once()->withArgs(function (User $sentUser, string $purpose) use ($user) {
            return $sentUser->is($user) && $purpose === AuthOtpService::PURPOSE_LOGIN;
        })->andReturn(true);
        $this->app->instance(AuthOtpService::class, $mock);

        $response = $this->post('/user-login', [
            'email' => $user->email,
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('auth.otp.notice', [
            'email' => $user->email,
            'purpose' => AuthOtpService::PURPOSE_LOGIN,
        ]));
    }

    public function test_users_can_complete_login_with_a_valid_otp(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $mock = Mockery::mock(AuthOtpService::class);
        $mock->shouldReceive('verify')->once()->withArgs(function (User $sentUser, string $otp, string $purpose) use ($user) {
            return $sentUser->is($user)
                && $otp === '12345'
                && $purpose === AuthOtpService::PURPOSE_LOGIN;
        })->andReturn(true);
        $mock->shouldReceive('clear')->once()->withArgs(function (User $sentUser) use ($user) {
            return $sentUser->is($user);
        });
        $this->app->instance(AuthOtpService::class, $mock);

        $response = $this->post('/otp/verify', [
            'email' => $user->email,
            'purpose' => AuthOtpService::PURPOSE_LOGIN,
            'otp' => '12345',
        ]);

        $response->assertRedirect(route('student.dashboard'));
        $this->get(route('student.dashboard'))->assertOk();
    }

    public function test_instructor_role_users_are_redirected_away_from_student_dashboard(): void
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($instructor)->get('/student/dashboard');

        $response->assertRedirect(route('instructor.dashboard'));
    }

    public function test_student_role_users_can_access_student_dashboard(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($student)->get('/student/dashboard');

        $response->assertOk();
    }

    public function test_instructor_role_users_are_redirected_away_from_student_wishlist(): void
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($instructor)->get('/student/wishlist');

        $response->assertRedirect(route('instructor.dashboard'));
    }

    public function test_instructor_role_users_are_redirected_away_from_checkout(): void
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($instructor)->get('/checkout');

        $response->assertRedirect(route('catalog'));
    }
}
