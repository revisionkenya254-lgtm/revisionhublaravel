<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PastPaperYearListingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
    }

    public function test_student_can_filter_accessible_past_papers_by_year(): void
    {
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $instructor = User::factory()->create(['role' => 'instructor', 'status' => 'active']);
        $paper2026 = $this->makePastPaper($instructor, 'Student Paper 2026', '2026');
        $paper2025 = $this->makePastPaper($instructor, 'Student Paper 2025', '2025');

        $response = $this->actingAs($student)->get(route('student.library', [
            'type' => Product::TYPE_PAST_PAPER,
            'year' => '2026',
        ]));

        $response->assertOk();
        $listedProducts = $response->viewData('items')->getCollection()->pluck('product');
        $this->assertTrue($listedProducts->contains('id', $paper2026->id));
        $this->assertFalse($listedProducts->contains('id', $paper2025->id));
        $response->assertSee('Year 2026');
        $response->assertSee('value="2026" selected', false);
    }

    public function test_instructor_past_paper_listing_filters_only_their_products_by_year(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor', 'status' => 'active']);
        $otherInstructor = User::factory()->create(['role' => 'instructor', 'status' => 'active']);
        $matching = $this->makePastPaper($instructor, 'Instructor Paper 2025', '2025');
        $wrongYear = $this->makePastPaper($instructor, 'Instructor Paper 2024', '2024');
        $otherOwner = $this->makePastPaper($otherInstructor, 'Other Instructor Paper 2025', '2025');

        $response = $this->actingAs($instructor)->get(route('instructor.products.index', [
            'type' => Product::TYPE_PAST_PAPER,
            'year' => '2025',
        ]));

        $response->assertOk();
        $response->assertSee($matching->title);
        $response->assertDontSee($wrongYear->title);
        $response->assertDontSee($otherOwner->title);
        $response->assertSee('Year 2025');
    }

    public function test_admin_can_combine_year_and_status_filters(): void
    {
        $admin = Admin::create([
            'name' => 'Catalog Admin',
            'email' => 'catalog-admin@example.com',
            'password' => 'secret123',
        ]);
        $instructor = User::factory()->create(['role' => 'instructor', 'status' => 'active']);
        $matching = $this->makePastPaper($instructor, 'Approved Paper 2024', '2024');
        $wrongYear = $this->makePastPaper($instructor, 'Approved Paper 2023', '2023');
        $wrongStatus = $this->makePastPaper($instructor, 'Inactive Paper 2024', '2024', 'inactive');

        $response = $this->actingAs($admin, 'admin')->get(route('admin.past-papers.index', [
            'year' => '2024',
            'status' => 'active',
        ]));

        $response->assertOk();
        $listedProducts = $response->viewData('resources')->getCollection();
        $this->assertTrue($listedProducts->contains('id', $matching->id));
        $this->assertFalse($listedProducts->contains('id', $wrongYear->id));
        $this->assertFalse($listedProducts->contains('id', $wrongStatus->id));
        $response->assertSeeText('Year');
    }

    public function test_legacy_past_papers_without_a_year_can_be_filtered(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor', 'status' => 'active']);
        $legacy = $this->makePastPaper($instructor, 'Legacy Paper', null);
        $dated = $this->makePastPaper($instructor, 'Dated Paper', '2026');

        $response = $this->actingAs($instructor)->get(route('instructor.products.index', [
            'type' => Product::TYPE_PAST_PAPER,
            'year' => Product::YEAR_MISSING,
        ]));

        $response->assertOk();
        $response->assertSee($legacy->title);
        $response->assertDontSee($dated->title);
        $response->assertSee('Year not set');
    }

    private function makePastPaper(
        User $instructor,
        string $title,
        ?string $year,
        string $status = 'active'
    ): Product {
        return Product::create([
            'instructor_id' => $instructor->id,
            'type' => Product::TYPE_PAST_PAPER,
            'title' => $title,
            'slug' => Str::slug($title) . '-' . fake()->unique()->randomNumber(5),
            'price' => 0,
            'status' => $status,
            'is_approved' => 'approved',
            'metadata' => $year === null ? [] : ['year' => $year],
        ]);
    }
}

