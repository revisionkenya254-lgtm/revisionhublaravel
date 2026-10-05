<?php

namespace Tests\Feature;

use App\Models\AiDocument;
use App\Models\AiDocumentChunk;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Order\app\Models\Order;
use Modules\Order\app\Models\OrderItem;
use Tests\TestCase;

class ProductDocumentReaderTest extends TestCase
{
    use RefreshDatabase;

    private array $createdFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    public function test_free_pdf_product_opens_in_reader_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $product = $this->makeDocumentProduct(Product::TYPE_PAST_PAPER, 0, 'pdf');

        $this->actingAs($user)
            ->get(route('product.read-document', $product->slug))
            ->assertOk()
            ->assertSee($product->title)
            ->assertSee('view-only', false);
    }

    public function test_paid_document_reader_forbids_user_without_purchase(): void
    {
        $user = User::factory()->create();
        $product = $this->makeDocumentProduct(Product::TYPE_PREDICTION, 30, 'pdf');

        $this->actingAs($user)
            ->get(route('product.read-document', $product->slug))
            ->assertForbidden();
    }

    public function test_paid_document_reader_allows_purchased_user_and_streams_inline_file(): void
    {
        $user = User::factory()->create();
        $product = $this->makeDocumentProduct(Product::TYPE_PREDICTION, 30, 'pdf');

        $this->purchaseProductFor($user, $product, 30);

        $this->actingAs($user)
            ->get(route('product.document-source', $product->slug))
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename="' . basename(public_path($product->file_path)) . '"')
            ->assertHeader('x-content-type-options', 'nosniff');
    }

    public function test_legacy_download_route_redirects_to_reader(): void
    {
        $user = User::factory()->create();
        $product = $this->makeDocumentProduct(Product::TYPE_PAST_PAPER, 0, 'pdf');

        $this->actingAs($user)
            ->get(route('product.download', $product->slug))
            ->assertRedirect(route('product.read-document', $product->slug));
    }

    public function test_reader_rejects_unsupported_file_types(): void
    {
        $user = User::factory()->create();
        $product = $this->makeDocumentProduct(Product::TYPE_PAST_PAPER, 0, 'txt');

        $this->actingAs($user)
            ->get(route('product.read-document', $product->slug))
            ->assertNotFound();
    }

    public function test_reader_falls_back_to_processed_ai_document_when_source_file_is_missing(): void
    {
        $user = User::factory()->create();
        $product = Product::create([
            'instructor_id' => User::factory()->create([
                'role' => 'instructor',
                'status' => 'active',
            ])->id,
            'type' => Product::TYPE_PAST_PAPER,
            'title' => 'Fallback Reader Product',
            'slug' => 'fallback-reader-product',
            'price' => 0,
            'status' => 'active',
            'is_approved' => 'approved',
            'file_path' => 'instructors/1019/products/past_paper/22/source/2026/08/missing-source-file.doc',
            'file_type' => 'docx',
        ]);

        AiDocument::create([
            'instructor_id' => $product->instructor_id,
            'product_id' => $product->id,
            'source_type' => 'product_resource',
            'source_name' => $product->title,
            'original_path' => $product->file_path,
            'storage_disk' => 'bunny',
            'mime_type' => 'application/msword',
            'file_extension' => 'doc',
            'file_hash' => hash('sha256', $product->file_path),
            'status' => 'processed',
            'progress' => 100,
            'page_count' => 4,
            'character_count' => 1200,
            'metadata' => [
                'public_url' => 'https://example.com/fallback-reader-product.doc',
            ],
            'processed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('product.read-document', $product->slug))
            ->assertOk()
            ->assertSee($product->title);

        $this->actingAs($user)
            ->get(route('product.document-source', $product->slug))
            ->assertRedirect('https://example.com/fallback-reader-product.doc');
    }

    public function test_docx_product_renders_text_reader_from_ai_document_chunks(): void
    {
        $user = User::factory()->create();
        $product = $this->makeTextReadableProduct(Product::TYPE_PAST_PAPER, 'docx');

        $this->actingAs($user)
            ->get(route('product.read-document', $product->slug))
            ->assertOk()
            ->assertSee('Text Reader')
            ->assertSee('Readable document content')
            ->assertSee('THE KENYA NATIONAL EXAMINATIONS COUNCIL', false)
            ->assertSee('Craft Certificate in Accountancy', false);
    }

    public function test_doc_product_renders_text_reader_from_ai_document_chunks(): void
    {
        $user = User::factory()->create();
        $product = $this->makeTextReadableProduct(Product::TYPE_PAST_PAPER, 'doc');

        $this->actingAs($user)
            ->get(route('product.read-document', $product->slug))
            ->assertOk()
            ->assertSee('Text Reader')
            ->assertSee('Readable document content')
            ->assertSee('SECTION A', false)
            ->assertSee('Answer ALL the questions in this section', false);
    }

    private function makeDocumentProduct(string $type, float $price, string $extension): Product
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);

        $relativePath = 'uploads/testing/' . uniqid('reader_', true) . '.' . $extension;
        $absolutePath = public_path($relativePath);
        $directory = dirname($absolutePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($absolutePath, $this->fakeFileContents($extension));
        $this->createdFiles[] = $absolutePath;

        return Product::create([
            'instructor_id' => $instructor->id,
            'type' => $type,
            'title' => 'Reader Product ' . fake()->unique()->word(),
            'slug' => 'reader-product-' . fake()->unique()->slug(),
            'price' => $price,
            'status' => 'active',
            'is_approved' => 'approved',
            'file_path' => $relativePath,
            'file_type' => $extension,
        ]);
    }

    private function makeTextReadableProduct(string $type, string $extension): Product
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);

        $product = Product::create([
            'instructor_id' => $instructor->id,
            'type' => $type,
            'title' => 'Text Reader Product ' . fake()->unique()->word(),
            'slug' => 'text-reader-product-' . fake()->unique()->slug(),
            'price' => 0,
            'status' => 'active',
            'is_approved' => 'approved',
            'file_path' => 'uploads/testing/missing-' . uniqid('reader_', true) . '.' . $extension,
            'file_type' => $extension,
        ]);

        $document = AiDocument::create([
            'instructor_id' => $instructor->id,
            'product_id' => $product->id,
            'source_type' => 'product_resource',
            'source_name' => $product->title,
            'original_path' => $product->file_path,
            'storage_disk' => 'bunny',
            'mime_type' => $extension === 'docx'
                ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                : 'application/msword',
            'file_extension' => $extension,
            'file_hash' => hash('sha256', $product->file_path),
            'status' => 'processed',
            'progress' => 100,
            'page_count' => 1,
            'character_count' => 1200,
            'extracted_text_excerpt' => 'THE KENYA NATIONAL EXAMINATIONS COUNCIL Craft Certificate in Accountancy Auditing July 2012.',
            'metadata' => [
                'extraction_method' => 'phpword',
            ],
            'processed_at' => now(),
        ]);

        AiDocumentChunk::create([
            'ai_document_id' => $document->id,
            'chunk_index' => 0,
            'page_start' => 1,
            'page_end' => 1,
            'heading' => 'Auditing',
            'content' => <<<'TEXT'
THE KENYA NATIONAL EXAMINATIONS COUNCIL
CRAFT CERTIFICATE IN ACCOUNTANCY
AUDITING

SECTION A
Answer ALL the questions in this section.
TEXT,
            'content_hash' => hash('sha256', 'text-reader-chunk-' . $product->id),
            'token_count' => 200,
            'metadata' => [],
        ]);

        return $product;
    }

    private function purchaseProductFor(User $user, Product $product, float $amount): void
    {
        $order = Order::create([
            'buyer_id' => $user->id,
            'payment_status' => 'paid',
            'payment_method' => 'test',
            'paid_amount' => $amount,
            'payable_amount' => $amount,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'qty' => 1,
            'price' => $amount,
            'item_type' => 'product',
            'product_id' => $product->id,
            'course_id' => null,
        ]);
    }

    private function fakeFileContents(string $extension): string
    {
        return match ($extension) {
            'pdf' => "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< >>\n%%EOF",
            'docx' => 'PK fake docx content',
            default => 'plain text',
        };
    }
}
