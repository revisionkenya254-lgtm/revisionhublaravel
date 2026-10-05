<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductReviewController extends Controller
{
    private array $types = [
        Product::TYPE_PAST_PAPER => [
            'route' => 'admin.past-papers',
            'title' => 'Past Paper Reviews',
            'singular' => 'Past Paper',
        ],
        Product::TYPE_PREDICTION => [
            'route' => 'admin.predictions',
            'title' => 'Prediction Reviews',
            'singular' => 'Prediction',
        ],
        Product::TYPE_NOTE => [
            'route' => 'admin.notes',
            'title' => 'Note Reviews',
            'singular' => 'Note',
        ],
        Product::TYPE_QUIZ => [
            'route' => 'admin.quizzes',
            'title' => 'Quiz Reviews',
            'singular' => 'Quiz',
        ],
    ];

    public function index(Request $request, string $type): View
    {
        $meta = $this->typeMeta($type);

        $query = ProductReview::query()
            ->with(['product:id,title,type', 'user:id,name'])
            ->whereHas('product', fn ($q) => $q->where('type', $type))
            ->whereHas('user');

        $query->when($request->keyword, function ($q) use ($request) {
            $q->where(function ($nestedQuery) use ($request) {
                $nestedQuery->whereHas('product', fn ($nested) => $nested->where('title', 'like', "%{$request->keyword}%"))
                    ->orWhereHas('user', fn ($nested) => $nested->where('name', 'like', "%{$request->keyword}%"));
            });
        });
        $query->when($request->status !== null && $request->status !== '', fn ($q) => $q->where('status', $request->status));

        $orderBy = $request->order_by == 1 ? 'asc' : 'desc';
        $reviews = $request->get('par-page') == 'all'
            ? $query->orderBy('id', $orderBy)->get()
            : $query->orderBy('id', $orderBy)->paginate($request->get('par-page') ?? null)->withQueryString();

        return view('admin.product-reviews.index', compact('reviews', 'meta', 'type'));
    }

    public function show(string $type, ProductReview $review): View
    {
        $review->load([
            'product.category.translation',
            'product.latestAiDocument.processingLogs',
            'product.note.topics',
            'product.quiz.questions.options',
            'user',
        ]);
        $this->ensureType($type, $review);
        $meta = $this->typeMeta($type);

        return view('admin.product-reviews.show', compact('review', 'meta', 'type'));
    }

    public function update(Request $request, string $type, ProductReview $review): RedirectResponse
    {
        $this->ensureType($type, $review);
        $meta = $this->typeMeta($type);

        $request->validate(['status' => 'required|in:0,1']);
        $review->update(['status' => $request->status]);

        return redirect()->route($meta['route'] . '.reviews.index')->with([
            'alert-type' => 'success',
            'messege' => __('Updated successfully'),
        ]);
    }

    public function destroy(string $type, ProductReview $review): RedirectResponse
    {
        $this->ensureType($type, $review);
        $meta = $this->typeMeta($type);

        $review->delete();

        return redirect()->route($meta['route'] . '.reviews.index')->with([
            'alert-type' => 'success',
            'messege' => __('Deleted successfully'),
        ]);
    }

    private function typeMeta(string $type): array
    {
        abort_unless(isset($this->types[$type]), 404);

        $meta = $this->types[$type];
        $meta['delete_url'] = route($meta['route'] . '.reviews.index');

        return $meta;
    }

    private function ensureType(string $type, ProductReview $review): void
    {
        abort_unless($review->product?->type === $type, 404);
    }
}
