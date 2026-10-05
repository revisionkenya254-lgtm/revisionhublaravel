@extends('frontend.instructor-dashboard.layouts.master')

@section('dashboard-contents')
    @php
        $errors = $errors ?? new \Illuminate\Support\ViewErrorBag();
        $noteType = \App\Models\Product::TYPE_NOTE;
        $productRecord = $product ?? null;
        $note = $noteForm ?? [];
        $action = route('instructor.products.store');
        $backUrl = route('instructor.products.index', ['type' => $noteType]);
        $titleValue = old('title', $productRecord?->title ?? ($note['title'] ?? ''));
        $descriptionValue = old('description', $productRecord?->description ?? ($note['description'] ?? ''));
        $statusValue = old('status', $note['status'] ?? 'is_draft');
        $tagsValue = old('tags', is_array($note['tags'] ?? null) ? implode(', ', $note['tags']) : ($note['tags'] ?? ''));
        $priceValue = old('price', $productRecord?->price ?? ($note['price'] ?? ''));
        $languageValue = old('language', $note['language'] ?? 'English');
        $educationLevelValue = old('education_level', $note['education_level'] ?? 'Senior School');
        $classGradeValue = old('class_grade', $note['class_grade'] ?? 'Grade 10');
        $examCategoryValue = old('exam_category', $note['exam_category'] ?? 'KCPE');
        $subjectValue = old('subject', $note['subject'] ?? 'STEM');
        $yearValue = old('year', $note['year'] ?? now()->year);
        $topicValue = old('topic', $note['topic'] ?? 'Algebra');
        $subTopicValue = old('sub_topic', $note['sub_topic'] ?? 'Quadratic Equations');
        $accessTypeValue = old('access_type', $note['access_type'] ?? 'paid');
        $noteEducationLevels = collect($paperEducationCategories ?? $educationCategories ?? $categories ?? [])
            ->map(fn ($category) => $category->translation?->name ?? $category->name ?? $category->label ?? (string) $category)
            ->filter()
            ->values()
            ->all();
        if (! filled($noteEducationLevels)) {
            $noteEducationLevels = $metadataOptions['education_levels'] ?? [];
        }
        $noteExamCategories = $metadataOptions['exam_categories'] ?? ['KCPE', 'KCSE', 'Mock', 'Mid-term', 'End-term'];
        $noteYears = $metadataOptions['years'] ?? range((int) now()->year, (int) now()->year - 20);
        $resolveClassGradeLabel = static function (?string $educationLevel): string {
            $level = strtolower(trim((string) $educationLevel));

            return preg_match('/tvet|university|college|certificate|diploma|undergraduate|tertiary|higher education/', $level)
                ? __('School of')
                : __('Class / Grade');
        };
        $normalizeLookupLabel = static function (?string $value): string {
            return strtolower(preg_replace('/\s+/', ' ', trim((string) $value)));
        };
        $findNodeByLabel = static function (array $nodes, string $label) use (&$findNodeByLabel, $normalizeLookupLabel): ?array {
            $normalizedLabel = $normalizeLookupLabel($label);

            foreach ($nodes as $node) {
                $nodeLabel = $node['label'] ?? $node['name'] ?? $node['title'] ?? '';
                $nodeSlug = $node['slug'] ?? '';

                if ($normalizeLookupLabel($nodeLabel) === $normalizedLabel || $normalizeLookupLabel($nodeSlug) === $normalizedLabel) {
                    return $node;
                }

                if (! empty($node['children']) && is_array($node['children'])) {
                    $match = $findNodeByLabel($node['children'], $label);
                    if ($match) {
                        return $match;
                    }
                }
            }

            return null;
        };
        $noteEducationTree = collect($educationTree ?? $paperEducationCategories ?? $educationCategories ?? $categories ?? [])
            ->map(function ($category) {
                if (is_array($category)) {
                    return $category;
                }

                return [
                    'label' => $category->translation?->name ?? $category->name ?? $category->label ?? (string) $category,
                    'slug' => $category->slug ?? '',
                    'children' => [],
                ];
            })
            ->values()
            ->all();
        $noteClassGradeOptions = [];
        if (! empty($noteEducationTree)) {
            $selectedLevelNode = $findNodeByLabel($noteEducationTree, $educationLevelValue);
            $noteClassGradeOptions = collect($selectedLevelNode['children'] ?? [])
                ->map(fn ($child) => $child['label'] ?? $child['name'] ?? $child['title'] ?? '')
                ->filter()
                ->values()
                ->all();
        }
        if (! filled($noteClassGradeOptions)) {
            $noteClassGradeOptions = $metadataOptions['class_grades_by_level'][$educationLevelValue]
                ?? ($metadataOptions['class_grades_by_level']['default'] ?? ['Grade 10', 'Grade 11', 'Grade 12']);
        }
        if (filled($classGradeValue) && ! in_array($classGradeValue, $noteClassGradeOptions, true)) {
            $noteClassGradeOptions[] = $classGradeValue;
        }
        $selectedClassGradeNode = null;
        if (! empty($noteEducationTree)) {
            $selectedLevelNode = $findNodeByLabel($noteEducationTree, $educationLevelValue);
            $selectedClassGradeNode = $selectedLevelNode
                ? $findNodeByLabel($selectedLevelNode['children'] ?? [], $classGradeValue)
                : null;
        }
        $noteSubjectOptions = collect($selectedClassGradeNode['children'] ?? [])
            ->map(fn ($child) => $child['label'] ?? $child['name'] ?? $child['title'] ?? '')
            ->filter()
            ->values()
            ->all();
        if (! filled($noteSubjectOptions)) {
            $noteSubjectOptions = ['STEM', 'Social Sciences', 'Arts & Sports', 'Languages'];
        }
        if (filled($subjectValue) && ! in_array($subjectValue, $noteSubjectOptions, true)) {
            $noteSubjectOptions[] = $subjectValue;
        }
        $classGradeLabel = $resolveClassGradeLabel($educationLevelValue);
        $tagList = collect(explode(',', (string) $tagsValue))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->values();
        $stepItems = [
            ['number' => 1, 'label' => __('Basic Information'), 'active' => true, 'url' => route('instructor.products.create', ['type' => 'note'])],
            ['number' => 2, 'label' => __('Content'), 'active' => false, 'url' => route('instructor.products.create', ['type' => 'note', 'step' => 2])],
            ['number' => 3, 'label' => __('Attachments'), 'active' => false, 'url' => route('instructor.products.create', ['type' => 'note', 'step' => 3])],
            ['number' => 4, 'label' => __('Settings'), 'active' => false, 'url' => route('instructor.products.create', ['type' => 'note', 'step' => 4])],
            ['number' => 5, 'label' => __('Review & Publish'), 'active' => false, 'url' => route('instructor.products.create', ['type' => 'note', 'step' => 5])],
        ];
    @endphp

    <div class="note-builder-page" data-note-wizard data-note-wizard-step="1" data-note-wizard-state='@json($wizardState ?? [])'>
        <div class="note-builder-page__top">
            <nav class="note-breadcrumb" aria-label="{{ __('Breadcrumb') }}">
                <a href="{{ route('instructor.dashboard') }}">{{ __('Dashboard') }}</a>
                <span>/</span>
                <a href="{{ $backUrl }}">{{ __('Notes') }}</a>
                <span>/</span>
                <span>{{ __('Create New Note') }}</span>
            </nav>

            <button type="button" class="note-preview-button">
                <i class="far fa-eye"></i>
                <span>{{ __('Preview Note') }}</span>
            </button>
        </div>

        <div class="note-builder-page__hero">
            <div>
                <p class="note-builder-page__eyebrow">{{ __('Instructor Notes') }}</p>
                <h1>{{ __('Create New Note') }}</h1>
                <p>{{ __('Start with a clear note title, then add the details students need before you move to the content step.') }}</p>
            </div>

            <div class="note-builder-page__hero-card">
                <span>{{ __('Step 1 of 5') }}</span>
                <strong>{{ __('Basic Information') }}</strong>
                <p>{{ __('Set the foundation for your note and prepare it for the remaining steps.') }}</p>
            </div>
        </div>

        <div class="note-stepper" role="list" aria-label="{{ __('Note creation progress') }}">
            @foreach ($stepItems as $step)
                <div class="note-stepper__item {{ $step['active'] ? 'is-active' : '' }}" role="button" tabindex="0" data-note-step-url="{{ $step['url'] }}" aria-label="{{ __('Go to :step', ['step' => $step['label']]) }}">
                    <span class="note-stepper__dot">{{ $step['number'] }}</span>
                    <span class="note-stepper__label">{{ $step['label'] }}</span>
                    @if (! $loop->last)
                        <span class="note-stepper__line"></span>
                    @endif
                </div>
            @endforeach
        </div>

        <form action="{{ $action }}" method="POST" class="note-builder-grid" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="type" value="{{ $noteType }}">
            <input type="hidden" name="status" value="{{ $statusValue }}">

            <section class="note-card note-card--main">
                <div class="note-card__head">
                    <div>
                        <h2>{{ __('Basic Information') }}</h2>
                        <p>{{ __('These details shape the note card students will see in the catalog.') }}</p>
                    </div>
                    <span class="note-status-pill note-status-pill--success">{{ __('Draft ready') }}</span>
                </div>

                <div class="note-field note-field--full">
                    <label for="title">{{ __('Note Title') }} <span>*</span></label>
                    <input id="title" name="title" type="text" required value="{{ $titleValue }}" placeholder="{{ __('Quadratic Equations - Complete Notes') }}">
                    @error('title')
                        <small class="note-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="note-field note-field--full">
                    <label for="description">{{ __('Short Description') }} <span>*</span></label>
                    <textarea id="description" name="description" rows="4" required placeholder="{{ __('Detailed notes covering the topic, examples and revision hints.') }}">{{ $descriptionValue }}</textarea>
                    @error('description')
                        <small class="note-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="note-field-grid note-field-grid--3">
                    <div class="note-field">
                        <label for="education_level">{{ __('Education Level') }} <span>*</span></label>
                        <select id="education_level" name="education_level" required>
                            @foreach ($noteEducationLevels as $option)
                                <option value="{{ $option }}" @selected($educationLevelValue === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="note-field">
                        <label for="class_grade">{{ $classGradeLabel }} <span>*</span></label>
                        <select id="class_grade" name="class_grade" required data-note-class-grade-options='@json($noteClassGradeOptions)'>
                            <option value="" @selected($classGradeValue === '')>{{ __('None') }}</option>
                            @foreach ($noteClassGradeOptions as $option)
                                <option value="{{ $option }}" @selected($classGradeValue === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="note-field">
                        <label for="subject">{{ __('Subject') }} <span>*</span></label>
                        <select id="subject" name="subject" required>
                            <option value="" @selected($subjectValue === '')>{{ __('None') }}</option>
                            @foreach ($noteSubjectOptions as $option)
                                <option value="{{ $option }}" @selected($subjectValue === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="note-field-grid note-field-grid--3">
                    <div class="note-field">
                        <label for="exam_category">{{ __('Exam Category') }} <span>*</span></label>
                        <select id="exam_category" name="exam_category" required>
                            <option value="" @selected($examCategoryValue === '')>{{ __('None') }}</option>
                            @foreach ($noteExamCategories as $option)
                                <option value="{{ $option }}" @selected($examCategoryValue === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="note-field">
                        <label for="year">{{ __('Year') }}</label>
                        <select id="year" name="year">
                            <option value="" @selected($yearValue === '')>{{ __('None') }}</option>
                            @foreach ($noteYears as $option)
                                <option value="{{ $option }}" @selected((string) $yearValue === (string) $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="note-field">
                        <label for="topic">{{ __('Topic') }} <span>*</span></label>
                        <input id="topic" name="topic" type="text" required value="{{ $topicValue }}" placeholder="{{ __('Algebra') }}">
                    </div>

                    <div class="note-field">
                        <label for="sub_topic">{{ __('Sub Topic') }}</label>
                        <input id="sub_topic" name="sub_topic" type="text" value="{{ $subTopicValue }}" placeholder="{{ __('Quadratic Equations') }}">
                    </div>
                </div>

                <div class="note-field note-field--full">
                    <label for="tags">{{ __('Tags') }}</label>
                    <input id="tags" name="tags" type="text" value="{{ $tagsValue }}" placeholder="{{ __('KCSE, Mathematics, Algebra, Grade 10') }}">
                    <div class="note-tags">
                        @forelse ($tagList as $tag)
                            <span>{{ $tag }} <i class="fas fa-xmark"></i></span>
                        @empty
                            <small>{{ __('Use commas to add tags students can search by.') }}</small>
                        @endforelse
                    </div>
                </div>

                <div class="note-field-grid note-field-grid--3">
                    <div class="note-field">
                        <label for="access_type">{{ __('Access Type') }}</label>
                        <div class="note-radios">
                            <label>
                                <input type="radio" name="access_type" value="paid" @checked($accessTypeValue === 'paid')>
                                <span>{{ __('Paid (Premium)') }}</span>
                            </label>
                            <label>
                                <input type="radio" name="access_type" value="free" @checked($accessTypeValue === 'free')>
                                <span>{{ __('Free') }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="note-field">
                        <label for="price">{{ __('Price (KES)') }} <span>*</span></label>
                        <input id="price" name="price" type="number" min="0" step="0.01" required value="{{ $priceValue }}">
                        @error('price')
                            <small class="note-error">{{ $message }}</small>
                        @enderror
                    </div>

                </div>

                <div class="note-field-grid note-field-grid--2">
                    <div class="note-field">
                        <label for="language">{{ __('Language') }}</label>
                        <select id="language" name="language">
                            @foreach (['English', 'Swahili', 'Bilingual'] as $option)
                                <option value="{{ $option }}" @selected($languageValue === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="note-field">
                        <label>{{ __('Visibility') }}</label>
                        <small class="note-field__hint">{{ __('Set visibility from the Settings step before publishing.') }}</small>
                    </div>
                </div>

                <div class="note-card__footer">
                    <a href="{{ $backUrl }}" class="note-action note-action--ghost">
                        <i class="fas fa-arrow-left"></i>
                        <span>{{ __('Back to Notes') }}</span>
                    </a>
                    <a href="{{ route('instructor.products.create', ['type' => 'note', 'step' => 2]) }}" class="note-action note-action--primary">
                        <span>{{ __('Next: Add Content') }}</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </section>

            <aside class="note-sidebar">
                <section class="note-card note-card--summary">
                    <div class="note-summary__head">
                        <div>
                            <p>{{ __('Note Snapshot') }}</p>
                            <h3>{{ __('What students will see') }}</h3>
                        </div>
                        <span class="note-status-pill">{{ __('Step 1') }}</span>
                    </div>

                    <div class="note-summary__hero">
                        <span>{{ __('Draft note') }}</span>
                        <strong>{{ $titleValue ?: __('Quadratic Equations - Complete Notes') }}</strong>
                        <p>{{ $descriptionValue ?: __('A polished note introduction helps students know what they are about to read.') }}</p>
                    </div>

                    <div class="note-summary__stats">
                        <div>
                            <span>{{ __('Education') }}</span>
                            <strong>{{ $educationLevelValue }}</strong>
                        </div>
                        <div>
                            <span>{{ __('Class') }}</span>
                            <strong>{{ $classGradeValue }}</strong>
                        </div>
                        <div>
                            <span>{{ __('Subject') }}</span>
                            <strong>{{ $subjectValue }}</strong>
                        </div>
                    </div>

                    <div class="note-summary__meta">
                        <div>
                            <span>{{ __('Topic') }}</span>
                            <strong>{{ $topicValue }}</strong>
                        </div>
                        <div>
                            <span>{{ __('Sub Topic') }}</span>
                            <strong>{{ $subTopicValue }}</strong>
                        </div>
                        <div>
                            <span>{{ __('Access') }}</span>
                            <strong>{{ $accessTypeValue === 'free' ? __('Free') : __('Paid') }}</strong>
                        </div>
                        <div>
                            <span>{{ __('Price') }}</span>
                            <strong data-note-price-display>{{ filled($priceValue) ? defaultCurrency((float) $priceValue) : '' }}</strong>
                        </div>
                    </div>
                </section>

                <section class="note-card note-card--tips">
                    <div class="note-summary__head">
                        <div>
                            <p>{{ __('Quick Tips') }}</p>
                            <h3>{{ __('Make the note easier to buy') }}</h3>
                        </div>
                    </div>

                    <div class="note-tip-list">
                        <article class="note-tip">
                            <span class="note-tip__icon note-tip__icon--indigo">T</span>
                            <div>
                                <strong>{{ __('Create a descriptive title') }}</strong>
                                <p>{{ __('Use a title that tells students the topic and scope immediately.') }}</p>
                            </div>
                        </article>

                        <article class="note-tip">
                            <span class="note-tip__icon note-tip__icon--blue"><i class="fas fa-list"></i></span>
                            <div>
                                <strong>{{ __('Add a detailed description') }}</strong>
                                <p>{{ __('Explain what the note covers, what students will learn and why it matters.') }}</p>
                            </div>
                        </article>

                        <article class="note-tip">
                            <span class="note-tip__icon note-tip__icon--green"><i class="fas fa-tag"></i></span>
                            <div>
                                <strong>{{ __('Use relevant tags') }}</strong>
                                <p>{{ __('Tags make the note easier to search and surface in the dashboard catalog.') }}</p>
                            </div>
                        </article>

                        <article class="note-tip">
                            <span class="note-tip__icon note-tip__icon--amber"><i class="far fa-image"></i></span>
                            <div>
                                <strong>{{ __('Keep the preview focused') }}</strong>
                                <p>{{ __('Preview pages should tease the content without giving away everything.') }}</p>
                            </div>
                        </article>
                    </div>
                </section>
            </aside>
        </form>
    </div>
@endsection

@push('styles')
    <style>
        .note-builder-page {
            display: grid;
            gap: 18px;
            padding-bottom: 20px;
        }

        .note-builder-page__top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .note-breadcrumb {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #667085;
            font-size: 13px;
            font-weight: 500;
            flex-wrap: wrap;
        }

        .note-breadcrumb a {
            color: #5b57d6;
            font-weight: 700;
        }

        .note-preview-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 18px;
            border: 1px solid #5b57d6;
            border-radius: 12px;
            background: #fff;
            color: #5b57d6;
            font-weight: 700;
            box-shadow: 0 10px 24px rgba(91, 87, 214, 0.08);
        }

        .note-builder-page__hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 248px;
            gap: 18px;
            align-items: stretch;
        }

        .note-builder-page__eyebrow {
            margin: 0 0 8px;
            color: #5b57d6;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .note-builder-page__hero h1 {
            margin: 0;
            font-size: clamp(30px, 3.5vw, 44px);
            line-height: 1.05;
            color: #16213f;
            font-weight: 900;
        }

        .note-builder-page__hero p {
            max-width: 720px;
            margin: 10px 0 0;
            color: #667085;
            font-size: 15px;
        }

        .note-builder-page__hero-card {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 8px;
            padding: 18px;
            border: 1px solid rgba(91, 87, 214, 0.14);
            border-radius: 18px;
            background: linear-gradient(145deg, rgba(91, 87, 214, 0.09), rgba(255, 255, 255, 0.95));
            box-shadow: 0 18px 42px rgba(20, 33, 61, 0.05);
        }

        .note-builder-page__hero-card span {
            color: #5b57d6;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .note-builder-page__hero-card strong {
            color: #16213f;
            font-size: 18px;
            font-weight: 800;
        }

        .note-builder-page__hero-card p {
            margin: 0;
            color: #667085;
            font-size: 13px;
            line-height: 1.6;
        }

        .note-stepper {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
        }

        .note-stepper__item {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            align-items: center;
            gap: 12px;
            min-height: 44px;
        }

        .note-stepper__dot {
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            border-radius: 999px;
            border: 1px solid #d9e0f0;
            background: #fff;
            color: #49607f;
            font-size: 14px;
            font-weight: 800;
        }

        .note-stepper__label {
            color: #4e5e77;
            font-size: 14px;
            font-weight: 700;
        }

        .note-stepper__line {
            display: block;
            height: 1px;
            background: linear-gradient(90deg, rgba(91, 87, 214, 0.28), rgba(216, 222, 234, 0.6));
            width: 100%;
        }

        .note-stepper__item.is-active .note-stepper__dot {
            border-color: transparent;
            background: linear-gradient(135deg, #5b57d6, #6d4fff);
            color: #fff;
            box-shadow: 0 12px 24px rgba(91, 87, 214, 0.28);
        }

        .note-stepper__item.is-active .note-stepper__label {
            color: #16213f;
        }

        .note-builder-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.55fr) minmax(300px, 0.95fr);
            gap: 18px;
            align-items: start;
        }

        .note-card {
            border: 1px solid rgba(216, 222, 234, 0.95);
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 18px 48px rgba(20, 33, 61, 0.05);
        }

        .note-card--main {
            padding: 22px;
        }

        .note-card__head,
        .note-summary__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }

        .note-card__head h2,
        .note-summary__head h3 {
            margin: 0;
            color: #5b57d6;
            font-size: 24px;
            font-weight: 900;
        }

        .note-card__head p,
        .note-summary__head p {
            margin: 6px 0 0;
            color: #6a7287;
            font-size: 13px;
            line-height: 1.6;
        }

        .note-status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(91, 87, 214, 0.1);
            color: #5b57d6;
            font-size: 13px;
            font-weight: 800;
            white-space: nowrap;
        }

        .note-status-pill--success {
            background: rgba(22, 163, 74, 0.12);
            color: #15945c;
        }

        .note-field,
        .note-field-grid {
            display: grid;
            gap: 8px;
        }

        .note-field--full {
            margin-bottom: 16px;
        }

        .note-field-grid {
            margin-bottom: 16px;
        }

        .note-field-grid--2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .note-field-grid--3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .note-field label {
            color: #1f2d4a;
            font-size: 14px;
            font-weight: 700;
        }

        .note-field label span {
            color: #ef4444;
        }

        .note-field input,
        .note-field select,
        .note-field textarea {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #d7ddea;
            border-radius: 12px;
            background: #fff;
            color: #16213f;
            font-size: 14px;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .note-field textarea {
            resize: vertical;
            min-height: 122px;
        }

        .note-field input:focus,
        .note-field select:focus,
        .note-field textarea:focus {
            outline: 0;
            border-color: rgba(91, 87, 214, 0.55);
            box-shadow: 0 0 0 4px rgba(91, 87, 214, 0.08);
        }

        .note-radios {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .note-radios label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 13px;
            border: 1px solid #d7ddea;
            border-radius: 12px;
            background: #fbfcff;
            color: #344767;
            font-size: 13px;
            font-weight: 600;
        }

        .note-radios input {
            margin: 0;
        }

        .note-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            min-height: 30px;
        }

        .note-tags span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(91, 87, 214, 0.1);
            color: #5b57d6;
            font-size: 13px;
            font-weight: 700;
        }

        .note-tags small {
            color: #6a7287;
            font-size: 13px;
        }

        .note-field__hint {
            color: #6a7287;
            font-size: 13px;
            line-height: 1.5;
        }

        .note-error {
            color: #ef4444;
            font-size: 12px;
            font-weight: 600;
        }

        .note-sidebar {
            display: grid;
            gap: 18px;
        }

        .note-card--summary,
        .note-card--tips {
            padding: 20px;
        }

        .note-summary__hero {
            padding: 18px;
            border-radius: 18px;
            background: linear-gradient(145deg, rgba(91, 87, 214, 0.12), rgba(91, 87, 214, 0.03));
            border: 1px solid rgba(91, 87, 214, 0.08);
            margin-bottom: 16px;
        }

        .note-summary__hero span {
            display: inline-flex;
            margin-bottom: 10px;
            color: #5b57d6;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .note-summary__hero strong {
            display: block;
            color: #16213f;
            font-size: 20px;
            line-height: 1.2;
            font-weight: 900;
        }

        .note-summary__hero p {
            margin: 10px 0 0;
            color: #667085;
            font-size: 14px;
            line-height: 1.6;
        }

        .note-summary__stats,
        .note-summary__meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .note-summary__stats div,
        .note-summary__meta div {
            padding: 14px;
            border: 1px solid #e2e8f3;
            border-radius: 14px;
            background: #fbfcff;
        }

        .note-summary__stats span,
        .note-summary__meta span {
            display: block;
            margin-bottom: 6px;
            color: #667085;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .note-summary__stats strong,
        .note-summary__meta strong {
            color: #16213f;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.35;
        }

        .note-summary__stats {
            margin-bottom: 12px;
        }

        .note-tip-list {
            display: grid;
            gap: 14px;
        }

        .note-tip {
            display: grid;
            grid-template-columns: 44px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
        }

        .note-tip__icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border-radius: 16px;
            font-size: 18px;
            font-weight: 800;
        }

        .note-tip__icon--indigo {
            background: rgba(91, 87, 214, 0.12);
            color: #5b57d6;
        }

        .note-tip__icon--blue {
            background: rgba(59, 130, 246, 0.12);
            color: #3b82f6;
        }

        .note-tip__icon--green {
            background: rgba(22, 163, 74, 0.12);
            color: #16a34a;
        }

        .note-tip__icon--amber {
            background: rgba(245, 158, 11, 0.12);
            color: #f59e0b;
        }

        .note-tip strong {
            display: block;
            margin-bottom: 5px;
            color: #16213f;
            font-size: 14px;
            font-weight: 800;
        }

        .note-tip p {
            margin: 0;
            color: #667085;
            font-size: 13px;
            line-height: 1.6;
        }

        .note-card__footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid #edf1f7;
        }

        .note-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 12px;
            border: 1px solid transparent;
            font-size: 14px;
            font-weight: 800;
        }

        .note-action--ghost {
            background: #fff;
            border-color: #d7ddea;
            color: #24304f;
        }

        .note-action--primary {
            background: linear-gradient(135deg, #5b57d6, #6d4fff);
            color: #fff;
            box-shadow: 0 16px 30px rgba(91, 87, 214, 0.22);
        }

        @media (max-width: 1200px) {
            .note-builder-page__hero,
            .note-builder-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        @media (max-width: 992px) {
            .note-stepper {
                grid-template-columns: 1fr;
            }

            .note-stepper__line {
                display: none;
            }

            .note-field-grid--2,
            .note-field-grid--3,
            .note-summary__stats,
            .note-summary__meta {
                grid-template-columns: minmax(0, 1fr);
            }

            .note-builder-page__top,
            .note-card__footer {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
    @include('frontend.instructor-dashboard.partials.note-wizard-shell-styles')
    @include('frontend.instructor-dashboard.partials.note-wizard-card-styles')
@endpush

@push('scripts')
    @include('frontend.instructor-dashboard.partials.note-wizard-script')
@endpush
