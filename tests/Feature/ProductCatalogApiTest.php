<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Course\app\Models\CourseCategory;
use Tests\TestCase;

class ProductCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_are_grouped_by_type(): void
    {
        $instructor = User::factory()->create();

        $pastPaper = $this->createProduct($instructor, Product::TYPE_PAST_PAPER, 'past-paper');
        $note = $this->createProduct($instructor, Product::TYPE_NOTE, 'note');
        $this->createProduct($instructor, Product::TYPE_QUIZ, 'draft-quiz', 'is_draft');

        $response = $this->getJson('/api/catalog/products');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.past_paper.0.slug', $pastPaper->slug)
            ->assertJsonPath('data.note.0.slug', $note->slug)
            ->assertJsonCount(0, 'data.quiz');
    }

    public function test_products_can_be_filtered_by_type(): void
    {
        $instructor = User::factory()->create();
        $note = $this->createProduct($instructor, Product::TYPE_NOTE, 'revision-note');
        $this->createProduct($instructor, Product::TYPE_PAST_PAPER, 'revision-paper');

        $response = $this->getJson('/api/catalog/products/note');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $note->slug)
            ->assertJsonPath('data.0.type', Product::TYPE_NOTE);
    }

    public function test_product_results_are_bounded_and_include_pagination_metadata(): void
    {
        $instructor = User::factory()->create();

        foreach (range(1, 3) as $number) {
            $this->createProduct($instructor, Product::TYPE_NOTE, "bounded-note-{$number}");
        }

        $response = $this->getJson('/api/catalog/products/note?limit=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonPath('pagination.total', 3)
            ->assertJsonPath('pagination.last_page', 2);
    }

    public function test_metadata_only_products_are_filtered_in_sql_by_catalog_path(): void
    {
        $instructor = User::factory()->create();
        $product = $this->createProduct($instructor, Product::TYPE_QUIZ, 'metadata-only-quiz');
        $product->update(['metadata' => [
            'catalog_identity' => [
                'main_category' => 'senior-school',
                'category' => 'form-4',
                'subject' => 'mathematics',
            ],
        ]]);

        $this->getJson('/api/catalog/products/quiz?category_path=senior-school/form-4/mathematics')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'metadata-only-quiz');
    }

    public function test_products_without_categories_do_not_trigger_relationship_queries_per_item(): void
    {
        $instructor = User::factory()->create();
        foreach (range(1, 5) as $number) {
            $this->createProduct($instructor, Product::TYPE_NOTE, "query-count-note-{$number}");
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->getJson('/api/catalog/products/note?limit=5')->assertOk();

        $this->assertLessThanOrEqual(3, count(DB::getQueryLog()));
    }

    public function test_products_can_be_filtered_by_a_grandchild_category(): void
    {
        $instructor = User::factory()->create();
        $root = CourseCategory::create(['slug' => 'secondary-school']);
        $child = CourseCategory::create(['slug' => 'form-one', 'parent_id' => $root->id]);
        $grandchild = CourseCategory::create(['slug' => 'mathematics', 'parent_id' => $child->id]);

        $note = $this->createProduct($instructor, Product::TYPE_NOTE, 'mathematics-note', 'active', $grandchild->id);
        $this->createProduct($instructor, Product::TYPE_PAST_PAPER, 'form-one-paper', 'active', $child->id);

        $otherRoot = CourseCategory::create(['slug' => 'junior-school']);
        $otherParent = CourseCategory::create(['slug' => 'grade-seven', 'parent_id' => $otherRoot->id]);
        $otherMathematics = CourseCategory::create(['slug' => 'mathematics', 'parent_id' => $otherParent->id]);
        $this->createProduct($instructor, Product::TYPE_QUIZ, 'junior-mathematics-quiz', 'active', $otherMathematics->id);

        $response = $this->getJson('/api/catalog/products?category_path=secondary-school/form-one/mathematics');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.note.0.slug', $note->slug)
            ->assertJsonPath('data.note.0.category.slug', 'mathematics')
            ->assertJsonPath('data.note.0.category.parent.slug', 'form-one')
            ->assertJsonPath('data.note.0.category.parent.grandparent.slug', 'secondary-school')
            ->assertJsonCount(0, 'data.past_paper')
            ->assertJsonCount(0, 'data.quiz');
    }

    public function test_category_apis_only_include_grandchildren_with_active_approved_content(): void
    {
        $instructor = User::factory()->create();
        $root = CourseCategory::create(['slug' => 'lower-primary']);
        CourseCategory::create(['slug' => 'tvet']);
        $gradeOne = CourseCategory::create(['slug' => 'grade-1', 'parent_id' => $root->id]);
        $gradeTwo = CourseCategory::create(['slug' => 'grade-2', 'parent_id' => $root->id]);
        $gradeOneMathematics = CourseCategory::create(['slug' => 'mathematics', 'parent_id' => $gradeOne->id]);
        $gradeOneEnglish = CourseCategory::create(['slug' => 'english', 'parent_id' => $gradeOne->id]);
        $gradeOneNumeracy = CourseCategory::create(['slug' => 'numeracy', 'parent_id' => $gradeOne->id]);
        $gradeTwoMathematics = CourseCategory::create(['slug' => 'mathematics', 'parent_id' => $gradeTwo->id]);

        $mathematics = $this->createProduct($instructor, Product::TYPE_NOTE, 'active-grade-one-mathematics', 'active', $gradeOneMathematics->id);
        $mathematics->update(['metadata' => ['subject' => 'English']]);
        $this->createProduct($instructor, Product::TYPE_NOTE, 'active-grade-one-numeracy', 'active', $gradeOneNumeracy->id);
        $this->createProduct($instructor, Product::TYPE_NOTE, 'inactive-grade-one-english', 'inactive', $gradeOneEnglish->id);
        $this->createProduct($instructor, Product::TYPE_NOTE, 'inactive-grade-two-mathematics', 'inactive', $gradeTwoMathematics->id);
        $metadataOnly = $this->createProduct($instructor, Product::TYPE_QUIZ, 'metadata-only-grade-two-science');
        $metadataOnly->update(['metadata' => [
            'education_level' => 'Lower Primary',
            'class_grade' => 'Grade 2',
            'subject' => 'Science & Technology',
        ]]);

        $response = $this->getJson('/api/catalog/categories/lower-primary/subcategories');

        $response->assertOk();
        $grades = collect($response->json('data'))->keyBy('slug');

        $this->assertSame(['mathematics', 'numeracy'], collect($grades['grade-1']['children'])->pluck('slug')->all());
        $this->assertSame(['science-technology'], collect($grades['grade-2']['children'])->pluck('slug')->all());

        $this->createProduct($instructor, Product::TYPE_QUIZ, 'active-grade-one-english', 'active', $gradeOneEnglish->id);

        $refreshedResponse = $this->getJson('/api/catalog/categories/lower-primary/subcategories');
        $refreshedGrades = collect($refreshedResponse->json('data'))->keyBy('slug');

        $this->assertSame(
            ['mathematics', 'english', 'numeracy'],
            collect($refreshedGrades['grade-1']['children'])->pluck('slug')->all()
        );

        $menuResponse = $this->getJson('/api/bootstrap/menu');
        $menuResponse->assertOk();
        $lowerPrimary = collect($menuResponse->json('data.categories'))->firstWhere('slug', 'lower-primary');
        $menuGrades = collect($lowerPrimary['children'])->keyBy('slug');

        $this->assertSame(['mathematics', 'english', 'numeracy'], collect($menuGrades['grade-1']['children'])->pluck('slug')->all());
        $this->assertSame(['science-technology'], collect($menuGrades['grade-2']['children'])->pluck('slug')->all());
    }

    public function test_an_active_approved_product_can_be_retrieved_by_type_and_slug(): void
    {
        $instructor = User::factory()->create();
        $root = CourseCategory::create(['slug' => 'high']);
        $parent = CourseCategory::create(['slug' => 'kcse', 'parent_id' => $root->id]);
        $grandchild = CourseCategory::create(['slug' => 'mathematics', 'parent_id' => $parent->id]);
        $product = $this->createProduct(
            $instructor,
            Product::TYPE_PAST_PAPER,
            'kcse-mathematics-paper',
            'active',
            $grandchild->id
        );

        $response = $this->getJson(
            '/api/catalog/products/past_paper/kcse-mathematics-paper?category_path=high/kcse/mathematics'
        );

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.slug', $product->slug)
            ->assertJsonPath('data.type', Product::TYPE_PAST_PAPER);
    }

    public function test_a_product_preview_can_be_retrieved_without_a_category_path(): void
    {
        $instructor = User::factory()->create();
        $product = $this->createProduct($instructor, Product::TYPE_PAST_PAPER, 'kcse-mathematics-paper');

        $this->getJson('/api/catalog/products/past_paper/kcse-mathematics-paper')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.slug', $product->slug)
            ->assertJsonPath('data.type', Product::TYPE_PAST_PAPER);
    }

    public function test_a_product_preview_rejects_a_supplied_non_matching_category_path(): void
    {
        $instructor = User::factory()->create();
        $root = CourseCategory::create(['slug' => 'high']);
        $parent = CourseCategory::create(['slug' => 'kcse', 'parent_id' => $root->id]);
        $category = CourseCategory::create(['slug' => 'mathematics', 'parent_id' => $parent->id]);
        $this->createProduct($instructor, Product::TYPE_PAST_PAPER, 'kcse-mathematics-paper', 'active', $category->id);

        $this->getJson('/api/catalog/products/past_paper/kcse-mathematics-paper?category_path=junior-school/grade-seven/mathematics')
            ->assertNotFound();
    }

    private function createProduct(
        User $instructor,
        string $type,
        string $slug,
        string $status = 'active',
        ?int $categoryId = null
    ): Product
    {
        return Product::create([
            'instructor_id' => $instructor->id,
            'category_id' => $categoryId,
            'type' => $type,
            'title' => str($slug)->replace('-', ' ')->title(),
            'slug' => $slug,
            'price' => 0,
            'status' => $status,
            'is_approved' => 'approved',
        ]);
    }
}
