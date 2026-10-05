@extends('frontend.instructor-dashboard.layouts.master')

@section('dashboard-contents')
    @php
        $files = $files ?? [];
        $stepItems = [
            ['number' => '&#10003;', 'label' => __('Basic Information'), 'active' => false, 'completed' => true, 'url' => route('instructor.products.create', ['type' => 'note'])],
            ['number' => '&#10003;', 'label' => __('Content'), 'active' => false, 'completed' => true, 'url' => route('instructor.products.create', ['type' => 'note', 'step' => 2])],
            ['number' => 3, 'label' => __('Attachments'), 'active' => true, 'completed' => false, 'url' => route('instructor.products.create', ['type' => 'note', 'step' => 3])],
            ['number' => 4, 'label' => __('Settings'), 'active' => false, 'completed' => false, 'url' => route('instructor.products.create', ['type' => 'note', 'step' => 4])],
            ['number' => 5, 'label' => __('Review & Publish'), 'active' => false, 'completed' => false, 'url' => route('instructor.products.create', ['type' => 'note', 'step' => 5])],
        ];
    @endphp

    <div class="note-builder-page note-builder-page--attachments"
        data-note-wizard
        data-note-wizard-step="3"
        data-note-wizard-state='@json($wizardState ?? [])'
        data-note-attachments-upload-url="{{ route('instructor.products.note-attachments.upload') }}"
        data-note-attachments-delete-url="{{ route('instructor.products.note-attachments.destroy') }}">
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

        <section class="note-attachments-card">
            <div class="note-attachments-card__head">
                <div>
                    <h2>{{ __('Attachments') }}</h2>
                    <p>{{ __('Upload files related to this note. Supported formats: PDF, DOC, DOCX, PPT, ZIP, MP4, 50MB per file.') }}</p>
                </div>
            </div>

            <label class="note-dropzone" for="note-files">
                <input id="note-files" name="files[]" type="file" multiple hidden accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.mp4,.mov,.webm">
                <div class="note-dropzone__icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <strong>{{ __('Drag & drop files here') }}</strong>
                <span>{{ __('or') }}</span>
                <span class="note-dropzone__button">{{ __('Choose Files') }}</span>
            </label>

            <div class="note-attachments-hint">
                <span class="note-attachments-hint__label">{{ __('Accepted') }}</span>
                <div class="note-attachments-hint__chips">
                    <span>PDF</span>
                    <span>DOC</span>
                    <span>DOCX</span>
                    <span>PPT</span>
                    <span>ZIP</span>
                    <span>MP4</span>
                </div>
            </div>

            <p class="note-upload-status" data-note-upload-status aria-live="polite"></p>
            <div class="note-upload-errors" data-note-upload-errors aria-live="polite"></div>

            <div class="note-upload-summary" data-note-upload-summary>
                <div class="note-upload-summary__item">
                    <strong data-note-upload-summary-uploaded>{{ count($files) }}</strong>
                    <span>{{ __('Uploaded') }}</span>
                </div>
                <div class="note-upload-summary__item">
                    <strong data-note-upload-summary-pending>0</strong>
                    <span>{{ __('Uploading') }}</span>
                </div>
                <div class="note-upload-summary__item">
                    <strong data-note-upload-summary-failed>0</strong>
                    <span>{{ __('Failed') }}</span>
                </div>
                <div class="note-upload-summary__item">
                    <strong data-note-upload-summary-size>{{ __('0 KB') }}</strong>
                    <span>{{ __('Total Size') }}</span>
                </div>
            </div>

            <div class="note-attachments-summary">
                <h3>{{ __('Uploaded Files') }} (<span data-note-file-count>{{ count($files) }}</span>)</h3>
                <span>{{ __('Total Size:') }} <strong data-note-file-size>{{ __('0 KB') }}</strong></span>
            </div>

            <div class="note-file-reorder-hint">
                <i class="fas fa-grip-vertical"></i>
                <span>{{ __('Drag uploaded rows to change their final order.') }}</span>
            </div>

            <div class="note-file-empty" data-note-file-empty @if (count($files) > 0) style="display: none;" @endif>
                <i class="fas fa-folder-open"></i>
                <strong>{{ __('No files uploaded yet') }}</strong>
                <p>{{ __('Choose files above to add them to this note.') }}</p>
            </div>

            <div class="note-file-list" data-note-file-list>
                @foreach ($files as $file)
                    <article class="note-file-row">
                        <div class="note-file-row__icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="note-file-row__body">
                            <strong>{{ $file['title'] }}</strong>
                            <span>{{ $file['size'] }}</span>
                        </div>
                        <div class="note-file-row__actions">
                            <div class="note-file-row__meta">
                                <button type="button" class="note-file-row__drag" aria-label="{{ __('Drag to reorder') }}">
                                    <i class="fas fa-grip-vertical"></i>
                                </button>
                                <span class="note-file-row__type">{{ strtoupper($file['resource_type'] ?? 'FILE') }}</span>
                                <span class="note-file-row__status">
                                    <span class="note-file-row__status-badge">{{ __('Uploaded') }}</span>
                                </span>
                            </div>
                            <div class="note-file-row__meta">
                                <button type="button" aria-label="{{ __('Download file') }}">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="note-attachments-card__footer">
                <a href="{{ route('instructor.products.create', ['type' => 'note', 'step' => 2]) }}" class="note-action note-action--ghost">
                    <i class="fas fa-arrow-left"></i>
                    <span>{{ __('Back to Content') }}</span>
                </a>

                <a href="{{ route('instructor.products.create', ['type' => 'note', 'step' => 4]) }}" class="note-action note-action--primary">
                    <span>{{ __('Next: Settings') }}</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .note-attachments-card {
            padding: 22px;
            border: 1px solid rgba(216, 222, 234, 0.95);
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 18px 48px rgba(20, 33, 61, 0.05);
        }

        .note-attachments-card__head h2 {
            margin: 0;
            color: #5b57d6;
            font-size: 24px;
            font-weight: 900;
        }

        .note-attachments-card__head p {
            margin: 6px 0 0;
            color: #6a7287;
            font-size: 13px;
            line-height: 1.6;
        }

        .note-dropzone {
            display: grid;
            place-items: center;
            gap: 10px;
            min-height: 160px;
            margin-top: 16px;
            padding: 28px;
            border: 2px dashed rgba(91, 87, 214, 0.45);
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(91, 87, 214, 0.03), rgba(255, 255, 255, 1));
            text-align: center;
            cursor: pointer;
        }

        .note-dropzone.is-dragover {
            border-color: #5b57d6;
            background: rgba(91, 87, 214, 0.08);
        }

        .note-dropzone.is-loading {
            opacity: 0.75;
            pointer-events: none;
        }

        .note-dropzone__icon {
            color: #5b57d6;
            font-size: 42px;
        }

        .note-dropzone strong {
            color: #16213f;
            font-size: 16px;
            font-weight: 800;
        }

        .note-dropzone span {
            color: #6a7287;
            font-size: 13px;
            font-weight: 600;
        }

        .note-dropzone__button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0 18px;
            border-radius: 10px;
            background: linear-gradient(135deg, #5b57d6, #6d4fff);
            color: #fff !important;
            font-weight: 800;
        }

        .note-upload-status {
            min-height: 20px;
            margin: 10px 0 0;
            color: #667085;
            font-size: 13px;
            font-weight: 600;
        }

        .note-upload-status.is-error {
            color: #dc2626;
        }

        .note-upload-status.is-success {
            color: #16a34a;
        }

        .note-attachments-hint {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-top: 12px;
        }

        .note-attachments-hint__label {
            color: #667085;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .note-attachments-hint__chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .note-attachments-hint__chips span {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 0 10px;
            border: 1px solid rgba(91, 87, 214, 0.16);
            border-radius: 999px;
            background: rgba(91, 87, 214, 0.05);
            color: #5b57d6;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

            .note-upload-errors {
                display: grid;
                gap: 8px;
                margin-top: 10px;
            }

        .note-upload-error {
            display: inline-flex;
            align-items: flex-start;
            gap: 8px;
            padding: 10px 12px;
            border: 1px solid rgba(220, 38, 38, 0.16);
            border-radius: 12px;
            background: rgba(220, 38, 38, 0.05);
            color: #b91c1c;
            font-size: 13px;
            line-height: 1.5;
            font-weight: 600;
        }

        .note-attachments-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 18px 0 12px;
        }

        .note-attachments-summary h3 {
            margin: 0;
            color: #16213f;
            font-size: 18px;
            font-weight: 900;
        }

        .note-attachments-summary span {
            color: #667085;
            font-size: 14px;
            font-weight: 600;
        }

        .note-attachments-summary strong {
            color: #16213f;
        }

        .note-file-reorder-hint {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 6px;
            color: #667085;
            font-size: 12px;
            font-weight: 700;
        }

        .note-file-reorder-hint i {
            color: #5b57d6;
            font-size: 14px;
        }

        .note-upload-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-top: 14px;
        }

        .note-file-row.is-upload-failed {
            border-color: rgba(220, 38, 38, 0.24);
            background: linear-gradient(180deg, rgba(220, 38, 38, 0.03), #fff);
        }

        .note-upload-summary__item {
            display: grid;
            gap: 4px;
            padding: 12px 14px;
            border: 1px solid rgba(91, 87, 214, 0.12);
            border-radius: 14px;
            background: rgba(91, 87, 214, 0.04);
        }

        .note-upload-summary__item strong {
            color: #16213f;
            font-size: 16px;
            font-weight: 900;
        }

        .note-upload-summary__item span {
            color: #667085;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .note-file-list {
            display: grid;
            gap: 10px;
        }

        .note-file-empty {
            display: grid;
            place-items: center;
            gap: 8px;
            padding: 28px;
            margin-top: 12px;
            border: 1px dashed rgba(91, 87, 214, 0.3);
            border-radius: 16px;
            background: rgba(91, 87, 214, 0.03);
            text-align: center;
            color: #667085;
        }

        .note-file-empty i {
            color: #5b57d6;
            font-size: 28px;
        }

        .note-file-empty strong {
            color: #16213f;
            font-size: 15px;
            font-weight: 800;
        }

        .note-file-empty p {
            margin: 0;
            font-size: 13px;
            line-height: 1.6;
        }

        .note-file-row {
            display: grid;
            grid-template-columns: 40px minmax(0, 1fr) auto;
            gap: 14px;
            align-items: center;
            min-height: 74px;
            padding: 12px 14px;
            border: 1px solid #e1e6f1;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 8px 18px rgba(20, 33, 61, 0.03);
        }

        .note-file-row.is-uploading {
            border-color: rgba(91, 87, 214, 0.28);
            background: linear-gradient(180deg, rgba(91, 87, 214, 0.04), #fff);
        }

        .note-file-row.is-dragging {
            opacity: 0.55;
            transform: scale(0.99);
        }

        .note-file-row.is-drop-target {
            border-color: rgba(91, 87, 214, 0.45);
            box-shadow: 0 0 0 3px rgba(91, 87, 214, 0.08);
        }

        .note-file-row__body strong {
            display: block;
            color: #16213f;
            font-size: 14px;
            font-weight: 800;
        }

        .note-file-row__body span {
            display: block;
            margin-top: 2px;
            color: #667085;
            font-size: 13px;
        }

        .note-file-row__actions {
            display: grid;
            justify-items: end;
            gap: 8px;
            color: #52607b;
        }

        .note-file-row__actions button {
            display: inline-grid;
            place-items: center;
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 10px;
            background: rgba(91, 87, 214, 0.08);
            color: #5b57d6;
            transition: background-color 0.18s ease, color 0.18s ease, transform 0.18s ease;
        }

        .note-file-row__drag {
            background: rgba(91, 87, 214, 0.12);
            color: #4338ca;
            cursor: grab;
        }

        .note-file-row__actions button:hover {
            background: rgba(91, 87, 214, 0.14);
            color: #4338ca;
            transform: translateY(-1px);
        }

        .note-file-row__drag:hover {
            background: rgba(91, 87, 214, 0.18);
        }

        .note-file-row__drag:active {
            cursor: grabbing;
        }

        .note-file-row__actions button.is-danger {
            background: rgba(220, 38, 38, 0.08);
            color: #b91c1c;
        }

        .note-file-row__actions button.is-danger:hover {
            background: rgba(220, 38, 38, 0.14);
            color: #991b1b;
        }

        .note-file-row__actions button.is-primary {
            background: rgba(91, 87, 214, 0.12);
            color: #4f46e5;
        }

        .note-file-row__actions button.is-primary:hover {
            background: rgba(91, 87, 214, 0.18);
            color: #4338ca;
        }

        .note-file-row__status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #16a34a;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .note-file-row__status-badge {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            padding: 0 10px;
            border-radius: 999px;
            background: rgba(22, 163, 74, 0.08);
            color: #15803d;
        }

        .note-file-row__status-badge--uploading {
            background: rgba(91, 87, 214, 0.08);
            color: #5b57d6;
        }

        .note-file-row__status-badge--error {
            background: rgba(220, 38, 38, 0.08);
            color: #b91c1c;
        }

        .note-file-row__progress {
            width: 100%;
            min-width: 160px;
            height: 7px;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(91, 87, 214, 0.08);
        }

        .note-file-row__progress-bar {
            width: 0%;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #5b57d6, #6d4fff);
            transition: width 0.16s ease;
        }

        .note-file-row__meta {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .note-file-row__type {
            display: inline-flex;
            align-items: center;
            min-height: 22px;
            padding: 0 8px;
            border-radius: 999px;
            background: rgba(91, 87, 214, 0.08);
            color: #5b57d6;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .note-attachments-card__footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 26px;
            padding-top: 20px;
        }

        @media (max-width: 992px) {
            .note-attachments-card__footer {
                flex-direction: column;
                align-items: stretch;
            }

            .note-attachments-card__footer .note-action {
                width: 100%;
            }

            .note-attachments-summary {
                flex-direction: column;
                align-items: flex-start;
            }

            .note-upload-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 576px) {
            .note-upload-summary {
                grid-template-columns: 1fr;
            }

            .note-file-row {
                grid-template-columns: 34px minmax(0, 1fr);
            }

            .note-file-row__actions {
                grid-column: 1 / -1;
                justify-items: start;
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
