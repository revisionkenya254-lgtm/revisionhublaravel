<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductQuizSubmissionRequest;
use App\Models\Product;
use App\Models\ProductQuizAttempt;
use App\Services\ProductQuizAttemptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Order\app\Models\OrderItem;

class ProductQuizController extends Controller
{
    public function __construct(private readonly ProductQuizAttemptService $attemptService)
    {
    }

    public function launch(string $slug): View
    {
        $product = $this->quizProduct($slug);
        abort_unless($this->hasAccess($product), 403);

        $attemptsUsed = $this->attemptService->attemptsUsed($product->quiz, userAuth()->id);

        return view('frontend.pages.product-quiz-launch', compact('product', 'attemptsUsed'));
    }

    public function attempt(string $slug): View|RedirectResponse
    {
        $product = $this->quizProduct($slug);
        abort_unless($this->hasAccess($product), 403);

        $attemptsUsed = $this->attemptService->attemptsUsed($product->quiz, userAuth()->id);

        if ($attemptsUsed >= $product->quiz->attempt_limit) {
            return redirect()->route('product.start-quiz', $product->slug)
                ->with(['alert-type' => 'error', 'messege' => __('You reached the maximum number of attempts for this quiz.')]);
        }

        return view('frontend.pages.product-quiz-attempt', compact('product', 'attemptsUsed'));
    }

    public function submit(ProductQuizSubmissionRequest $request, string $slug): RedirectResponse
    {
        $product = $this->quizProduct($slug);
        abort_unless($this->hasAccess($product), 403);

        $attemptsUsed = $this->attemptService->attemptsUsed($product->quiz, userAuth()->id);

        if ($attemptsUsed >= $product->quiz->attempt_limit) {
            return redirect()->route('product.start-quiz', $product->slug)
                ->with(['alert-type' => 'error', 'messege' => __('You reached the maximum number of attempts for this quiz.')]);
        }

        $attempt = $this->attemptService->submit($product, $product->quiz, userAuth()->id, $request->validated('answers'));

        return redirect()->route('product.quiz.result', [$product->slug, $attempt->id]);
    }

    public function result(string $slug, int $attemptId): View
    {
        $product = $this->quizProduct($slug);
        abort_unless($this->hasAccess($product), 403);
        $attempt = ProductQuizAttempt::findOrFail($attemptId);
        abort_unless((int) $attempt->product_id === (int) $product->id && (int) $attempt->user_id === (int) userAuth()->id, 404);

        $attempt->load(['answers.question.options', 'answers.selectedOption', 'quiz']);

        return view('frontend.pages.product-quiz-result', compact('product', 'attempt'));
    }

    private function quizProduct(string $slug): Product
    {
        return Product::approved()
            ->where('type', Product::TYPE_QUIZ)
            ->whereHas('quiz')
            ->with(['quiz.questions.options'])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    private function hasAccess(Product $product): bool
    {
        if (!auth('web')->check()) {
            return false;
        }

        if (hasActiveSubscription()) {
            return true;
        }

        if ((float) $product->effective_price === 0.0) {
            return true;
        }

        return OrderItem::where('item_type', 'product')
            ->where('product_id', $product->id)
            ->whereHas('order', fn ($q) => $q->where('buyer_id', userAuth()->id)->where('payment_status', 'paid'))
            ->exists();
    }
}
