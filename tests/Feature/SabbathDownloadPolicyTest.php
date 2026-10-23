<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\SabbathDownloadTagService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SabbathDownloadPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('sabbath.enabled', true);
        config()->set('sabbath.timezone', 'Africa/Nairobi');
        Carbon::setTestNow(Carbon::parse('2026-10-23 20:00:00', 'Africa/Nairobi'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_android_product_download_receives_an_encrypted_sabbath_policy(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $product = $this->paidProduct();
        Sanctum::actingAs($student);

        $response = $this->getJson("/api/downloads/products/{$product->type}/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('data.sabbath_download', true)
            ->assertJsonPath('data.download_policy.type', 'sabbath_temporary')
            ->assertJsonPath('data.download_policy.temporary', true)
            ->assertJsonPath('data.delete_after', '2026-10-24T16:00:00+00:00');

        $tag = (string) $response->json('data.sabbath_download_tag');
        $downloadUrl = (string) $response->json('data.download_url');
        $this->assertNotSame('', $tag);
        $this->assertStringContainsString('sabbath_tag=', $downloadUrl);
        $this->assertNotNull(app(SabbathDownloadTagService::class)->validate($tag, 'product', $product->id));

        $this->get($downloadUrl)
            ->assertOk()
            ->assertDownload()
            ->assertHeader('X-RevisionHub-Download-Policy', 'sabbath-temporary')
            ->assertHeader('X-RevisionHub-Sabbath-Tag', $tag)
            ->assertHeader('X-RevisionHub-Delete-After', '2026-10-24T16:00:00+00:00');
    }

    public function test_active_paid_subscriber_download_is_permanent_during_sabbath(): void
    {
        $subscriber = User::factory()->create([
            'role' => 'student',
            'subscription_plan_id' => 'month-1',
            'subscription_started_at' => now()->subDay(),
            'subscription_expires_at' => now()->addMonth(),
        ]);
        $product = $this->paidProduct();
        Sanctum::actingAs($subscriber);

        $this->getJson("/api/downloads/products/{$product->type}/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.sabbath_download', false)
            ->assertJsonPath('data.sabbath_download_tag', null)
            ->assertJsonPath('data.delete_after', null)
            ->assertJsonPath('data.download_policy.type', 'permanent');
    }

    public function test_browser_download_is_blocked_for_sabbath_only_access(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $product = $this->paidProduct();

        $this->actingAs($student)
            ->get(route('product.download', [$product->type, $product->id]))
            ->assertForbidden();
    }

    public function test_browser_download_remains_available_to_active_paid_subscriber(): void
    {
        $subscriber = User::factory()->create([
            'role' => 'student',
            'subscription_plan_id' => 'month-1',
            'subscription_started_at' => now()->subDay(),
            'subscription_expires_at' => now()->addMonth(),
        ]);
        $product = $this->paidProduct();

        $this->actingAs($subscriber)
            ->get(route('product.download', [$product->type, $product->id]))
            ->assertOk()
            ->assertDownload();
    }

    private function paidProduct(): Product
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        return Product::create([
            'instructor_id' => $instructor->id,
            'type' => Product::TYPE_PAST_PAPER,
            'title' => 'Sabbath Download Test',
            'slug' => 'sabbath-download-test-'.str()->random(8),
            'price' => 100,
            'status' => 'active',
            'is_approved' => 'approved',
            'file_path' => 'index.php',
            'file_type' => 'php',
        ]);
    }
}
