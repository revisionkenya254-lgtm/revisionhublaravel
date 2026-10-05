@extends('frontend.instructor-dashboard.layouts.master')

@section('dashboard-contents')
    @php
        $chapters = $chapters ?? [];
        $stepItems = [
            ['number' => '&#10003;', 'label' => __('Basic Information'), 'active' => false, 'completed' => true, 'url' => route('instructor.products.create', ['type' => 'note'])],
            ['number' => 2, 'label' => __('Content'), 'active' => true, 'completed' => false, 'url' => route('instructor.products.create', ['type' => 'note', 'step' => 2])],
            ['number' => 3, 'label' => __('Attachments'), 'active' => false, 'completed' => false, 'url' => route('instructor.products.create', ['type' => 'note', 'step' => 3])],
            ['number' => 4, 'label' => __('Settings'), 'active' => false, 'completed' => false, 'url' => route('instructor.products.create', ['type' => 'note', 'step' => 4])],
            ['number' => 5, 'label' => __('Review & Publish'), 'active' => false, 'completed' => false, 'url' => route('instructor.products.create', ['type' => 'note', 'step' => 5])],
        ];
    @endphp

    <div class="note-builder-page note-builder-page--content" data-note-wizard data-note-wizard-step="2" data-note-wizard-state='@json($wizardState ?? [])'>
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
                <p>{{ __('Add and organize your note content. You can add chapters, sections and pages.') }}</p>
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

        <section class="note-content-card">
            <div class="note-content-card__head">
                <div>
                    <h2>{{ __('Note Content') }}</h2>
                    <p>{{ __('Add and organize your note content. You can add chapters/sections and pages.') }}</p>
                </div>
            </div>

            <div class="note-chapter-empty" data-note-chapter-empty>
                <i class="fas fa-layer-group"></i>
                <strong>{{ __('No chapters yet') }}</strong>
                <p>{{ __('Create your first chapter to start building the note structure.') }}</p>
                <button type="button" class="note-chapter-empty__action" data-note-add-chapter-empty>
                    <i class="fas fa-plus"></i>
                    <span>{{ __('Create First Chapter') }}</span>
                </button>
            </div>

            <div class="note-chapter-list" data-note-chapter-list>
            </div>
        </section>

        <div class="note-content-page-nav">
            <a href="{{ route('instructor.products.create', ['type' => 'note']) }}" class="note-action note-action--ghost">
                <i class="fas fa-arrow-left"></i>
                <span>{{ __('Back to Basic Information') }}</span>
            </a>

            <a href="{{ route('instructor.products.create', ['type' => 'note', 'step' => 3]) }}" class="note-action note-action--primary">
                <span>{{ __('Next: Attachments') }}</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="note-chapter-modal" data-note-chapter-modal aria-hidden="true">
            <div class="note-chapter-modal__backdrop" data-note-chapter-close></div>
            <div class="note-chapter-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="chapter-modal-title">
                <div class="note-chapter-modal__head">
                    <div>
                        <p>{{ __('Chapter Builder') }}</p>
                        <h3 id="chapter-modal-title">{{ __('Add Chapter') }}</h3>
                    </div>

                    <button type="button" class="note-chapter-modal__close" data-note-chapter-close aria-label="{{ __('Close dialog') }}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form class="note-chapter-form" data-note-chapter-form>
                    <input type="hidden" name="chapter_index" value="">

                    <label class="note-chapter-field">
                        <span>{{ __('Chapter Title') }}</span>
                        <input type="text" name="chapter_title" placeholder="{{ __('Introduction to Quadratic Equations') }}" required>
                    </label>

                    <label class="note-chapter-field">
                        <span>{{ __('Pages') }}</span>
                        <input type="number" name="chapter_pages" min="1" step="1" value="1" required>
                    </label>

                    <div class="note-chapter-content">
                        <div class="note-chapter-content__head">
                            <div>
                                <strong>{{ __('Chapter Content') }}</strong>
                                <p>{{ __('Type content here. This is the main body of the chapter.') }}</p>
                            </div>

                            <span class="note-chapter-content__badge">{{ __('Primary') }}</span>
                        </div>

                        <textarea name="chapter_content" rows="10" placeholder="{{ __('Type content here...') }}"></textarea>
                    </div>

                    <div class="note-chapter-sections note-chapter-sections--optional">
                        <div class="note-chapter-sections__head">
                            <button type="button" class="note-chapter-sections__add" data-note-add-section>
                                <i class="fas fa-plus"></i>
                                <span>{{ __('Add Section') }}</span>
                            </button>
                        </div>

                        <div class="note-chapter-sections__caption">
                            <strong>{{ __('Optional sections / topics') }}</strong>
                            <p>{{ __('Add sections only if this chapter needs smaller parts or quick references.') }}</p>
                        </div>

                        <div class="note-chapter-sections__list" data-note-chapter-sections></div>
                    </div>

                    <div class="note-chapter-modal__footer">
                        <button type="button" class="note-action note-action--ghost" data-note-chapter-close>
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="note-action note-action--primary">
                            {{ __('Save Chapter') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="note-section-modal" data-note-section-modal aria-hidden="true">
            <div class="note-section-modal__backdrop" data-note-section-close></div>
            <div class="note-section-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="section-modal-title">
                <div class="note-chapter-modal__head">
                    <div>
                        <p>{{ __('Section Editor') }}</p>
                        <h3 id="section-modal-title">{{ __('Edit Reading Content') }}</h3>
                    </div>

                    <button type="button" class="note-chapter-modal__close" data-note-section-close aria-label="{{ __('Close dialog') }}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form class="note-section-form" data-note-section-form>
                    <input type="hidden" name="section_index" value="">
                    <input type="hidden" name="section_chapter_index" value="">

                    <label class="note-chapter-field">
                        <span>{{ __('Section / Topic Title') }}</span>
                        <input type="text" name="section_title" placeholder="{{ __('Definition and Examples') }}" required>
                    </label>

                    <label class="note-chapter-field">
                        <span>{{ __('Reading Content HTML') }}</span>
                        <textarea name="section_content" rows="8" placeholder="{{ __('Write the reading content for this section...') }}"></textarea>
                    </label>

                    <div class="note-section-form__preview">
                        <strong>{{ __('Preview') }}</strong>
                        <div class="note-section-form__preview-box" data-note-section-preview>{{ __('No content yet.') }}</div>
                    </div>

                    <div class="note-chapter-modal__footer">
                        <button type="button" class="note-action note-action--ghost" data-note-section-close>
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="note-action note-action--primary">
                            {{ __('Save Section') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .note-builder-page--content {
            gap: 8px;
        }

        .note-builder-page__hero {
            gap: 6px;
        }

        .note-builder-page__hero > div {
            display: grid;
            gap: 0;
        }

        .note-stepper--content .note-stepper__item.is-completed .note-stepper__dot {
            border-color: transparent;
            background: rgba(22, 163, 74, 0.14);
            color: #15945c;
        }

        .note-stepper--content .note-stepper__item.is-completed .note-stepper__label {
            color: #16213f;
        }

        .note-stepper--content .note-stepper__item.is-active .note-stepper__dot {
            border-color: transparent;
            background: linear-gradient(135deg, #5b57d6, #6d4fff);
            color: #fff;
            box-shadow: 0 12px 24px rgba(91, 87, 214, 0.28);
        }

        .note-content-card {
            display: flex;
            flex-direction: column;
            padding: 24px;
            border: 1px solid rgba(216, 222, 234, 0.95);
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 18px 48px rgba(20, 33, 61, 0.05);
        }

        .note-content-card__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
        }

        .note-content-card__head h2 {
            margin: 0;
            color: #5b57d6;
            font-size: 24px;
            font-weight: 900;
        }

        .note-content-card__head p {
            margin: 6px 0 0;
            color: #6a7287;
            font-size: 13px;
            line-height: 1.6;
        }

        .note-add-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 42px;
            padding: 0 18px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, #5b57d6, #6d4fff);
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            box-shadow: 0 16px 30px rgba(91, 87, 214, 0.22);
        }

        .note-chapter-empty {
            display: grid;
            place-items: center;
            gap: 8px;
            padding: 32px 20px;
            border: 1px dashed rgba(91, 87, 214, 0.25);
            border-radius: 18px;
            background: rgba(91, 87, 214, 0.03);
            text-align: center;
        }

        .note-chapter-empty i {
            color: #5b57d6;
            font-size: 30px;
        }

        .note-chapter-empty strong {
            color: #16213f;
            font-size: 16px;
            font-weight: 800;
        }

        .note-chapter-empty p {
            margin: 0;
            color: #6a7287;
            font-size: 13px;
            line-height: 1.6;
        }

        .note-chapter-empty__action,
        .note-chapter-row__add {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 36px;
            padding: 0 14px;
            border: 1px solid rgba(91, 87, 214, 0.18);
            border-radius: 12px;
            background: rgba(91, 87, 214, 0.06);
            color: #5b57d6;
            font-size: 13px;
            font-weight: 800;
            white-space: nowrap;
        }

        .note-chapter-empty__action {
            margin-top: 8px;
        }

        .note-chapter-list {
            position: relative;
            display: grid;
            gap: 14px;
            margin-top: 14px;
            padding-right: 4px;
            overflow: visible;
            flex: 0 0 auto;
            min-height: 0;
        }

        .note-chapter-row {
            display: grid;
            grid-template-columns: 30px minmax(0, 1fr) 100px 96px;
            gap: 16px;
            align-items: center;
            min-height: 68px;
            padding: 14px 16px;
            border: 1px solid #e1e6f1;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 10px 22px rgba(20, 33, 61, 0.04);
        }

        .note-chapter-row__handle,
        .note-chapter-row__actions button {
            display: grid;
            place-items: center;
            color: #5d6a85;
        }

        .note-chapter-row__handle {
            font-size: 18px;
        }

        .note-chapter-row__number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 30px;
            padding: 0 12px;
            border-radius: 999px;
            background: #f5f7fb;
            color: #16213f;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
            flex: 0 0 auto;
        }

        .note-chapter-row__title-line {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .note-chapter-row__title strong {
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #16213f;
            font-size: 17px;
            font-weight: 800;
        }

        .note-chapter-row__title {
            min-width: 0;
            display: grid;
            gap: 4px;
        }

        .note-chapter-row__content-preview {
            display: block;
            margin-top: 5px;
            color: #5f6b84;
            font-size: 12px;
            font-weight: 500;
            line-height: 1.5;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .note-chapter-row__content-preview strong {
            color: #4f46e5;
            font-weight: 800;
        }

        .note-chapter-row__footer {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            margin-top: 4px;
        }

        .note-chapter-row__pages {
            color: #4b5b77;
            font-size: 15px;
            font-weight: 700;
            text-align: right;
        }

        .note-chapter-row__actions {
            display: inline-flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .note-chapter-row__actions button {
            width: 34px;
            height: 34px;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: #51607c;
            font-size: 15px;
        }

        .note-chapter-row__section-count {
            display: none;
            align-items: center;
            justify-self: start;
            margin: 0;
            min-height: 0;
            padding: 0;
            border: 0;
            background: transparent;
            color: #5b57d6;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .note-chapter-row__section-count.is-visible {
            display: inline-flex;
        }

        .note-chapter-topic-list {
            grid-column: 1 / -1;
            display: grid;
            gap: 10px;
            margin-top: 0;
            padding-top: 12px;
            border-top: 1px dashed #e6ebf4;
            overflow: auto;
            padding-right: 4px;
        }

        .note-chapter-topic-item,
        .note-chapter-topic-empty {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 12px;
            border: 1px solid #e1e6f1;
            border-radius: 14px;
            background: #fbfcff;
        }

        .note-chapter-topic-item__body {
            min-width: 0;
        }

        .note-chapter-topic-item__body strong {
            display: block;
            color: #16213f;
            font-size: 13px;
            font-weight: 800;
        }

        .note-chapter-topic-item__body small,
        .note-chapter-topic-empty {
            color: #6a7287;
            font-size: 12px;
            line-height: 1.5;
        }

        .note-chapter-topic-more {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 30px;
            padding: 0 12px;
            border: 0;
            border-radius: 999px;
            background: rgba(91, 87, 214, 0.08);
            color: #5b57d6;
            font-size: 12px;
            font-weight: 800;
            justify-self: start;
            cursor: pointer;
        }

        .note-chapter-topic-item button {
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 11px;
            background: rgba(91, 87, 214, 0.08);
            color: #5b57d6;
        }

        .note-chapter-topic-empty {
            display: grid;
            justify-items: center;
            gap: 8px;
            text-align: center;
            font-style: normal;
        }

        .note-chapter-topic-empty > span {
            color: #6a7287;
            font-size: 12px;
            line-height: 1.5;
        }

        .note-chapter-topic-empty__action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 34px;
            padding: 0 14px;
            border: 1px solid rgba(91, 87, 214, 0.18);
            border-radius: 12px;
            background: rgba(91, 87, 214, 0.08);
            color: #5b57d6;
            font-size: 13px;
            font-weight: 800;
            font-style: normal;
            white-space: nowrap;
        }

        .note-content-page-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 14px;
            padding: 16px 2px 0;
            border-top: 1px solid #e9edf5;
            flex: 0 0 auto;
        }

        .note-chapter-modal {
            position: fixed;
            inset: 0;
            z-index: 90;
            display: none;
        }

        .note-chapter-modal.is-open {
            display: grid;
            place-items: center;
        }

        .note-chapter-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
        }

        .note-chapter-modal__dialog {
            position: relative;
            z-index: 1;
            width: min(560px, calc(100vw - 32px));
            padding: 22px;
            border: 1px solid rgba(216, 222, 234, 0.95);
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 28px 70px rgba(15, 23, 42, 0.22);
            max-height: calc(100vh - 32px);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .note-chapter-modal__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .note-chapter-modal__head p {
            margin: 0 0 4px;
            color: #5b57d6;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .note-chapter-modal__head h3 {
            margin: 0;
            color: #16213f;
            font-size: 22px;
            font-weight: 900;
        }

        .note-chapter-modal__close {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            border: 1px solid #d7ddea;
            border-radius: 12px;
            background: #fff;
            color: #5d6a85;
        }

        .note-section-modal {
            position: fixed;
            inset: 0;
            z-index: 95;
            display: none;
        }

        .note-section-modal.is-open {
            display: grid;
            place-items: center;
        }

        .note-section-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.5);
        }

        .note-section-modal__dialog {
            position: relative;
            z-index: 1;
            width: min(640px, calc(100vw - 32px));
            padding: 22px;
            border: 1px solid rgba(216, 222, 234, 0.95);
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 28px 70px rgba(15, 23, 42, 0.24);
            max-height: calc(100vh - 32px);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .note-section-form {
            display: grid;
            gap: 14px;
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
            padding-right: 4px;
        }

        .note-section-form textarea {
            width: 100%;
            min-height: 180px;
            padding: 12px 14px;
            border: 1px solid #d7ddea;
            border-radius: 12px;
            background: #fff;
            color: #16213f;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
        }

        .note-section-form__preview {
            display: grid;
            gap: 8px;
            padding: 14px;
            border: 1px solid #e1e6f1;
            border-radius: 14px;
            background: #fbfcff;
        }

        .note-section-form__preview strong {
            color: #16213f;
            font-size: 13px;
            font-weight: 800;
        }

        .note-section-form__preview-box {
            min-height: 72px;
            padding: 12px;
            border-radius: 12px;
            background: #fff;
            color: #4b5b77;
            font-size: 13px;
            line-height: 1.6;
        }

        .note-section-modal {
            position: fixed;
            inset: 0;
            z-index: 95;
            display: none;
        }

        .note-section-modal.is-open {
            display: grid;
            place-items: center;
        }

        .note-section-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.5);
        }

        .note-section-modal__dialog {
            position: relative;
            z-index: 1;
            width: min(620px, calc(100vw - 32px));
            padding: 22px;
            border: 1px solid rgba(216, 222, 234, 0.95);
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 28px 70px rgba(15, 23, 42, 0.24);
        }

        .note-section-form {
            display: grid;
            gap: 14px;
        }

        .note-section-form textarea {
            width: 100%;
            min-height: 180px;
            padding: 12px 14px;
            border: 1px solid #d7ddea;
            border-radius: 12px;
            background: #fff;
            color: #16213f;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
        }

        .note-section-form__preview {
            display: grid;
            gap: 8px;
            padding: 14px;
            border: 1px solid #e1e6f1;
            border-radius: 14px;
            background: #fbfcff;
        }

        .note-section-form__preview strong {
            color: #16213f;
            font-size: 13px;
            font-weight: 800;
        }

        .note-section-form__preview-box {
            min-height: 72px;
            padding: 12px;
            border-radius: 12px;
            background: #fff;
            color: #4b5b77;
            font-size: 13px;
            line-height: 1.6;
        }

        .note-chapter-form {
            display: grid;
            gap: 14px;
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
            padding-right: 4px;
        }

        .note-chapter-field {
            display: grid;
            gap: 8px;
        }

        .note-chapter-field span {
            color: #1f2d4a;
            font-size: 14px;
            font-weight: 800;
        }

        .note-chapter-field input {
            width: 100%;
            min-height: 44px;
            padding: 11px 14px;
            border: 1px solid #d7ddea;
            border-radius: 12px;
            background: #fff;
            color: #16213f;
            font-size: 14px;
        }

        .note-chapter-content {
            display: grid;
            gap: 12px;
            padding: 18px;
            border: 1px solid rgba(91, 87, 214, 0.22);
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(91, 87, 214, 0.06), rgba(255, 255, 255, 0.94));
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
        }

        .note-chapter-content__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .note-chapter-content__head strong {
            display: block;
            color: #4f46e5;
            font-size: 16px;
            font-weight: 900;
            letter-spacing: -0.01em;
        }

        .note-chapter-content__head p {
            margin: 4px 0 0;
            color: #5f6b84;
            font-size: 13px;
            line-height: 1.5;
        }

        .note-chapter-content__badge {
            display: inline-flex;
            align-items: center;
            min-height: 30px;
            padding: 0 10px;
            border-radius: 999px;
            background: rgba(91, 87, 214, 0.12);
            color: #4f46e5;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .note-chapter-content textarea {
            width: 100%;
            min-height: 220px;
            padding: 16px;
            border: 1px solid rgba(91, 87, 214, 0.22);
            border-radius: 16px;
            background: #fff;
            color: #16213f;
            font-size: 15px;
            line-height: 1.7;
            font-family: inherit;
            resize: vertical;
            box-shadow: 0 10px 28px rgba(91, 87, 214, 0.06);
        }

        .note-chapter-content textarea::placeholder {
            color: #8c96ab;
            font-weight: 600;
        }

        .note-chapter-sections {
            display: grid;
            gap: 10px;
            padding: 14px 15px;
            border: 1px dashed #d8def0;
            border-radius: 16px;
            background: #fafbff;
        }

        .note-chapter-sections__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .note-chapter-sections__add {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 36px;
            padding: 0 13px;
            border: 1px solid #d7ddea;
            border-radius: 10px;
            background: #fff;
            color: #5b57d6;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .note-chapter-sections__caption {
            display: grid;
            gap: 2px;
            padding: 2px 2px 0;
        }

        .note-chapter-sections__caption strong {
            color: #6b7280;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .note-chapter-sections__caption p {
            margin: 0;
            color: #7b8496;
            font-size: 12px;
            line-height: 1.45;
        }

        .note-chapter-sections__list {
            display: grid;
            gap: 10px;
            max-height: 220px;
            overflow: auto;
            padding-right: 4px;
        }

        .note-chapter-sections__empty {
            padding: 10px 2px 2px;
            color: #7b8496;
            font-size: 12px;
            line-height: 1.5;
        }

        .note-chapter-section-row {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto auto;
            gap: 10px;
            align-items: center;
            padding: 10px 12px;
            border: 1px solid #dbe3f1;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 8px 20px rgba(20, 33, 61, 0.03);
        }

        .note-chapter-section-row.is-dragging {
            opacity: 0.6;
            box-shadow: 0 16px 30px rgba(20, 33, 61, 0.08);
        }

        .note-chapter-section-row__handle {
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 10px;
            background: #f5f7fb;
            color: #5d6a85;
            cursor: grab;
        }

        .note-chapter-section-row__handle:active {
            cursor: grabbing;
        }

        .note-chapter-section-row input {
            width: 100%;
            min-height: 42px;
            padding: 10px 12px;
            border: 1px solid #d7ddea;
            border-radius: 12px;
            background: #fff;
            color: #16213f;
            font-size: 14px;
        }

        .note-chapter-section-row button {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 10px;
            background: rgba(239, 68, 68, 0.08);
            color: #ef4444;
        }

        .note-chapter-section-row__handle {
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 10px;
            background: #f5f7fb !important;
            color: #5d6a85 !important;
            cursor: grab;
        }

        .note-chapter-section-row__handle:active {
            cursor: grabbing;
        }

        .note-chapter-row__content-preview {
            display: block;
            margin-top: 6px;
            color: #5f6b84;
            font-size: 12px;
            font-weight: 500;
            line-height: 1.5;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .note-chapter-row__content-preview strong {
            color: #4f46e5;
            font-weight: 800;
        }

        .note-chapter-modal__footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: auto;
            padding-top: 14px;
            position: sticky;
            bottom: 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0), #fff 22%);
        }

        @media (max-width: 1200px) {
            .note-chapter-row {
                grid-template-columns: 26px 58px minmax(0, 1fr);
                align-items: start;
            }

            .note-chapter-row__pages {
                text-align: left;
            }

            .note-chapter-row__actions {
                grid-column: 1 / -1;
                justify-content: flex-start;
                margin-top: 6px;
            }
        }

        @media (max-width: 992px) {
            .note-chapter-list {
                overflow: visible;
            }

            .note-content-card__head,
            .note-content-page-nav {
                flex-direction: column;
                align-items: stretch;
            }

            .note-content-page-nav .note-action {
                width: 100%;
            }

            .note-chapter-modal__footer {
                flex-direction: column;
                align-items: stretch;
                padding-top: 12px;
            }

            .note-chapter-modal__footer .note-action {
                width: 100%;
                min-height: 48px;
            }

            .note-chapter-sections__list {
                max-height: 180px;
            }
        }

        @media (max-width: 767px) {
            .note-chapter-modal__dialog,
            .note-section-modal__dialog {
                width: calc(100vw - 18px);
                max-height: calc(100vh - 18px);
                padding: 16px;
                border-radius: 18px;
            }

            .note-section-modal__dialog {
                width: calc(100vw - 18px);
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
