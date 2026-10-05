<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductQuiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Order\app\Models\Order;
use Modules\Order\app\Models\OrderItem;
use Tests\TestCase;

class StudentDashboardProductCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
    }

    public function test_student_dashboard_and_library_expose_all_product_types(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
        ]);

        $courseProduct = $this->makeProduct(Product::TYPE_COURSE, 'Course Resource');
        $noteProduct = $this->makeProduct(Product::TYPE_NOTE, 'Note Resource');
        $pastPaperProduct = $this->makeProduct(Product::TYPE_PAST_PAPER, 'Past Paper Resource', 25);
        $predictionProduct = $this->makeProduct(Product::TYPE_PREDICTION, 'Prediction Resource');
        $quizProduct = $this->makeQuizProduct('Quiz Resource');

        $this->purchaseProduct($student, $pastPaperProduct, 25);

        $dashboard = $this->actingAs($student)->get(route('student.dashboard'));
        $dashboard->assertOk();
        $dashboard->assertSee('Product Coverage');
        $dashboard->assertSee('My Products');
        $dashboard->assertSee('Course Products');
        $dashboard->assertSee('Notes');
        $dashboard->assertSee('Past Papers');
        $dashboard->assertSee('Predictions');
        $dashboard->assertSee('Quizzes');
        $dashboard->assertSee('AI Workspace');
        $dashboard->assertSee('AI Chat');
        $dashboard->assertSee('AI Credits');
        $dashboard->assertSee('Notes');
        $dashboard->assertSee('Past Papers');
        $dashboard->assertSee('Predictions');
        $dashboard->assertSee('Quizzes');
        $dashboard->assertSee($courseProduct->title);
        $dashboard->assertSee($noteProduct->title);
        $dashboard->assertSee($pastPaperProduct->title);
        $dashboard->assertSee($predictionProduct->title);
        $dashboard->assertSee($quizProduct->title);
        $dashboard->assertSee(route('student.library'), false);
        $dashboard->assertSee(route('student.quiz-attempts'), false);
        $dashboard->assertSee(route('student.ai-chat.index'), false);
        $dashboard->assertSee(route('ai-chat.credits'), false);

        $library = $this->actingAs($student)->get(route('student.library'));
        $library->assertOk();
        $library->assertSee($courseProduct->title);
        $library->assertSee($noteProduct->title);
        $library->assertSee($pastPaperProduct->title);
        $library->assertSee($predictionProduct->title);
        $library->assertSee($quizProduct->title);
        $library->assertSee(route('product.show', $courseProduct->slug), false);
        $library->assertSee(route('product.read-note', $noteProduct->slug), false);
        $library->assertSee(route('product.read-document', $pastPaperProduct->slug), false);
        $library->assertSee(route('product.read-document', $predictionProduct->slug), false);
        $library->assertSee(route('product.start-quiz', $quizProduct->slug), false);
        $library->assertSee('Free');
        $library->assertSee('Purchased');
    }

    private function makeProduct(string $type, string $title, float $price = 0): Product
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);

        return Product::create([
            'instructor_id' => $instructor->id,
            'type' => $type,
            'title' => $title,
            'slug' => Str::slug($title) . '-' . fake()->unique()->randomNumber(5),
            'price' => $price,
            'status' => 'active',
            'is_approved' => 'approved',
        ]);
    }

    private function makeQuizProduct(string $title): Product
    {
        $product = $this->makeProduct(Product::TYPE_QUIZ, $title);

        ProductQuiz::create([
            'product_id' => $product->id,
            'tier' => 'short',
            'difficulty' => 'beginner',
            'duration_minutes' => 15,
            'attempt_limit' => 3,
            'pass_mark' => 5,
            'question_count_cache' => 3,
        ]);

        return $product;
    }

    private function purchaseProduct(User $student, Product $product, float $price): void
    {
        $order = Order::create([
            'buyer_id' => $student->id,
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_method' => 'manual',
            'paid_amount' => $price,
            'payable_amount' => $price,
            'payable_currency' => 'KES',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'qty' => 1,
            'price' => $price,
            'item_type' => 'product',
            'product_id' => $product->id,
            'course_id' => null,
        ]);
    }
}
