<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\SabbathDownloadTagService;
use App\Services\SabbathPlanService;
use Carbon\Carbon;
use Tests\TestCase;

class SabbathPlanServiceTest extends TestCase
{
    private SabbathPlanService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('sabbath.enabled', true);
        config()->set('sabbath.timezone', 'Africa/Nairobi');
        $this->service = app(SabbathPlanService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_sabbath_window_uses_inclusive_start_and_exclusive_end(): void
    {
        $timezone = 'Africa/Nairobi';

        $this->assertFalse($this->service->isActive(Carbon::parse('2026-10-23 17:59:59', $timezone)));
        $this->assertTrue($this->service->isActive(Carbon::parse('2026-10-23 18:00:00', $timezone)));
        $this->assertTrue($this->service->isActive(Carbon::parse('2026-10-24 18:59:59', $timezone)));
        $this->assertFalse($this->service->isActive(Carbon::parse('2026-10-24 19:00:00', $timezone)));
    }

    public function test_only_students_receive_sabbath_access(): void
    {
        $duringSabbath = Carbon::parse('2026-10-23 20:00:00', 'Africa/Nairobi');
        $student = new User(['role' => 'student']);
        $instructor = new User(['role' => 'instructor']);

        $this->assertTrue($this->service->isActiveFor($student, $duringSabbath));
        $this->assertFalse($this->service->isActiveFor($instructor, $duringSabbath));
    }

    public function test_sabbath_plan_is_not_a_public_purchasable_plan(): void
    {
        $this->assertSame('sabbath', $this->service->plan()['id']);
        $this->assertFalse($this->service->plan()['visible_to_students']);
        $this->assertNull(revisionHubSubscriptionPlan('sabbath'));
        $this->assertNotContains('sabbath', array_column(revisionHubSubscriptionPlans(), 'id'));
    }

    public function test_sabbath_access_grants_full_catalogue_access_without_a_paid_subscription(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-23 20:00:00', 'Africa/Nairobi'));
        $student = new User(['role' => 'student']);

        $this->assertFalse(hasActiveSubscription($student));
        $this->assertTrue(hasSabbathAccess($student));
        $this->assertTrue(hasFullCatalogAccess($student));
    }

    public function test_plan_can_be_disabled_from_configuration(): void
    {
        config()->set('sabbath.enabled', false);

        $this->assertFalse(
            $this->service->isActive(Carbon::parse('2026-10-23 20:00:00', 'Africa/Nairobi'))
        );
    }

    public function test_sabbath_download_tags_are_encrypted_bound_to_the_resource_and_expire_at_cutoff(): void
    {
        $issuedAt = Carbon::parse('2026-10-23 20:00:00', 'Africa/Nairobi');
        $student = new User(['role' => 'student']);
        $student->id = 42;
        $tags = app(SabbathDownloadTagService::class);

        $policy = $tags->temporaryPolicy($student, 'product', 99, $issuedAt);

        $this->assertTrue($policy['temporary']);
        $this->assertTrue($policy['sabbath_download']);
        $this->assertNotEmpty($policy['tag']);
        $this->assertSame('2026-10-24T16:00:00+00:00', $policy['delete_after']);
        $this->assertNotNull($tags->validate($policy['tag'], 'product', 99, $issuedAt));
        $this->assertNull($tags->validate($policy['tag'], 'product', 100, $issuedAt));
        $this->assertNull($tags->validate(
            $policy['tag'],
            'product',
            99,
            Carbon::parse('2026-10-24 19:00:00', 'Africa/Nairobi')
        ));
    }
}
