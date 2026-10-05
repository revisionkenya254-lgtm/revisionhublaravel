<div class="dashboard__sidebar-wrap">
    <div class="dashboard__sidebar-title mb-20">
        <h6 class="title">{{ __('Welcome') }}, {{ userAuth()->name }}</h6>
    </div>
    @use('Nwidart\Modules\Facades\Module')
    <nav class="dashboard__sidebar-menu">
        <ul class="list-wrap">
            <li class="{{ Route::is('student.dashboard') ? 'active' : '' }}">
                <a href="{{ route('student.dashboard') }}">
                    <img src="{{ asset('uploads/website-images/dashboard.svg') }}">
                    {{ __('Dashboard') }}
                </a>
            </li>

            <li class="{{ Route::is('student.orders.index') ? 'active' : '' }}">
                <a href="{{ route('student.orders.index') }}">
                    <img src="{{ asset('uploads/website-images/order-history.svg') }}">
                    {{ __('Order History') }}
                </a>
            </li>
            <li class="{{ Route::is('refund-request.index') ? 'active' : '' }}">
                <a href="{{ route('refund-request.index') }}">
                    <img src="{{ asset('uploads/website-images/refund.svg') }}">
                    {{ __('Refund History') }}
                </a>
            </li>

            <li class="{{ Route::is('student.enrolled-courses') ? 'active' : '' }}">
                <a href="{{ route('student.enrolled-courses') }}">
                    <i class="flaticon-mortarboard"></i>{{ __('Enrolled Courses') }}</a>
            </li>
            <li class="{{ Route::is('student.library') ? 'active' : '' }}">
                <a href="{{ route('student.library') }}">
                    <i class="flaticon-book"></i>{{ __('Library') }}</a>
            </li>
            @if (!empty($studentSidebarSections) && $studentSidebarSections->isNotEmpty())
                <li class="dashboard__sidebar-products-header">
                    <span>{{ __('My Products') }}</span>
                </li>
                @foreach ($studentSidebarSections as $section)
                    @php
                        $isAccordionSection = true;
                        $isAccordionSectionActive = $isAccordionSection && $section->items->contains(function ($productItem) {
                            $url = request()->url();
                            return $url === ($productItem->detail_url ?? null);
                        });
                    @endphp
                    @if ($isAccordionSection)
                        <li class="dashboard__sidebar-products-section dashboard__sidebar-products-section--accordion">
                            <details class="dashboard__sidebar-products-section__details" {{ $isAccordionSectionActive ? 'open' : '' }}>
                                <summary class="dashboard__sidebar-products-section__summary">
                                    <span class="dashboard__sidebar-products-section__title">
                                        <span class="dashboard__sidebar-product-link__icon dashboard__sidebar-product-link__icon--{{ $section->tone }}">
                                            <i class="{{ $section->icon }}"></i>
                                        </span>
                                        {{ $section->label }}
                                    </span>
                                    <span class="dashboard__sidebar-products-section__meta">
                                        <span class="dashboard__sidebar-products-section__count">{{ $section->count }}</span>
                                        <i class="fas fa-chevron-down dashboard__sidebar-products-section__chevron" aria-hidden="true"></i>
                                    </span>
                                </summary>
                                <ul class="list-wrap dashboard__sidebar-products-section__list">
                                    @forelse ($section->items as $productItem)
                                        @php
                                            $product = $productItem->product ?? null;
                                            $itemUrl = $productItem->detail_url ?? '#';
                                            $itemTitle = $product?->title ?? $productItem->title ?? __('Untitled');
                                            $itemSubtitle = $productItem->access_source_label ?? $productItem->subtitle ?? null;
                                            $itemProgress = $productItem->progress_label ?? null;
                                            $isActiveSidebarProduct = request()->url() === $itemUrl
                                                || ($product?->type === \App\Models\Product::TYPE_COURSE
                                                    && $product?->course?->slug
                                                    && request()->url() === route('course.show', $product->course->slug));
                                        @endphp
                                        <li class="{{ $isActiveSidebarProduct ? 'active' : '' }}">
                                            <a href="{{ $itemUrl }}" class="dashboard__sidebar-product-link">
                                                <span class="dashboard__sidebar-product-link__content">
                                                    <strong>{{ truncate($itemTitle, 30) }}</strong>
                                                    @if (!empty($itemSubtitle) || !empty($itemProgress))
                                                        <small>
                                                            {{ $itemSubtitle }}
                                                            @if (!empty($itemProgress))
                                                                &middot; {{ $itemProgress }}
                                                            @endif
                                                        </small>
                                                    @endif
                                                </span>
                                            </a>
                                        </li>
                                    @empty
                                        <li class="dashboard__sidebar-products-section__empty">
                                            {{ __('No items yet.') }}
                                        </li>
                                    @endforelse
                                </ul>
                            </details>
                        </li>
                    @endif
                @endforeach
                <li>
                    <a href="{{ route('student.library') }}">
                        <i class="flaticon-book"></i>{{ __('View All Products') }}
                    </a>
                </li>
            @endif
            <li class="{{ Route::is('student.wishlist') ? 'active' : '' }}">
                <a href="{{ route('student.wishlist') }}">
                    <img src="{{ asset('uploads/website-images/heart.svg') }}">{{ __('Wishlist') }}</a>
            </li>
            <li class="{{ Route::is('student.reviews.index') ? 'active' : '' }}">
                <a href="{{ route('student.reviews.index') }}">
                    <img src="{{ asset('uploads/website-images/reviews.svg') }}">{{ __('Reviews') }}</a>
            </li>
            <li class="{{ Route::is('student.quiz-attempts') ? 'active' : '' }}">
                <a href="{{ route('student.quiz-attempts') }}">
                    <img src="{{ asset('uploads/website-images/quiz.svg') }}">{{ __('My Quiz Attempts') }}</a>
            </li>
            <li class="{{ Route::is('student.ai-chat.*') ? 'active' : '' }}">
                <a href="{{ route('student.ai-chat.index') }}">
                    <i class="fas fa-comments"></i>{{ __('AI Chat') }}</a>
            </li>
        </ul>
    </nav>
    <div class="dashboard__sidebar-title mt-30 mb-20">
        <h6 class="title">{{ __('User') }}</h6>
    </div>
    <nav class="dashboard__sidebar-menu">
        <ul class="list-wrap">
            @if (Module::has('LiveChat') && Module::isEnabled('LiveChat') && $setting?->pusher_status == 'active')
                <li class="{{ Route::is('student.live-chat.*') ? 'active' : '' }}">
                    <a href="{{ route('student.live-chat.index') }}">
                        <img src="{{ asset('uploads/website-images/live-chat.svg') }}">
                        {{ __('Live Chat') }}
                    </a>
                </li>
            @endif
            <li class="{{ Route::is('student.devices.index') ? 'active' : '' }}">
                <a href="{{ route('student.devices.index') }}">
                    <img src="{{ asset('uploads/website-images/device.svg') }}">
                    {{ __('Active Devices') }}
                </a>
            </li>
            <li class="{{ Route::is('student.setting.index') ? 'active' : '' }}">
                <a href="{{ route('student.setting.index') }}">
                    <i class="flaticon-user"></i>
                    {{ __('Profile Settings') }}
                </a>
            </li>
            @if (userAuth()?->instructorInfo)
                @php
                    $instructorStatusRoute = isInstructorAccount() ? 'instructor.dashboard' : 'become-instructor.review';
                @endphp
                <li class="{{ Route::is('become-instructor.review', 'instructor.dashboard') ? 'active' : '' }}">
                    <a href="{{ route($instructorStatusRoute) }}">
                        <i class="flaticon-user"></i>
                        {{ __('Instructor status') }}
                    </a>
                </li>
            @endif
            <li>
                <a href="{{ route('logout') }}"
                    onclick="event.preventDefault(); $('#student-logout-form').trigger('submit');">
                    <img src="{{ asset('uploads/website-images/logout.svg') }}">
                    {{ __('Logout') }}
                </a>
            </li>
        </ul>
    </nav>
</div>

{{-- start student logout form --}}
<form id="student-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>
{{-- end student logout form --}}

@push('styles')
    <style>
        .dashboard__sidebar-products-header {
            padding: 12px 0 8px;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 12px;
            font-weight: 800;
            color: #94a3b8;
            border-top: 1px solid rgba(148, 163, 184, 0.25);
        }

        .dashboard__sidebar-products-header span {
            display: inline-block;
            padding-left: 6px;
        }

        .dashboard__sidebar-products-section {
            margin-bottom: 10px;
        }

        .dashboard__sidebar-products-section--accordion {
            margin-bottom: 6px;
        }

        .dashboard__sidebar-products-section__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 8px 6px 6px;
        }

        .dashboard__sidebar-products-section__details {
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.14);
            background: rgba(248, 250, 252, 0.66);
            overflow: hidden;
        }

        .dashboard__sidebar-products-section__summary {
            list-style: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 12px;
            user-select: none;
        }

        .dashboard__sidebar-products-section__summary::-webkit-details-marker {
            display: none;
        }

        .dashboard__sidebar-products-section__meta {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .dashboard__sidebar-products-section__chevron {
            font-size: 12px;
            color: #94a3b8;
            transition: transform 0.2s ease;
        }

        .dashboard__sidebar-products-section__details[open] .dashboard__sidebar-products-section__chevron {
            transform: rotate(180deg);
        }

        .dashboard__sidebar-products-section__details .dashboard__sidebar-products-section__list {
            padding: 0 10px 10px;
        }

        .dashboard__sidebar-products-section__title {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 800;
            color: #475569;
        }

        .dashboard__sidebar-products-section__count {
            min-width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 0 8px;
            background: #f1f5f9;
            color: #334155;
            font-size: 12px;
            font-weight: 800;
        }

        .dashboard__sidebar-products-section__list {
            margin-top: 0;
        }

        .dashboard__sidebar-products-section__list > li {
            margin-bottom: 8px;
        }

        .dashboard__sidebar-products-section__empty {
            padding: 0 10px 10px;
            color: #94a3b8;
            font-size: 12px;
        }

        .dashboard__sidebar-product-link {
            align-items: flex-start;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 14px;
            background: rgba(248, 250, 252, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.14);
        }

        .dashboard__sidebar-product-link__icon {
            width: 28px;
            height: 28px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 28px;
            font-size: 12px;
        }

        .dashboard__sidebar-product-link__icon--blue {
            background: rgba(37, 99, 235, 0.12);
            color: #2563eb;
        }

        .dashboard__sidebar-product-link__icon--green {
            background: rgba(22, 163, 74, 0.12);
            color: #16a34a;
        }

        .dashboard__sidebar-product-link__icon--amber {
            background: rgba(245, 158, 11, 0.14);
            color: #f59e0b;
        }

        .dashboard__sidebar-product-link__icon--rose {
            background: rgba(244, 114, 182, 0.14);
            color: #db2777;
        }

        .dashboard__sidebar-product-link__icon--violet {
            background: rgba(124, 58, 237, 0.14);
            color: #7c3aed;
        }

        .dashboard__sidebar-product-link__icon--slate {
            background: rgba(100, 116, 139, 0.14);
            color: #475569;
        }

        .dashboard__sidebar-product-link__content {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }

        .dashboard__sidebar-product-link__content strong,
        .dashboard__sidebar-product-link__content small {
            display: block;
        }

        .dashboard__sidebar-product-link__content strong {
            font-size: 13px;
            line-height: 1.3;
            color: inherit;
            white-space: normal;
        }

        .dashboard__sidebar-product-link__content small {
            font-size: 11px;
            line-height: 1.35;
            color: #94a3b8;
            white-space: normal;
        }

        @media (max-width: 767.98px) {
            .dashboard__sidebar-products-header {
                padding-left: 2px;
            }

            .dashboard__sidebar-products-section__head {
                padding-left: 2px;
                padding-right: 2px;
            }

            .dashboard__sidebar-product-link {
                padding: 10px;
            }
        }
    </style>
@endpush
