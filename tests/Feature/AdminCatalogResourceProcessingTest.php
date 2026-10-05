<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CatalogResourceController;
use App\Models\AiDocument;
use App\Models\Product;
use App\Models\User;
use App\Services\Ai\AiDocumentService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AdminCatalogResourceProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
    }

    public function test_pdf_past_paper_processing_defaults_to_openai_review(): void
    {
        $instructor = User::factory()->create();

        $product = Product::create([
            'instructor_id' => $instructor->id,
            'type' => Product::TYPE_PAST_PAPER,
            'title' => 'Admin PDF Review Paper',
            'slug' => 'admin-pdf-review-paper',
            'price' => 0,
            'status' => 'active',
            'is_approved' => 'approved',
            'file_path' => 'uploads/testing/admin-pdf-review-paper.pdf',
            'file_type' => 'pdf',
        ]);

        $aiDocumentService = Mockery::mock(AiDocumentService::class);
        $aiDocumentService->shouldReceive('registerUpload')
            ->once()
            ->andReturnUsing(function (int $instructorId, array $upload, array $context) use ($product) {
                return AiDocument::create([
                    'instructor_id' => $instructorId,
                    'product_id' => $product->id,
                    'source_type' => $context['source_type'] ?? 'product_resource',
                    'source_name' => $context['source_name'] ?? $product->title,
                    'original_path' => $upload['path'],
                    'storage_disk' => 'bunny',
                    'bunny_folder_path' => $upload['folder_path'] ?? null,
                    'mime_type' => $upload['mime_type'] ?? 'application/pdf',
                    'file_extension' => $upload['extension'] ?? 'pdf',
                    'file_hash' => $upload['hash'] ?? hash('sha256', $upload['path']),
                    'status' => 'pending',
                    'progress' => 0,
                    'metadata' => array_filter($context['metadata'] ?? []),
                ]);
            });
        $aiDocumentService->shouldReceive('resolveProcessingQueueConnection')
            ->andReturn('database');

        $this->app->instance(AiDocumentService::class, $aiDocumentService);
        $this->app->instance('request', Request::create(
            '/admin/past-papers/' . $product->id . '/ai-reprocess',
            'POST',
            ['queue_mode' => 'redis'],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        ));

        /** @var CatalogResourceController $controller */
        $controller = $this->app->make(CatalogResourceController::class);
        $response = $controller->reprocessAiDocument($product, 'past_paper');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('success', $response->getData(true)['status']);
        $this->assertSame('openai', $response->getData(true)['review_mode']);
    }
}
