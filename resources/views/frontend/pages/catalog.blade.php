@extends('frontend.layouts.master')
@php
    use App\Models\Product;
@endphp
@section('meta_title', __('Catalog') . ' || ' . $setting->app_name)
@section('meta_description', __('Browse courses, past papers, predictions, notes, and quizzes.'))
@section('body_class', 'catalog-page')

@section('contents')
    @if ($currentMainCategory)
        @php
            $isLoggedIn = auth()->check();
            $selectedMainSlug = (string) data_get($currentMainCategory, 'slug', '');
            $selectedLevelSlug = (string) data_get($selectedCurriculumLevel, 'slug', '');
            $selectedLevelName = (string) data_get($selectedCurriculumLevel, 'name', __('All'));
            $selectedSubjectSlug = (string) data_get($selectedSubject, 'slug', '');
            $selectedSubjectName = (string) data_get($selectedSubject, 'name', '');
            $selectedType = (string) request('type', '');
            $selectedFocusLabel = (string) data_get($selectedFocus, 'label', trim($currentCategoryTitle . ' - ' . $selectedLevelName, ' -'));
            $selectedFocusName = (string) data_get($selectedFocus, 'name', $selectedLevelName);
            $catalogQuery = collect(request()->only(['search', 'type', 'order']))
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->all();
            $subscribeNowUrl = $isLoggedIn
                ? route('checkout.index', ['subscription_plan' => 'month-1'])
                : route('subscriptions', ['plan' => 'month-1']);
            $topUpCreditsUrl = $isLoggedIn
                ? route('ai-chat.credits')
                : route('login');
            $landingTitle = $selectedFocusLabel;
            $landingStats = [
                ['key' => Product::TYPE_COURSE, 'label' => __('Videos'), 'icon' => 'fa-video', 'color' => '#2563eb'],
                ['key' => Product::TYPE_NOTE, 'label' => __('Notes'), 'icon' => 'fa-file-alt', 'color' => '#ec4899'],
                ['key' => Product::TYPE_PAST_PAPER, 'label' => __('Past Papers'), 'icon' => 'fa-file-pdf', 'color' => '#16a34a'],
                ['key' => Product::TYPE_PREDICTION, 'label' => __('Predictions'), 'icon' => 'fa-bolt', 'color' => '#ff7a18'],
                ['key' => Product::TYPE_QUIZ, 'label' => __('Quizzes'), 'icon' => 'fa-question-circle', 'color' => '#6d4cff'],
            ];
            $landingCategoryIcons = [
                'pre-primary' => 'fa-child',
                'lower-primary' => 'fa-school',
                'upper-primary' => 'fa-user-graduate',
                'junior-school' => 'fa-book-reader',
                'senior-school-cbc' => 'fa-graduation-cap',
                'high-school' => 'fa-book',
                'tvet' => 'fa-wrench',
                'certificate-courses' => 'fa-certificate',
                'diploma-courses' => 'fa-file-alt',
                'undergraduate' => 'fa-university',
                'professional-courses' => 'fa-award',
                'teacher-resources' => 'fa-book-open',
            ];
            $landingCategoryColors = ['#ff7a18', '#22c55e', '#2563eb', '#5b37ff', '#f97316', '#0ea5e9', '#ec4899'];
        @endphp

        <section class="catalog-landing">
            <div class="container custom-container xl_container">
                <div class="catalog-hero catalog-hero--compact">
                    <div class="catalog-hero__copy">
                        <nav class="catalog-hero__breadcrumb" aria-label="{{ __('Breadcrumb') }}">
                            @foreach ($catalogBreadcrumbs as $crumb)
                                @if (! $loop->first)
                                    <span class="catalog-hero__crumb-separator"><i class="fas fa-angle-right"></i></span>
                                @endif
                                @if (!empty(data_get($crumb, 'url')))
                                    <a href="{{ data_get($crumb, 'url') }}">{{ data_get($crumb, 'label') }}</a>
                                @else
                                    <span class="is-current">{{ data_get($crumb, 'label') }}</span>
                                @endif
                            @endforeach
                        </nav>
                        <h1 class="catalog-hero__title">{{ $landingTitle }}</h1>
                        <div class="catalog-hero__meta">
                            <span class="catalog-hero__meta-star"><i class="fas fa-star"></i></span>
                            <span><strong data-catalog-total>{{ ($catalogSummary['total'] ?? 0) }}</strong> {{ __('Resources') }}</span>
                        </div>
                    </div>
                    <div class="catalog-hero__art" aria-hidden="true">
                        <span class="catalog-hero__shape catalog-hero__shape--paper"></span>
                        <span class="catalog-hero__shape catalog-hero__shape--arc"></span>
                        <span class="catalog-hero__shape catalog-hero__shape--note"></span>
                    </div>
                </div>

                <div class="catalog-main-actions catalog-main-actions--curriculum">
                    <button type="button" class="catalog-curriculum-toggle" data-curriculum-action="toggle" data-label-open="{{ __('Show Curriculum') }}" data-label-close="{{ __('Hide Curriculum') }}" aria-expanded="false" aria-label="{{ __('Show Curriculum') }}">
                        <i class="fas fa-sitemap"></i>
                        <span>{{ __('Show Curriculum') }}</span>
                    </button>
                </div>

                <div class="catalog-shell catalog-shell--rightbar-collapsed">
                    <div class="row g-4 align-items-start catalog-shell__grid">
                        <div class="col-xl-3 col-lg-3 catalog-shell__curriculum-col">
                            <aside class="catalog-sidebar">
                            <div class="catalog-card">
                                <div class="catalog-card__header catalog-card__header--tight">
                                    <h3>{{ __('Curriculum') }}</h3>
                                </div>
                                <div class="catalog-tree">
                                    @foreach ($categoryTree as $category)
                                        @php
                                            $categorySlug = (string) data_get($category, 'slug', '');
                                            $categoryIcon = $landingCategoryIcons[$categorySlug] ?? 'fa-folder-open';
                                            $categoryColor = $landingCategoryColors[$loop->index % count($landingCategoryColors)];
                                            $rootNode = array_merge($category, [
                                                'icon' => $categoryIcon,
                                                'accent' => $categoryColor,
                                            ]);
                                        @endphp
                                        @include('frontend.partials.catalog-tree-node', [
                                            'node' => $rootNode,
                                            'rootSlug' => $categorySlug,
                                            'parentCategorySlug' => null,
                                            'selectedMainSlug' => $selectedMainSlug,
                                            'selectedLevelSlug' => $selectedLevelSlug,
                                            'selectedSubjectSlug' => $selectedSubjectSlug,
                                            'catalogQuery' => $catalogQuery,
                                            'depth' => 0,
                                        ])
                                    @endforeach
                                </div>
                            </div>

                            <div class="catalog-card mt-4">
                                <div class="catalog-card__header">
                                    <h3>{{ __('Filters') }}</h3>
                                </div>
                                <div class="catalog-filter-group">
                                    @foreach ([
                                        __('Free Resources'),
                                        __('Premium Resources'),
                                        __('Subscription Included'),
                                        __('Downloadable'),
                                        __('AI Supported'),
                                        __('Latest Resources'),
                                    ] as $filterLabel)
                                        <label class="catalog-filter-check">
                                            <input type="checkbox" checked>
                                            <span>{{ $filterLabel }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                <div class="catalog-price-range">
                                    <h4>{{ __('PRICE (KES)') }}</h4>
                                    <div class="catalog-price-range__track">
                                        <span class="catalog-price-range__thumb"></span>
                                        <span class="catalog-price-range__thumb catalog-price-range__thumb--end"></span>
                                    </div>
                                    <div class="catalog-price-range__labels">
                                        <span>0</span>
                                        <span>5,000+</span>
                                    </div>
                                </div>

                                <div class="catalog-rating-filter">
                                    <h4>{{ __('RATING') }}</h4>
                                    @foreach ([5, 4, 3] as $rating)
                                        <div class="catalog-rating-filter__row">
                                            <span class="catalog-rating-filter__stars">
                                                @for ($star = 0; $star < $rating; $star++)
                                                    <i class="fas fa-star"></i>
                                                @endfor
                                            </span>
                                            <span>&amp; up</span>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="catalog-accordion-row">{{ __('LANGUAGE') }} <i class="fas fa-chevron-down"></i></div>
                                <div class="catalog-accordion-row">{{ __('INSTRUCTOR') }} <i class="fas fa-chevron-down"></i></div>
                            </div>
                            </aside>
                        </div>

                        <div class="catalog-shell__main-col">
                            <div class="catalog-shell__main-content">
                            <div class="catalog-path">
                                <span class="catalog-path__crumb">{{ __('Home') }} <i class="fas fa-angle-right"></i></span>
                                <span class="catalog-path__crumb">{{ $currentCategoryTitle }} <i class="fas fa-angle-right"></i></span>
                                @if ($selectedSubjectName !== '')
                                    <span class="catalog-path__crumb">{{ $selectedLevelName }} <i class="fas fa-angle-right"></i></span>
                                    <span class="catalog-path__crumb is-current">{{ $selectedSubjectName }}</span>
                                @else
                                    <span class="catalog-path__crumb is-current">{{ $selectedLevelName }}</span>
                                @endif
                            </div>

                            <div class="catalog-focus">
                                <div class="catalog-focus__icon">123</div>
                                <div class="catalog-focus__copy">
                                    <h2>{{ $landingTitle }}</h2>
                                    <p>{{ $selectedFocusName }}</p>
                                </div>
                            </div>

                            <div class="catalog-tabs catalog-tabs--landing" role="tablist" aria-label="{{ __('Product type') }}">
                                <button type="button" class="catalog-type-tab {{ $selectedType === '' ? 'is-active' : '' }}" data-type="">{{ __('All') }}</button>
                                <button type="button" class="catalog-type-tab {{ $selectedType === 'course' ? 'is-active' : '' }}" data-type="course">{{ __('Video Lessons') }}</button>
                                <button type="button" class="catalog-type-tab {{ $selectedType === 'note' ? 'is-active' : '' }}" data-type="note">{{ __('Notes') }}</button>
                                <button type="button" class="catalog-type-tab {{ $selectedType === 'past_paper' ? 'is-active' : '' }}" data-type="past_paper">{{ __('Past Papers') }}</button>
                                <button type="button" class="catalog-type-tab {{ $selectedType === 'prediction' ? 'is-active' : '' }}" data-type="prediction">{{ __('Predictions') }}</button>
                                <button type="button" class="catalog-type-tab {{ $selectedType === 'quiz' ? 'is-active' : '' }}" data-type="quiz">{{ __('Quizzes') }}</button>
                            </div>

                            <div class="catalog-toolbar-holder">
                                @include('frontend.partials.catalog-toolbar', ['catalogToolbar' => $catalogToolbar])
                            </div>

                            <div class="catalog-holder row g-1 courses__grid-wrap"></div>
                            <div class="catalog-load-more d-none">
                                <button type="button" class="catalog-load-more__btn">
                                    {{ __('Load More Resources') }} <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                            <div class="pagination-wrap d-none">
                                <div class="pagination"></div>
                            </div>
                            </div>

                            <div class="catalog-main-footer">
                                <div class="catalog-main-footer__item">
                                    <i class="fas fa-shield-alt"></i>
                                    <div>
                                        <strong>{{ __('Verified Instructors') }}</strong>
                                        <span>{{ __('All instructors are verified') }}</span>
                                    </div>
                                </div>
                                <div class="catalog-main-footer__item">
                                    <i class="fas fa-award"></i>
                                    <div>
                                        <strong>{{ __('Quality Content') }}</strong>
                                        <span>{{ __('Reviewed & updated regularly') }}</span>
                                    </div>
                                </div>
                                <div class="catalog-main-footer__item">
                                    <i class="fas fa-lock"></i>
                                    <div>
                                        <strong>{{ __('Safe & Secure') }}</strong>
                                        <span>{{ __('Your data is protected') }}</span>
                                    </div>
                                </div>
                                <div class="catalog-main-footer__item">
                                    <i class="fas fa-laptop"></i>
                                    <div>
                                        <strong>{{ __('Learn Anywhere') }}</strong>
                                        <span>{{ __('Online & offline access') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="catalog-shell__sidebar-col">
                            <aside class="catalog-rightbar catalog-rightbar--drawer" aria-hidden="false">
                                <button type="button" class="catalog-rightbar__close" data-rightbar-action="toggle" data-label-open="{{ __('Show Sidebar') }}" data-label-close="{{ __('Hide Sidebar') }}" aria-expanded="false" aria-label="{{ __('Show Sidebar') }}">
                                    <i class="fas fa-th-large"></i>
                                    <span>{{ __('Show Sidebar') }}</span>
                                </button>
                            <div class="catalog-right-card catalog-ai-card">
                                <div class="catalog-right-card__header">
                                    <span class="catalog-ai-card__icon"><i class="fas fa-robot"></i></span>
                                    <h3>{{ __('AI Study Assistant') }}</h3>
                                </div>
                                <p>{{ __('Ask anything about') }} {{ $landingTitle }}</p>
                                <input type="text" class="catalog-ai-card__input" placeholder="{{ __('Type your question...') }}">
                                <button type="button" class="catalog-ai-card__button">{{ __('Ask AI') }}</button>
                                <div class="catalog-credit-box">
                                <div class="catalog-credit-box__row">
                                        <span>{{ __('Remaining Credits') }}</span>
                                        <strong>2,540 / 3,000</strong>
                                    </div>
                                    <div class="catalog-credit-box__bar"><span></span></div>
                                    <a href="{{ $topUpCreditsUrl }}" class="catalog-credit-box__link">{{ __('Top Up Credits') }}</a>
                                </div>
                            </div>

                            <div class="catalog-right-card catalog-offer-card">
                                <h3><i class="fas fa-crown"></i> {{ __('Unlock All Resources') }}</h3>
                                <ul>
                                    <li>{{ __('Unlimited access to all resources') }}</li>
                                    <li>{{ __('All 5 resource types') }}</li>
                                    <li>{{ __('AI credits every month') }}</li>
                                    <li>{{ __('Offline downloads') }}</li>
                                <li>{{ __('Cancel anytime') }}</li>
                            </ul>
                            <div class="catalog-offer-card__price">{{ __('KES 500') }} <span>/ {{ __('month') }}</span></div>
                                <a href="{{ $subscribeNowUrl }}" class="catalog-offer-card__button">{{ __('Subscribe Now') }}</a>
                            </div>

                            <div class="catalog-right-card catalog-recent-card">
                                <div class="catalog-right-card__header">
                                    <h3>{{ __('Recently Viewed') }}</h3>
                                    <a href="javascript:;">{{ __('View all') }}</a>
                                </div>
                                <div class="catalog-recent-list">
                                    <div class="catalog-recent-item">
                                        <div class="catalog-recent-item__thumb"></div>
                                        <div>
                                            <strong>{{ __('Counting 1 to 20') }}</strong>
                                            <span>{{ __('Video') }} · 18:40</span>
                                        </div>
                                    </div>
                                    <div class="catalog-recent-item">
                                        <div class="catalog-recent-item__thumb catalog-recent-item__thumb--paper"></div>
                                        <div>
                                            <strong>{{ __('PP1 Term 1 Past Paper 2024') }}</strong>
                                            <span>{{ __('Past Paper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                                <div class="catalog-right-card catalog-help-card">
                                    <h3>{{ __('Need help?') }}</h3>
                                    <p>{{ __('Ask RevisionHub AI') }}</p>
                                </div>
                            </aside>
                        </div>
                </div>

                <div class="catalog-curriculum-backdrop" data-curriculum-action="close" aria-hidden="true"></div>

            </div>
        </section>
    @elseif (request('view') === 'levels')
        @php
            $allEducationalLevels = collect($categoryTree ?? []);
        @endphp

        <x-frontend.breadcrumb
            :title="__('Educational Levels')"
            :links="[['url' => route('home'), 'text' => __('Home')], ['url' => '', 'text' => __('Educational Levels')]]"
        />

        <section class="all-courses-area section-py-120 top-baseline catalog-page catalog-page--levels">
            <div class="container position-relative">
                <div class="catalog-levels-hero">
                    <div>
                        <span class="catalog-levels-hero__eyebrow">{{ __('Browse all levels') }}</span>
                        <h2 class="catalog-levels-hero__title">{{ __('Educational Levels') }}</h2>
                        <p class="catalog-levels-hero__subtitle">{{ __('Choose a level to jump into its curriculum, subjects, and resources.') }}</p>
                    </div>
                    <a href="{{ route('home') }}" class="catalog-levels-hero__back">
                        <i class="fas fa-arrow-left"></i>
                        <span>{{ __('Back to Home') }}</span>
                    </a>
                </div>

                <div class="catalog-levels-grid">
                    @foreach ($allEducationalLevels as $category)
                        @php
                            $categorySlug = (string) data_get($category, 'slug', '');
                            $categoryName = (string) data_get($category, 'name', $categorySlug);
                            $categoryChildren = collect(data_get($category, 'children', []));
                            $categoryIcon = [
                                'pre-primary' => 'fa-child',
                                'lower-primary' => 'fa-school',
                                'upper-primary' => 'fa-user-graduate',
                                'junior-school' => 'fa-book-reader',
                                'senior-school-cbc' => 'fa-graduation-cap',
                                'high-school' => 'fa-book',
                                'tvet' => 'fa-wrench',
                                'certificate-courses' => 'fa-certificate',
                                'diploma-courses' => 'fa-file-alt',
                                'undergraduate' => 'fa-university',
                                'professional-courses' => 'fa-award',
                                'teacher-resources' => 'fa-book-open',
                            ][$categorySlug] ?? 'fa-folder-open';
                            $cardAccent = [
                                'pre-primary' => '#ff7a18',
                                'lower-primary' => '#22c55e',
                                'upper-primary' => '#2563eb',
                                'junior-school' => '#5b37ff',
                                'senior-school-cbc' => '#f97316',
                                'high-school' => '#0ea5e9',
                                'tvet' => '#ec4899',
                                'certificate-courses' => '#8b5cf6',
                                'diploma-courses' => '#14b8a6',
                                'undergraduate' => '#2563eb',
                                'professional-courses' => '#7c3aed',
                                'teacher-resources' => '#f59e0b',
                            ][$categorySlug] ?? '#5b37ff';
                            $childPreview = $categoryChildren->take(4);
                        @endphp

                        <a href="{{ route('catalog', ['main_category' => $categorySlug]) }}" class="catalog-level-card" style="--level-accent: {{ $cardAccent }};">
                            <span class="catalog-level-card__icon">
                                <i class="fas {{ $categoryIcon }}" aria-hidden="true"></i>
                            </span>
                            <span class="catalog-level-card__copy">
                                <strong>{{ $categoryName }}</strong>
                                <span>{{ $categoryChildren->count() }} {{ __('sub-levels') }}</span>
                            </span>
                            <span class="catalog-level-card__chips">
                                @foreach ($childPreview as $child)
                                    <span>{{ data_get($child, 'name') }}</span>
                                @endforeach
                                @if ($categoryChildren->count() > $childPreview->count())
                                    <span>+{{ $categoryChildren->count() - $childPreview->count() }}</span>
                                @endif
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection

@push('styles')
    <style>
        .catalog-hero--compact {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
            padding: 6px 14px;
            border-radius: 16px;
            background: linear-gradient(180deg, #fbfbff 0%, #ffffff 100%);
            box-shadow: 0 8px 18px rgba(91, 55, 255, 0.04);
        }
        .catalog-hero__breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 4px;
            color: #7b7f98;
            font-size: 12px;
            font-weight: 500;
        }
        .catalog-hero__title {
            margin: 0;
            color: #161439;
            font-size: 22px;
            font-weight: 800;
            line-height: 1.05;
        }
        .catalog-hero__meta {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 3px;
            color: #4d5373;
            font-size: 12px;
            font-weight: 600;
        }
        .catalog-hero__meta strong {
            color: #161439;
        }
        .catalog-hero__meta-star {
            color: #f59e0b;
        }
        .catalog-hero__art {
            position: relative;
            width: 160px;
            min-height: 50px;
            flex: 0 0 auto;
        }
        .catalog-hero__shape {
            position: absolute;
            border: 1px dashed rgba(91, 55, 255, 0.18);
            border-radius: 18px;
        }
        .catalog-hero__shape--paper {
            top: 4px;
            right: 48px;
            width: 36px;
            height: 20px;
            transform: rotate(14deg);
        }
        .catalog-hero__shape--arc {
            top: 0;
            right: 0;
            width: 78px;
            height: 38px;
            border-radius: 999px;
            border-width: 2px 0 0 0;
            border-style: dotted;
        }
        .catalog-hero__shape--note {
            right: 30px;
            bottom: -4px;
            width: 28px;
            height: 18px;
            transform: rotate(-12deg);
        }
        .catalog-shell {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 2px;
        }
        .catalog-shell__grid {
            align-items: stretch;
            flex-wrap: nowrap;
            width: 100%;
        }
        .catalog-shell__main-col {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-self: stretch;
            flex: 1 1 0;
            width: 0;
            min-width: 0;
            max-width: none;
            min-height: calc(100vh - 120px);
        }
        .catalog-shell__curriculum-col {
            min-width: 0;
        }
        .catalog-shell__main-content {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            gap: 8px;
            min-height: 0;
        }
        .catalog-shell__sidebar-col {
            display: flex;
            align-self: stretch;
            flex: 0 0 284px;
            width: 284px;
            max-width: 284px;
            min-width: 0;
            min-height: calc(100vh - 120px);
            overflow: hidden;
            transition: flex-basis 0.28s ease, width 0.28s ease, max-width 0.28s ease, opacity 0.28s ease, padding 0.28s ease;
        }
        .catalog-shell__sidebar-col .catalog-rightbar--drawer {
            position: sticky;
            top: 102px;
            display: flex;
            flex-direction: column;
            width: 100%;
            height: 100%;
            min-height: 100%;
            max-height: none;
        }
        .catalog-shell--rightbar-collapsed .catalog-shell__sidebar-col {
            flex-basis: 74px;
            width: 74px;
            max-width: 74px;
            opacity: 1;
            pointer-events: auto;
            padding-left: 0;
            padding-right: 0;
        }
        .catalog-shell--rightbar-open .catalog-shell__sidebar-col {
            opacity: 1;
            pointer-events: auto;
        }
        .catalog-card {
            padding: 12px;
            border: 1px solid rgba(22, 20, 57, 0.06);
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 10px 22px rgba(17, 24, 39, 0.04);
        }
        .catalog-card__header--tight {
            margin-bottom: 6px;
        }
        .catalog-card__header h3 {
            margin: 0;
            color: #161439;
            font-size: 19px;
            font-weight: 800;
        }
        .catalog-tree {
            display: grid;
            gap: 4px;
        }
        .catalog-tree__group {
            border-radius: 12px;
        }
        .catalog-tree__group--selected > .catalog-tree__summary {
            margin-bottom: 2px;
            background: rgba(91, 55, 255, 0.06);
            border: 1px solid rgba(91, 55, 255, 0.08);
        }
        .catalog-tree__summary {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            width: 100%;
            padding: 8px 9px;
            border-radius: 12px;
            cursor: pointer;
            color: #161439;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.25;
            user-select: none;
        }
        .catalog-tree__summary::-webkit-details-marker {
            display: none;
        }
        .catalog-tree__summary-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 8px;
            background: color-mix(in srgb, var(--tree-accent, #5b37ff) 14%, white);
            color: var(--tree-accent, #5b37ff);
            flex: 0 0 auto;
        }
        .catalog-tree__summary-link {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            min-width: 0;
            width: 100%;
            color: inherit;
            text-decoration: none;
        }
        .catalog-tree__summary-link > span:last-child,
        .catalog-tree__item > span:last-child {
            display: block;
            min-width: 0;
            line-height: 1.3;
            overflow-wrap: anywhere;
        }
        .catalog-tree__summary-text {
            color: inherit;
        }
        .catalog-tree__summary-text.is-active {
            color: #5b37ff;
        }
        .catalog-tree__summary-caret {
            margin-left: auto;
            color: #7b7f98;
            font-size: 14px;
        }
        .catalog-tree__group[open] > .catalog-tree__summary {
            background: rgba(91, 55, 255, 0.04);
        }
        .catalog-tree__children {
            padding: 4px 0 0 14px;
            margin-left: 10px;
            border-left: 1px dashed rgba(91, 55, 255, 0.2);
        }
        .catalog-tree__children--nested {
            margin-left: 16px;
            padding-top: 2px;
        }
        .catalog-tree__item {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            min-width: 0;
            min-height: 30px;
            padding: 6px 9px;
            border-radius: 9px;
            color: #4d5373;
            font-size: 14px;
            font-weight: 500;
            line-height: 1.3;
            text-decoration: none;
        }
        .catalog-tree__item--level-1,
        .catalog-tree__item--level-2 {
            min-height: 26px;
            padding-top: 4px;
            padding-bottom: 4px;
            font-size: 13px;
        }
        .catalog-tree__item--level-0 {
            padding-left: 6px;
        }
        .catalog-tree__item--level-1 {
            padding-left: 14px;
        }
        .catalog-tree__item--level-2 {
            padding-left: 22px;
        }
        .catalog-tree__item:hover,
        .catalog-tree__item.is-active {
            background: rgba(91, 55, 255, 0.08);
            color: #5b37ff;
        }
        .catalog-tree__item.is-disabled {
            opacity: 0.72;
            cursor: default;
            pointer-events: none;
        }
        .catalog-tree__item-mark {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #c8cbe0;
            flex: 0 0 auto;
            margin-top: 4px;
        }
        .catalog-filter-group {
            display: grid;
            gap: 7px;
            margin-bottom: 14px;
        }
        .catalog-filter-check {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #4d5373;
            font-size: 14px;
            font-weight: 500;
        }
        .catalog-filter-check input {
            width: 16px;
            height: 16px;
            accent-color: #5b37ff;
        }
        .catalog-price-range,
        .catalog-rating-filter {
            margin-bottom: 12px;
            padding-top: 8px;
            border-top: 1px solid rgba(22, 20, 57, 0.08);
        }
        .catalog-price-range h4,
        .catalog-rating-filter h4 {
            margin-bottom: 10px;
            color: #161439;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.03em;
        }
        .catalog-price-range__track {
            position: relative;
            height: 4px;
            border-radius: 999px;
            background: rgba(91, 55, 255, 0.2);
            margin: 12px 2px 8px;
        }
        .catalog-price-range__thumb {
            position: absolute;
            top: 50%;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #5b37ff;
            transform: translateY(-50%);
            box-shadow: 0 0 0 4px rgba(91, 55, 255, 0.12);
        }
        .catalog-price-range__thumb--end {
            right: 0;
        }
        .catalog-price-range__labels {
            display: flex;
            justify-content: space-between;
            color: #7b7f98;
            font-size: 12px;
            font-weight: 600;
        }
        .catalog-rating-filter__row {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #4d5373;
            font-size: 13px;
            margin-bottom: 6px;
        }
        .catalog-rating-filter__stars {
            color: #111827;
            letter-spacing: 0.5px;
        }
        .catalog-accordion-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 38px;
            padding: 0 2px;
            border-top: 1px solid rgba(22, 20, 57, 0.08);
            color: #161439;
            font-size: 14px;
            font-weight: 700;
        }
        .catalog-path {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 10px;
            color: #7b7f98;
            font-size: 12px;
            font-weight: 500;
        }
        .catalog-path__crumb {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .catalog-path__crumb.is-current {
            color: #161439;
            font-weight: 700;
        }
        .catalog-focus {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .catalog-focus__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 46px;
            height: 46px;
            border-radius: 12px;
            border: 1px solid rgba(91, 55, 255, 0.18);
            background: #fff;
            color: #5b37ff;
            font-size: 18px;
            font-weight: 900;
            box-shadow: 0 8px 18px rgba(91, 55, 255, 0.06);
        }
        .catalog-focus__copy h2 {
            margin: 0;
            color: #161439;
            font-size: 22px;
            font-weight: 800;
            line-height: 1.1;
        }
        .catalog-focus__copy p {
            margin: 3px 0 0;
            color: #7b7f98;
            font-size: 12px;
            font-weight: 500;
        }
        .catalog-loading-card,
        .catalog-empty-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 280px;
            padding: 24px;
            border: 1px solid rgba(22, 20, 57, 0.08);
            border-radius: 22px;
            background: #fff;
            box-shadow: 0 12px 24px rgba(17, 24, 39, 0.04);
            text-align: center;
        }
        .catalog-loading-card {
            align-items: stretch;
            gap: 16px;
            padding: 18px;
        }
        .catalog-loading-card__thumb {
            height: 150px;
            border-radius: 18px;
            background: linear-gradient(135deg, #edf0ff, #f7f8ff);
        }
        .catalog-loading-card__body {
            display: grid;
            gap: 10px;
        }
        .catalog-loading-card__line {
            height: 14px;
            border-radius: 999px;
            background: linear-gradient(135deg, #eef2ff, #f7f8ff);
        }
        .catalog-loading-card__line--short {
            width: 72%;
        }
        .catalog-loading-card__line--tiny {
            width: 46%;
        }
        .catalog-loading-card__actions {
            display: flex;
            gap: 10px;
            margin-top: 4px;
        }
        .catalog-loading-card__pill {
            width: 92px;
            height: 34px;
            border-radius: 999px;
            background: linear-gradient(135deg, #eef2ff, #f7f8ff);
        }
        .catalog-empty-card__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 62px;
            height: 62px;
            margin-bottom: 14px;
            border-radius: 18px;
            background: rgba(91, 55, 255, 0.08);
            color: #5b37ff;
            font-size: 24px;
        }
        .catalog-empty-card h6 {
            margin: 0;
            color: #161439;
            font-size: 18px;
            font-weight: 800;
        }
        .catalog-empty-card p {
            max-width: 420px;
            margin: 8px 0 0;
            color: #6d7491;
            font-size: 13px;
            line-height: 1.6;
        }
        .catalog-grid-item--document {
            display: flex;
        }
        .catalog-paper-card {
            display: flex;
            flex-direction: column;
            width: 100%;
            min-height: 188px;
            padding: 14px;
            border: 1px solid rgba(22, 20, 57, 0.08);
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(16, 24, 40, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .catalog-paper-card:hover {
            transform: translateY(-2px);
            border-color: rgba(91, 55, 255, 0.24);
            box-shadow: 0 16px 30px rgba(91, 55, 255, 0.12);
        }
        .catalog-paper-card__top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
        }
        .catalog-paper-card__pill {
            display: inline-flex;
            align-items: center;
            min-height: 20px;
            padding: 0 8px;
            border-radius: 999px;
            background: rgba(53, 197, 98, 0.08);
            color: #2f9e44;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .catalog-paper-card__bookmark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            padding: 0;
            border: 0;
            background: transparent;
            color: #98a2b3;
            font-size: 13px;
        }
        .catalog-paper-card__media {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            flex: 1;
            color: inherit;
            text-decoration: none;
        }
        .catalog-paper-card__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 48px;
            width: 48px;
            height: 60px;
            border-radius: 12px;
            background: linear-gradient(180deg, #f2f6ff 0%, #e8efff 100%);
            border: 1px solid rgba(91, 55, 255, 0.08);
            box-shadow: inset 0 -10px 18px rgba(91, 55, 255, 0.05);
        }
        .catalog-paper-card__file {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 9px;
            background: #5b37ff;
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.02em;
        }
        .catalog-paper-card__body {
            display: flex;
            flex-direction: column;
            min-width: 0;
            gap: 4px;
        }
        .catalog-paper-card__eyebrow {
            color: #22a06b;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .catalog-paper-card__title {
            margin: 0;
            color: #161439;
            font-size: 14px;
            line-height: 1.35;
            font-weight: 800;
        }
        .catalog-paper-card__meta {
            color: #6d7491;
            font-size: 11px;
            line-height: 1.4;
        }
        .catalog-paper-card__footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid rgba(22, 20, 57, 0.08);
        }
        .catalog-paper-card__price {
            display: flex;
            align-items: baseline;
            gap: 4px;
            color: #167c37;
        }
        .catalog-paper-card__price-label {
            font-size: 11px;
            font-weight: 800;
        }
        .catalog-paper-card__price-value {
            font-size: 13px;
            font-weight: 800;
        }
        .catalog-paper-card__download {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 28px;
            padding: 0 10px;
            border: 1px solid rgba(22, 20, 57, 0.12);
            border-radius: 8px;
            background: #fff;
            color: #161439;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }
        .catalog-paper-card__download:hover {
            border-color: rgba(91, 55, 255, 0.28);
            color: #5b37ff;
        }
        .shimmer {
            position: relative;
            overflow: hidden;
        }
        .shimmer::after {
            content: "";
            position: absolute;
            inset: 0;
            transform: translateX(-100%);
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.7), transparent);
            animation: catalog-shimmer 1.2s ease-in-out infinite;
        }
        @keyframes catalog-shimmer {
            100% {
                transform: translateX(100%);
            }
        }
        .catalog-summary-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 6px;
            margin-bottom: 8px;
        }
        .catalog-summary-grid--hero {
            width: 100%;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin: 6px 0 0;
        }
        .catalog-summary-card {
            display: flex;
            align-items: center;
            gap: 7px;
            min-height: 42px;
            padding: 7px 9px;
            border: 1px solid rgba(22, 20, 57, 0.05);
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 6px 12px rgba(17, 24, 39, 0.03);
        }
        .catalog-summary-card__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 9px;
            background: color-mix(in srgb, var(--summary-accent, #5b37ff) 12%, white);
            color: var(--summary-accent, #5b37ff);
            flex: 0 0 auto;
        }
        .catalog-summary-card__copy strong {
            display: block;
            color: #161439;
            font-size: 13px;
            font-weight: 800;
            line-height: 1;
        }
        .catalog-summary-card__copy span {
            display: block;
            margin-top: 1px;
            color: #4d5373;
            font-size: 10px;
            font-weight: 500;
            line-height: 1.1;
        }
        .catalog-tabs--landing {
            gap: 8px;
            margin-bottom: 10px;
        }
        .catalog-tabs--landing .catalog-type-tab {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 40px;
            padding: 0 13px;
            border-radius: 11px;
            border-color: rgba(22, 20, 57, 0.08);
            color: #161439;
            box-shadow: 0 8px 16px rgba(17, 24, 39, 0.025);
        }
        .catalog-tabs--landing .catalog-type-tab.is-active {
            background: #5b37ff;
            border-color: #5b37ff;
        }
        .catalog-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }
        .catalog-toolbar__left {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 7px;
        }
        .catalog-toolbar__left > span,
        .catalog-toolbar__right > span {
            color: #161439;
            font-size: 13px;
            font-weight: 700;
        }
        .catalog-topic-pill {
            display: inline-flex;
            align-items: center;
            min-height: 32px;
            padding: 0 12px;
            border: 1px solid rgba(22, 20, 57, 0.08);
            border-radius: 9px;
            background: #fff;
            color: #4d5373;
            font-size: 12px;
            font-weight: 600;
        }
        .catalog-topic-pill.is-active,
        .catalog-topic-pill:hover {
            background: rgba(91, 55, 255, 0.08);
            border-color: rgba(91, 55, 255, 0.16);
            color: #5b37ff;
        }
        .catalog-topic-pill--more {
            gap: 6px;
        }
        .catalog-toolbar__right {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .catalog-toolbar__right select,
        .orderby {
            min-height: 38px;
            padding: 0 32px 0 11px;
            border: 1px solid rgba(22, 20, 57, 0.08);
            border-radius: 10px;
            background-color: #fff;
            color: #161439;
        }
        .catalog-load-more {
            display: flex;
            justify-content: center;
            margin-top: 14px;
        }
        .catalog-load-more__btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 42px;
            padding: 0 16px;
            border: 1px solid rgba(91, 55, 255, 0.16);
            border-radius: 11px;
            background: #fff;
            color: #5b37ff;
            font-weight: 700;
            box-shadow: 0 8px 16px rgba(17, 24, 39, 0.03);
        }
        .catalog-main-footer {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-top: 18px;
            padding: 18px 0 0;
        }
        .catalog-main-footer__item {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 70px;
            padding: 14px 16px;
            border: 1px solid rgba(91, 55, 255, 0.1);
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(91, 55, 255, 0.04), rgba(255, 255, 255, 0.98));
            box-shadow: 0 12px 24px rgba(17, 24, 39, 0.04);
        }
        .catalog-main-footer__item i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: rgba(91, 55, 255, 0.12);
            color: #5b37ff;
            font-size: 16px;
            flex: 0 0 auto;
        }
        .catalog-main-footer__item strong,
        .catalog-main-footer__item span {
            display: block;
            line-height: 1.2;
        }
        .catalog-main-footer__item strong {
            color: #161439;
            font-size: 14px;
            font-weight: 800;
        }
        .catalog-main-footer__item span {
            margin-top: 3px;
            color: #6d7491;
            font-size: 12px;
        }
        .catalog-main-actions {
            position: sticky;
            top: 8px;
            z-index: 30;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            width: 100%;
            margin: 0 0 8px;
            padding: 4px 4px 4px 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 255, 255, 0.82));
            backdrop-filter: blur(8px);
            border-radius: 12px;
        }
        .catalog-main-actions--curriculum {
            display: none;
        }
        .catalog-main-actions--toggle.catalog-rightbar-toggle,
        .catalog-curriculum-toggle,
        .catalog-rightbar__close {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 38px;
            padding: 0 14px;
            border: 1px solid rgba(91, 55, 255, 0.16);
            border-radius: 11px;
            background: #fff;
            color: #5b37ff;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 8px 16px rgba(17, 24, 39, 0.03);
        }
        .catalog-main-actions--toggle {
            cursor: pointer;
        }
        .catalog-rightbar-toggle,
        .catalog-curriculum-toggle,
        .catalog-rightbar__close {
            min-width: 0;
        }
        .catalog-curriculum-backdrop {
            display: none;
        }
        .catalog-rightbar {
            display: grid;
            gap: 10px;
        }
        .catalog-rightbar--drawer {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            width: 100%;
            min-height: 100%;
            height: 100%;
            max-height: none;
            overflow: auto;
            padding: 10px;
            border: 1px solid rgba(22, 20, 57, 0.06);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 24px 60px rgba(17, 24, 39, 0.16);
            opacity: 1;
            transform: none;
            transition: opacity 0.28s ease, box-shadow 0.28s ease;
        }
        .catalog-shell--rightbar-collapsed .catalog-rightbar--drawer {
            opacity: 1;
            overflow: hidden;
        }
        .catalog-shell--rightbar-collapsed .catalog-rightbar--drawer > :not(.catalog-rightbar__close) {
            display: none;
        }
        .catalog-shell--rightbar-collapsed .catalog-rightbar__close {
            justify-content: center;
            margin-bottom: 0;
        }
        .catalog-rightbar__close {
            width: 100%;
            justify-content: flex-end;
            margin: 0 0 10px;
            padding: 0 14px;
            min-height: 42px;
        }
        .catalog-right-card {
            padding: 10px;
            border: 1px solid rgba(22, 20, 57, 0.06);
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 10px 22px rgba(17, 24, 39, 0.04);
        }
        .catalog-right-card__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
        }
        .catalog-right-card__header h3,
        .catalog-offer-card h3,
        .catalog-help-card h3 {
            margin: 0;
            color: #161439;
            font-size: 14px;
            font-weight: 800;
        }
        .catalog-ai-card__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            border-radius: 8px;
            background: rgba(91, 55, 255, 0.12);
            color: #5b37ff;
        }
        .catalog-ai-card p {
            margin-bottom: 8px;
            color: #4d5373;
            font-size: 11px;
        }
        .catalog-ai-card__input {
            width: 100%;
            min-height: 36px;
            margin-bottom: 6px;
            padding: 0 10px;
            border: 1px solid rgba(22, 20, 57, 0.1);
            border-radius: 10px;
            font-size: 11px;
        }
        .catalog-ai-card__button,
        .catalog-offer-card__button,
        .catalog-credit-box__link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 36px;
            border-radius: 10px;
            background: linear-gradient(90deg, #5b37ff, #7c3aed);
            color: #fff;
            font-weight: 700;
            font-size: 12px;
        }
        .catalog-credit-box {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid rgba(22, 20, 57, 0.08);
        }
        .catalog-credit-box__row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 6px;
            color: #161439;
            font-size: 11px;
            font-weight: 700;
        }
        .catalog-credit-box__bar {
            height: 7px;
            border-radius: 999px;
            background: #e5f6eb;
            margin-bottom: 8px;
            overflow: hidden;
        }
        .catalog-credit-box__bar span {
            display: block;
            width: 75%;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #16a34a, #0ea5e9);
        }
        .catalog-credit-box__link {
            background: #fff;
            color: #5b37ff;
            border: 1px solid rgba(91, 55, 255, 0.18);
        }
        .catalog-offer-card {
            background: linear-gradient(180deg, #fffef7 0%, #ffffff 100%);
        }
        .catalog-offer-card ul {
            display: grid;
            gap: 6px;
            margin: 8px 0;
            padding: 0;
            list-style: none;
            color: #4d5373;
            font-size: 11px;
        }
        .catalog-offer-card ul li::before {
            content: "✓";
            margin-right: 8px;
            color: #16a34a;
            font-weight: 800;
        }
        .catalog-offer-card__price {
            margin-bottom: 8px;
            color: #161439;
            font-size: 18px;
            font-weight: 900;
        }
        .catalog-offer-card__price span {
            color: #7b7f98;
            font-size: 12px;
            font-weight: 600;
        }
        .catalog-offer-card h3 i {
            color: #f59e0b;
            margin-right: 6px;
        }
        .catalog-recent-card {
            background: #fff;
        }
        .catalog-recent-card .catalog-right-card__header a {
            color: #5b37ff;
            font-size: 13px;
            font-weight: 700;
        }
        .catalog-recent-list {
            display: grid;
            gap: 6px;
        }
        .catalog-recent-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .catalog-recent-item__thumb {
            width: 48px;
            height: 36px;
            border-radius: 9px;
            background: linear-gradient(135deg, #d1fae5, #93c5fd);
        }
        .catalog-recent-item__thumb--paper {
            background: linear-gradient(135deg, #fbcfe8, #ddd6fe);
        }
        .catalog-recent-item strong {
            display: block;
            color: #161439;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.25;
        }
        .catalog-recent-item span,
        .catalog-help-card p {
            color: #7b7f98;
            font-size: 10px;
        }
        .catalog-help-card {
            padding: 12px 10px;
            background: linear-gradient(180deg, #ffffff, #fff7ef);
        }
        .catalog-help-card p {
            margin: 6px 0 0;
        }

        .catalog-landing {
            padding: 10px 0 50px;
            background: linear-gradient(180deg, #ffffff 0%, #faf8ff 100%);
        }
        .catalog-hero {
            position: relative;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 10px;
            padding: 8px 16px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(91, 55, 255, 0.08), rgba(255, 255, 255, 0.96));
            box-shadow: 0 10px 22px rgba(91, 55, 255, 0.05);
            overflow: hidden;
        }
        .catalog-hero__copy {
            position: relative;
            z-index: 1;
        }
        .catalog-hero__title {
            margin: 0 0 4px;
            color: #161439;
            font-size: 24px;
            font-weight: 800;
            line-height: 1.05;
        }
        .catalog-hero__breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            color: #7b7f98;
            font-size: 12px;
            font-weight: 500;
        }
        .catalog-hero__breadcrumb a,
        .catalog-hero__breadcrumb .is-current {
            color: inherit;
        }
        .catalog-hero__breadcrumb .is-current {
            color: #161439;
            font-weight: 700;
        }
        .catalog-hero__crumb-separator {
            color: #b8bdd3;
        }
        .catalog-hero__art {
            position: relative;
            width: 180px;
            min-height: 58px;
            flex: 0 0 auto;
        }
        .catalog-hero__shape {
            position: absolute;
            border: 1px dashed rgba(91, 55, 255, 0.28);
            border-radius: 18px;
        }
        .catalog-hero__shape--paper {
            top: 6px;
            right: 52px;
            width: 42px;
            height: 24px;
            transform: rotate(16deg);
        }
        .catalog-hero__shape--stars {
            top: 0;
            right: 0;
            width: 90px;
            height: 46px;
            border-radius: 999px;
            border-style: dotted;
            border-width: 2px 0 0 0;
        }
        .catalog-hero__shape--note {
            right: 34px;
            bottom: -2px;
            width: 34px;
            height: 24px;
            transform: rotate(-12deg);
        }
        .catalog-layout {
            margin-top: 4px;
        }
        .catalog-sidebar {
            position: relative;
        }
        .catalog-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }
        .catalog-page-header {
            margin-bottom: 16px;
        }
        .catalog-page-title {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.2;
            color: #111;
        }
        .catalog-type-tab {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 0 16px;
            border: 1px solid #d9d9d9;
            background: #fff;
            color: #111;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
        }
        .catalog-type-tab.is-active {
            background: #111;
            border-color: #111;
            color: #fff;
        }
        .catalog-product-badge {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            padding: 0 9px;
            border-radius: 6px;
            background: #eef2ff;
            color: #26358c;
            font-size: 12px;
            font-weight: 700;
        }
        .catalog-holder > .catalog-grid-item {
            display: flex;
        }
        .catalog-holder {
            --bs-gutter-x: 4px;
            --bs-gutter-y: 4px;
        }
        .catalog-listing-card {
            display: flex;
            flex-direction: column;
            width: 100%;
            min-height: 100%;
            overflow: hidden;
            border: 1px solid color-mix(in srgb, var(--catalog-card-accent, #5b37ff) 10%, rgba(22, 20, 57, 0.06));
            border-radius: 14px;
            background:
                linear-gradient(180deg, color-mix(in srgb, var(--catalog-card-accent, #5b37ff) 5%, #ffffff) 0%, #ffffff 24%);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.035);
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .catalog-listing-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.07);
        }
        .catalog-listing-card__media {
            position: relative;
            padding: 7px 7px 5px;
        }
        .catalog-listing-card__badge {
            position: absolute;
            top: 12px;
            left: 12px;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-height: 21px;
            padding: 0 9px;
            border-radius: 999px;
            background: var(--catalog-card-accent, #5b37ff);
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            box-shadow: 0 10px 20px color-mix(in srgb, var(--catalog-card-accent, #5b37ff) 22%, transparent);
        }
        .catalog-listing-card__bookmark {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border: 1px solid rgba(22, 20, 57, 0.08);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.95);
            color: #475467;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
        }
        .catalog-listing-card__image-link {
            display: block;
            aspect-ratio: 4 / 3;
            overflow: hidden;
            border-radius: 11px;
            background: color-mix(in srgb, var(--catalog-card-accent, #5b37ff) 4%, #f8fafc);
        }
        .catalog-listing-card__image-link img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .catalog-listing-card__body {
            display: flex;
            flex-direction: column;
            gap: 5px;
            flex: 1;
            padding: 0 10px 10px;
        }
        .catalog-listing-card__title {
            margin: 0;
            color: #161439;
            font-size: 12px;
            font-weight: 800;
            line-height: 1.3;
        }
        .catalog-listing-card__title a {
            color: inherit;
        }
        .catalog-listing-card__meta-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 5px;
            flex-wrap: wrap;
            color: #667085;
            font-size: 11px;
        }
        .catalog-listing-card__category {
            color: var(--catalog-card-accent, #4f46e5);
            font-weight: 800;
        }
        .catalog-listing-card__rating {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            color: #f59e0b;
            font-weight: 800;
        }
        .catalog-listing-card__meta {
            margin: 0;
            color: #667085;
            font-size: 11px;
            line-height: 1.35;
        }
        .catalog-listing-card__footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 7px;
            margin-top: auto;
            padding-top: 6px;
            border-top: 1px solid rgba(22, 20, 57, 0.08);
        }
        .catalog-listing-card__price {
            display: flex;
            align-items: baseline;
            gap: 5px;
            flex-wrap: wrap;
            color: var(--catalog-card-accent, #5b37ff);
            font-weight: 900;
        }
        .catalog-listing-card__price-label {
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .catalog-listing-card__price-value {
            font-size: 16px;
            font-weight: 900;
            line-height: 1;
        }
        .catalog-listing-card__actions {
            flex: 0 0 auto;
        }
        .catalog-listing-card__actions--dual {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px;
            min-width: 0;
        }
        .catalog-listing-card__button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 31px;
            padding: 0 10px;
            border: 1px solid color-mix(in srgb, var(--catalog-card-accent, #5b37ff) 30%, white);
            border-radius: 11px;
            background: #fff;
            color: var(--catalog-card-accent, #5b37ff);
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
            box-shadow: 0 8px 16px rgba(15, 23, 42, 0.04);
            transition: background-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease, color 0.2s ease, border-color 0.2s ease;
        }
        .catalog-listing-card__button--buy-now {
            background: #ffc221 !important;
            border-color: #111827 !important;
            color: #111827 !important;
            box-shadow: 4px 4px 0 #111827 !important;
        }
        .catalog-listing-card__button--buy-now:hover,
        .catalog-listing-card__button--buy-now:focus-visible {
            background: #f4b800 !important;
            border-color: #111827 !important;
            color: #111827 !important;
            box-shadow: 5px 5px 0 #111827 !important;
        }
        .catalog-listing-card__button--buy-now.is-loading,
        .catalog-listing-card__button--buy-now[aria-busy="true"] {
            cursor: progress !important;
            transform: none !important;
            box-shadow: 4px 4px 0 #111827 !important;
        }
        .catalog-listing-card__button--buy-now.is-loading .text,
        .catalog-listing-card__button--buy-now[aria-busy="true"] .text {
            display: none;
        }
        .catalog-listing-card__actions--dual .catalog-listing-card__button {
            width: 100%;
            min-width: 0;
        }
        .catalog-listing-card__button:hover {
            transform: translateY(-1px);
            background: color-mix(in srgb, var(--catalog-card-accent, #5b37ff) 8%, white);
            border-color: color-mix(in srgb, var(--catalog-card-accent, #5b37ff) 48%, white);
            color: var(--catalog-card-accent, #5b37ff);
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.06);
        }
        .catalog-listing-card__button--solid {
            background: var(--catalog-card-accent, #5b37ff);
            border-color: var(--catalog-card-accent, #5b37ff);
            color: #fff;
            box-shadow: 0 10px 20px color-mix(in srgb, var(--catalog-card-accent, #5b37ff) 18%, transparent);
        }
        .catalog-holder .catalog-grid-item--document .catalog-paper-card {
            height: 100%;
        }
        .catalog-page--levels {
            padding-top: 4px;
        }
        .catalog-levels-hero {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
            padding: 20px 22px;
            border: 1px solid rgba(91, 55, 255, 0.08);
            border-radius: 22px;
            background: linear-gradient(135deg, rgba(91, 55, 255, 0.08), rgba(255, 255, 255, 0.98));
            box-shadow: 0 14px 30px rgba(17, 24, 39, 0.05);
        }
        .catalog-levels-hero__eyebrow {
            display: inline-flex;
            margin-bottom: 8px;
            color: #5b37ff;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .catalog-levels-hero__title {
            margin: 0;
            color: #161439;
            font-size: clamp(24px, 3vw, 36px);
            font-weight: 800;
            letter-spacing: -0.04em;
        }
        .catalog-levels-hero__subtitle {
            max-width: 720px;
            margin: 8px 0 0;
            color: #4d5373;
            font-size: 14px;
            line-height: 1.6;
        }
        .catalog-levels-hero__back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex: 0 0 auto;
            min-height: 42px;
            padding: 0 14px;
            border: 1px solid rgba(91, 55, 255, 0.16);
            border-radius: 12px;
            background: #fff;
            color: #5b37ff;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 8px 16px rgba(17, 24, 39, 0.03);
        }
        .catalog-levels-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }
        .catalog-level-card {
            display: grid;
            grid-template-columns: 52px minmax(0, 1fr);
            grid-template-areas:
                "icon copy"
                "icon chips";
            gap: 4px 14px;
            align-items: center;
            padding: 18px;
            border: 1px solid rgba(22, 20, 57, 0.08);
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 10px 22px rgba(17, 24, 39, 0.04);
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .catalog-level-card:hover {
            transform: translateY(-2px);
            border-color: color-mix(in srgb, var(--level-accent, #5b37ff) 20%, #ffffff);
            box-shadow: 0 16px 28px rgba(17, 24, 39, 0.08);
        }
        .catalog-level-card__icon {
            grid-area: icon;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: color-mix(in srgb, var(--level-accent, #5b37ff) 12%, #ffffff);
            color: var(--level-accent, #5b37ff);
            font-size: 20px;
        }
        .catalog-level-card__copy {
            grid-area: copy;
            display: grid;
            gap: 4px;
        }
        .catalog-level-card__copy strong {
            color: #161439;
            font-size: 16px;
            font-weight: 800;
            line-height: 1.2;
        }
        .catalog-level-card__copy span {
            color: #6d7491;
            font-size: 12px;
            font-weight: 600;
        }
        .catalog-level-card__chips {
            grid-area: chips;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .catalog-level-card__chips span {
            display: inline-flex;
            align-items: center;
            min-height: 26px;
            padding: 0 10px;
            border-radius: 999px;
            background: rgba(91, 55, 255, 0.08);
            color: #5b37ff;
            font-size: 12px;
            font-weight: 700;
        }
        @media (max-width: 1599.98px) {
            .catalog-summary-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            .catalog-summary-grid--hero {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 1399.98px) {
            .catalog-hero--compact {
                padding: 6px 12px;
            }
            .catalog-hero__title {
                font-size: 20px;
            }
            .catalog-toolbar {
                flex-direction: column;
                align-items: flex-start;
            }
            .catalog-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .catalog-main-footer {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 1199.98px) {
            .catalog-rightbar {
                margin-top: 4px;
            }
            .catalog-shell__sidebar-col {
                flex-basis: min(284px, calc(100vw - 32px));
                width: min(284px, calc(100vw - 32px));
                max-width: min(284px, calc(100vw - 32px));
                min-height: calc(100vh - 120px);
            }
            .catalog-shell__sidebar-col .catalog-rightbar--drawer {
                max-height: calc(100vh - 108px);
                height: 100%;
            }
            .catalog-hero__art {
                display: none;
            }
            .catalog-levels-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 991.98px) {
            .catalog-main-actions--curriculum {
                display: flex;
                justify-content: flex-start;
                margin-bottom: 8px;
                z-index: 40;
            }
            .catalog-curriculum-backdrop {
                display: block;
                position: fixed;
                inset: 0;
                z-index: 38;
                background: rgba(17, 24, 39, 0.32);
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.28s ease;
            }
            .catalog-shell__grid {
                flex-wrap: wrap;
            }
            .catalog-shell__curriculum-col,
            .catalog-shell__main-col,
            .catalog-shell__sidebar-col {
                flex-basis: 100%;
                width: 100%;
                max-width: 100%;
            }
            .catalog-shell__main-col {
                order: 1;
            }
            .catalog-shell__curriculum-col {
                position: fixed;
                top: 118px;
                left: 12px;
                z-index: 39;
                width: min(320px, calc(100vw - 24px));
                max-width: min(320px, calc(100vw - 24px));
                height: calc(100vh - 136px);
                min-height: 0;
                order: 2;
                transform: translateX(-110%);
                opacity: 1;
                pointer-events: none;
                padding: 0;
                background: #fff;
                border-radius: 18px;
                box-shadow: 0 24px 60px rgba(17, 24, 39, 0.18);
                overflow: hidden;
                transition: transform 0.28s ease, opacity 0.28s ease, box-shadow 0.28s ease;
            }
            .catalog-shell--curriculum-open .catalog-shell__curriculum-col {
                transform: translateX(0);
                pointer-events: auto;
            }
            .catalog-shell--curriculum-collapsed .catalog-shell__curriculum-col {
                transform: translateX(-110%);
            }
            .catalog-shell--curriculum-open ~ .catalog-curriculum-backdrop {
                opacity: 1;
                pointer-events: auto;
            }
            .catalog-shell__curriculum-col .catalog-sidebar {
                height: 100%;
                overflow: auto;
                padding: 12px;
            }
            .catalog-shell__sidebar-col {
                order: 3;
                min-height: auto;
            }
            .catalog-shell__sidebar-col .catalog-rightbar--drawer {
                position: relative;
                top: auto;
                max-height: none;
                height: auto;
            }
            .catalog-shell--rightbar-collapsed .catalog-shell__sidebar-col {
                flex-basis: 100%;
                width: 100%;
                max-width: 100%;
            }
            .catalog-shell--rightbar-collapsed .catalog-rightbar--drawer {
                padding: 10px;
            }
            .catalog-shell--rightbar-collapsed .catalog-rightbar__close {
                width: 100%;
                min-height: 42px;
                margin-bottom: 0;
                padding: 0 14px;
                border-radius: 12px;
                justify-content: center;
            }
            .catalog-shell--rightbar-collapsed .catalog-rightbar__close span {
                display: inline;
            }
            .catalog-shell--rightbar-collapsed .catalog-rightbar__close i {
                font-size: inherit;
            }
            .catalog-shell--rightbar-collapsed .catalog-rightbar--drawer > :not(.catalog-rightbar__close) {
                display: none;
            }
            .catalog-card {
                padding: 13px;
            }
            .catalog-card__header h3 {
                font-size: 18px;
            }
            .catalog-tree {
                gap: 5px;
            }
            .catalog-tree__summary {
                padding: 9px 10px;
                font-size: 14px;
            }
            .catalog-tree__item {
                min-height: 28px;
                padding: 6px 9px;
                font-size: 13px;
            }
            .catalog-tree__item--level-1,
            .catalog-tree__item--level-2 {
                min-height: 24px;
                font-size: 12px;
            }
            .catalog-tabs {
                gap: 6px;
                margin-bottom: 16px;
                overflow-x: auto;
                flex-wrap: nowrap;
                padding-bottom: 2px;
                scrollbar-width: none;
            }
            .catalog-tabs::-webkit-scrollbar {
                display: none;
            }
            .catalog-type-tab {
                flex: 0 0 auto;
                white-space: nowrap;
            }
            .catalog-hero--compact {
                flex-direction: column;
            }
            .catalog-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .catalog-main-actions {
                justify-content: stretch;
            }
            .catalog-levels-hero {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        @media (max-width: 767.98px) {
            .catalog-shell {
                gap: 6px;
            }
            .catalog-main-actions--curriculum {
                margin-bottom: 6px;
            }
            .catalog-curriculum-toggle {
                width: 100%;
                justify-content: center;
            }
            .catalog-summary-grid {
                grid-template-columns: 1fr;
            }
            .catalog-main-footer {
                grid-template-columns: 1fr;
            }
            .catalog-shell__curriculum-col,
            .catalog-shell__main-col,
            .catalog-shell__sidebar-col {
                flex-basis: 100%;
                width: 100%;
                max-width: 100%;
            }
            .catalog-shell__main-col {
                order: 1;
            }
            .catalog-shell__curriculum-col {
                top: 112px;
                left: 10px;
                width: min(300px, calc(100vw - 20px));
                max-width: min(300px, calc(100vw - 20px));
                height: calc(100vh - 126px);
            }
            .catalog-focus__copy h2,
            .catalog-hero__title {
                font-size: 19px;
            }
            .catalog-focus__icon {
                width: 42px;
                height: 42px;
                font-size: 16px;
            }
            .catalog-type-tab {
                min-height: 32px;
                padding: 0 12px;
                font-size: 13px;
            }
            .catalog-tree__summary {
                padding: 8px 9px;
                font-size: 13px;
            }
            .catalog-tree__item {
                min-height: 26px;
                padding: 6px 8px;
                font-size: 12px;
            }
            .catalog-tree__item--level-1,
            .catalog-tree__item--level-2 {
                min-height: 22px;
                font-size: 12px;
            }
            .catalog-tree__children {
                padding-left: 12px;
                margin-left: 8px;
            }
            .catalog-tree__children--nested {
                margin-left: 14px;
            }
            .catalog-right-card {
                padding: 9px;
            }
            .catalog-ai-card__button,
            .catalog-offer-card__button,
            .catalog-credit-box__link {
                min-height: 34px;
            }
            .catalog-main-footer__item {
                padding: 12px 14px;
                min-height: 64px;
            }
            .catalog-levels-grid {
                grid-template-columns: 1fr;
            }
            .catalog-level-card {
                grid-template-columns: 48px minmax(0, 1fr);
                padding: 16px;
            }
            .catalog-level-card__icon {
                width: 48px;
                height: 48px;
            }
            .catalog-paper-card {
                min-height: 172px;
            }
            .catalog-paper-card__footer {
                flex-wrap: wrap;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        window.__catalogStrings = {
            loadFailedTitle: @json(__('Could not load resources')),
            loadFailedMessage: @json(__('Please try again in a moment.')),
        };
    </script>
    <script src="{{ asset('frontend/js/default/catalog-page.js') }}?v={{ filemtime(public_path('frontend/js/default/catalog-page.js')) }}"></script>
@endpush
