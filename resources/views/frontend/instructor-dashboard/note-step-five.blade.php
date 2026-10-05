@extends('frontend.instructor-dashboard.layouts.master')

@section('dashboard-contents')
    @php
        $note = $note ?? [];
        $summary = $summary ?? [];
        $files = $files ?? [];
        $settings = $settings ?? [];
        $resolveClassGradeLabel = static function (?string $educationLevel): string {
            $level = strtolower(trim((string) $educationLevel));

            return preg_match('/tvet|university|college|certificate|diploma|undergraduate|tertiary|higher education/', $level)
                ? __('School of')
                : __('Class / Grade');
        };
        $classGradeLabel = $resolveClassGradeLabel($note['education_level'] ?? null);
        $stepItems = [
            ['number' => '&#10003;', 'label' => __('Basic Information'), 'active' => false, 'completed' => true, 'url' => route('instructor.products.create', ['type' => 'note'])],
            ['number' => '&#10003;', 'label' => __('Content'), 'active' => false, 'completed' => true, 'url' => route('instructor.products.create', ['type' => 'note', 'step' => 2])],
            ['number' => '&#10003;', 'label' => __('Attachments'), 'active' => false, 'completed' => true, 'url' => route('instructor.products.create', ['type' => 'note', 'step' => 3])],
            ['number' => '&#10003;', 'label' => __('Settings'), 'active' => false, 'completed' => true, 'url' => route('instructor.products.create', ['type' => 'note', 'step' => 4])],
            ['number' => 5, 'label' => __('Review & Send for Review'), 'active' => true, 'completed' => false, 'url' => route('instructor.products.create', ['type' => 'note', 'step' => 5])],
        ];
    @endphp

    <div class="note-builder-page note-builder-page--review" data-note-wizard data-note-wizard-step="5" data-note-wizard-state='@json($wizardState ?? [])'>
        <div class="note-builder-page__top">
            <nav class="note-breadcrumb" aria-label="{{ __('Breadcrumb') }}">
                <a href="{{ route('instructor.dashboard') }}">{{ __('Dashboard') }}</a>
                <span>/</span>
                <a href="{{ route('instructor.products.index', ['type' => 'note']) }}">{{ __('Notes') }}</a>
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
                <p>{{ __('Create comprehensive notes for your students.') }}</p>
            </div>
        </div>

        <div class="note-stepper note-stepper--content" role="list" aria-label="{{ __('Note creation progress') }}">
            @foreach ($stepItems as $step)
                <div class="note-stepper__item {{ $step['active'] ? 'is-active' : '' }} {{ $step['completed'] ? 'is-completed' : '' }}" role="button" tabindex="0" data-note-step-url="{{ $step['url'] }}" aria-label="{{ __('Go to :step', ['step' => $step['label']]) }}">
                    <span class="note-stepper__dot">{!! $step['number'] !!}</span>
                    <span class="note-stepper__label">{{ $step['label'] }}</span>
                    @if (! $loop->last)
                        <span class="note-stepper__line"></span>
                    @endif
                </div>
            @endforeach
        </div>

        <section class="note-review-card">
            <div class="note-review-card__head">
                <div>
                    <h2>{{ __('Review Note') }}</h2>
                    <p>{{ __('Please review all details before sending for review.') }}</p>
                </div>

                <a href="{{ route('instructor.products.create', ['type' => 'note']) }}" class="note-edit-button">
                    <i class="far fa-edit"></i>
                    <span>{{ __('Edit') }}</span>
                </a>
            </div>

            <div class="note-review-grid">
                <section class="note-review-panel">
                    <h3>{{ __('Basic Information') }}</h3>
                    <dl class="note-review-list">
                        <div><dt>{{ __('Note Title') }}</dt><dd>{{ $note['title'] ?? __('Quadratic Equations - Complete Notes') }}</dd></div>
                        <div><dt>{{ __('Short Description') }}</dt><dd>{{ $note['description'] ?? __('Detailed notes covering quadratic equations, methods of solving, examples and past exam questions.') }}</dd></div>
                        <div><dt>{{ __('Education Level') }}</dt><dd>{{ $note['education_level'] ?? __('Senior School') }}</dd></div>
                        <div><dt>{{ $classGradeLabel }}</dt><dd>{{ $note['class_grade'] ?? __('Form 4') }}</dd></div>
                        <div><dt>{{ __('Subject') }}</dt><dd>{{ $note['subject'] ?? __('Mathematics') }}</dd></div>
                        <div><dt>{{ __('Exam Category') }}</dt><dd>{{ $note['exam_category'] ?? __('KCSE') }}</dd></div>
                        <div><dt>{{ __('Topic') }}</dt><dd>{{ $note['topic'] ?? __('Algebra') }}</dd></div>
                        <div><dt>{{ __('Sub Topic') }}</dt><dd>{{ $note['sub_topic'] ?? __('Quadratic Equations') }}</dd></div>
                        <div><dt>{{ __('Tags') }}</dt><dd>
                            <div class="note-tag-list">
                                @foreach (($note['tags'] ?? ['KCSE', 'Mathematics', 'Algebra', 'Form 4']) as $tag)
                                    <span>{{ $tag }}</span>
                                @endforeach
                            </div>
                        </dd></div>
                        <div><dt>{{ __('Access Type') }}</dt><dd>{{ ($note['access_type'] ?? 'paid') === 'paid' ? __('Paid (Premium)') : __('Free') }}</dd></div>
                        <div><dt>{{ __('Price') }}</dt><dd data-note-price-display>{{ isset($note['price']) ? defaultCurrency((float) $note['price']) : '' }}</dd></div>
                        <div><dt>{{ __('Preview Pages') }}</dt><dd>{{ $note['preview_pages'] ?? __('No preview') }}</dd></div>
                        <div><dt>{{ __('Language') }}</dt><dd>{{ $note['language'] ?? __('English') }}</dd></div>
                        <div><dt>{{ __('Status') }}</dt><dd>{{ $note['status'] ?? __('Draft') }}</dd></div>
                    </dl>
                </section>

                <section class="note-review-panel">
                    <h3>{{ __('Content Summary') }}</h3>
                    <dl class="note-review-list note-review-list--summary">
                        <div><dt>{{ __('Chapters / Sections') }}</dt><dd data-note-summary-chapters>{{ $summary['chapters'] ?? 0 }}</dd></div>
                        <div><dt>{{ __('Total Pages') }}</dt><dd data-note-summary-pages>{{ $summary['pages'] ?? 0 }} {{ __('Pages') }}</dd></div>
                        <div><dt>{{ __('Total Words (Est.)') }}</dt><dd data-note-summary-words>{{ $summary['words'] ?? '0' }} {{ __('words') }}</dd></div>
                        <div><dt>{{ __('Includes Examples') }}</dt><dd data-note-summary-examples>{{ !empty($summary['includes_examples']) ? __('Yes') : __('No') }}</dd></div>
                        <div><dt>{{ __('Includes Past Questions') }}</dt><dd data-note-summary-past-questions>{{ !empty($summary['includes_past_questions']) ? __('Yes') : __('No') }}</dd></div>
                        <div><dt>{{ __('Includes Formulas') }}</dt><dd data-note-summary-formulas>{{ !empty($summary['includes_formulas']) ? __('Yes') : __('No') }}</dd></div>
                        <div><dt>{{ __('Includes Diagrams') }}</dt><dd data-note-summary-diagrams>{{ !empty($summary['includes_diagrams']) ? __('Yes') : __('No') }}</dd></div>
                    </dl>
                </section>

                <section class="note-review-panel">
                    <h3>{{ __('Attachments') }}</h3>
                    <dl class="note-review-list note-review-list--attachments">
                        <div><dt>{{ __('Total Files') }}</dt><dd data-note-review-file-count>{{ count($files) }}</dd></div>
                        <div><dt>{{ __('Total Size') }}</dt><dd data-note-review-file-size>{{ __('0 KB') }}</dd></div>
                    </dl>

                    <div class="note-review-file-empty" data-note-review-file-empty @if (count($files) > 0) style="display: none;" @endif>
                        <i class="fas fa-folder-open"></i>
                        <strong>{{ __('No files uploaded yet') }}</strong>
                        <p>{{ __('Add attachments in the previous step to include them here.') }}</p>
                    </div>

                    <div class="note-review-file-list" data-note-review-file-list>
                        @foreach ($files as $file)
                            <div class="note-review-file">
                                <div class="note-review-file__icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="note-review-file__name">{{ $file['title'] }}</div>
                                <div class="note-review-file__size">{{ $file['size'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="note-review-panel note-review-panel--split">
                    <div class="note-review-panel__split-column">
                        <h3>{{ __('Note Settings') }}</h3>
                        <dl class="note-review-settings">
                            <div><dt>{{ __('Allow Download') }}</dt><dd><i class="fas fa-check-circle text-success"></i></dd></div>
                            <div><dt>{{ __('Add to Bundle') }}</dt><dd><i class="fas fa-check-circle text-success"></i></dd></div>
                            <div><dt>{{ __('Featured Note') }}</dt><dd><i class="fas fa-check-circle text-success"></i></dd></div>
                            <div><dt>{{ __('Allow Comments') }}</dt><dd><i class="fas fa-check-circle text-success"></i></dd></div>
                        </dl>
                    </div>

                    <div class="note-review-panel__split-column">
                        <h3>{{ __('SEO / Additional Info') }}</h3>
                        <dl class="note-review-settings note-review-settings--seo">
                            <div><dt>{{ __('Meta Title') }}</dt><dd>{{ $settings['meta_title'] ?? __('Quadratic Equations Notes - Form 4 Mathematics') }}</dd></div>
                            <div><dt>{{ __('Meta Description') }}</dt><dd>{{ $settings['meta_description'] ?? __('Comprehensive notes on quadratic equations for Form 4 KCSE students. Includes formulas, methods, examples, and past questions with solutions.') }}</dd></div>
                            <div><dt>{{ __('Keywords') }}</dt><dd>{{ $settings['keywords'] ?? __('quadratic equations, form 4, algebra, kcse, math notes') }}</dd></div>
                            <div><dt>{{ __('Sort Order') }}</dt><dd>{{ $settings['sort_order'] ?? 10 }}</dd></div>
                        </dl>
                    </div>

                    <div class="note-review-panel__publishing">
                        <h3>{{ __('Review Note') }}</h3>
                        <div class="note-review-field">
                            <label for="preview_pages">{{ __('Preview Pages') }}</label>
                            <select id="preview_pages" name="preview_pages" data-note-preview-pages>
                                @foreach (['No preview', 'First 1 page', 'First 2 pages', 'First 3 pages'] as $option)
                                    <option value="{{ $option }}" @selected(($note['preview_pages'] ?? 'No preview') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            <small>{{ __('Choose how many pages students can preview before purchase.') }}</small>
                        </div>
                        <ul>
                            <li>{{ __('Once approved, this note will be visible to students according to the visibility setting.') }}</li>
                            <li>{{ __('You can edit or update the note anytime after approval.') }}</li>
                            <li>{{ __('Make sure all information is correct before sending for review.') }}</li>
                        </ul>
                    </div>
                </section>
            </div>

            <form class="note-review-card__footer" method="POST" action="{{ route('instructor.products.store') }}" data-note-publish-form>
                @csrf
                <input type="hidden" name="type" value="note">
                <input type="hidden" name="title" value="{{ $note['title'] ?? '' }}">
                <textarea name="description" class="d-none">{{ $note['description'] ?? '' }}</textarea>
                <input type="hidden" name="price" value="{{ $note['price'] ?? '' }}" data-note-price-input>
                <input type="hidden" name="discount" value="0">
                <input type="hidden" name="status" value="active">
                <input type="hidden" name="note_payload" value="">

                <a href="{{ route('instructor.products.create', ['type' => 'note', 'step' => 4]) }}" class="note-action note-action--ghost">
                    <i class="fas fa-arrow-left"></i>
                    <span>{{ __('Back to Settings') }}</span>
                </a>

                <button type="submit" class="note-publish-button">
                    <span>{{ __('Send for Review') }}</span>
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .note-review-card {
            padding: 22px;
            border: 1px solid rgba(216, 222, 234, 0.95);
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 18px 48px rgba(20, 33, 61, 0.05);
        }

        .note-review-card__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .note-review-card__head h2 {
            margin: 0;
            color: #5b57d6;
            font-size: 24px;
            font-weight: 900;
        }

        .note-review-card__head p {
            margin: 6px 0 0;
            color: #6a7287;
            font-size: 13px;
            line-height: 1.6;
        }

        .note-edit-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 40px;
            padding: 0 16px;
            border: 1px solid #7a74e6;
            border-radius: 10px;
            color: #5b57d6;
            font-weight: 800;
        }

        .note-review-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .note-review-panel {
            padding: 16px;
            border: 1px solid #e1e6f1;
            border-radius: 14px;
            background: #fff;
        }

        .note-review-panel h3 {
            margin: 0 0 14px;
            color: #5b57d6;
            font-size: 16px;
            font-weight: 900;
        }

        .note-review-list {
            display: grid;
            gap: 10px;
        }

        .note-review-list > div,
        .note-review-settings > div {
            display: grid;
            grid-template-columns: minmax(120px, 1fr) minmax(0, 1.6fr);
            gap: 12px;
            align-items: start;
            font-size: 13px;
        }

        .note-review-list dt,
        .note-review-settings dt {
            color: #16213f;
            font-weight: 800;
        }

        .note-review-list dd,
        .note-review-settings dd {
            margin: 0;
            color: #4b5b77;
            line-height: 1.5;
        }

        .note-tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .note-tag-list span {
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(91, 87, 214, 0.12);
            color: #5b57d6;
            font-size: 12px;
            font-weight: 700;
        }

        .note-review-list--summary dd,
        .note-review-settings dd {
            text-align: right;
        }

        .note-review-file-list {
            display: grid;
            gap: 8px;
        }

        .note-review-file {
            display: grid;
            grid-template-columns: 28px minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
            padding: 6px 0;
            font-size: 13px;
        }

        .note-review-file__icon {
            color: #ef4444;
        }

        .note-review-file__name {
            color: #16213f;
            font-weight: 700;
        }

        .note-review-file__size {
            color: #667085;
        }

        .note-review-panel--split {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: minmax(0, 0.75fr) minmax(0, 1fr) minmax(0, 0.95fr);
            gap: 12px;
        }

        .note-review-panel__split-column {
            padding-right: 12px;
        }

        .note-review-panel__split-column + .note-review-panel__split-column,
        .note-review-panel__split-column + .note-review-panel__publishing {
            border-left: 1px solid #e1e6f1;
            padding-left: 12px;
        }

        .note-review-settings {
            display: grid;
            gap: 10px;
        }

        .note-review-panel__publishing {
            padding-left: 12px;
        }

        .note-review-field {
            display: grid;
            gap: 8px;
            margin-bottom: 16px;
        }

        .note-review-field label {
            color: #16213f;
            font-size: 14px;
            font-weight: 800;
        }

        .note-review-field select {
            width: 100%;
            min-height: 44px;
            padding: 11px 14px;
            border: 1px solid #d7ddea;
            border-radius: 12px;
            background: #fff;
            color: #16213f;
            font-size: 14px;
        }

        .note-review-field small {
            color: #667085;
            font-size: 12px;
            line-height: 1.5;
        }

        .note-review-panel__publishing ul {
            margin: 0;
            padding-left: 18px;
            color: #4b5b77;
            font-size: 13px;
            line-height: 1.7;
        }

        .note-review-card__footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 22px;
        }

        .note-publish-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 46px;
            padding: 0 18px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, #29b766, #1fab58);
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            box-shadow: 0 16px 30px rgba(22, 163, 74, 0.22);
        }

        @media (max-width: 1200px) {
            .note-review-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .note-review-panel--split {
                grid-template-columns: minmax(0, 1fr);
            }

            .note-review-panel__split-column + .note-review-panel__split-column,
            .note-review-panel__split-column + .note-review-panel__publishing {
                border-left: 0;
                border-top: 1px solid #e1e6f1;
                padding-left: 0;
                padding-top: 12px;
                margin-top: 12px;
            }
        }

        @media (max-width: 992px) {
            .note-review-card__head,
            .note-review-card__footer {
                flex-direction: column;
                align-items: stretch;
            }

            .note-review-card__footer .note-action,
            .note-review-card__footer .note-publish-button {
                width: 100%;
            }
        }
    </style>
    @include('frontend.instructor-dashboard.partials.note-wizard-shell-styles')
    @include('frontend.instructor-dashboard.partials.note-wizard-card-styles')
    @include('frontend.instructor-dashboard.partials.note-wizard-row-styles')
@endpush

@push('scripts')
    @include('frontend.instructor-dashboard.partials.note-wizard-script')
@endpush
