<?php

namespace Tests\Feature;

use App\Models\Admin;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSabbathPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('sabbath.enabled', true);
        config()->set('sabbath.timezone', 'Africa/Nairobi');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_sabbath_plan_page_requires_an_authenticated_admin(): void
    {
        $this->get(route('admin.sabbath-plan.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_the_internal_sabbath_plan(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-23 20:00:00', 'Africa/Nairobi'));
        $admin = Admin::create([
            'name' => 'Sabbath Administrator',
            'email' => 'sabbath-admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.sabbath-plan.index'))
            ->assertOk()
            ->assertSee('Sabbath Plan')
            ->assertSee('Active now')
            ->assertSee('Friday 6:00 PM to Saturday 7:00 PM')
            ->assertSee('hidden from student subscription pages');
    }
}
