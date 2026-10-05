<?php

namespace App\Services;

use App\Models\AiChatConversation;
use App\Models\Product;
use App\Models\ProductNoteProgress;
use App\Models\ProductQuizAttempt;
use App\Services\Ai\AiCreditLedgerService;
use Illuminate\Support\Collection;
use Modules\Order\app\Models\OrderItem;

class StudentDashboardSidebarService
{
    public function sidebarSections(?int $itemsPerSection = 3): Collection
    {
        if (! auth()->check()) {
            return collect();
        }

        $user = userAuth();
        $subscriptionActive = hasActiveSubscription($user);

        $purchasedOrderItems = OrderItem::with(['order:id,buyer_id,payment_status,created_at', 'product.category.translation', 'product.course'])
            ->where('item_type', 'product')
            ->whereHas('order', fn ($q) => $q->where('buyer_id', $user->id)->where('payment_status', 'paid'))
            ->orderByDesc('id')
            ->get()
            ->keyBy('product_id');

        $latestNoteProgress = ProductNoteProgress::query()
            ->where('user_id', $user->id)
            ->orderByDesc('last_read_at')
            ->get()
            ->groupBy('product_id')
            ->map(fn (Collection $items) => $items->first());

        $latestQuizAttempts = ProductQuizAttempt::query()
            ->where('user_id', $user->id)
            ->orderByDesc('submitted_at')
            ->get()
            ->groupBy('product_id')
            ->map(fn (Collection $items) => $items->first());

        $products = Product::approved()
            ->with(['category.translation', 'course', 'note', 'quiz'])
            ->get()
            ->filter(function (Product $product) use ($subscriptionActive, $purchasedOrderItems) {
                return $subscriptionActive
                    || (float) $product->effective_price === 0.0
                    || $purchasedOrderItems->has($product->id);
            })
            ->groupBy('type');

        $sectionOrder = [
            Product::TYPE_PAST_PAPER,
            Product::TYPE_PREDICTION,
            Product::TYPE_NOTE,
            Product::TYPE_QUIZ,
            Product::TYPE_COURSE,
        ];

        $sections = collect($sectionOrder)
            ->map(function (string $type) use ($products, $subscriptionActive, $purchasedOrderItems, $latestNoteProgress, $latestQuizAttempts, $itemsPerSection) {
                $typeProducts = $products->get($type, collect());

                $sectionProducts = $typeProducts
                    ->sortByDesc('updated_at')
                    ->take($itemsPerSection)
                    ->map(function (Product $product) use ($type, $subscriptionActive, $purchasedOrderItems, $latestNoteProgress, $latestQuizAttempts) {
                        $orderItem = $purchasedOrderItems->get($product->id);
                        $noteProgress = $latestNoteProgress->get($product->id);
                        $quizAttempt = $latestQuizAttempts->get($product->id);
                        $accessSource = $subscriptionActive
                            ? 'subscription'
                            : (($orderItem !== null) ? 'purchase' : 'free');

                        return (object) [
                            'product' => $product,
                            'type' => $type,
                            'type_label' => $product->type_label,
                            'icon' => $this->productIcon($type),
                            'tone' => $this->productTone($type),
                            'detail_url' => $this->productDetailUrl($product),
                            'access_source_label' => $this->accessSourceLabel($accessSource),
                            'progress_label' => $this->resolveProductProgressLabel($product, $noteProgress, $quizAttempt),
                            'progress_percent' => $this->resolveProductProgressPercent($product, $noteProgress, $quizAttempt),
                        ];
                    })
                    ->values();

                $firstItem = $sectionProducts->first();

                return (object) [
                    'type' => $type,
                    'label' => $this->sectionLabel($type, $firstItem?->type_label),
                    'icon' => $firstItem?->icon ?? $this->productIcon($type),
                    'tone' => $firstItem?->tone ?? $this->productTone($type),
                    'count' => $typeProducts->count(),
                    'items' => $sectionProducts,
                ];
            })
            ->values();

        return $sections->push($this->buildAiSection($user))->values();
    }

    private function resolveProductProgressPercent(Product $product, ?ProductNoteProgress $noteProgress = null, ?ProductQuizAttempt $quizAttempt = null): float
    {
        return match ($product->type) {
            Product::TYPE_NOTE => (float) ($noteProgress?->completion_percent ?? 0),
            Product::TYPE_QUIZ => (float) ($quizAttempt?->percentage ?? 0),
            default => 0.0,
        };
    }

    private function resolveProductProgressLabel(Product $product, ?ProductNoteProgress $noteProgress = null, ?ProductQuizAttempt $quizAttempt = null): string
    {
        return match ($product->type) {
            Product::TYPE_NOTE => $noteProgress?->completion_percent !== null
                ? number_format((float) $noteProgress->completion_percent, 1) . '% ' . __('read')
                : __('Ready to read'),
            Product::TYPE_QUIZ => $quizAttempt?->percentage !== null
                ? number_format((float) $quizAttempt->percentage, 1) . '% ' . __('score')
                : __('Ready to start'),
            Product::TYPE_PAST_PAPER, Product::TYPE_PREDICTION => __('Ready to read'),
            Product::TYPE_COURSE => __('Ready to open'),
            default => __('Open'),
        };
    }

    private function productIcon(string $type): string
    {
        return match ($type) {
            Product::TYPE_NOTE => 'fas fa-sticky-note',
            Product::TYPE_PAST_PAPER => 'fas fa-file-alt',
            Product::TYPE_PREDICTION => 'fas fa-bullseye',
            Product::TYPE_QUIZ => 'fas fa-poll',
            Product::TYPE_COURSE => 'flaticon-mortarboard',
            default => 'fas fa-layer-group',
        };
    }

    private function productTone(string $type): string
    {
        return match ($type) {
            Product::TYPE_NOTE => 'pink',
            Product::TYPE_PAST_PAPER => 'green',
            Product::TYPE_PREDICTION => 'orange',
            Product::TYPE_QUIZ => 'violet',
            Product::TYPE_COURSE => 'blue',
            default => 'slate',
        };
    }

    private function sectionLabel(string $type, ?string $fallback = null): string
    {
        return $fallback ?? match ($type) {
            Product::TYPE_NOTE => __('Notes'),
            Product::TYPE_PAST_PAPER => __('Past Papers'),
            Product::TYPE_PREDICTION => __('Predictions'),
            Product::TYPE_QUIZ => __('Quizzes'),
            Product::TYPE_COURSE => __('Courses'),
            default => __('Products'),
        };
    }

    private function buildAiSection($user): object
    {
        $creditLedger = app(AiCreditLedgerService::class);
        $creditLedger->ensureMonthlyGrant($user);

        $aiConversationCount = AiChatConversation::query()
            ->where('user_id', $user->id)
            ->count();

        $currentBalance = $creditLedger->currentBalance($user);

        return (object) [
            'type' => 'ai',
            'label' => __('AI'),
            'icon' => 'fas fa-robot',
            'tone' => 'violet',
            'count' => 2,
            'items' => collect([
                (object) [
                    'title' => __('AI Chat'),
                    'detail_url' => route('student.ai-chat.index'),
                    'access_source_label' => __('Open chat'),
                    'progress_label' => $aiConversationCount > 0
                        ? __(':count conversations', ['count' => number_format($aiConversationCount)])
                        : __('Start a new chat'),
                    'icon' => 'fas fa-comments',
                    'tone' => 'violet',
                ],
                (object) [
                    'title' => __('AI Credits'),
                    'detail_url' => route('ai-chat.credits'),
                    'access_source_label' => __('Manage balance'),
                    'progress_label' => __(':count credits remaining', ['count' => number_format($currentBalance)]),
                    'icon' => 'fas fa-wallet',
                    'tone' => 'blue',
                ],
            ]),
        ];
    }

    private function accessSourceLabel(string $source): string
    {
        return match ($source) {
            'subscription' => __('Subscription'),
            'purchase' => __('Purchased'),
            'free' => __('Free'),
            default => __('Open'),
        };
    }

    private function productDetailUrl(Product $product): string
    {
        return match ($product->type) {
            Product::TYPE_NOTE => route('product.read-note', $product->slug),
            Product::TYPE_QUIZ => route('product.start-quiz', $product->slug),
            Product::TYPE_PAST_PAPER, Product::TYPE_PREDICTION => route('product.read-document', $product->slug),
            Product::TYPE_COURSE => $product->course?->slug ? route('course.show', $product->course->slug) : route('product.show', $product->slug),
            default => route('product.show', $product->slug),
        };
    }
}
