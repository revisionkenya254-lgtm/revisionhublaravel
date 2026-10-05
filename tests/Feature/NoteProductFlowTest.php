<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\ProductNoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Modules\Order\app\Models\Order;
use Modules\Order\app\Models\OrderItem;
use Tests\TestCase;

class NoteProductFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
    }

    public function test_note_dashboard_create_page_renders_step_one_ui(): void
    {
        $user = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);

        $product = $this->makeNoteProduct(0, $user);

        $this->actingAs($user)
            ->get(route('instructor.products.create', ['type' => 'note']))
            ->assertOk()
            ->assertSee('Create New Note')
            ->assertSee('Basic Information')
            ->assertSee('Preview Note')
            ->assertSee('Topic');

        $this->actingAs($user)
            ->get(route('instructor.products.create', ['type' => 'note', 'step' => 2]))
            ->assertOk()
            ->assertSee('Note Content')
            ->assertSee('Add Chapter')
            ->assertSee('No chapters yet');

        $this->actingAs($user)
            ->get(route('instructor.products.create', ['type' => 'note', 'step' => 3]))
            ->assertOk()
            ->assertSee('Attachments')
            ->assertSeeText('Drag & drop files here')
            ->assertSee('No files uploaded yet');

        $this->actingAs($user)
            ->get(route('instructor.products.create', ['type' => 'note', 'step' => 4]))
            ->assertOk()
            ->assertSee('Note Settings')
            ->assertSee('SEO / Additional Info')
            ->assertSee('Allow Download');

        $this->actingAs($user)
            ->get(route('instructor.products.create', ['type' => 'note', 'step' => 5]))
            ->assertOk()
            ->assertSee('Review Note')
            ->assertSee('Content Summary')
            ->assertSee('Send for Review');

        $this->actingAs($user)
            ->get(route('instructor.products.edit', $product->id))
            ->assertRedirect(route('product.show', $product->slug));
    }

    public function test_note_wizard_publish_saves_product_and_note_structure(): void
    {
        $user = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);

        $payload = [
            'note' => [
                'title' => 'Quadratic Equations - Complete Notes',
                'description' => 'Detailed notes covering quadratic equations.',
                'education_level' => 'Senior School',
                'class_grade' => 'Form 4',
                'exam_category' => 'KCSE',
                'subject' => 'Mathematics',
                'topic' => 'Algebra',
                'sub_topic' => 'Quadratic Equations',
                'tags' => ['KCSE', 'Mathematics', 'Algebra', 'Form 4'],
                'access_type' => 'paid',
                'price' => '120.00',
                'preview_pages' => '3 Pages',
                'language' => 'English',
                'status' => 'active',
                'note_visibility' => 'public',
            ],
            'curriculum' => [[
                'type' => 'chapter',
                'title' => 'Introduction to Quadratic Equations',
                'is_published' => true,
                'children' => [[
                    'type' => 'topic',
                    'title' => 'Definition and Examples',
                    'is_published' => true,
                    'children' => [[
                        'type' => 'reading',
                        'title' => 'Reading Text',
                        'is_published' => true,
                        'blocks' => [[
                            'type' => 'rich_text',
                            'content' => ['html' => '<p>Hello note</p>'],
                        ]],
                    ]],
                ]],
            ]],
            'resources' => [[
                'title' => 'Quadratic_Equations_Notes.pdf',
                'resource_type' => 'file',
                'url_or_path' => 'Quadratic_Equations_Notes.pdf',
                'size' => '2.45 MB',
            ]],
            'settings' => [
                'allow_download' => true,
                'add_to_bundle' => true,
                'featured_note' => true,
                'allow_comments' => true,
                'visibility' => 'public',
                'meta_title' => 'Quadratic Equations Notes - Form 4 Mathematics',
                'meta_description' => 'Comprehensive notes on quadratic equations.',
                'keywords' => 'quadratic equations, form 4, algebra, kcse, math notes',
                'sort_order' => 10,
            ],
        ];

        $this->actingAs($user)
            ->post(route('instructor.products.store'), [
                'type' => 'note',
                'title' => $payload['note']['title'],
                'description' => $payload['note']['description'],
                'price' => $payload['note']['price'],
                'status' => 'active',
                'note_payload' => json_encode($payload),
            ])
            ->assertRedirect(route('instructor.products.index', ['type' => 'note']));

        $product = Product::where('title', $payload['note']['title'])->firstOrFail();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'type' => Product::TYPE_NOTE,
            'title' => $payload['note']['title'],
        ]);

        $this->assertDatabaseHas('product_notes', [
            'product_id' => $product->id,
            'difficulty' => null,
        ]);
    }

    public function test_note_attachment_upload_and_delete_flow_works(): void
    {
        $user = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);

        $file = UploadedFile::fake()->create('Quadratic_Equations_Notes.pdf', 1024, 'application/pdf');

        $uploadResponse = $this->actingAs($user)->postJson(route('instructor.products.note-attachments.upload'), [
            'files' => [$file],
        ]);

        $uploadResponse->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'files')
            ->assertJsonPath('files.0.title', 'Quadratic_Equations_Notes.pdf');

        $uploadedPath = $uploadResponse->json('files.0.url_or_path');

        $this->assertNotEmpty($uploadedPath);
        $this->assertFileExists(public_path($uploadedPath));

        $this->actingAs($user)
            ->deleteJson(route('instructor.products.note-attachments.destroy'), [
                'path' => $uploadedPath,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertFileDoesNotExist(public_path($uploadedPath));
    }

    public function test_free_note_reader_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $product = $this->makeNoteProduct(0);

        $response = $this->actingAs($user)->get(route('product.read-note', $product->slug));

        $response->assertOk();
        $response->assertSee('Chapter One');
        $response->assertSee('Reading Text');
        $response->assertSee($product->title);
    }

    public function test_paid_note_reader_forbids_user_without_purchase(): void
    {
        $user = User::factory()->create();
        $product = $this->makeNoteProduct(25);

        $this->actingAs($user)
            ->get(route('product.read-note', $product->slug))
            ->assertForbidden();
    }

    public function test_paid_note_reader_allows_purchased_user(): void
    {
        $user = User::factory()->create();
        $product = $this->makeNoteProduct(25);

        $order = Order::create([
            'buyer_id' => $user->id,
            'payment_status' => 'paid',
            'payment_method' => 'test',
            'paid_amount' => 25,
            'payable_amount' => 25,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'qty' => 1,
            'price' => 25,
            'item_type' => 'product',
            'product_id' => $product->id,
            'course_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('product.read-note', $product->slug))
            ->assertOk()
            ->assertSee('Reading Text');
    }

    public function test_note_progress_endpoint_tracks_reading_nodes(): void
    {
        $user = User::factory()->create();
        $product = $this->makeNoteProduct(0);
        $readingNode = $product->note->topics()->where('node_type', 'reading')->firstOrFail();

        $this->actingAs($user)
            ->postJson(route('product.note-progress', $product->slug), [
                'node_id' => $readingNode->id,
            ])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('product_note_progress', [
            'product_id' => $product->id,
            'topic_id' => $readingNode->id,
        ]);
    }

    private function makeNoteProduct(float $price, ?User $owner = null): Product
    {
        $instructor = $owner ?? User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);

        $product = Product::create([
            'instructor_id' => $instructor->id,
            'type' => Product::TYPE_NOTE,
            'title' => 'Study Note ' . fake()->unique()->word(),
            'slug' => 'study-note-' . fake()->unique()->slug(),
            'price' => $price,
            'status' => 'active',
            'is_approved' => 'approved',
        ]);

        app(ProductNoteService::class)->sync($product, [
            'note' => [
                'excerpt' => 'Quick summary',
                'intro_html' => '<p>Reader intro</p>',
                'estimated_read_minutes' => 12,
                'difficulty' => 'beginner',
                'show_resources' => true,
                'show_discussion' => true,
                'prerequisites' => [],
            ],
            'curriculum' => [[
                'type' => 'chapter',
                'title' => 'Chapter One',
                'summary' => 'Top level section',
                'estimated_read_minutes' => null,
                'is_published' => true,
                'children' => [[
                    'type' => 'topic',
                    'title' => 'Getting Started',
                    'summary' => 'Start here',
                    'estimated_read_minutes' => 5,
                    'is_published' => true,
                    'children' => [[
                        'type' => 'reading',
                        'title' => 'Reading Text',
                        'estimated_read_minutes' => 5,
                        'is_published' => true,
                        'blocks' => [
                            [
                                'type' => 'rich_text',
                                'content' => ['html' => '<p>Hello note</p>'],
                            ],
                        ],
                    ]],
                ]],
            ]],
            'resources' => [],
        ]);

        return $product->fresh(['note.topics', 'note.resources']);
    }
}
