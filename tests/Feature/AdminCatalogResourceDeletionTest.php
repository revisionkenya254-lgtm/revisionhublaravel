<?php

namespace Tests\Feature;

use App\Models\AiDocument;
use App\Models\Admin;
use App\Models\Product;
use App\Models\User;
use App\Services\Ai\BunnyDocumentStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AdminCatalogResourceDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_admin_delete_removes_bunny_backed_product_assets_before_soft_deleting_the_product(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $owner = User::factory()->create();

        $product = Product::create([
            'instructor_id' => $owner->id,
            'type' => Product::TYPE_PAST_PAPER,
            'title' => 'Admin Cleanup Paper',
            'slug' => 'admin-cleanup-paper',
            'price' => 20,
            'status' => 'active',
            'is_approved' => 'approved',
            'file_path' => 'instructors/' . $owner->id . '/past_papers/admin-cleanup-paper.pdf',
            'file_type' => 'pdf',
        ]);

        $aiDocument = AiDocument::create([
            'instructor_id' => $owner->id,
            'product_id' => $product->id,
            'source_type' => 'product_resource',
            'source_name' => $product->title,
            'original_path' => $product->file_path,
            'storage_disk' => 'bunny',
            'bunny_folder_path' => 'instructors/' . $owner->id . '/past_papers',
            'mime_type' => 'application/pdf',
            'file_extension' => 'pdf',
            'file_hash' => hash('sha256', $product->file_path),
            'status' => 'processed',
            'progress' => 100,
            'extracted_text_path' => 'instructors/' . $owner->id . '/past_papers/admin-cleanup-paper.txt',
        ]);

        $storage = Mockery::mock(BunnyDocumentStorageService::class);
        $storage->shouldReceive('deletePath')
            ->once()
            ->with($product->file_path)
            ->andReturnTrue();
        $storage->shouldReceive('deletePath')
            ->once()
            ->with('instructors/' . $owner->id . '/past_papers/admin-cleanup-paper.txt')
            ->andReturnTrue();

        $this->app->instance(BunnyDocumentStorageService::class, $storage);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.past-papers.destroy', ['resource' => $product]))
            ->assertRedirect(route('admin.past-papers.index'));

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);

        $this->assertDatabaseMissing('ai_documents', [
            'id' => $aiDocument->id,
        ]);
    }

    public function test_admin_update_returns_to_the_current_past_papers_page(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'editor@example.com',
            'password' => 'secret123',
        ]);

        $product = Product::create([
            'type' => Product::TYPE_PAST_PAPER,
            'title' => 'Existing Paper',
            'slug' => 'existing-paper',
            'price' => 20,
            'status' => 'active',
            'is_approved' => 'approved',
        ]);

        $query = ['page' => 22, 'keyword' => 'English'];

        $this->actingAs($admin, 'admin')
            ->put(route('admin.past-papers.update', array_merge(['resource' => $product], $query)), [
                'title' => 'Existing Paper',
                'price' => 20,
                'status' => 'active',
                'is_approved' => 'approved',
            ])
            ->assertRedirect(route('admin.past-papers.index', $query));
    }
}
