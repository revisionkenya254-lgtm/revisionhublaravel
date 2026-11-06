<?php

namespace Tests\Feature;

use App\Jobs\PreparePushCampaignJob;
use App\Jobs\SendPushCampaignBatchJob;
use App\Models\Admin;
use App\Models\PushDeviceToken;
use App\Models\PushNotificationCampaign;
use App\Models\PushNotificationDelivery;
use App\Models\User;
use App\Services\Push\FirebaseMessagingService;
use App\Services\Push\PushAudience;
use App\Services\Taxonomy\TaxonomyCatalogRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PushNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_register_refresh_and_unregister_an_android_push_token(): void
    {
        $student = User::factory()->create();
        $token = 'fcm-token-for-test';

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/push/devices', ['token' => $token, 'platform' => 'android'])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->postJson('/api/push/devices', ['token' => $token, 'platform' => 'android'])->assertOk();
        $this->assertDatabaseCount('push_device_tokens', 1);

        $this->deleteJson('/api/push/devices', ['token' => $token])
            ->assertOk()
            ->assertJsonPath('status', 'success');
        $this->assertDatabaseCount('push_device_tokens', 0);
    }

    public function test_non_student_cannot_register_a_push_device(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        $this->actingAs($instructor, 'sanctum')
            ->postJson('/api/push/devices', ['token' => 'fcm-token', 'platform' => 'android'])
            ->assertForbidden();
    }

    public function test_push_campaign_ui_requires_an_admin_with_customer_permission(): void
    {
        $this->get(route('admin.push-notifications.index'))->assertRedirect(route('admin.login'));

        $admin = $this->makeAdminWithCustomerPermission();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.push-notifications.create'))
            ->assertOk()
            ->assertSee('Create Push Campaign')
            ->assertSee('One education level')
            ->assertSee('Estimated recipients');
    }

    public function test_admin_can_queue_an_education_level_campaign(): void
    {
        Queue::fake();
        $admin = $this->makeAdminWithCustomerPermission();
        $level = app(TaxonomyCatalogRepository::class)->educationLevels()[0]['slug'];
        $this->mock(FirebaseMessagingService::class, function ($mock): void {
            $mock->shouldReceive('isConfigured')->andReturn(true);
        });

        $this->actingAs($admin, 'admin')
            ->post(route('admin.push-notifications.store'), [
                'title' => 'Grade resources',
                'body' => 'New revision materials are available.',
                'target_type' => 'education_level',
                'education_level' => $level,
                'target_url' => 'https://revisionhubkenya.com/catalog',
            ])
            ->assertRedirect(route('admin.push-notifications.index'));

        $campaign = PushNotificationCampaign::query()->firstOrFail();
        $this->assertSame('preparing', $campaign->status);
        $this->assertSame($level, $campaign->education_level);
        Queue::assertPushed(PreparePushCampaignJob::class, fn ($job) => $job->campaignId === $campaign->id);
    }

    public function test_campaign_preparation_limits_level_audience_to_active_student_tokens(): void
    {
        Queue::fake();
        $level = app(TaxonomyCatalogRepository::class)->educationLevels()[0]['slug'];
        $otherLevel = app(TaxonomyCatalogRepository::class)->educationLevels()[1]['slug'];
        $matchedStudent = User::factory()->create(['education_level' => $level]);
        $otherStudent = User::factory()->create(['education_level' => $otherLevel]);
        $studentWithoutLevel = User::factory()->create(['education_level' => null]);
        $instructor = User::factory()->create(['role' => 'instructor', 'education_level' => $level]);
        $matchedToken = $this->registerToken($matchedStudent, 'matched');
        $this->registerToken($otherStudent, 'other');
        $this->registerToken($studentWithoutLevel, 'none');
        $this->registerToken($instructor, 'instructor');

        $campaign = PushNotificationCampaign::query()->create([
            'title' => 'Level update',
            'body' => 'Materials are ready.',
            'target_type' => 'education_level',
            'education_level' => $level,
            'status' => 'preparing',
        ]);

        (new PreparePushCampaignJob($campaign->id))->handle(app(PushAudience::class));

        $campaign->refresh();
        $this->assertSame(1, $campaign->recipient_count);
        $this->assertSame('sending', $campaign->status);
        $this->assertDatabaseHas('push_notification_deliveries', [
            'campaign_id' => $campaign->id,
            'push_device_token_id' => $matchedToken->id,
        ]);
        $this->assertDatabaseCount('push_notification_deliveries', 1);
        Queue::assertPushed(SendPushCampaignBatchJob::class);
    }

    public function test_send_job_records_success_and_finishes_campaign(): void
    {
        $student = User::factory()->create();
        $token = $this->registerToken($student, 'send');
        $campaign = $this->campaignWithDelivery($token);
        $messaging = \Mockery::mock(FirebaseMessagingService::class);
        $messaging->shouldReceive('send')->once()->andReturn([
            'status' => 'sent',
            'message_id' => 'projects/test/messages/123',
            'error_code' => null,
        ]);

        (new SendPushCampaignBatchJob($campaign->id))->handle($messaging);

        $campaign->refresh();
        $this->assertSame('sent', $campaign->status);
        $this->assertSame(1, $campaign->sent_count);
        $this->assertDatabaseHas('push_notification_deliveries', [
            'campaign_id' => $campaign->id,
            'status' => 'sent',
            'message_id' => 'projects/test/messages/123',
        ]);
    }

    public function test_unregistered_fcm_token_is_removed_and_counted_as_failed(): void
    {
        $student = User::factory()->create();
        $token = $this->registerToken($student, 'unregistered');
        $campaign = $this->campaignWithDelivery($token);
        $messaging = \Mockery::mock(FirebaseMessagingService::class);
        $messaging->shouldReceive('send')->once()->andReturn([
            'status' => 'unregistered',
            'message_id' => null,
            'error_code' => 'UNREGISTERED',
        ]);

        (new SendPushCampaignBatchJob($campaign->id))->handle($messaging);

        $campaign->refresh();
        $this->assertSame('failed', $campaign->status);
        $this->assertSame(1, $campaign->failed_count);
        $this->assertDatabaseMissing('push_device_tokens', ['id' => $token->id]);
        $this->assertDatabaseHas('push_notification_deliveries', [
            'campaign_id' => $campaign->id,
            'status' => 'failed',
            'error_code' => 'UNREGISTERED',
        ]);
    }

    private function makeAdminWithCustomerPermission(): Admin
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permission = Permission::findOrCreate('customer.view', 'admin');
        $admin = Admin::create([
            'name' => 'Push Administrator',
            'email' => 'push-admin-'.Str::random(8).'@example.com',
            'password' => 'password',
        ]);
        $admin->givePermissionTo($permission);

        return $admin;
    }

    private function registerToken(User $user, string $value): PushDeviceToken
    {
        return PushDeviceToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $value),
            'token' => $value,
            'platform' => 'android',
            'last_registered_at' => now(),
        ]);
    }

    private function campaignWithDelivery(PushDeviceToken $token): PushNotificationCampaign
    {
        $campaign = PushNotificationCampaign::query()->create([
            'title' => 'Study update',
            'body' => 'New material is ready.',
            'target_type' => 'all',
            'status' => 'sending',
            'recipient_count' => 1,
        ]);

        PushNotificationDelivery::query()->create([
            'campaign_id' => $campaign->id,
            'push_device_token_id' => $token->id,
            'token_hash' => $token->token_hash,
            'status' => 'pending',
        ]);

        return $campaign;
    }
}
