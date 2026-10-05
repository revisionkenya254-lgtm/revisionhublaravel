<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorProductSlugTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
    }

    public function test_instructor_product_publishing_keeps_unique_slugs_for_distinct_submissions_with_same_title(): void
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);

        $payload = [
            'type' => Product::TYPE_PAST_PAPER,
            'title' => 'Revision Planner',
            'price' => 25,
            'status' => 'is_draft',
        ];

        $this->actingAs($instructor)
            ->post(route('instructor.products.store'), $payload)
            ->assertRedirect(route('instructor.products.index', ['type' => Product::TYPE_PAST_PAPER]));

        $payload['price'] = 30;

        $this->actingAs($instructor)
            ->post(route('instructor.products.store'), $payload)
            ->assertRedirect(route('instructor.products.index', ['type' => Product::TYPE_PAST_PAPER]));

        $products = Product::query()
            ->where('instructor_id', $instructor->id)
            ->where('title', 'Revision Planner')
            ->orderBy('id')
            ->get(['slug']);

        $this->assertCount(2, $products);
        $this->assertSame('revision-planner', $products[0]->slug);
        $this->assertSame('revision-planner-1', $products[1]->slug);
    }

    public function test_instructor_product_publishing_blocks_exact_duplicate_submissions(): void
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);

        $payload = [
            'type' => Product::TYPE_PAST_PAPER,
            'title' => 'Revision Planner',
            'price' => 25,
            'status' => 'is_draft',
        ];

        $this->actingAs($instructor)
            ->post(route('instructor.products.store'), $payload)
            ->assertRedirect(route('instructor.products.index', ['type' => Product::TYPE_PAST_PAPER]));

        $firstProduct = Product::query()
            ->where('instructor_id', $instructor->id)
            ->where('type', Product::TYPE_PAST_PAPER)
            ->where('title', 'Revision Planner')
            ->firstOrFail();

        $this->actingAs($instructor)
            ->post(route('instructor.products.store'), $payload)
            ->assertRedirect(route('instructor.products.edit', $firstProduct->id))
            ->assertSessionHas('warning');

        $this->assertSame(1, Product::query()
            ->where('instructor_id', $instructor->id)
            ->where('type', Product::TYPE_PAST_PAPER)
            ->where('title', 'Revision Planner')
            ->count());
    }

    public function test_instructor_product_publishing_skips_soft_deleted_slug_collisions(): void
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);

        $deletedProduct = Product::create([
            'instructor_id' => $instructor->id,
            'type' => Product::TYPE_PAST_PAPER,
            'title' => 'Auditing July 2012 KNEC Certificate',
            'slug' => 'auditing-july-2012-knec-certificate',
            'price' => 30,
            'status' => 'active',
            'is_approved' => 'approved',
        ]);

        $deletedProduct->delete();

        $this->actingAs($instructor)
            ->post(route('instructor.products.store'), [
                'type' => Product::TYPE_PAST_PAPER,
                'title' => 'Auditing July 2012 KNEC Certificate',
                'price' => 30,
                'status' => 'is_draft',
            ])
            ->assertRedirect(route('instructor.products.index', ['type' => Product::TYPE_PAST_PAPER]));

        $newProduct = Product::withTrashed()
            ->where('instructor_id', $instructor->id)
            ->where('title', 'Auditing July 2012 KNEC Certificate')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('auditing-july-2012-knec-certificate-1', $newProduct->slug);
    }
}
