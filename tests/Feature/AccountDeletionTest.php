<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Ai\AiDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Mockery;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
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
            'mail_host' => 'localhost',
            'mail_port' => '1025',
            'mail_sender_email' => 'noreply@example.test',
            'mail_sender_name' => 'RevisionHub',
        ]);
    }

    public function test_public_deletion_request_sends_a_signed_confirmation_link_without_disclosing_account_existence(): void
    {
        $user = User::factory()->create(['email' => 'learner@example.test']);
        $confirmationBody = '';

        Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function (string $body, callable $callback) use (&$confirmationBody): bool {
                $confirmationBody = $body;

                return str_contains($body, '/delete-account/confirm/')
                    && is_callable($callback);
            });

        $this->get('/delete-account')->assertOk();
        $response = $this->from('/delete-account')->post('/delete-account', [
            'email' => $user->email,
        ]);

        $response->assertRedirect('/delete-account')
            ->assertSessionHas('status', 'If an account matches that email address, we have sent a confirmation link.');

        preg_match('/https?:\/\/\S+/', $confirmationBody, $matches);
        $confirmationPage = $this->get($matches[0] ?? '/');
        $confirmationPage->assertOk()
            ->assertViewHas('account', fn (User $account): bool => $account->is($user));
        $confirmationUrl = $confirmationPage->viewData('confirmationUrl');
        $this->post($confirmationUrl)->assertRedirect(route('home'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);

        $missingAccountResponse = $this->from('/delete-account')->post('/delete-account', [
            'email' => 'missing@example.test',
        ]);

        $missingAccountResponse->assertRedirect('/delete-account')
            ->assertSessionHas('status', 'If an account matches that email address, we have sent a confirmation link.');
    }

    public function test_authenticated_mobile_account_can_request_email_confirmation(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Mail::shouldReceive('raw')->once();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/auth/account')
            ->assertAccepted()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_signed_confirmation_deletes_student_account_and_anonymizes_order_records(): void
    {
        $user = User::factory()->create();
        $orderId = DB::table('orders')->insertGetId([
            'buyer_id' => $user->id,
            'payment_details' => json_encode(['payer_email' => $user->email]),
            'order_details' => json_encode(['buyer_name' => $user->name]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $emailHash = hash_hmac('sha256', $user->email, (string) config('app.key'));
        $url = URL::temporarySignedRoute(
            'account-deletion.confirm.destroy',
            now()->addHour(),
            ['user' => $user->id, 'email_hash' => $emailHash]
        );
        $this->app->instance(AiDocumentService::class, Mockery::mock(AiDocumentService::class));

        $this->post($url)->assertRedirect(route('home'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'buyer_id' => null,
            'payment_details' => null,
            'order_details' => null,
        ]);
    }

    public function test_signed_confirmation_anonymizes_instructor_and_keeps_published_content_owner_reference(): void
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'email' => 'teacher@example.test',
        ]);
        $buyer = User::factory()->create();
        $courseId = DB::table('courses')->insertGetId([
            'instructor_id' => $instructor->id,
            'type' => 'course',
            'title' => 'Published course',
            'slug' => 'published-course',
            'demo_video_storage' => 'upload',
            'status' => 'active',
            'is_approved' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sellerOrderId = DB::table('orders')->insertGetId([
            'buyer_id' => $buyer->id,
            'seller_id' => $instructor->id,
            'payment_details' => json_encode(['payer_email' => $buyer->email]),
            'order_details' => json_encode(['buyer_name' => $buyer->name]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $emailHash = hash_hmac('sha256', $instructor->email, (string) config('app.key'));
        $url = URL::temporarySignedRoute(
            'account-deletion.confirm.destroy',
            now()->addHour(),
            ['user' => $instructor->id, 'email_hash' => $emailHash]
        );
        $this->app->instance(AiDocumentService::class, Mockery::mock(AiDocumentService::class));

        $this->post($url)->assertRedirect(route('home'));

        $this->assertDatabaseHas('users', [
            'id' => $instructor->id,
            'name' => 'Deleted instructor',
            'email' => "deleted-instructor-{$instructor->id}@deleted.invalid",
            'status' => 'deactive',
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $courseId,
            'instructor_id' => $instructor->id,
            'title' => 'Published course',
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $sellerOrderId,
            'buyer_id' => $buyer->id,
            'seller_id' => null,
            'payment_details' => json_encode(['payer_email' => $buyer->email]),
            'order_details' => json_encode(['buyer_name' => $buyer->name]),
        ]);
    }
}
