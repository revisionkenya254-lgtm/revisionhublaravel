<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductQuizAttempt;
use App\Models\User;
use App\Services\ProductQuizBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Order\app\Models\Order;
use Modules\Order\app\Models\OrderItem;
use Tests\TestCase;

class ProductQuizFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
    }

    public function test_instructor_can_create_short_quiz_with_valid_question_count(): void
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);

        $response = $this->actingAs($instructor)->post(route('instructor.quizzes.store'), [
            'title' => 'Biology Drill',
            'price' => 50,
            'status' => 'is_draft',
            'tier' => 'short',
            'difficulty' => 'beginner',
            'attempt_limit' => 2,
            'pass_mark' => 2,
            'questions' => $this->questionPayload(2),
        ]);

        $product = Product::where('title', 'Biology Drill')->first();

        $response->assertRedirect(route('instructor.quizzes.edit', $product->id));
        $this->assertNotNull($product);
        $this->assertSame(Product::TYPE_QUIZ, $product->type);
        $this->assertDatabaseHas('product_quizzes', [
            'product_id' => $product->id,
            'tier' => 'short',
            'question_count_cache' => 2,
        ]);
        $this->assertDatabaseCount('product_quiz_questions', 2);
    }

    public function test_instructor_cannot_create_the_same_quiz_submission_twice(): void
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);

        $payload = [
            'title' => 'Biology Drill',
            'price' => 50,
            'status' => 'is_draft',
            'tier' => 'short',
            'difficulty' => 'beginner',
            'attempt_limit' => 2,
            'pass_mark' => 2,
            'questions' => $this->questionPayload(2),
        ];

        $this->actingAs($instructor)
            ->post(route('instructor.quizzes.store'), $payload)
            ->assertRedirect(route('instructor.quizzes.edit', Product::query()->where('title', 'Biology Drill')->value('id')));

        $product = Product::query()
            ->where('instructor_id', $instructor->id)
            ->where('type', Product::TYPE_QUIZ)
            ->where('title', 'Biology Drill')
            ->firstOrFail();

        $this->actingAs($instructor)
            ->post(route('instructor.quizzes.store'), $payload)
            ->assertRedirect(route('instructor.quizzes.edit', $product->id))
            ->assertSessionHas('warning');

        $this->assertSame(1, Product::query()
            ->where('instructor_id', $instructor->id)
            ->where('type', Product::TYPE_QUIZ)
            ->where('title', 'Biology Drill')
            ->count());
        $this->assertSame(1, $product->quiz()->count());
        $this->assertDatabaseCount('product_quiz_questions', 2);
    }

    public function test_short_quiz_rejects_ten_questions(): void
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);

        $response = $this->from(route('instructor.quizzes.create-tier', 'short'))
            ->actingAs($instructor)
            ->post(route('instructor.quizzes.store'), [
                'title' => 'Too Long Short Quiz',
                'price' => 50,
                'status' => 'is_draft',
                'tier' => 'short',
                'difficulty' => 'intermediate',
                'attempt_limit' => 1,
                'pass_mark' => 5,
                'questions' => $this->questionPayload(10),
            ]);

        $response->assertRedirect(route('instructor.quizzes.create-tier', 'short'));
        $response->assertSessionHasErrors('questions');
    }

    public function test_long_quiz_rejects_sale_price_below_twenty(): void
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);

        $response = $this->from(route('instructor.quizzes.create-tier', 'long'))
            ->actingAs($instructor)
            ->post(route('instructor.quizzes.store'), [
                'title' => 'Underpriced Long Quiz',
                'price' => 19,
                'status' => 'is_draft',
                'tier' => 'long',
                'difficulty' => 'advanced',
                'attempt_limit' => 1,
                'pass_mark' => 6,
                'questions' => $this->questionPayload(11),
            ]);

        $response->assertRedirect(route('instructor.quizzes.create-tier', 'long'));
        $response->assertSessionHasErrors('price');
    }

    public function test_paid_quiz_requires_purchase_before_start(): void
    {
        $student = User::factory()->create();
        $product = $this->makeQuizProduct(price: 25, discount: null);

        $this->actingAs($student)
            ->get(route('product.start-quiz', $product->slug))
            ->assertForbidden();
    }

    public function test_discounted_free_quiz_is_accessible_without_purchase(): void
    {
        $student = User::factory()->create();
        $product = $this->makeQuizProduct(price: 0, discount: null);

        $this->actingAs($student)
            ->get(route('product.start-quiz', $product->slug))
            ->assertOk()
            ->assertSee($product->title);
    }

    public function test_student_can_submit_quiz_and_receive_marked_result(): void
    {
        $student = User::factory()->create();
        $product = $this->makeQuizProduct(price: 0, discount: null, withShortAnswer: true);
        $quiz = $product->quiz()->with('questions.options')->firstOrFail();
        $singleChoiceQuestion = $quiz->questions->firstWhere('question_type', 'single_choice');
        $shortAnswerQuestion = $quiz->questions->firstWhere('question_type', 'short_answer');
        $correctOption = $singleChoiceQuestion->options->firstWhere('is_correct', true);

        $response = $this->actingAs($student)->post(route('product.quiz.submit', $product->slug), [
            'answers' => [
                $singleChoiceQuestion->id => [
                    'selected_option_id' => $correctOption->id,
                ],
                $shortAnswerQuestion->id => [
                    'typed_answer' => 'Nairobi',
                ],
            ],
        ]);

        $attempt = ProductQuizAttempt::latest('id')->first();

        $response->assertRedirect(route('product.quiz.result', [$product->slug, $attempt->id]));
        $this->assertNotNull($attempt);
        $this->assertSame('pass', $attempt->status);
        $this->assertDatabaseHas('product_quiz_attempts', [
            'id' => $attempt->id,
            'product_id' => $product->id,
            'user_id' => $student->id,
            'score' => 2,
            'status' => 'pass',
        ]);
        $this->assertDatabaseHas('product_quiz_attempt_answers', [
            'attempt_id' => $attempt->id,
            'question_id' => $shortAnswerQuestion->id,
            'typed_answer' => 'Nairobi',
            'is_correct' => 1,
        ]);

        $this->actingAs($student)
            ->get(route('product.quiz.result', [$product->slug, $attempt->id]))
            ->assertOk()
            ->assertSee('PASS')
            ->assertSee('Instructor Answer')
            ->assertSee('Nairobi');
    }

    public function test_attempt_history_survives_quiz_question_updates(): void
    {
        $student = User::factory()->create();
        $product = $this->makeQuizProduct(price: 0, discount: null, withShortAnswer: true);
        $quiz = $product->quiz()->with('questions.options')->firstOrFail();
        $singleChoiceQuestion = $quiz->questions->firstWhere('question_type', 'single_choice');
        $shortAnswerQuestion = $quiz->questions->firstWhere('question_type', 'short_answer');
        $correctOption = $singleChoiceQuestion->options->firstWhere('is_correct', true);

        $this->actingAs($student)->post(route('product.quiz.submit', $product->slug), [
            'answers' => [
                $singleChoiceQuestion->id => [
                    'selected_option_id' => $correctOption->id,
                ],
                $shortAnswerQuestion->id => [
                    'typed_answer' => 'Nairobi',
                ],
            ],
        ]);

        $attempt = ProductQuizAttempt::with('answers')->latest('id')->firstOrFail();

        $singleChoiceQuestion->delete();

        $attempt = $attempt->fresh('answers.question');

        $this->assertCount(2, $attempt->answers);
        $this->assertNull($attempt->answers->firstWhere('question_id', $singleChoiceQuestion->id)?->question);
        $this->assertNotNull($attempt->snapshotQuestionById($singleChoiceQuestion->id));
    }

    private function makeQuizProduct(float $price, ?float $discount, bool $withShortAnswer = false): Product
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);

        $product = Product::create([
            'instructor_id' => $instructor->id,
            'type' => Product::TYPE_QUIZ,
            'title' => 'Quiz Product ' . fake()->unique()->word(),
            'slug' => 'quiz-product-' . fake()->unique()->slug(),
            'price' => $price,
            'discount' => $discount,
            'status' => 'active',
            'is_approved' => 'approved',
        ]);

        app(ProductQuizBuilderService::class)->sync($product, [
            'status' => 'active',
            'tier' => 'short',
            'difficulty' => 'beginner',
            'duration_minutes' => 15,
            'attempt_limit' => 2,
            'pass_mark' => 2,
            'questions' => $withShortAnswer
                ? [
                    [
                        'prompt' => 'Capital city of Kenya?',
                        'question_type' => 'single_choice',
                        'marks' => 1,
                        'options' => [
                            ['option_text' => 'Kisumu', 'is_correct' => false],
                            ['option_text' => 'Nairobi', 'is_correct' => true],
                        ],
                    ],
                    [
                        'prompt' => 'Type the capital city of Kenya.',
                        'question_type' => 'short_answer',
                        'marks' => 1,
                        'correct_text_answer' => 'Nairobi',
                    ],
                ]
                : $this->questionPayload(2),
        ], true);

        return $product->fresh(['quiz.questions.options']);
    }

    private function questionPayload(int $count): array
    {
        $questions = [];

        for ($i = 0; $i < $count; $i++) {
            $questions[] = [
                'prompt' => 'Question ' . ($i + 1),
                'question_type' => 'single_choice',
                'marks' => 1,
                'options' => [
                    ['option_text' => 'Answer A', 'is_correct' => true],
                    ['option_text' => 'Answer B', 'is_correct' => false],
                ],
            ];
        }

        return $questions;
    }
}
