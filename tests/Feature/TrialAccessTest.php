<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AuthOtpService;
use App\Services\TrialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class TrialAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_verified_student_can_receive_one_seven_day_trial(): void
    {
        Carbon::setTestNow('2026-11-12 10:00:00');
        $user = User::factory()->create();

        $service = app(TrialService::class);

        $this->assertTrue($service->start($user));
        $this->assertFalse($service->start($user));

        $user->refresh();
        $this->assertTrue(hasActiveTrial($user));
        $this->assertTrue(hasFullCatalogAccess($user));
        $this->assertSame('trial', fullCatalogAccessSource($user));
        $this->assertTrue($user->trial_expires_at->equalTo(now()->addDays(7)));
    }

    public function test_unverified_user_does_not_receive_a_trial(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $this->assertFalse(app(TrialService::class)->start($user));
        $this->assertNull($user->refresh()->trial_claimed_at);
    }

    public function test_trial_expires_without_a_scheduled_job(): void
    {
        Carbon::setTestNow('2026-11-12 10:00:00');
        $user = User::factory()->create();
        app(TrialService::class)->start($user);

        Carbon::setTestNow('2026-11-19 10:00:00');
        $user->refresh();

        $this->assertFalse(hasActiveTrial($user));
        $this->assertFalse(hasFullCatalogAccess($user));
        $this->assertNull(fullCatalogAccessSource($user));
    }

    public function test_paid_subscription_preserves_unused_trial_time(): void
    {
        Carbon::setTestNow('2026-11-12 10:00:00');
        $user = User::factory()->create();
        app(TrialService::class)->start($user);

        Carbon::setTestNow('2026-11-14 10:00:00');
        $user->refresh();
        activateSubscriptionPlanForUser($user, 'month-1');

        $user->refresh();
        $this->assertFalse(hasActiveTrial($user));
        $this->assertTrue(hasActiveSubscription($user));
        $this->assertSame('subscription', fullCatalogAccessSource($user));
        $this->assertTrue($user->subscription_expires_at->equalTo(now()->addMonthNoOverflow()->addDays(5)));
    }

    public function test_subscription_api_reports_trial_separately_from_paid_subscription(): void
    {
        Carbon::setTestNow('2026-11-12 10:00:00');
        $user = User::factory()->create();
        app(TrialService::class)->start($user);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/subscriptions/me')
            ->assertOk()
            ->assertJsonPath('data.active', false)
            ->assertJsonPath('data.has_full_catalog_access', true)
            ->assertJsonPath('data.access_source', 'trial')
            ->assertJsonPath('data.trial.active', true)
            ->assertJsonPath('data.trial.claimed', true)
            ->assertJsonPath('data.trial.remaining_days', 7);
    }

    public function test_successful_registration_otp_starts_the_trial(): void
    {
        Carbon::setTestNow('2026-11-12 10:00:00');
        $user = User::factory()->create(['email_verified_at' => null]);

        $otp = Mockery::mock(AuthOtpService::class);
        $otp->shouldReceive('verify')
            ->once()
            ->with(Mockery::on(fn (User $candidate) => $candidate->is($user)), '12345', AuthOtpService::PURPOSE_REGISTER)
            ->andReturnTrue();
        $otp->shouldReceive('clear')->once();
        $this->app->instance(AuthOtpService::class, $otp);

        $response = $this->post(route('auth.otp.verify'), [
            'email' => $user->email,
            'purpose' => AuthOtpService::PURPOSE_REGISTER,
            'otp' => '12345',
        ]);

        $response->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(hasActiveTrial($user));
        $this->assertTrue($user->trial_expires_at->equalTo(now()->addDays(7)));
    }
}
