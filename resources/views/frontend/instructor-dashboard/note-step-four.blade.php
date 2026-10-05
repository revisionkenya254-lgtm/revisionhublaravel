@extends('frontend.instructor-dashboard.layouts.master')

@section('dashboard-contents')
    @php
        $settings = $settings ?? [];
        $stepItems = [
            ['number' => '&#10003;', 'label' => __('Basic Information'), 'active' => false, 'completed' => true, 'url' => route('instructor.products.create', ['type' => 'note'])],
            ['number' => '&#10003;', 'label' => __('Content'), 'active' => false, 'completed' => true, 'url' => route('instructor.products.create', ['type' => 'note', 'step' => 2])],
            ['number' => '&#10003;', 'label' => __('Attachments'), 'active' => false, 'completed' => true, 'url' => route('instructor.products.create', ['type' => 'note', 'step' => 3])],
            ['number' => 4, 'label' => __('Settings'), 'active' => true, 'completed' => false, 'url' => route('instructor.products.create', ['type' => 'note', 'step' => 4])],
            ['number' => 5, 'label' => __('Review & Publish'), 'active' => false, 'completed' => false, 'url' => route('instructor.products.create', ['type' => 'note', 'step' => 5])],
        ];
    @endphp

    <div class="note-builder-page note-builder-page--settings" data-note-wizard data-note-wizard-step="4" data-note-wizard-state='@json($wizardState ?? [])'>
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

        <section class="note-settings-card">
            <div class="note-settings-card__head">
                <div>
                    <h2>{{ __('Note Settings') }}</h2>
                    <p>{{ __('Configure settings and additional information for your note.') }}</p>
                </div>
            </div>

            <div class="note-settings-grid">
                <div class="note-settings-column">
                    <div class="note-toggle-row">
                        <div>
                            <strong>{{ __('Allow Download') }}</strong>
                            <p>{{ __('Allow students to download this note') }}</p>
                        </div>
                        <label class="note-switch">
                            <input type="checkbox" checked>
                            <span></span>
                        </label>
                    </div>

                    <div class="note-toggle-row">
                        <div>
                            <strong>{{ __('Add to Bundle') }}</strong>
                            <p>{{ __('Add this note to a bundle / package') }}</p>
                        </div>
                        <label class="note-switch">
                            <input type="checkbox" checked>
                            <span></span>
                        </label>
                    </div>

                    <div class="note-toggle-row">
                        <div>
                            <strong>{{ __('Featured Note') }}</strong>
                            <p>{{ __('Make this note featured on the platform') }}</p>
                        </div>
                        <label class="note-switch">
                            <input type="checkbox" checked>
                            <span></span>
                        </label>
                    </div>

                    <div class="note-toggle-row">
                        <div>
                            <strong>{{ __('Allow Comments') }}</strong>
                            <p>{{ __('Allow students to comment on this note') }}</p>
                        </div>
                        <label class="note-switch">
                            <input type="checkbox" checked>
                            <span></span>
                        </label>
                    </div>

                    <div class="note-visibility-block">
                        <h3>{{ __('Visibility') }}</h3>

                        <label class="note-visibility-option">
                            <input type="radio" name="visibility" checked>
                            <span class="note-visibility-option__dot"></span>
                            <span>
                                <strong>{{ __('Public') }}</strong>
                                <small>{{ __('Anyone can see this note') }}</small>
                            </span>
                        </label>

                        <label class="note-visibility-option">
                            <input type="radio" name="visibility">
                            <span class="note-visibility-option__dot"></span>
                            <span>
                                <strong>{{ __('Private') }}</strong>
                                <small>{{ __('Only enrolled students can see this note') }}</small>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="note-settings-column note-settings-column--seo">
                    <h3>{{ __('SEO / Additional Info') }}</h3>

                    <div class="note-field">
                        <label for="meta_title">{{ __('Meta Title') }}</label>
                        <input id="meta_title" type="text" value="{{ $settings['meta_title'] ?? __('Quadratic Equations Notes - Form 4 Mathematics') }}">
                        <small>{{ __('Recommended length: 50-60 characters') }}</small>
                    </div>

                    <div class="note-field">
                        <label for="meta_description">{{ __('Meta Description') }}</label>
                        <textarea id="meta_description" rows="4">{{ $settings['meta_description'] ?? __('Comprehensive notes on quadratic equations for Form 4 KCSE students. Includes formulas, methods, examples, and past questions with solutions.') }}</textarea>
                        <small class="note-field__counter">142/160</small>
                    </div>

                    <div class="note-field">
                        <label for="keywords">{{ __('Keywords') }}</label>
                        <input id="keywords" type="text" value="{{ $settings['keywords'] ?? __('quadratic equations, form 4, algebra, kcse, math notes') }}">
                        <small>{{ __('Separate keywords with commas') }}</small>
                    </div>

                    <div class="note-field">
                        <label for="sort_order">{{ __('Sort Order') }}</label>
                        <input id="sort_order" type="number" value="{{ $settings['sort_order'] ?? 10 }}">
                        <small>{{ __('Lower numbers appear first.') }}</small>
                    </div>
                </div>
            </div>

            <div class="note-settings-card__footer">
                <a href="{{ route('instructor.products.create', ['type' => 'note', 'step' => 3]) }}" class="note-action note-action--ghost">
                    <i class="fas fa-arrow-left"></i>
                    <span>{{ __('Back to Attachments') }}</span>
                </a>

                <a href="{{ route('instructor.products.create', ['type' => 'note', 'step' => 5]) }}" class="note-action note-action--primary">
                    <span>{{ __('Next: Review & Publish') }}</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .note-settings-card {
            padding: 22px;
            border: 1px solid rgba(216, 222, 234, 0.95);
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 18px 48px rgba(20, 33, 61, 0.05);
        }

        .note-settings-card__head h2 {
            margin: 0;
            color: #5b57d6;
            font-size: 24px;
            font-weight: 900;
        }

        .note-settings-card__head p {
            margin: 6px 0 0;
            color: #6a7287;
            font-size: 13px;
            line-height: 1.6;
        }

        .note-settings-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(360px, 0.9fr);
            border: 1px solid #e1e6f1;
            border-radius: 14px;
            overflow: hidden;
            margin-top: 18px;
        }

        .note-settings-column {
            padding: 18px;
        }

        .note-settings-column + .note-settings-column {
            border-left: 1px solid #e1e6f1;
        }

        .note-toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 12px 0;
        }

        .note-toggle-row strong,
        .note-visibility-block h3,
        .note-settings-column--seo h3 {
            color: #16213f;
            font-size: 16px;
            font-weight: 800;
        }

        .note-toggle-row p {
            margin: 4px 0 0;
            color: #6a7287;
            font-size: 13px;
        }

        .note-switch {
            position: relative;
            width: 52px;
            height: 28px;
            flex: 0 0 auto;
        }

        .note-switch input {
            position: absolute;
            opacity: 0;
        }

        .note-switch span {
            position: absolute;
            inset: 0;
            border-radius: 999px;
            background: #d9e1ef;
            transition: background-color 0.2s ease;
        }

        .note-switch span::after {
            content: '';
            position: absolute;
            top: 4px;
            left: 4px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 2px 8px rgba(20, 33, 61, 0.16);
            transition: transform 0.2s ease;
        }

        .note-switch input:checked + span {
            background: #5b57d6;
        }

        .note-switch input:checked + span::after {
            transform: translateX(24px);
        }

        .note-visibility-block {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid #e6ebf4;
        }

        .note-visibility-block h3 {
            margin: 0 0 14px;
        }

        .note-visibility-option {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 0;
            cursor: pointer;
        }

        .note-visibility-option input {
            position: absolute;
            opacity: 0;
        }

        .note-visibility-option__dot {
            width: 20px;
            height: 20px;
            margin-top: 2px;
            border: 2px solid #c9d1e3;
            border-radius: 50%;
            flex: 0 0 20px;
            position: relative;
        }

        .note-visibility-option input:checked + .note-visibility-option__dot {
            border-color: #5b57d6;
        }

        .note-visibility-option input:checked + .note-visibility-option__dot::after {
            content: '';
            position: absolute;
            inset: 3px;
            border-radius: 50%;
            background: #5b57d6;
        }

        .note-visibility-option strong,
        .note-visibility-option small {
            display: block;
        }

        .note-visibility-option strong {
            color: #16213f;
            font-size: 14px;
            font-weight: 800;
        }

        .note-visibility-option small {
            color: #6a7287;
            font-size: 13px;
            margin-top: 4px;
        }

        .note-settings-column--seo h3 {
            margin: 0 0 16px;
            color: #5b57d6;
        }

        .note-settings-column--seo .note-field {
            display: grid;
            gap: 8px;
            margin-bottom: 16px;
        }

        .note-settings-column--seo .note-field label {
            color: #16213f;
            font-size: 14px;
            font-weight: 700;
        }

        .note-settings-column--seo .note-field input,
        .note-settings-column--seo .note-field textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d7ddea;
            border-radius: 12px;
            font-size: 14px;
        }

        .note-settings-column--seo .note-field small {
            color: #6a7287;
            font-size: 12px;
        }

        .note-field__counter {
            text-align: right;
        }

        .note-settings-card__footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 24px;
        }

        @media (max-width: 1200px) {
            .note-settings-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .note-settings-column + .note-settings-column {
                border-left: 0;
                border-top: 1px solid #e1e6f1;
            }
        }

        @media (max-width: 992px) {
            .note-settings-card__footer {
                flex-direction: column;
                align-items: stretch;
            }

            .note-settings-card__footer .note-action {
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
