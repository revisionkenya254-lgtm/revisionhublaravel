@php
    $isEdit = isset($resource);
    $resource = $resource ?? null;
    $returnQuery = request()->query();
    $action = $isEdit
        ? route($meta['route'] . '.update', array_merge(['resource' => $resource?->id], $returnQuery))
        : route($meta['route'] . '.store');
    $resourceMetadata = $resource?->metadata ?? [];
    $resourceTypeLabel = $meta['singular'] ?? ucfirst((string) $type);
    $paper = $paperForm ?? [];
    $sharedEducationLevels = collect($paperEducationCategories ?? $educationCategories ?? $categories ?? $metadataOptions['education_levels'] ?? [])
        ->map(fn ($category) => $category->translation?->name ?? $category->name ?? $category->label ?? (string) $category)
        ->filter()
        ->values()
        ->all();
    $sharedClassGradesByLevel = $metadataOptions['class_grades_by_level'] ?? [];
    $currentEducationLevel = old('education_level', $paper['education_level'] ?? ($resourceMetadata['education_level'] ?? ''));
    if (filled($currentEducationLevel) && ! in_array($currentEducationLevel, $sharedEducationLevels, true)) {
        $sharedEducationLevels[] = $currentEducationLevel;
    }
    $sharedClassGrades = $sharedClassGradesByLevel[$currentEducationLevel] ?? ($sharedClassGradesByLevel['default'] ?? $metadataOptions['class_grades']);
    $currentClassGrade = old('class_grade', $paper['class_grade'] ?? ($resourceMetadata['class_grade'] ?? ''));
    if (filled($currentClassGrade) && ! in_array($currentClassGrade, $sharedClassGrades, true)) {
        $sharedClassGrades[] = $currentClassGrade;
    }
    $sharedExamCategories = collect($metadataOptions['exam_categories_by_level'] ?? [])
        ->flatten()
        ->filter()
        ->unique()
        ->values()
        ->all();
    $currentExamCategory = old('exam_category', $paper['exam_category'] ?? ($resourceMetadata['exam_category'] ?? ''));
    if (filled($currentExamCategory) && ! in_array($currentExamCategory, $sharedExamCategories, true)) {
        $sharedExamCategories[] = $currentExamCategory;
    }
    $sharedSubjectOptions = collect($metadataOptions['subject_overrides_by_class_grade_and_exam_category'] ?? [])
        ->flatten()
        ->map(fn ($subject) => is_array($subject) ? ($subject['label'] ?? $subject['name'] ?? $subject['value'] ?? null) : $subject)
        ->filter()
        ->unique()
        ->values()
        ->all();
    $sharedYears = $metadataOptions['years'];
    $sharedLanguages = $metadataOptions['languages'];
    $sharedAccessTypes = $metadataOptions['access_types'];
    $sharedPreviewPages = $metadataOptions['preview_pages'];
    $sharedNoteVisibility = $metadataOptions['note_visibility'];
    $subjectValue = old('subject', old('course', $paper['subject'] ?? ($paper['course'] ?? ($resourceMetadata['subject'] ?? ($resourceMetadata['course'] ?? '')))));
    if (filled($subjectValue) && ! in_array($subjectValue, $sharedSubjectOptions, true)) {
        $sharedSubjectOptions[] = $subjectValue;
    }
    $extractionConfirmed = filter_var(old('extraction_confirmed', data_get($resourceMetadata, 'extraction_confirmed', false)), FILTER_VALIDATE_BOOL);
    $aiSettings = $aiSettings ?? null;
    $resolveClassGradeLabel = static function (?string $educationLevel): string {
        $level = strtolower(trim((string) $educationLevel));

        return preg_match('/tvet|university|college|certificate|diploma|undergraduate|tertiary|higher education/', $level)
            ? __('School of')
            : __('Class / Grade');
    };
    $classGradeLabel = $resolveClassGradeLabel($currentEducationLevel);
    $resolveSubjectLabel = static function (?string $educationLevel): string {
        $level = strtolower(trim((string) $educationLevel));

        return preg_match('/tvet|university|college|certificate|diploma|undergraduate|professional|tertiary|higher education/', $level)
            ? __('Courses')
            : __('Subject');
    };
    $resolveSubjectFieldName = static function (?string $educationLevel): string {
        $level = strtolower(trim((string) $educationLevel));

        return preg_match('/tvet|university|college|certificate|diploma|undergraduate|professional|tertiary|higher education/', $level)
            ? 'course'
            : 'subject';
    };
    $subjectLabel = $resolveSubjectLabel($currentEducationLevel);
    $subjectFieldName = $resolveSubjectFieldName($currentEducationLevel);
    $reviewTitle = $paper['title'] ?? $resource?->title ?? '';
    $reviewDescription = $paper['description'] ?? $resource?->description ?? '';
    $reviewEducationLevel = $paper['education_level'] ?? $resourceMetadata['education_level'] ?? '';
    $reviewClassGrade = $paper['class_grade'] ?? $resourceMetadata['class_grade'] ?? '';
    $reviewSubject = $paper['subject'] ?? ($paper['course'] ?? ($resourceMetadata['subject'] ?? ($resourceMetadata['course'] ?? '')));
    $reviewExamCategory = $paper['exam_category'] ?? $resourceMetadata['exam_category'] ?? '';
    $reviewYear = $paper['year'] ?? $resourceMetadata['year'] ?? '';
    $reviewLanguage = $paper['language'] ?? $resourceMetadata['language'] ?? '';
    $reviewAccessType = $paper['access_type'] ?? $resourceMetadata['access_type'] ?? '';
    $reviewPreviewPages = $paper['preview_pages'] ?? $resourceMetadata['preview_pages'] ?? '';
    $formatMetadataValue = function ($value) {
        if (is_array($value)) {
            return implode(', ', collect($value)->filter()->map(fn ($item) => is_array($item) ? json_encode($item) : (string) $item)->all());
        }

        if (is_bool($value)) {
            return $value ? __('Yes') : __('No');
        }

        return trim((string) $value);
    };
    $reviewSnapshot = collect([
        ['label' => __('Title'), 'value' => $reviewTitle],
        ['label' => __('Education Level'), 'value' => $reviewEducationLevel],
        ['label' => __('Class / Grade'), 'value' => $reviewClassGrade],
        ['label' => __('Subject / Course'), 'value' => $reviewSubject],
        ['label' => __('Exam Category'), 'value' => $reviewExamCategory],
        ['label' => __('Year'), 'value' => $reviewYear],
        ['label' => __('Language'), 'value' => $reviewLanguage],
        ['label' => __('Access Type'), 'value' => $reviewAccessType],
        ['label' => __('Preview Pages'), 'value' => $reviewPreviewPages],
        ['label' => __('Status'), 'value' => match ($resource?->status ?? '') {
            'active' => __('Published'),
            'inactive' => __('Unpublished'),
            'is_draft' => __('Drafted'),
            default => ucfirst((string) ($resource?->status ?? '')),
        }],
        ['label' => __('Approval'), 'value' => match ($resource?->is_approved ?? '') {
            'pending' => __('Pending'),
            'approved' => __('Approved'),
            'rejected' => __('Rejected'),
            default => ucfirst((string) ($resource?->is_approved ?? '')),
        }],
        ['label' => __('File Type'), 'value' => $resource?->file_type ?: ($resource?->file_path ? __('Attached') : '')],
        ['label' => __('Price'), 'value' => ($resource?->price ?? 0) == 0 ? __('Free') : currency(($resource?->discount ?? 0) > 0 ? $resource?->discount : ($resource?->price ?? 0))],
        ['label' => __('Discount Price'), 'value' => ($resource?->discount ?? 0) > 0 ? currency($resource?->discount) : __('None')],
        ['label' => __('File'), 'value' => $resource?->file_path ? basename($resource?->file_path) : __('Not attached')],
    ])->filter(fn ($item) => filled($item['value']));
    $latestAiDocumentMethodLabel = __('local processing');
    if (! empty($latestAiDocument)) {
        $latestExtractionMethod = strtolower(trim((string) data_get($latestAiDocument->metadata ?? [], 'extraction_method', '')));
        $latestQuestionMethod = strtolower(trim((string) data_get($latestAiDocument->metadata ?? [], 'question_extraction_method', '')));
        $latestMethodMap = [
            'word2007' => __('DOCX text extraction'),
            'word2007-xml' => __('DOCX XML text extraction'),
            'msdoc' => __('legacy DOC extraction'),
            'legacy-ole' => __('legacy DOC extraction'),
            'legacy-ole-low-confidence' => __('legacy DOC extraction with low-confidence fallback'),
            'legacy-doc-render-text' => __('legacy DOC rendering'),
            'legacy-doc-render-images' => __('legacy DOC image rendering'),
            'prinsfrank' => __('PDF text extraction'),
            'tesseract' => __('OCR fallback'),
            'ocr' => __('OCR fallback'),
            'openai_vision' => __('OpenAI vision OCR'),
            'openai_vision_image' => __('OpenAI vision image OCR'),
            'openai_direct_review' => __('OpenAI direct review'),
            'heuristic' => __('question parsing heuristics'),
            'openai' => __('OpenAI question extraction'),
        ];
        $latestLabels = [];
        if ($latestExtractionMethod !== '') {
            $latestLabels[] = $latestMethodMap[$latestExtractionMethod] ?? str_replace('_', ' ', $latestExtractionMethod);
        }
        if ($latestQuestionMethod !== '' && $latestQuestionMethod !== $latestExtractionMethod) {
            $latestLabels[] = $latestMethodMap[$latestQuestionMethod] ?? str_replace('_', ' ', $latestQuestionMethod);
        }
        if (! empty($latestLabels)) {
            $latestAiDocumentMethodLabel = implode(' + ', $latestLabels);
        }
    }
    $latestAiDocumentReady = ! empty($latestAiDocument) && in_array($latestAiDocument->status ?? '', ['processed', 'requires_ocr'], true);
    $latestAiDocumentNeedsOpenAiReview = ! empty($latestAiDocument) && in_array($latestAiDocument->status ?? '', ['failed', 'requires_ocr'], true);
    $latestAiDocumentOpenAiRetryReady = ! empty($latestAiDocument) && (
        in_array($latestAiDocument->status ?? '', ['failed', 'requires_ocr'], true)
        || (
            ($latestAiDocument->status ?? '') === 'processed'
            && (
                data_get($latestAiDocument->metadata ?? [], 'requires_transcription', false)
                || in_array(strtolower(trim((string) data_get($latestAiDocument->metadata ?? [], 'extraction_method', ''))), ['msdoc', 'legacy-ole-low-confidence', 'legacy-ole', 'word2007', 'word2007-xml', 'prinsfrank', 'tesseract', 'ocr'], true)
                || in_array(strtolower(trim((string) data_get($latestAiDocument->metadata ?? [], 'question_extraction_method', ''))), ['heuristic', 'heuristic-fallback'], true)
            )
        )
    );
    $headerAiDocumentStatusMeta = [
        'pending' => ['label' => __('Queued'), 'class' => 'paper-status-pill--amber'],
        'processing' => ['label' => __('Processing'), 'class' => 'paper-status-pill--blue'],
        'processed' => ['label' => __('Processed'), 'class' => 'paper-status-pill--green'],
        'requires_ocr' => ['label' => __('Needs OCR'), 'class' => 'paper-status-pill--dark'],
        'failed' => ['label' => __('Failed'), 'class' => 'paper-status-pill--red'],
    ][($latestAiDocument->status ?? 'pending')] ?? ['label' => __('No document'), 'class' => 'paper-status-pill--muted'];
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag();
    $titlePlaceholder = $isEdit ? __('Enter title') : __('Enter title');
    $descriptionPlaceholder = __('Describe the resource clearly for staff and students.');
    $defaultQueueMode = old('queue_mode', $aiSettings->document_queue_mode ?? config('ai.document_processing.default_mode', 'local'));
    $queueModeLabelMap = [
        'local' => __('Local'),
        'redis' => __('Redis'),
    ];
    $queueModeDescriptionMap = [
        'local' => __('Processes immediately on click using the local server request.'),
        'redis' => __('Uses Redis so Horizon can manage document processing.'),
    ];
@endphp

@extends('admin.master_layout')

@section('title')
    <title>{{ $isEdit ? __('Edit') : __('Create') }} {{ __($meta['singular']) }}</title>
@endsection

@section('admin-content')
    <div class="main-content paper-editor-page">
        <section class="section">
            <div class="paper-editor-topbar">
                <div class="paper-editor-topbar__left">
                    <a href="{{ route($meta['route'] . '.index', $returnQuery) }}" class="paper-btn paper-btn--ghost">
                        <i class="fas fa-arrow-left"></i>
                        <span>{{ __('Back') }}</span>
                    </a>
                    <div>
                        <h1 class="paper-editor-title">{{ $isEdit ? __('Edit :type', ['type' => $resourceTypeLabel]) : __('Create :type', ['type' => $resourceTypeLabel]) }}</h1>
                        <div class="paper-editor-breadcrumb">
                            <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a>
                            <span>/</span>
                            <a href="{{ route($meta['route'] . '.index', $returnQuery) }}">{{ __($meta['title']) }}</a>
                            <span>/</span>
                            <span>{{ __('Edit') }}</span>
                        </div>
                    </div>
                </div>

                <div class="paper-editor-topbar__right">
                    <div class="paper-editor-topbar__actions">
                        @if ($isEdit)
                            <div class="paper-queue-toggle" data-queue-mode-toggle>
                                <input type="hidden" id="paper-queue-mode" value="{{ $defaultQueueMode }}">
                                <span class="paper-queue-toggle__label">{{ __('Document Queue') }}</span>
                                <div class="paper-queue-toggle__options" role="group" aria-label="{{ __('Document queue mode') }}">
                                    @foreach (['local', 'redis'] as $mode)
                                        <button
                                            type="button"
                                            class="paper-queue-toggle__option"
                                            data-queue-mode-option="{{ $mode }}"
                                            data-queue-mode-label="{{ $queueModeLabelMap[$mode] }}"
                                            data-queue-mode-description="{{ $queueModeDescriptionMap[$mode] }}"
                                        >
                                            <span>{{ $queueModeLabelMap[$mode] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                                <small class="paper-queue-toggle__hint" id="paper-queue-mode-hint">{{ $queueModeDescriptionMap[$defaultQueueMode] ?? $queueModeDescriptionMap['local'] }}</small>
                            </div>
                            <button
                                type="button"
                                class="paper-btn paper-btn--primary paper-btn--process"
                                id="process-document-button"
                                    data-reprocess-url="{{ route($meta['route'] . '.ai-reprocess', $resource?->id) }}"
                                    data-status-url="{{ route($meta['route'] . '.ai-document', $resource?->id) }}"
                            >
                                <i class="fas fa-play-circle"></i>
                                <span>{{ __('Process Document') }}</span>
                            </button>
                            @if ($latestAiDocumentNeedsOpenAiReview)
                                <button
                                    type="button"
                                    class="paper-btn paper-btn--outline paper-btn--process"
                                    data-process-openai
                                >
                                    <i class="fas fa-brain"></i>
                                    <span>{{ __('Retry with OpenAI') }}</span>
                                </button>
                            @endif
                            <span class="paper-status-pill {{ $headerAiDocumentStatusMeta['class'] }}">
                                {{ $headerAiDocumentStatusMeta['label'] }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            @if ($isEdit)
                <div class="paper-queue-notice paper-queue-notice--{{ $defaultQueueMode }}">
                    <div class="paper-queue-notice__icon">
                        <i class="fas {{ $defaultQueueMode === 'redis' ? 'fa-bolt' : 'fa-server' }}"></i>
                    </div>
                    <div class="paper-queue-notice__body">
                        <strong>{{ __('Document queue mode: :mode', ['mode' => $queueModeLabelMap[$defaultQueueMode] ?? __('Local')]) }}</strong>
                        <p>{{ $queueModeDescriptionMap[$defaultQueueMode] ?? $queueModeDescriptionMap['local'] }}</p>
                    </div>
                    <div class="paper-queue-notice__meta">
                        @if ($defaultQueueMode === 'redis')
                            <span class="paper-status-pill paper-status-pill--green">{{ __('Horizon ready') }}</span>
                            <small>{{ __('Use Redis + Horizon on production.') }}</small>
                        @else
                            <span class="paper-status-pill paper-status-pill--blue">{{ __('Immediate mode') }}</span>
                            <small>{{ __('Processing starts right away without a queue worker.') }}</small>
                        @endif
                    </div>
                </div>
            @endif

            <form id="past-paper-edit-form" action="{{ $action }}" method="POST">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <div class="paper-grid">
                    <div class="paper-grid__main">
                        <section class="paper-card">
                            <div class="paper-card__head">
                                <div class="paper-step-badge">1</div>
                                <div>
                                    <h2>{{ __('Basic Details') }}</h2>
                                    <p>{{ __('Core product information and publishing state.') }}</p>
                                </div>
                            </div>

                            <div class="paper-form-grid paper-form-grid--2">
                                <div class="paper-field paper-field--full">
                                    <label for="title">{{ __('Title') }} <span>*</span></label>
                                    <input id="title" name="title" type="text" value="{{ old('title', $paper['title'] ?? ($resource?->title ?? '')) }}" placeholder="{{ $titlePlaceholder }}">
                                    @error('title')<small class="paper-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="paper-field">
                                    <label for="education_level">{{ __('Education Level') }} <span>*</span></label>
                                    <select id="education_level" name="education_level">
                                        <option value="">{{ __('Select level') }}</option>
                                        @foreach ($sharedEducationLevels as $option)
                                            <option value="{{ $option }}" @selected($currentEducationLevel === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="paper-field">
                                    <label for="class_grade">
                                        <span class="paper-field__label-text" data-label-for="class_grade">{{ $classGradeLabel }}</span>
                                        <span>*</span>
                                        <span class="paper-field__loading" data-select-loading="class_grade" hidden aria-hidden="true">
                                            <span class="paper-field__spinner"></span>
                                        </span>
                                    </label>
                                    <select id="class_grade" name="class_grade">
                                        <option value="">{{ __('Select grade') }}</option>
                                        @foreach ($sharedClassGrades as $option)
                                            <option value="{{ $option }}" @selected($currentClassGrade === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="paper-field">
                                    <label for="subject">
                                        <span class="paper-field__label-text" data-label-for="subject">{{ $subjectLabel }}</span>
                                        <span>*</span>
                                        <span class="paper-field__loading" data-select-loading="subject" hidden aria-hidden="true">
                                            <span class="paper-field__spinner"></span>
                                        </span>
                                    </label>
                                    <select id="subject" name="{{ $subjectFieldName }}">
                                        <option value="">{{ __('Select subject') }}</option>
                                        @foreach ($sharedSubjectOptions as $option)
                                            <option value="{{ $option }}" @selected($subjectValue === $option)>{{ $option }}</option>
                                        @endforeach
                                        @if (filled($subjectValue) && ! in_array($subjectValue, $sharedSubjectOptions, true))
                                            <option value="{{ $subjectValue }}" selected>{{ $subjectValue }}</option>
                                        @endif
                                    </select>
                                </div>

                                <div class="paper-field">
                                    <label for="exam_category">
                                        <span class="paper-field__label-text" data-label-for="exam_category">{{ __('Exam Category') }}</span>
                                        <span>*</span>
                                        <span class="paper-field__loading" data-select-loading="exam_category" hidden aria-hidden="true">
                                            <span class="paper-field__spinner"></span>
                                        </span>
                                    </label>
                                    <select id="exam_category" name="exam_category">
                                        <option value="">{{ __('Select exam') }}</option>
                                        @foreach ($sharedExamCategories as $option)
                                            <option value="{{ $option }}" @selected(old('exam_category', $paper['exam_category'] ?? ($resourceMetadata['exam_category'] ?? '')) === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="paper-field">
                                    <label for="year">{{ __('Year') }} <span>*</span></label>
                                    <select id="year" name="year">
                                        <option value="">{{ __('Select year') }}</option>
                                        @foreach ($sharedYears as $year)
                                            <option value="{{ $year }}" @selected((string) old('year', $paper['year'] ?? ($resourceMetadata['year'] ?? '')) === (string) $year)>{{ $year }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="paper-field">
                                    <label for="language">{{ __('Language') }} <span>*</span></label>
                                    <select id="language" name="language">
                                        <option value="">{{ __('Select language') }}</option>
                                        @foreach ($sharedLanguages as $option)
                                            <option value="{{ $option }}" @selected(old('language', $paper['language'] ?? ($resourceMetadata['language'] ?? '')) === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="paper-field">
                                    <label for="access_type">{{ __('Access Type') }} <span>*</span></label>
                                    <select id="access_type" name="access_type">
                                        <option value="">{{ __('Select access') }}</option>
                                        @foreach ($sharedAccessTypes as $option)
                                            <option value="{{ $option }}" @selected(old('access_type', $paper['access_type'] ?? ($resourceMetadata['access_type'] ?? '')) === $option)>{{ ucfirst($option) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="paper-field">
                                    <label for="preview_pages">{{ __('Preview Pages') }} <span>*</span></label>
                                    <select id="preview_pages" name="preview_pages">
                                        <option value="">{{ __('Select pages') }}</option>
                                        @foreach ($sharedPreviewPages as $option)
                                            <option value="{{ $option }}" @selected(old('preview_pages', $paper['preview_pages'] ?? ($resourceMetadata['preview_pages'] ?? '')) === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="paper-field">
                                    <label for="price">{{ __('Price (KES)') }} <span>*</span></label>
                                    <input id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price', $resource?->price ?? 0) }}">
                                </div>

                                <div class="paper-field">
                                    <label for="discount">{{ __('Discount Price (KES)') }}</label>
                                    <input id="discount" name="discount" type="number" step="0.01" min="0" value="{{ old('discount', $resource?->discount ?? '') }}">
                                </div>

                                <div class="paper-field">
                                    <label for="status">{{ __('Status') }} <span>*</span></label>
                                    <select id="status" name="status">
                                        <option value="active" @selected(old('status', $resource?->status ?? 'is_draft') === 'active')>{{ __('Published') }}</option>
                                        <option value="inactive" @selected(old('status', $resource?->status ?? 'is_draft') === 'inactive')>{{ __('Unpublished') }}</option>
                                        <option value="is_draft" @selected(old('status', $resource?->status ?? 'is_draft') === 'is_draft')>{{ __('Drafted') }}</option>
                                    </select>
                                </div>

                                <div class="paper-field">
                                    <label for="is_approved">{{ __('Approval Status') }} <span>*</span></label>
                                    <select id="is_approved" name="is_approved">
                                        <option value="pending" @selected(old('is_approved', $resource?->is_approved ?? 'pending') === 'pending')>{{ __('Pending') }}</option>
                                        <option value="approved" @selected(old('is_approved', $resource?->is_approved ?? 'pending') === 'approved')>{{ __('Approved') }}</option>
                                        <option value="rejected" @selected(old('is_approved', $resource?->is_approved ?? 'pending') === 'rejected')>{{ __('Rejected') }}</option>
                                    </select>
                                </div>

                                <div class="paper-field paper-field--full">
                                    <div class="paper-confirmation-box">
                                        <label class="paper-confirmation-box__label" for="extraction_confirmed">
                                            <input
                                                id="extraction_confirmed"
                                                name="extraction_confirmed"
                                                type="checkbox"
                                                value="1"
                                                @checked($extractionConfirmed)
                                            >
                                            <span>{{ __('Confirm extracted document reviewed') }}</span>
                                        </label>
                                        <p class="paper-confirmation-box__note">
                                            {{ __('Check this only after the source file has been extracted and reviewed. Approval cannot be set to Approved until this step is confirmed.') }}
                                        </p>
                                        <p class="paper-confirmation-box__warning" id="paper-save-guard-message" hidden>
                                            {{ __('Confirm extraction before saving an Approved past paper.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="paper-card">
                            <div class="paper-card__head">
                                <div class="paper-step-badge">2</div>
                                <div>
                                    <h2>{{ __('File / Source') }}</h2>
                                    <p>{{ __('Attached thumbnail and source file for this record.') }}</p>
                                </div>
                            </div>

                            <div class="paper-form-grid">
                                <div class="paper-field paper-field--full">
                                    <label for="thumbnail">{{ __('Thumbnail') }}</label>
                                    <div class="paper-file-picker">
                                        <input id="thumbnail" readonly type="text" name="thumbnail" value="{{ old('thumbnail', $resource?->thumbnail ?? '') }}" placeholder="{{ __('/uploads/thumbnails/...') }}">
                                        <a data-input="thumbnail" data-preview="holder" class="paper-file-picker__button file-manager-image">{{ __('Choose') }}</a>
                                    </div>
                                    @if (!empty($resource?->thumbnail))
                                        <div class="paper-file-card-mini">
                                            <span class="paper-file-card-mini__icon"><i class="fas fa-image"></i></span>
                                            <div>
                                                <strong>{{ __('Thumbnail attached') }}</strong>
                                                <small>{{ basename($resource?->thumbnail) }}</small>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="paper-field paper-field--full">
                                    <label for="file_path">{{ __('Source File') }} <span>*</span></label>
                                    <div class="paper-file-picker">
                                        <input id="file_path" readonly type="text" name="file_path" value="{{ old('file_path', $resource?->file_path ?? '') }}" placeholder="{{ __('Select a source file') }}">
                                        <a data-input="file_path" data-preview="holder" class="paper-file-picker__button file-manager">{{ __('Choose') }}</a>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        {{ __('DOCX is recommended for reliable AI extraction. DOC is still supported, but legacy or scanned DOC files may need an admin to retry them with OpenAI-assisted review if local extraction cannot read them.') }}
                                    </small>
                                    @if (!empty($resource?->file_path))
                                        <div class="paper-file-card-mini">
                                            <span class="paper-file-card-mini__icon paper-file-card-mini__icon--red"><i class="fas fa-file-pdf"></i></span>
                                            <div>
                                                <strong>{{ __('File attached') }}</strong>
                                                <small>{{ basename($resource?->file_path) }}</small>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="paper-field paper-field--full">
                                    <label for="file_type">{{ __('File Type') }}</label>
                                    <select id="file_type" name="file_type">
                                        <option value="pdf" @selected(old('file_type', $resource?->file_type ?? '') === 'pdf')>{{ __('PDF') }}</option>
                                        <option value="docx" @selected(old('file_type', $resource?->file_type ?? '') === 'docx')>{{ __('DOCX') }}</option>
                                        <option value="doc" @selected(old('file_type', $resource?->file_type ?? '') === 'doc')>{{ __('DOC') }}</option>
                                        <option value="link" @selected(old('file_type', $resource?->file_type ?? '') === 'link')>{{ __('Link') }}</option>
                                    </select>
                                </div>
                            </div>
                        </section>

                        <section class="paper-card">
                            <div class="paper-card__head">
                                <div class="paper-step-badge">3</div>
                                <div>
                                    <h2>{{ __('Description') }}</h2>
                                    <p>{{ __('Short summary for reviewers and students.') }}</p>
                                </div>
                            </div>

                            <div class="paper-field">
                                <label for="description">{{ __('Description') }} <span>*</span></label>
                                <textarea id="description" name="description" rows="7" placeholder="{{ $descriptionPlaceholder }}">{{ old('description', $paper['description'] ?? ($resource?->description ?? '')) }}</textarea>
                            </div>
                        </section>
                    </div>

                    <aside class="paper-grid__side">
                        <div class="paper-card paper-card--soft-green">
                            @php
                                $fileUploaded = filled($resource?->file_path ?? '');
                                $thumbnailAvailable = filled($resource?->thumbnail ?? '');
                                $metadataChecks = [
                                    filled($reviewTitle ?? null),
                                    filled($reviewEducationLevel ?? null),
                                    filled($reviewClassGrade ?? null),
                                    filled($reviewSubject ?? null),
                                    filled($reviewExamCategory ?? null),
                                    filled($reviewYear ?? null),
                                    filled($reviewLanguage ?? null),
                                    filled($reviewAccessType ?? null),
                                    filled($reviewPreviewPages ?? null),
                                ];
                                $metadataComplete = collect($metadataChecks)->every(fn ($value) => $value);
                                $descriptionReviewed = filled(trim((string) ($reviewDescription ?? ''))) && mb_strlen(trim((string) ($reviewDescription ?? ''))) > 20;
                                $aiReady = isset($latestAiDocument) && in_array($latestAiDocument->status ?? '', ['processed', 'requires_ocr'], true);
                                $readyForPublishing = $fileUploaded && $thumbnailAvailable && $metadataComplete && $descriptionReviewed && $aiReady && $extractionConfirmed;
                                $checklistItems = [
                                    ['label' => __('File uploaded'), 'done' => $fileUploaded],
                                    ['label' => __('Thumbnail attached'), 'done' => $thumbnailAvailable],
                                    ['label' => __('Metadata complete'), 'done' => $metadataComplete],
                                    ['label' => __('AI extraction complete'), 'done' => $aiReady],
                                    ['label' => __('Extraction confirmed'), 'done' => $extractionConfirmed],
                                    ['label' => __('Description reviewed'), 'done' => $descriptionReviewed],
                                    ['label' => __('Ready to publish'), 'done' => $readyForPublishing],
                                ];
                                $completedCount = collect($checklistItems)->where('done', true)->count();
                                $progressPercent = (int) round(($completedCount / max(1, count($checklistItems))) * 100);
                            @endphp
                            <div class="paper-card__head paper-card__head--spread">
                                <div>
                                    <h2>{{ __('Readiness Checklist') }}</h2>
                                    <p>{{ $completedCount }} / {{ count($checklistItems) }} {{ __('Complete') }}</p>
                                </div>
                                <span class="paper-pill paper-pill--green">{{ $progressPercent }}%</span>
                            </div>
                            <div class="paper-checklist">
                                @foreach ($checklistItems as $item)
                                    <div class="paper-checklist__item">
                                        <span class="paper-checklist__icon {{ $item['done'] ? 'is-done' : '' }}">
                                            <i class="fas {{ $item['done'] ? 'fa-check' : 'fa-circle' }}"></i>
                                        </span>
                                        <span>{{ $item['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <div class="paper-progress">
                                <div class="paper-progress__bar" style="width: {{ $progressPercent }}%"></div>
                            </div>
                        </div>

                        <div class="paper-card">
                            <div class="paper-card__head">
                                <div class="paper-icon paper-icon--blue"><i class="fas fa-eye"></i></div>
                                <div>
                                    <h2>{{ __('Review Snapshot') }}</h2>
                                    <p>{{ __('Quick view of the data that will be saved on this record.') }}</p>
                                </div>
                            </div>
                            <div class="paper-chip-grid paper-chip-grid--review">
                                @foreach ($reviewSnapshot as $item)
                                    <div class="paper-chip-card">
                                        <small>{{ $item['label'] }}</small>
                                        <strong>{{ $item['value'] }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="paper-card paper-card--soft-blue">
                            @if (!empty($latestAiDocument))
                                @php
                                    $aiDocumentStatusMeta = [
                                        'pending' => ['label' => __('Queued'), 'class' => 'paper-pill--amber'],
                                        'processing' => ['label' => __('Processing'), 'class' => 'paper-pill--blue'],
                                        'processed' => ['label' => __('Processed'), 'class' => 'paper-pill--green'],
                                        'requires_ocr' => ['label' => __('Needs OCR'), 'class' => 'paper-pill--dark'],
                                        'failed' => ['label' => __('Failed'), 'class' => 'paper-pill--red'],
                                    ][$latestAiDocument->status] ?? ['label' => ucfirst((string) $latestAiDocument->status), 'class' => 'paper-pill--muted'];
                                    $aiMetadata = $latestAiDocument->metadata ?? [];
                                    $latestAiFailureMessage = filled($latestAiDocument->failure_reason)
                                        ? $latestAiDocument->failure_reason
                                        : ($latestAiDocument->processingLogs->reverse()->firstWhere('stage', 'failed')->message ?? '');
                                @endphp
                                <div class="paper-card__head paper-card__head--spread">
                                    <div>
                                        <h2>{{ __('AI Processing Snapshot') }}</h2>
                                        <p>{{ __('Extraction status and processing evidence.') }}</p>
                                    </div>
                                </div>

                                <div class="paper-ai-top" id="ai-processing-top">
                                    <span class="paper-pill {{ $aiDocumentStatusMeta['class'] }}" id="ai-processing-status-pill">{{ $aiDocumentStatusMeta['label'] }}</span>
                                    <span class="paper-pill paper-pill--amber paper-ai-retry-badge" id="ai-processing-openai-badge" @if (! $latestAiDocumentOpenAiRetryReady) hidden @endif>
                                        {{ __('OpenAI retry ready') }}
                                    </span>
                                    <div class="paper-ai-summary">
                                        <div class="paper-ai-summary__item">
                                            <small>{{ __('Source Type') }}</small>
                                            <strong id="ai-processing-source-type">{{ $latestAiDocument->source_type }}</strong>
                                        </div>
                                        <div class="paper-ai-summary__item">
                                            <small>{{ __('Pages') }}</small>
                                            <strong id="ai-processing-page-count">{{ $latestAiDocument->page_count ?? __('N/A') }}</strong>
                                        </div>
                                        <div class="paper-ai-summary__item">
                                            <small>{{ __('Characters') }}</small>
                                            <strong id="ai-processing-character-count">{{ $latestAiDocument->character_count ? number_format($latestAiDocument->character_count) : __('N/A') }}</strong>
                                        </div>
                                        <div class="paper-ai-summary__item">
                                            <small>{{ __('OCR') }}</small>
                                            <strong id="ai-processing-ocr">{{ !empty($aiMetadata['requires_transcription']) ? __('Required') : __('Not Required') }}</strong>
                                        </div>
                                        <div class="paper-ai-summary__item">
                                            <small>{{ __('Questions') }}</small>
                                            <strong id="ai-processing-question-count">{{ isset($latestAiDocument->questions) ? $latestAiDocument->questions->count() : ($aiMetadata['question_count'] ?? __('N/A')) }}</strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="paper-ai-workspace">
                                    <div class="paper-ai-tabs" role="tablist" aria-label="{{ __('AI processing views') }}">
                                        <button type="button" class="paper-ai-tab is-active" data-ai-tab="snapshot" role="tab" aria-selected="true">
                                            {{ __('Snapshot') }}
                                        </button>
                                        <button type="button" class="paper-ai-tab" data-ai-tab="questions" role="tab" aria-selected="false">
                                            {{ __('Questions') }}
                                            <span class="paper-ai-tab__count" id="ai-processing-question-count-badge">{{ $latestAiDocument->questions->count() }}</span>
                                        </button>
                                        <button type="button" class="paper-ai-export" id="ai-processing-export-json" data-ai-export-json>
                                            <i class="fas fa-download"></i>
                                            <span>{{ __('Export JSON') }}</span>
                                        </button>
                                    </div>

                                    <div class="paper-ai-panel-stack">
                                        <div class="paper-ai-panel-sheet is-active" data-ai-panel="snapshot">
                                            <div
                                                class="paper-failure-callout {{ $latestAiDocument->status === 'failed' ? 'is-visible' : '' }}"
                                                id="ai-processing-failure"
                                                @if (! in_array($latestAiDocument->status, ['failed', 'requires_ocr'], true)) hidden @endif
                                            >
                                                <div class="paper-failure-callout__icon">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                </div>
                                                <div class="paper-failure-callout__body">
                                                    <strong>{{ $latestAiDocument->status === 'failed' ? __('Processing failed') : __('OpenAI-assisted review needed') }}</strong>
                                                    <p>{{ $latestAiFailureMessage ?: __('The document could not be processed.') }}</p>
                                                </div>
                                            </div>

                                            @if ($latestAiDocumentNeedsOpenAiReview)
                                                <div class="paper-ai-recovery">
                                                    <div class="paper-ai-recovery__body">
                                                        <strong>{{ __('Need a smarter retry?') }}</strong>
                                                        <p>{{ __('Use OpenAI-assisted review when the local Word extraction is too noisy or the file is scanned.') }}</p>
                                                    </div>
                                                    <button type="button" class="paper-btn paper-btn--outline paper-ai-recovery__btn" data-process-openai>
                                                        <i class="fas fa-brain"></i>
                                                        <span>{{ __('Retry with OpenAI') }}</span>
                                                    </button>
                                                </div>
                                            @endif

                                            <div class="paper-ai-grid">
                                                <div class="paper-ai-panel">
                                                    <h3>{{ __('Extracted Text Preview') }}</h3>
                                                    <div class="paper-code-block" id="ai-processing-excerpt">
                                                        {{ \Illuminate\Support\Str::limit($latestAiDocument->extracted_text_excerpt ?: ($aiMetadata['excerpt'] ?? ''), 380) ?: __('No extracted excerpt available yet.') }}
                                                    </div>
                                                </div>

                                                <div class="paper-ai-panel">
                                                    <h3>{{ __('Latest Processing Logs') }}</h3>
                                                    <div class="paper-log-list" id="ai-processing-logs">
                                                        @forelse ($latestAiDocument->processingLogs->take(4) as $log)
                                                            <div class="paper-log-list__item">
                                                                <span class="paper-log-list__dot"></span>
                                                                <div>
                                                                    <strong>{{ ucfirst((string) $log->stage) }}</strong>
                                                                    <p>{{ $log->message }}</p>
                                                                </div>
                                                            </div>
                                                        @empty
                                                            <p class="paper-empty">{{ __('No processing logs available yet.') }}</p>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="paper-ai-footer paper-ai-footer--stacked">
                                                <span>{{ __('Method:') }} <strong id="ai-processing-method">{{ $latestAiDocumentMethodLabel }}</strong></span>
                                            </div>

                                            <div class="paper-ai-footer">
                                                <span>{{ __('Last processed:') }} <strong id="ai-processing-last-processed">{{ $latestAiDocument->processed_at ? $latestAiDocument->processed_at->diffForHumans() : __('Not processed yet') }}</strong></span>
                                                <span>{{ __('Updated:') }} <strong id="ai-processing-updated">{{ $latestAiDocument->updated_at ? $latestAiDocument->updated_at->diffForHumans() : __('N/A') }}</strong></span>
                                            </div>
                                        </div>

                                        <div class="paper-ai-panel-sheet" data-ai-panel="questions" hidden>
                                            <div class="paper-ai-panel-sheet__head">
                                                <div>
                                                    <h3>{{ __('Extracted Questions') }}</h3>
                                                    <p>{{ __('A structured table of all questions detected in the processed paper.') }}</p>
                                                </div>
                                                <div class="paper-ai-saved-badge-wrap">
                                                    <span class="paper-pill paper-pill--green paper-ai-saved-badge" id="ai-processing-saved-badge">
                                                        {{ $latestAiDocument->questions->count() ? __(':count questions saved', ['count' => $latestAiDocument->questions->count()]) : __('No questions saved yet') }}
                                                    </span>
                                                    <small class="paper-ai-saved-note" id="ai-processing-saved-note">
                                                        {{ $latestAiDocument->questions->count() ? __('Questions synced to database') : __('Waiting for saved questions') }}
                                                    </small>
                                                </div>
                                                <button type="button" class="paper-btn paper-btn--outline paper-ai-export" id="ai-processing-export-json-inline" data-ai-export-json>
                                                    <i class="fas fa-download"></i>
                                                    <span>{{ __('Export JSON') }}</span>
                                                </button>
                                            </div>
                                            <div class="paper-question-table-wrap">
                                                <table class="paper-question-table">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ __('Question No.') }}</th>
                                                            <th>{{ __('Section') }}</th>
                                                            <th>{{ __('Marks') }}</th>
                                                            <th>{{ __('Raw Extracted Text') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="ai-processing-question-table-body">
                                                        @if ($latestAiDocument->questions->isNotEmpty())
                                                            @foreach ($latestAiDocument->questions as $question)
                                                                <tr>
                                                                    <td>{{ $question->question_number ?: $question->question_label }}</td>
                                                                    <td>{{ $question->section_label ?: __('N/A') }}</td>
                                                                    <td>{{ $question->marks_label ?: ($question->marks ? $question->marks . ' marks' : __('N/A')) }}</td>
                                                                    <td>{{ \Illuminate\Support\Str::limit($question->raw_text ?: $question->content, 220) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        @else
                                                            <tr>
                                                                <td colspan="4" class="paper-question-table__empty">{{ __('No extracted questions available yet.') }}</td>
                                                            </tr>
                                                        @endif
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            @else
                                <div class="paper-card__head paper-card__head--spread">
                                    <div>
                                        <h2>{{ __('AI Processing Snapshot') }}</h2>
                                        <p>{{ __('No AI document is attached to this paper yet.') }}</p>
                                    </div>
                                </div>
                                <p class="paper-empty">{{ __('Upload or attach a source file first, then use document processing to generate extraction and OCR review data.') }}</p>
                            @endif
                        </div>
                    </aside>
                </div>

                @if (!empty($latestAiDocument))
                    <div class="paper-card paper-card--soft-green paper-ai-questions-section">
                        <div class="paper-card__head paper-card__head--spread">
                            <div>
                                <h2>{{ __('All Extracted Questions') }}</h2>
                                <p>{{ __('Every question saved to the database for this paper is listed below.') }}</p>
                            </div>
                            <span class="paper-pill paper-pill--green">
                                {{ $latestAiDocument->questions->count() ? __(':count questions saved', ['count' => $latestAiDocument->questions->count()]) : __('No questions saved yet') }}
                            </span>
                        </div>

                        @if ($latestAiDocument->questions->isNotEmpty())
                            <div class="paper-question-table-wrap">
                                <table class="paper-question-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Question No.') }}</th>
                                            <th>{{ __('Section') }}</th>
                                            <th>{{ __('Marks') }}</th>
                                            <th>{{ __('Content') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($latestAiDocument->questions as $question)
                                            <tr>
                                                <td>{{ $question->question_number ?: $question->question_label }}</td>
                                                <td>{{ $question->section_label ?: __('N/A') }}</td>
                                                <td>{{ $question->marks_label ?: ($question->marks ? $question->marks . ' marks' : __('N/A')) }}</td>
                                                <td>{{ \Illuminate\Support\Str::limit($question->content ?: $question->raw_text, 320) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="paper-empty">{{ __('No extracted questions available yet.') }}</p>
                        @endif
                    </div>
                @endif

                <div class="paper-editor-actions paper-editor-actions--bottom">
                    <a href="{{ route($meta['route'] . '.index', $returnQuery) }}" class="paper-btn paper-btn--light">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="paper-btn paper-btn--primary">
                        <span>{{ __('Save Changes') }}</span>
                    </button>
                </div>
            </form>

            <div class="paper-process-modal" id="paper-process-modal" hidden>
                <div class="paper-process-modal__backdrop" data-process-close></div>
                <div class="paper-process-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="paper-process-modal-title">
                    <div class="paper-process-modal__head">
                        <div>
                            <p>{{ __('Document processing') }}</p>
                            <h3 id="paper-process-modal-title">{{ __('Process Document') }}</h3>
                        </div>
                        <button type="button" class="paper-process-modal__close" data-process-close aria-label="{{ __('Close') }}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="paper-process-modal__status">
                        <div class="paper-process-status-line">
                            <span class="paper-process-spinner" id="paper-process-spinner"></span>
                            <span class="paper-process-review-indicator" id="paper-process-review-indicator" hidden>
                                <i class="fas fa-brain"></i>
                                <span>{{ __('Reviewing with OpenAI') }}</span>
                            </span>
                            <strong id="paper-process-state">{{ __('Waiting to start...') }}</strong>
                            <span class="paper-pill paper-pill--amber paper-process-retry-badge" id="paper-process-openai-badge" @if (! $latestAiDocumentOpenAiRetryReady) hidden @endif>
                                {{ __('OpenAI retry ready') }}
                            </span>
                            <span class="paper-process-progress-value" id="paper-process-progress-value">0%</span>
                        </div>
                        <p id="paper-process-message">{{ __('We are preparing the source file for extraction and OCR review.') }}</p>
                        <div class="paper-progress paper-progress--dialog">
                            <div class="paper-progress__bar" id="paper-process-progress" style="width: 0%"></div>
                        </div>
                    </div>

                    <div class="paper-process-metrics">
                        <div class="paper-process-metric">
                            <small>{{ __('Status') }}</small>
                            <strong id="paper-process-status">{{ __('Queued') }}</strong>
                        </div>
                        <div class="paper-process-metric">
                            <small>{{ __('Pages') }}</small>
                            <strong id="paper-process-pages">{{ __('N/A') }}</strong>
                        </div>
                        <div class="paper-process-metric">
                            <small>{{ __('Characters') }}</small>
                            <strong id="paper-process-characters">{{ __('N/A') }}</strong>
                        </div>
                        <div class="paper-process-metric">
                            <small>{{ __('OCR') }}</small>
                            <strong id="paper-process-ocr">{{ __('Pending') }}</strong>
                        </div>
                        <div class="paper-process-metric">
                            <small>{{ __('Questions') }}</small>
                            <strong id="paper-process-question-count">{{ __('N/A') }}</strong>
                        </div>
                    </div>

                    <div class="paper-process-result">
                        <div class="paper-process-result__header">
                            <h4>{{ __('Processed Details') }}</h4>
                            <span id="paper-process-result-state">{{ __('Not ready yet') }}</span>
                        </div>
                        <dl class="paper-process-detail-list">
                            <div>
                                <dt>{{ __('Source Type') }}</dt>
                                <dd id="paper-process-source-type">{{ __('N/A') }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('Processed At') }}</dt>
                                <dd id="paper-process-processed-at">{{ __('N/A') }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('Updated At') }}</dt>
                                <dd id="paper-process-updated-at">{{ __('N/A') }}</dd>
                            </div>
                            <div class="paper-process-detail-list__wide">
                                <dt>{{ __('Excerpt') }}</dt>
                                <dd id="paper-process-excerpt">{{ __('The processed excerpt will appear here once the document is ready.') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="paper-process-log-panel">
                        <h4>{{ __('Latest Processing Logs') }}</h4>
                        <div class="paper-log-list" id="paper-process-logs">
                            <p class="paper-empty">{{ __('Processing logs will appear here.') }}</p>
                        </div>
                    </div>

                    <div class="paper-process-result">
                        <div class="paper-process-result__header">
                            <h4>{{ __('Extracted Questions') }}</h4>
                            <span id="paper-process-questions-state">{{ __('No questions extracted yet') }}</span>
                        </div>
                        <div class="paper-question-list" id="paper-process-questions">
                            <p class="paper-empty">{{ __('Question extraction details will appear here after processing.') }}</p>
                        </div>
                    </div>

                    <div class="paper-process-modal__footer">
                        <button type="button" class="paper-btn paper-btn--light" data-process-close>
                            {{ __('Cancel') }}
                        </button>
                        <button type="button" class="paper-btn paper-btn--outline" id="paper-process-openai" data-process-openai>
                            <i class="fas fa-brain"></i>
                            <span>{{ __('Retry with OpenAI') }}</span>
                        </button>
                        <button type="button" class="paper-btn paper-btn--primary" id="paper-process-apply" disabled>
                            <span>{{ __('Use Details') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </div>

@push('css')
    <style>
        .paper-editor-page {
            background: linear-gradient(180deg, #f7f8fc 0%, #f4f7fb 100%);
            padding-bottom: 32px;
        }

        .paper-editor-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .paper-editor-topbar__left {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .paper-editor-topbar__right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .paper-editor-topbar__actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .paper-queue-notice {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin: 4px 0 18px;
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid rgba(91, 108, 248, 0.16);
            background: linear-gradient(180deg, rgba(247, 248, 255, 0.98) 0%, rgba(255, 255, 255, 1) 100%);
            box-shadow: 0 10px 24px rgba(18, 28, 45, 0.05);
        }

        .paper-queue-notice--redis {
            border-color: rgba(46, 204, 113, 0.18);
            background: linear-gradient(180deg, rgba(242, 250, 245, 0.98) 0%, rgba(255, 255, 255, 1) 100%);
        }

        .paper-queue-notice__icon {
            flex: 0 0 auto;
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: rgba(91, 108, 248, 0.12);
            color: #5b6cf8;
            font-size: 18px;
        }

        .paper-queue-notice--redis .paper-queue-notice__icon {
            background: rgba(46, 204, 113, 0.12);
            color: #1f9d5a;
        }

        .paper-queue-notice__body {
            min-width: 0;
            flex: 1 1 auto;
        }

        .paper-queue-notice__body strong {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }

        .paper-queue-notice__body p {
            margin: 0;
            color: #667085;
            font-size: 13px;
            line-height: 1.5;
        }

        .paper-queue-notice__meta {
            display: grid;
            justify-items: end;
            gap: 6px;
            text-align: right;
            flex: 0 0 auto;
        }

        .paper-queue-notice__meta small {
            color: #667085;
            font-size: 12px;
            line-height: 1.4;
        }

        .paper-queue-toggle {
            display: grid;
            gap: 8px;
            min-width: 220px;
            padding: 10px 12px;
            border: 1px solid rgba(99, 102, 241, 0.18);
            border-radius: 16px;
            background: linear-gradient(180deg, rgba(245, 247, 255, 0.98) 0%, rgba(255, 255, 255, 1) 100%);
            box-shadow: 0 10px 24px rgba(79, 70, 229, 0.08);
        }

        .paper-queue-toggle__label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #6b7280;
        }

        .paper-queue-toggle__options {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .paper-queue-toggle__option {
            border: 1px solid rgba(107, 114, 128, 0.18);
            background: #fff;
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            transition: all 0.18s ease;
            text-align: center;
        }

        .paper-queue-toggle__option.is-active {
            border-color: rgba(79, 70, 229, 0.35);
            background: linear-gradient(180deg, #5b6cf8 0%, #6d28d9 100%);
            color: #fff;
            box-shadow: 0 10px 18px rgba(91, 108, 248, 0.22);
        }

        .paper-queue-toggle__hint {
            display: block;
            font-size: 12px;
            line-height: 1.4;
            color: #667085;
        }

        .paper-editor-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 18px;
            flex-wrap: wrap;
        }

        .paper-editor-actions--bottom {
            position: sticky;
            bottom: 16px;
            z-index: 25;
            margin-top: 24px;
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(16, 24, 40, 0.08);
            box-shadow: 0 14px 30px rgba(18, 28, 45, 0.12);
            backdrop-filter: blur(12px);
        }

        .paper-editor-title {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #151a25;
        }

        .paper-editor-breadcrumb {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-top: 4px;
            color: #738097;
            font-size: 13px;
            flex-wrap: wrap;
        }

        .paper-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(340px, 0.95fr);
            gap: 16px;
            align-items: start;
        }

        .paper-grid__side {
            position: sticky;
            top: 20px;
            display: grid;
            gap: 14px;
        }

        .paper-card {
            background: #fff;
            border: 1px solid rgba(16, 24, 40, 0.08);
            border-radius: 20px;
            box-shadow: 0 8px 24px rgba(18, 28, 45, 0.06);
            padding: 18px;
        }

        .paper-card--soft-green {
            background: linear-gradient(180deg, rgba(244, 250, 246, 1) 0%, rgba(255, 255, 255, 1) 100%);
            border-color: rgba(87, 186, 118, 0.16);
        }

        .paper-card--soft-blue {
            background: linear-gradient(180deg, rgba(245, 248, 255, 1) 0%, rgba(255, 255, 255, 1) 100%);
            border-color: rgba(88, 117, 255, 0.15);
        }

        .paper-card__head {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 16px;
        }

        .paper-card__head--spread {
            justify-content: space-between;
            align-items: center;
        }

        .paper-card__head h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #121826;
        }

        .paper-card__head p {
            margin: 4px 0 0;
            color: #6f7b8f;
            font-size: 13px;
        }

        .paper-step-badge, .paper-icon {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            font-weight: 700;
        }

        .paper-step-badge {
            background: linear-gradient(180deg, #7c52ff 0%, #5a39dc 100%);
            color: #fff;
            box-shadow: 0 10px 18px rgba(90, 57, 220, 0.18);
        }

        .paper-icon--blue { background: rgba(59, 130, 246, 0.11); color: #2563eb; }
        .paper-icon--violet { background: rgba(124, 82, 255, 0.11); color: #6d28d9; }

        .paper-form-grid {
            display: grid;
            gap: 16px;
        }

        .paper-form-grid--2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .paper-field--full {
            grid-column: 1 / -1;
        }

        .paper-field label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #1e2637;
        }

        .paper-field label span {
            color: #ef4444;
        }

        .paper-field input,
        .paper-field select,
        .paper-field textarea {
            width: 100%;
            border: 1px solid #d9e0ea;
            border-radius: 12px;
            background: #fff;
            padding: 12px 14px;
            color: #111827;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .paper-field textarea {
            min-height: 170px;
            resize: vertical;
        }

        .paper-field input:focus,
        .paper-field select:focus,
        .paper-field textarea:focus {
            border-color: #7c52ff;
            box-shadow: 0 0 0 4px rgba(124, 82, 255, 0.12);
            outline: none;
        }

        .paper-confirmation-box {
            border: 1px solid #dbe4f0;
            border-radius: 14px;
            background: linear-gradient(180deg, #fcfdff 0%, #f8fbff 100%);
            padding: 14px 16px;
        }

        .paper-confirmation-box__label {
            display: flex !important;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px !important;
            font-weight: 700 !important;
            color: #0f172a;
        }

        .paper-confirmation-box__label input {
            width: 18px;
            height: 18px;
            margin: 0;
            accent-color: #7c52ff;
            flex: 0 0 auto;
        }

        .paper-confirmation-box__note {
            margin: 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
        }

        .paper-confirmation-box__warning {
            margin: 10px 0 0;
            color: #b45309;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.45;
            padding: 8px 10px;
            border-radius: 10px;
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .paper-file-picker {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
        }

        .paper-file-picker__button {
            height: 44px;
            padding: 0 16px;
            border-radius: 11px;
            border: 1px solid #d9e0ea;
            background: linear-gradient(180deg, #fff 0%, #f7f9fc 100%);
            color: #1f2937;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
        }

        .paper-file-card-mini {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 13px;
            border-radius: 13px;
            background: #f8fafc;
            border: 1px solid #e6ebf2;
        }

        .paper-file-card-mini__icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: rgba(37, 99, 235, 0.1);
            color: #2563eb;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .paper-file-card-mini__icon--red {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .paper-file-card-mini strong,
        .paper-chip-card strong {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #111827;
        }

        .paper-file-card-mini small,
        .paper-chip-card small {
            display: block;
            color: #6b7280;
            font-size: 12px;
            margin-top: 3px;
        }

        .paper-checklist {
            display: grid;
            gap: 8px;
        }

        .paper-checklist__item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #1f2937;
            font-size: 14px;
            padding: 2px 0;
        }

        .paper-checklist__icon {
            width: 22px;
            height: 22px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            color: #9aa3b2;
            border: 1.5px solid #d8dee8;
            background: #fff;
        }

        .paper-checklist__icon.is-done {
            background: #22c55e;
            border-color: #22c55e;
            color: #fff;
        }

        .paper-progress {
            margin-top: 12px;
            height: 8px;
            border-radius: 999px;
            background: #e9edf5;
            overflow: hidden;
        }

        .paper-progress__bar {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #21c55d 0%, #22c55e 100%);
        }

        .paper-chip-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .paper-chip-grid--review {
            grid-template-columns: repeat(auto-fit, minmax(158px, 1fr));
        }

        .paper-chip-grid--compact {
            grid-template-columns: repeat(auto-fit, minmax(124px, 1fr));
        }

        .paper-chip-grid--metadata {
            grid-template-columns: repeat(auto-fit, minmax(132px, 1fr));
        }

        .paper-chip-card {
            background: #f8fafc;
            border: 1px solid #e7edf4;
            border-radius: 12px;
            padding: 10px 12px;
        }

        .paper-chip-card--subtle {
            background: #fcfdff;
        }

        .paper-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 11px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .paper-pill--green { background: rgba(34, 197, 94, 0.12); color: #16a34a; }
        .paper-pill--amber { background: rgba(245, 158, 11, 0.14); color: #b45309; }
        .paper-pill--blue { background: rgba(59, 130, 246, 0.14); color: #1d4ed8; }
        .paper-pill--dark { background: rgba(15, 23, 42, 0.08); color: #0f172a; }
        .paper-pill--red { background: rgba(239, 68, 68, 0.12); color: #b91c1c; }
        .paper-pill--muted { background: #edf2f7; color: #475569; }
        .paper-ai-retry-badge,
        .paper-process-retry-badge {
            margin-left: 4px;
            white-space: nowrap;
            font-size: 11px;
        }

        .paper-ai-top {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 12px;
            align-items: start;
            margin-bottom: 12px;
        }

        .paper-failure-callout {
            display: none;
            gap: 12px;
            align-items: flex-start;
            margin: 0 0 16px;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid rgba(239, 68, 68, 0.18);
            background: linear-gradient(180deg, rgba(255, 245, 245, 1) 0%, rgba(255, 255, 255, 1) 100%);
        }

        .paper-failure-callout.is-visible {
            display: flex;
        }

        .paper-ai-recovery {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 14px;
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid rgba(37, 99, 235, 0.14);
            background: linear-gradient(180deg, rgba(245, 249, 255, 0.98) 0%, rgba(255, 255, 255, 1) 100%);
        }

        .paper-ai-recovery__body {
            min-width: 0;
        }

        .paper-ai-recovery__body strong {
            display: block;
            margin-bottom: 4px;
            color: #111827;
            font-size: 14px;
            font-weight: 700;
        }

        .paper-ai-recovery__body p {
            margin: 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
        }

        .paper-ai-recovery__btn {
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .paper-ai-saved-badge-wrap {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
            text-align: right;
        }

        .paper-ai-saved-badge {
            font-size: 11px;
            padding: 6px 10px;
        }

        .paper-ai-saved-note {
            display: block;
            font-size: 11px;
            color: #64748b;
            line-height: 1.2;
        }

        .paper-failure-callout__icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: rgba(239, 68, 68, 0.12);
            color: #dc2626;
            flex: 0 0 auto;
        }

        .paper-failure-callout__body {
            min-width: 0;
        }

        .paper-failure-callout__body strong {
            display: block;
            margin-bottom: 4px;
            color: #991b1b;
            font-size: 14px;
            font-weight: 700;
        }

        .paper-failure-callout__body p {
            margin: 0;
            color: #7f1d1d;
            font-size: 13px;
            line-height: 1.5;
            word-break: break-word;
        }

        .paper-ai-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }

        .paper-ai-summary__item {
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 9px 10px;
        }

        .paper-ai-summary__item small {
            display: block;
            color: #6b7280;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .paper-ai-summary__item strong {
            display: block;
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.25;
        }

        .paper-ai-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 10px;
        }

        .paper-ai-workspace {
            margin-top: 14px;
        }

        .paper-ai-tabs {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .paper-ai-tab,
        .paper-ai-export {
            border: 1px solid #dbe4ef;
            border-radius: 999px;
            background: #fff;
            color: #475569;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 800;
            line-height: 1;
            cursor: pointer;
        }

        .paper-ai-tab.is-active {
            border-color: rgba(109, 40, 217, 0.24);
            background: rgba(109, 40, 217, 0.08);
            color: #6d28d9;
        }

        .paper-ai-tab__count {
            min-width: 22px;
            height: 22px;
            border-radius: 999px;
            background: rgba(109, 40, 217, 0.12);
            color: #6d28d9;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 7px;
            font-size: 11px;
            font-weight: 800;
        }

        .paper-ai-panel-stack {
            display: grid;
        }

        .paper-ai-panel-sheet {
            display: grid;
            gap: 12px;
        }

        .paper-ai-panel-sheet[hidden] {
            display: none !important;
        }

        .paper-ai-panel-sheet__head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }

        .paper-ai-panel-sheet__head h3 {
            margin: 0 0 4px;
            font-size: 14px;
            font-weight: 700;
            color: #111827;
        }

        .paper-ai-panel-sheet__head p {
            margin: 0;
            color: #667085;
            font-size: 12px;
        }

        .paper-question-table-wrap {
            overflow: auto;
            border: 1px solid #dbe4ef;
            border-radius: 16px;
            background: #fff;
        }

        .paper-question-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 560px;
        }

        .paper-question-table th,
        .paper-question-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: top;
            text-align: left;
            font-size: 12px;
            color: #334155;
        }

        .paper-question-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .paper-question-table tr:last-child td {
            border-bottom: 0;
        }

        .paper-question-table__empty {
            text-align: center !important;
            color: #64748b !important;
            padding: 18px 14px;
        }

        .paper-ai-panel {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .paper-ai-grid h3 {
            margin: 0 0 8px;
            font-size: 14px;
            font-weight: 700;
            color: #111827;
        }

        .paper-code-block {
            border-radius: 14px;
            border: 1px solid #dbe4ef;
            background: #fff;
            padding: 14px;
            min-height: 168px;
            color: #334155;
            white-space: pre-line;
            line-height: 1.55;
        }

        .paper-log-list {
            display: grid;
            gap: 8px;
        }

        .paper-log-list__item {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 9px 10px;
            border-radius: 12px;
            background: #fff;
            border: 1px solid #dbe4ef;
        }

        .paper-log-list__item strong {
            display: block;
            font-size: 13px;
            color: #111827;
        }

        .paper-log-list__item p {
            margin: 3px 0 0;
            font-size: 12px;
            color: #64748b;
        }

        .paper-log-list__dot {
            width: 10px;
            height: 10px;
            margin-top: 5px;
            border-radius: 999px;
            background: #22c55e;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.15);
            flex: 0 0 auto;
        }

        .paper-question-list {
            display: grid;
            gap: 10px;
        }

        .paper-question-list__item {
            border: 1px solid #dbe4ef;
            border-radius: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
            padding: 12px 14px;
        }

        .paper-question-list__header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 6px;
        }

        .paper-question-list__header strong {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
        }

        .paper-question-list__header span {
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            text-align: right;
        }

        .paper-question-list__item p {
            margin: 0;
            color: #334155;
            font-size: 12px;
            line-height: 1.65;
            white-space: pre-line;
        }

        .paper-ai-footer {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #dbe4ef;
            color: #64748b;
            font-size: 13px;
            flex-wrap: wrap;
        }

        .paper-empty {
            margin: 0;
            color: #6b7280;
            font-size: 13px;
        }

        .paper-btn {
            height: 44px;
            padding: 0 16px;
            border-radius: 12px;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            white-space: nowrap;
        }

        .paper-btn--ghost {
            background: #fff;
            color: #111827;
            border-color: #d9e0ea;
        }

        .paper-btn--light {
            background: #fff;
            color: #111827;
            border-color: #d9e0ea;
        }

        .paper-btn--outline {
            background: #fff;
            color: #2563eb;
            border-color: rgba(37, 99, 235, 0.28);
        }

        .paper-btn--primary {
            background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
            color: #fff;
            box-shadow: 0 14px 28px rgba(109, 40, 217, 0.22);
        }

        .paper-btn--process {
            min-width: 170px;
        }

        .paper-status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 12px;
            min-height: 36px;
            border-radius: 999px;
            border: 1px solid transparent;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .paper-status-pill--amber {
            background: rgba(245, 158, 11, 0.12);
            color: #b45309;
            border-color: rgba(245, 158, 11, 0.18);
        }

        .paper-status-pill--blue {
            background: rgba(37, 99, 235, 0.12);
            color: #1d4ed8;
            border-color: rgba(37, 99, 235, 0.18);
        }

        .paper-status-pill--green {
            background: rgba(34, 197, 94, 0.12);
            color: #15803d;
            border-color: rgba(34, 197, 94, 0.18);
        }

        .paper-status-pill--dark {
            background: rgba(71, 85, 105, 0.1);
            color: #334155;
            border-color: rgba(71, 85, 105, 0.16);
        }

        .paper-status-pill--red {
            background: rgba(239, 68, 68, 0.12);
            color: #b91c1c;
            border-color: rgba(239, 68, 68, 0.18);
        }

        .paper-status-pill--muted {
            background: rgba(148, 163, 184, 0.12);
            color: #475569;
            border-color: rgba(148, 163, 184, 0.18);
        }

        .paper-process-modal {
            position: fixed;
            inset: 0;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            z-index: 120;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .paper-process-modal[hidden] {
            display: none !important;
        }

        .paper-process-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.58);
            backdrop-filter: blur(4px);
        }

        .paper-process-modal__dialog {
            position: relative;
            z-index: 1;
            width: min(820px, calc(100vw - 72px));
            max-height: min(94vh, 980px);
            overflow: auto;
            background: #fff;
            border-radius: 24px;
            border: 1px solid rgba(16, 24, 40, 0.08);
            box-shadow: 0 28px 80px rgba(15, 23, 42, 0.3);
            padding: 22px;
        }

        .paper-process-modal__head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 18px;
        }

        .paper-process-modal__head p {
            margin: 0 0 4px;
            color: #7b8698;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .paper-process-modal__head h3 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: #111827;
        }

        .paper-process-modal__close {
            width: 40px;
            height: 40px;
            border: 1px solid rgba(16, 24, 40, 0.08);
            border-radius: 12px;
            background: #fff;
            color: #475467;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .paper-process-modal__status {
            border: 1px solid rgba(89, 102, 255, 0.12);
            background: linear-gradient(180deg, rgba(245, 247, 255, 1) 0%, rgba(255, 255, 255, 1) 100%);
            border-radius: 18px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .paper-process-status-line {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }

        .paper-process-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(109, 40, 217, 0.18);
            border-top-color: #6d28d9;
            border-radius: 999px;
            animation: paperSpin 0.9s linear infinite;
        }

        .paper-process-review-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 10px;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.08);
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .paper-process-review-indicator i {
            font-size: 12px;
        }

        .paper-process-progress-value {
            margin-left: auto;
            color: #6d28d9;
            font-weight: 800;
            font-size: 13px;
            letter-spacing: 0.04em;
        }

        .paper-process-modal__status p {
            margin: 0 0 12px;
            color: #607089;
        }

        .paper-progress--dialog {
            height: 10px;
        }

        .paper-progress--dialog .paper-progress__bar {
            height: 100%;
            border-radius: 999px;
        }

        .paper-process-metrics {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .paper-process-metric {
            border: 1px solid rgba(16, 24, 40, 0.08);
            border-radius: 16px;
            padding: 12px;
            background: #fff;
        }

        .paper-process-metric small {
            display: block;
            margin-bottom: 6px;
            color: #7a8698;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .paper-process-metric strong {
            color: #111827;
            font-size: 15px;
        }

        .paper-field__label-text {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .paper-field__loading {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
        }

        .paper-field__loading[hidden] {
            display: none;
        }

        .paper-field__spinner {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(109, 40, 217, 0.18);
            border-top-color: #6d28d9;
            border-radius: 999px;
            animation: paperSpin 0.9s linear infinite;
        }

        .paper-process-result,
        .paper-process-log-panel {
            border: 1px solid rgba(16, 24, 40, 0.08);
            border-radius: 18px;
            padding: 16px;
            background: #fff;
            margin-bottom: 16px;
        }

        .paper-process-result__header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            margin-bottom: 12px;
        }

        .paper-process-result__header h4,
        .paper-process-log-panel h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #111827;
        }

        .paper-process-result__header span {
            color: #728097;
            font-size: 13px;
            font-weight: 600;
        }

        .paper-process-detail-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin: 0;
        }

        .paper-process-detail-list dt {
            color: #7a8698;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .paper-process-detail-list dd {
            margin: 0;
            color: #172033;
            line-height: 1.55;
        }

        .paper-process-detail-list__wide {
            grid-column: 1 / -1;
        }

        .paper-process-modal__footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }

        .paper-process-modal__footer .paper-btn {
            min-width: 130px;
        }

        body.paper-process-modal-open {
            overflow: hidden;
        }

        @keyframes paperSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @media (max-width: 1199px) {
            .paper-grid {
                grid-template-columns: 1fr;
            }

            .paper-grid__side {
                position: static;
            }

            .paper-process-metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .paper-ai-top {
                grid-template-columns: 1fr;
            }

            .paper-ai-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .paper-ai-grid,
            .paper-chip-grid--compact,
            .paper-chip-grid--review,
            .paper-chip-grid,
            .paper-form-grid--2 {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 767px) {
            .paper-editor-topbar__left,
            .paper-editor-topbar__right {
                width: 100%;
            }

            .paper-editor-topbar__right,
            .paper-editor-topbar__actions,
            .paper-editor-actions {
                justify-content: flex-start;
                flex-wrap: wrap;
            }

            .paper-editor-actions--bottom {
                bottom: 12px;
                padding: 12px;
            }

            .paper-form-grid--2,
            .paper-ai-grid,
            .paper-chip-grid--compact,
            .paper-chip-grid--review,
            .paper-ai-summary,
            .paper-chip-grid,
            .paper-process-metrics,
            .paper-process-detail-list {
                grid-template-columns: 1fr;
            }

            .paper-process-modal {
                padding: 12px;
            }

            .paper-process-modal__dialog {
                padding: 16px;
            }
        }

        @media (min-width: 1200px) {
            .paper-process-modal__dialog {
                width: min(760px, calc(100vw - 180px));
                max-height: 96vh;
            }
        }
    </style>
@endpush

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const educationLevelSelect = document.getElementById('education_level');
            const classGradeLabel = document.querySelector('label[for="class_grade"]');
            const classGradeSelect = document.getElementById('class_grade');
            const subjectSelect = document.getElementById('subject');
            const examCategorySelect = document.getElementById('exam_category');
            const subjectLabel = document.querySelector('label[for="subject"]');
            const classGradeLabelTextEl = classGradeLabel?.querySelector('[data-label-for="class_grade"]');
            const subjectLabelTextEl = subjectLabel?.querySelector('[data-label-for="subject"]');
            const classGradeLoading = document.querySelector('[data-select-loading="class_grade"]');
            const subjectLoading = document.querySelector('[data-select-loading="subject"]');
            const examCategoryLoading = document.querySelector('[data-select-loading="exam_category"]');
            const schoolOfLevelPattern = /tvet|university|college|certificate|diploma|undergraduate|tertiary|higher education/i;
            const coursesLabelPattern = /tvet|university|college|certificate|diploma|undergraduate|professional|tertiary|higher education/i;
            const educationTree = @json($educationTree ?? []);
            const examCategoriesByLevel = @json($metadataOptions['exam_categories_by_level'] ?? []);
            const subjectOptionsByCompoundKey = @json($metadataOptions['subject_overrides_by_class_grade_and_exam_category'] ?? []);
            const subjectFallbackOptions = @json($sharedSubjectOptions ?? []);
            const schoolOfLabel = @json(__('School of'));
            const classGradeLabelText = @json(__('Class / Grade'));
            const subjectLabelText = @json(__('Subject'));
            const coursesLabelText = @json(__('Courses'));
            const selectPlaceholderText = @json(__('Select'));

            const createOption = (value, label, disabled = false, selected = false) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                option.disabled = disabled;
                option.selected = selected;
                return option;
            };

            const normalizeLookupLabel = (value) => String(value || '')
                .trim()
                .replace(/\s+/g, ' ')
                .toLowerCase();

            const findNodeByLabel = (nodes, label) => {
                const normalizedLabel = normalizeLookupLabel(label);

                for (const node of nodes) {
                    if (normalizeLookupLabel(node.label) === normalizedLabel || normalizeLookupLabel(node.slug) === normalizedLabel) {
                        return node;
                    }

                    if (node.children && node.children.length) {
                        const match = findNodeByLabel(node.children, label);
                        if (match) {
                            return match;
                        }
                    }
                }

                return null;
            };

            const setFieldLoading = (element, indicator, isLoading) => {
                if (element) {
                    element.disabled = Boolean(isLoading);
                }

                if (indicator) {
                    indicator.hidden = !isLoading;
                }
            };

            const updateClassGradeLabel = () => {
                if (!educationLevelSelect || !classGradeLabelTextEl) {
                    return;
                }

                const label = schoolOfLevelPattern.test(String(educationLevelSelect.value || ''))
                    ? schoolOfLabel
                    : classGradeLabelText;

                classGradeLabelTextEl.replaceChildren(document.createTextNode(label));
            };

            const updateSubjectLabel = () => {
                if (!educationLevelSelect || !subjectLabelTextEl) {
                    return;
                }

                const label = coursesLabelPattern.test(String(educationLevelSelect.value || ''))
                    ? coursesLabelText
                    : subjectLabelText;

                subjectLabelTextEl.replaceChildren(document.createTextNode(label));
            };

            const populateClassGradeOptions = (educationLevel, preferredValue = '') => {
                if (!classGradeSelect) {
                    return;
                }

                const levelNode = findNodeByLabel(educationTree, educationLevel);
                const options = levelNode?.children ?? [];
                const previousValue = preferredValue || classGradeSelect.value;

                setFieldLoading(classGradeSelect, classGradeLoading, true);
                classGradeSelect.innerHTML = '';
                classGradeSelect.appendChild(createOption('', selectPlaceholderText, false, !previousValue));

                options.forEach((option) => {
                    const label = option.label || option.name || option.value || '';
                    classGradeSelect.appendChild(createOption(label, label, false, previousValue === label));
                });

                const matchedOption = options.find((option) => {
                    const label = option.label || option.name || option.value || '';
                    return normalizeLookupLabel(label) === normalizeLookupLabel(previousValue);
                });

                if (matchedOption) {
                    classGradeSelect.value = previousValue;
                } else if (previousValue) {
                    classGradeSelect.appendChild(createOption(previousValue, previousValue, false, true));
                    classGradeSelect.value = previousValue;
                } else if (options.length) {
                    classGradeSelect.value = options[0].label || options[0].name || options[0].value || '';
                } else {
                    classGradeSelect.value = '';
                }

                setFieldLoading(classGradeSelect, classGradeLoading, false);
            };

            const populateExamCategories = (educationLevel, preferredValue = '') => {
                if (!examCategorySelect) {
                    return;
                }

                const options = examCategoriesByLevel[educationLevel]
                    ?? examCategoriesByLevel.default
                    ?? [];
                const previousValue = preferredValue || examCategorySelect.value;

                setFieldLoading(examCategorySelect, examCategoryLoading, true);
                examCategorySelect.innerHTML = '';
                examCategorySelect.appendChild(createOption('', selectPlaceholderText, false, !previousValue));

                options.forEach((option) => {
                    examCategorySelect.appendChild(createOption(option, option, false, previousValue === option));
                });

                if (options.includes(previousValue)) {
                    examCategorySelect.value = previousValue;
                } else if (previousValue) {
                    examCategorySelect.appendChild(createOption(previousValue, previousValue, false, true));
                    examCategorySelect.value = previousValue;
                } else if (options.length) {
                    examCategorySelect.value = options[0];
                } else {
                    examCategorySelect.value = '';
                }

                setFieldLoading(examCategorySelect, examCategoryLoading, false);
            };

            const populateSubjects = (classGradeValue, examCategoryValue = '', preferredValue = '') => {
                if (!subjectSelect) {
                    return;
                }

                const compoundKey = `${classGradeValue}|${examCategoryValue}`;
                const levelNode = findNodeByLabel(educationTree, educationLevelSelect?.value || '');
                const classGradeNode = findNodeByLabel(levelNode?.children ?? [], classGradeValue);
                const overrideOptions = subjectOptionsByCompoundKey[compoundKey] ?? null;
                const options = (overrideOptions ?? (classGradeNode?.children ?? []))
                    .map((entry) => typeof entry === 'string' ? { label: entry } : entry);
                const previousValue = preferredValue || subjectSelect.value;
                const normalizedPreviousValue = normalizeLookupLabel(previousValue);

                setFieldLoading(subjectSelect, subjectLoading, true);
                subjectSelect.innerHTML = '';
                subjectSelect.appendChild(createOption('', selectPlaceholderText, false, !previousValue));

                let matched = false;

                options.forEach((option) => {
                    const value = option.label ?? option.name ?? option.value ?? '';
                    const isSelected = normalizeLookupLabel(value) === normalizedPreviousValue;

                    if (isSelected) {
                        matched = true;
                    }

                    subjectSelect.appendChild(createOption(value, value, false, isSelected));
                });

                if (!matched && previousValue) {
                    subjectSelect.appendChild(createOption(previousValue, previousValue, false, true));
                    subjectSelect.value = previousValue;
                } else if (!matched && options.length) {
                    const firstOption = options[0].label ?? options[0].name ?? options[0].value ?? '';
                    subjectSelect.value = firstOption;
                } else {
                    subjectSelect.value = previousValue;
                }

                setFieldLoading(subjectSelect, subjectLoading, false);
            };

            const syncDependentFields = (educationLevel, preferredValues = {}, { immediate = false } = {}) => {
                const runSync = () => {
                    populateExamCategories(educationLevel, preferredValues.examCategory ?? '');
                    populateClassGradeOptions(educationLevel, preferredValues.classGrade ?? '');
                    updateClassGradeLabel();
                    updateSubjectLabel();
                    populateSubjects(
                        classGradeSelect?.value || preferredValues.classGrade || '',
                        examCategorySelect?.value || preferredValues.examCategory || '',
                        preferredValues.subject ?? ''
                    );
                };

                if (immediate) {
                    runSync();
                    return;
                }

                setFieldLoading(classGradeSelect, classGradeLoading, true);
                setFieldLoading(subjectSelect, subjectLoading, true);
                setFieldLoading(examCategorySelect, examCategoryLoading, true);

                window.requestAnimationFrame(runSync);
            };

            syncDependentFields(educationLevelSelect?.value || '', {
                classGrade: classGradeSelect?.value || '',
                examCategory: examCategorySelect?.value || '',
                subject: subjectSelect?.value || '',
            }, { immediate: true });

            educationLevelSelect?.addEventListener('change', function () {
                syncDependentFields(this.value, {
                    classGrade: classGradeSelect?.value || '',
                    examCategory: examCategorySelect?.value || '',
                    subject: subjectSelect?.value || '',
                });
            });

            classGradeSelect?.addEventListener('change', function () {
                syncDependentFields(educationLevelSelect?.value || '', {
                    classGrade: this.value,
                    examCategory: examCategorySelect?.value || '',
                    subject: subjectSelect?.value || '',
                });
            });

            examCategorySelect?.addEventListener('change', function () {
                syncDependentFields(educationLevelSelect?.value || '', {
                    classGrade: classGradeSelect?.value || '',
                    examCategory: this.value,
                    subject: subjectSelect?.value || '',
                });
            });

            const pastPaperForm = document.getElementById('past-paper-edit-form');
            const statusSelect = document.getElementById('status');
            const approvalSelect = document.getElementById('is_approved');
            const extractionConfirmedCheckbox = document.getElementById('extraction_confirmed');
            const saveGuardMessage = document.getElementById('paper-save-guard-message');
            const checklistItems = Array.from(document.querySelectorAll('.paper-checklist__item'));
            const checklistIconIsDoneClass = 'is-done';
            let latestAiDocumentReady = @json($latestAiDocumentReady);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const processButton = document.getElementById('process-document-button');
            const queueModeInput = document.getElementById('paper-queue-mode');
            const queueModeHint = document.getElementById('paper-queue-mode-hint');
            const queueModeButtons = Array.from(document.querySelectorAll('[data-queue-mode-option]'));
            const processModal = document.getElementById('paper-process-modal');
            const processModalTitle = document.getElementById('paper-process-modal-title');
            const processSpinner = document.getElementById('paper-process-spinner');
            const processReviewIndicator = document.getElementById('paper-process-review-indicator');
            const processState = document.getElementById('paper-process-state');
            const processMessage = document.getElementById('paper-process-message');
            const processProgress = document.getElementById('paper-process-progress');
            const processProgressValue = document.getElementById('paper-process-progress-value');
            const processStatus = document.getElementById('paper-process-status');
            const processResultState = document.getElementById('paper-process-result-state');
            const processPages = document.getElementById('paper-process-pages');
            const processCharacters = document.getElementById('paper-process-characters');
            const processOcr = document.getElementById('paper-process-ocr');
            const processQuestionCount = document.getElementById('paper-process-question-count');
            const processSourceType = document.getElementById('paper-process-source-type');
            const processProcessedAt = document.getElementById('paper-process-processed-at');
            const processUpdatedAt = document.getElementById('paper-process-updated-at');
            const processExcerpt = document.getElementById('paper-process-excerpt');
            const processLogs = document.getElementById('paper-process-logs');
            const processQuestions = document.getElementById('paper-process-questions');
            const processQuestionsState = document.getElementById('paper-process-questions-state');
            const processApplyButton = document.getElementById('paper-process-apply');
            const processOpenAiButtons = Array.from(document.querySelectorAll('[data-process-openai]'));
            const processStatusPill = document.getElementById('ai-processing-status-pill');
            const aiOpenAiRetryBadge = document.getElementById('ai-processing-openai-badge');
            const processSourceTypePage = document.getElementById('ai-processing-source-type');
            const processPageCountPage = document.getElementById('ai-processing-page-count');
            const processCharacterCountPage = document.getElementById('ai-processing-character-count');
            const processOcrPage = document.getElementById('ai-processing-ocr');
            const processQuestionCountPage = document.getElementById('ai-processing-question-count');
            const processMethodPage = document.getElementById('ai-processing-method');
            const processExcerptPage = document.getElementById('ai-processing-excerpt');
            const processLogsPage = document.getElementById('ai-processing-logs');
            const processLastProcessedPage = document.getElementById('ai-processing-last-processed');
            const processUpdatedPage = document.getElementById('ai-processing-updated');
            const processFailurePage = document.getElementById('ai-processing-failure');
            const processOpenAiRetryBadge = document.getElementById('paper-process-openai-badge');
            const aiTabButtons = Array.from(document.querySelectorAll('[data-ai-tab]'));
            const aiPanels = Array.from(document.querySelectorAll('[data-ai-panel]'));
            const aiQuestionCountBadge = document.getElementById('ai-processing-question-count-badge');
            const aiSavedBadge = document.getElementById('ai-processing-saved-badge');
            const aiSavedNote = document.getElementById('ai-processing-saved-note');
            const aiQuestionTableBody = document.getElementById('ai-processing-question-table-body');
            const aiExportButtons = Array.from(document.querySelectorAll('[data-ai-export-json]'));
            const initialAiDocument = @json($latestAiDocumentPayload ?? null);
            const processStatusMap = {
                pending: {
                    label: @json(__('Queued')),
                    className: 'paper-pill--amber',
                    progress: 20,
                    state: @json(__('Queued for processing')),
                    message: @json(__('The document has been queued and is waiting for the extraction job to begin.')),
                    ocr: @json(__('Pending')),
                },
                processing: {
                    label: @json(__('Processing')),
                    className: 'paper-pill--blue',
                    progress: 58,
                    state: @json(__('Processing document')),
                    message: @json(__('The system is extracting text, building chunks, and checking for OCR fallback.')),
                    ocr: @json(__('Running')),
                },
                processed: {
                    label: @json(__('Processed')),
                    className: 'paper-pill--green',
                    progress: 100,
                    state: @json(__('Processing complete')),
                    message: @json(__('The document is ready to review and the extracted details can now be applied to the edit page.')),
                    ocr: @json(__('Completed')),
                },
                requires_ocr: {
                    label: @json(__('Needs OCR')),
                    className: 'paper-pill--dark',
                    progress: 100,
                    state: @json(__('OCR review complete')),
                    message: @json(__('The file could not be fully extracted locally. An admin can retry it with OpenAI-assisted OCR review, then you can apply the result and continue saving.')),
                    ocr: @json(__('Required')),
                },
                failed: {
                    label: @json(__('Failed')),
                    className: 'paper-pill--red',
                    progress: 100,
                    state: @json(__('Processing failed')),
                    message: @json(__('The document could not be processed. Review the failure reason and try again.')),
                    ocr: @json(__('Unavailable')),
                },
            };
            const terminalStatuses = ['processed', 'requires_ocr', 'failed'];
            let currentAiDocument = initialAiDocument;
            let lastProcessedToastKey = '';
            let pollingTimer = null;
            let modalOpen = false;
            let processContextUrl = processButton?.dataset?.reprocessUrl || '';
            let processStatusUrl = processButton?.dataset?.statusUrl || '';
            const defaultReviewMode = @json(strtolower((string) ($resource?->file_type ?? '')) === 'pdf' ? 'openai' : '');
            const fallbackText = @json(__('N/A'));
            const queueModeLabels = @json($queueModeLabelMap);
            const queueModeDescriptions = @json($queueModeDescriptionMap);

            if (processModal && processModal.parentElement !== document.body) {
                document.body.appendChild(processModal);
            }

            const formatDateTime = (value) => {
                if (!value) {
                    return fallbackText;
                }

                const date = new Date(value);
                if (Number.isNaN(date.getTime())) {
                    return String(value);
                }

                return new Intl.DateTimeFormat(undefined, {
                    dateStyle: 'medium',
                    timeStyle: 'short',
                }).format(date);
            };

            const setText = (element, value) => {
                if (element) {
                    element.textContent = value ?? fallbackText;
                }
            };

            const dispatchFieldEvent = (element, eventName) => {
                if (!element) {
                    return;
                }

                element.dispatchEvent(new Event(eventName, { bubbles: true }));
            };

            const setFieldValue = (element, value, { createMissingOption = false, triggerChange = false } = {}) => {
                if (!element || value === null || value === undefined || value === '') {
                    return false;
                }

                const nextValue = String(value);

                if (element.tagName === 'SELECT' && createMissingOption && !Array.from(element.options).some((option) => option.value === nextValue)) {
                    const option = document.createElement('option');
                    option.value = nextValue;
                    option.textContent = nextValue;
                    element.appendChild(option);
                }

                element.value = nextValue;
                dispatchFieldEvent(element, 'input');

                if (triggerChange) {
                    dispatchFieldEvent(element, 'change');
                }

                return true;
            };

            const fillAiEditableFields = (documentData) => {
                if (!documentData) {
                    return;
                }

                const metadata = documentData.metadata || {};
                const titleValue = metadata.title || documentData.source_name;
                const subjectValue = metadata.subject || metadata.course;
                const descriptionValue = metadata.description || documentData.extracted_text_excerpt;
                const fileTypeValue = String(documentData.file_extension || metadata.file_type || '').toLowerCase();
                const fileTypeMap = {
                    pdf: 'pdf',
                    doc: 'doc',
                    docx: 'docx',
                };

                setFieldValue(document.getElementById('title'), titleValue);
                setFieldValue(document.getElementById('education_level'), metadata.education_level, { triggerChange: true });
                setFieldValue(document.getElementById('class_grade'), metadata.class_grade, { createMissingOption: true });
                setFieldValue(document.getElementById('subject'), subjectValue, { createMissingOption: true });
                setFieldValue(document.getElementById('exam_category'), metadata.exam_category, { createMissingOption: true });
                setFieldValue(document.getElementById('year'), metadata.year, { createMissingOption: true });
                setFieldValue(document.getElementById('language'), metadata.language, { createMissingOption: true });
                setFieldValue(document.getElementById('access_type'), metadata.access_type, { createMissingOption: true });
                setFieldValue(document.getElementById('preview_pages'), metadata.preview_pages, { createMissingOption: true });

                if (fileTypeMap[fileTypeValue]) {
                    setFieldValue(document.getElementById('file_type'), fileTypeMap[fileTypeValue], { createMissingOption: true });
                }

                if (descriptionValue && !document.getElementById('description')?.value.trim()) {
                    setFieldValue(document.getElementById('description'), descriptionValue);
                }
            };

            const clearNode = (node) => {
                if (node) {
                    node.innerHTML = '';
                }
            };

            const buildLogList = (node, logs) => {
                if (!node) {
                    return;
                }

                node.innerHTML = '';

                if (!Array.isArray(logs) || !logs.length) {
                    const empty = document.createElement('p');
                    empty.className = 'paper-empty';
                    empty.textContent = @json(__('Processing logs will appear here.'));
                    node.appendChild(empty);
                    return;
                }

                logs.slice(-5).forEach((log) => {
                    const item = document.createElement('div');
                    item.className = 'paper-log-list__item';

                    const dot = document.createElement('span');
                    dot.className = 'paper-log-list__dot';

                    const body = document.createElement('div');
                    const strong = document.createElement('strong');
                    strong.textContent = String(log.stage || '').replace(/_/g, ' ');
                    const paragraph = document.createElement('p');
                    paragraph.textContent = log.message || '';

                    body.appendChild(strong);
                    body.appendChild(paragraph);
                    item.appendChild(dot);
                    item.appendChild(body);
                    node.appendChild(item);
                });
            };

            const buildQuestionList = (node, questions) => {
                if (!node) {
                    return;
                }

                node.innerHTML = '';

                if (!Array.isArray(questions) || !questions.length) {
                    const empty = document.createElement('p');
                    empty.className = 'paper-empty';
                    empty.textContent = @json(__('Question extraction details will appear here after processing.'));
                    node.appendChild(empty);
                    return;
                }

                questions.slice(0, 12).forEach((question) => {
                    const item = document.createElement('div');
                    item.className = 'paper-question-list__item';

                    const header = document.createElement('div');
                    header.className = 'paper-question-list__header';

                    const title = document.createElement('strong');
                    title.textContent = question.question_label || @json(__('Question'));

                    const meta = document.createElement('span');
                    meta.textContent = [
                        question.section_label,
                        question.marks_label || (question.marks ? `${question.marks} marks` : null),
                    ].filter(Boolean).join(' • ');

                    const body = document.createElement('p');
                    body.textContent = question.content || '';

                    header.appendChild(title);
                    header.appendChild(meta);
                    item.appendChild(header);
                    item.appendChild(body);
                    node.appendChild(item);
                });
            };

            const buildQuestionTable = (node, questions) => {
                if (!node) {
                    return;
                }

                node.innerHTML = '';

                if (!Array.isArray(questions) || !questions.length) {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');
                    cell.colSpan = 4;
                    cell.className = 'paper-question-table__empty';
                    cell.textContent = @json(__('No extracted questions available yet.'));
                    row.appendChild(cell);
                    node.appendChild(row);
                    return;
                }

                questions.forEach((question) => {
                    const row = document.createElement('tr');

                    const questionCell = document.createElement('td');
                    questionCell.textContent = question.question_number || question.question_label || @json(__('Question'));

                    const sectionCell = document.createElement('td');
                    sectionCell.textContent = question.section_label || @json(__('N/A'));

                    const marksCell = document.createElement('td');
                    marksCell.textContent = question.marks_label || (question.marks ? `${question.marks} marks` : @json(__('N/A')));

                    const contentCell = document.createElement('td');
                    contentCell.textContent = question.raw_text || question.content || '';

                    row.appendChild(questionCell);
                    row.appendChild(sectionCell);
                    row.appendChild(marksCell);
                    row.appendChild(contentCell);
                    node.appendChild(row);
                });
            };

            const setAiPanel = (panelName = 'snapshot') => {
                aiTabButtons.forEach((button) => {
                    const isActive = button.dataset.aiTab === panelName;
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });

                aiPanels.forEach((panel) => {
                    panel.hidden = panel.dataset.aiPanel !== panelName;
                    panel.classList.toggle('is-active', panel.dataset.aiPanel === panelName);
                });
            };

            const downloadAiQuestionsJson = () => {
                const questions = Array.isArray(currentAiDocument?.questions) ? currentAiDocument.questions : [];

                if (!questions.length) {
                    showToast('warning', @json(__('No extracted questions are available to export yet.')));
                    return;
                }

                const payload = {
                    document_id: currentAiDocument?.id || null,
                    source_name: currentAiDocument?.source_name || null,
                    status: currentAiDocument?.status || null,
                    question_count: questions.length,
                    questions,
                };

                const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `ai-questions-${currentAiDocument?.id || 'document'}.json`;
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.setTimeout(() => URL.revokeObjectURL(url), 1000);
            };

            const renderQuestionWorkspace = (documentData) => {
                const questions = Array.isArray(documentData?.questions) ? documentData.questions : [];
                const questionCount = questions.length;
                const savedCount = Number(documentData?.question_count || questionCount || 0);

                setText(processQuestionCountPage, questionCount ? Number(questionCount).toLocaleString() : fallbackText);
                setText(processQuestionCount, questionCount ? Number(questionCount).toLocaleString() : fallbackText);
                setText(aiQuestionCountBadge, questionCount ? Number(questionCount).toLocaleString() : '0');
                setText(aiSavedBadge, savedCount ? `${Number(savedCount).toLocaleString()} questions saved` : @json(__('No questions saved yet')));
                setText(aiSavedNote, savedCount ? @json(__('Questions synced to database')) : @json(__('Waiting for saved questions')));
                setText(processMethodPage, getProcessingMethodLabel(documentData));
                buildQuestionTable(aiQuestionTableBody, questions);

                aiExportButtons.forEach((button) => {
                    button.disabled = questionCount === 0;
                });
            };

            const shouldShowOpenAiRetryBadge = (documentData) => {
                if (!documentData) {
                    return false;
                }

                if (documentData.openai_retry_ready !== undefined) {
                    return Boolean(documentData.openai_retry_ready);
                }

                const status = String(documentData.status || '');
                if (['failed', 'requires_ocr'].includes(status)) {
                    return true;
                }

                if (status !== 'processed') {
                    return false;
                }

                const metadata = documentData.metadata || {};
                const extractionMethod = String(documentData.extraction_method || metadata.extraction_method || '').toLowerCase().trim();
                const questionMethod = String(documentData.question_extraction_method || metadata.question_extraction_method || '').toLowerCase().trim();
                const requiresTranscription = Boolean(metadata.requires_transcription);
                const lowQualityMethods = ['msdoc', 'legacy-ole-low-confidence', 'legacy-ole', 'word2007', 'word2007-xml', 'prinsfrank', 'tesseract', 'ocr'];

                return requiresTranscription
                    || lowQualityMethods.includes(extractionMethod)
                    || ['heuristic', 'heuristic-fallback'].includes(questionMethod);
            };

            const setOpenAiRetryBadge = (documentData) => {
                const visible = shouldShowOpenAiRetryBadge(documentData);
                [aiOpenAiRetryBadge, processOpenAiRetryBadge].forEach((badge) => {
                    if (!badge) {
                        return;
                    }

                    badge.hidden = !visible;
                });
            };

            const getProcessingMethodLabel = (documentData) => {
                const extractionMethod = String(documentData?.extraction_method || documentData?.metadata?.extraction_method || '').trim();
                const questionMethod = String(documentData?.question_extraction_method || documentData?.metadata?.question_extraction_method || '').trim();

                const labels = [];
                const methodMap = {
                    'Word2007': @json(__('DOCX text extraction')),
                    'Word2007-XML': @json(__('DOCX XML text extraction')),
                    'MsDoc': @json(__('legacy DOC extraction')),
                    'legacy-ole': @json(__('legacy DOC extraction')),
                    'legacy-ole-low-confidence': @json(__('legacy DOC extraction with low-confidence fallback')),
                    'legacy-doc-render-text': @json(__('legacy DOC rendering')),
                    'legacy-doc-render-images': @json(__('legacy DOC image rendering')),
                    'prinsfrank': @json(__('PDF text extraction')),
                    'tesseract': @json(__('OCR fallback')),
                    'ocr': @json(__('OCR fallback')),
                    'openai_vision': @json(__('OpenAI vision OCR')),
                    'openai_vision_image': @json(__('OpenAI vision image OCR')),
                    'openai_direct_review': @json(__('OpenAI direct review')),
                    'heuristic': @json(__('question parsing heuristics')),
                    'openai': @json(__('OpenAI question extraction')),
                };

                if (extractionMethod) {
                    labels.push(methodMap[extractionMethod] || extractionMethod.replace(/_/g, ' '));
                }

                if (questionMethod && questionMethod !== extractionMethod) {
                    labels.push(methodMap[questionMethod] || questionMethod.replace(/_/g, ' '));
                }

                return labels.length ? labels.join(' + ') : @json(__('local processing'));
            };

            const maybeToastProcessedDocument = (documentData) => {
                if (!documentData || String(documentData.status || '') !== 'processed') {
                    return;
                }

                const toastKey = [
                    documentData.id || '',
                    documentData.status || '',
                    documentData.updated_at || '',
                    documentData.extraction_method || documentData.metadata?.extraction_method || '',
                    documentData.question_extraction_method || documentData.metadata?.question_extraction_method || '',
                ].join('|');

                if (lastProcessedToastKey === toastKey) {
                    return;
                }

                lastProcessedToastKey = toastKey;

                const methodLabel = getProcessingMethodLabel(documentData);
                showToast('success', @json(__('Document processed successfully using')) + ` ${methodLabel}.`);
            };

            const getStatusMeta = (status) => processStatusMap[status] || {
                label: status ? String(status).replace(/_/g, ' ') : fallbackText,
                className: 'paper-pill--muted',
                progress: 45,
                state: @json(__('Processing document')),
                message: @json(__('Waiting for the extraction result.')),
                ocr: fallbackText,
            };

            const setProcessingLoading = (isLoading, mode = 'loading') => {
                const isReviewing = mode === 'reviewing';

                if (processSpinner) {
                    processSpinner.hidden = !isLoading || isReviewing;
                }

                if (processReviewIndicator) {
                    processReviewIndicator.hidden = !isLoading || !isReviewing;
                }

                if (processState) {
                    processState.classList.toggle('is-loading', isLoading && !isReviewing);
                }
            };

            const getDocumentFailureMessage = (documentData) => {
                const failureReason = String(documentData?.failure_reason || '').trim();

                if (failureReason) {
                    return failureReason;
                }

                const failedLog = Array.isArray(documentData?.processing_logs)
                    ? documentData.processing_logs.slice().reverse().find((log) => String(log?.stage || '').toLowerCase() === 'failed')
                    : null;

                return String(failedLog?.message || '').trim() || @json(__('The document could not be processed.'));
            };

            const showToast = (type, message) => {
                const text = String(message || '').trim();

                if (!text || !window.toastr) {
                    return;
                }

                if (type === 'success') {
                    toastr.success(text);
                } else if (type === 'warning') {
                    toastr.warning(text);
                } else {
                    toastr.error(text);
                }
            };

            const setFailureNotice = (message, visible = false) => {
                if (!processFailurePage) {
                    return;
                }

                const text = String(message || '').trim() || @json(__('The document could not be processed.'));
                processFailurePage.hidden = !visible;
                processFailurePage.classList.toggle('is-visible', visible);
                const body = processFailurePage.querySelector('.paper-failure-callout__body p');
                if (body) {
                    body.textContent = text;
                }
            };

            const getDocumentProgress = (documentData) => {
                const numericProgress = Number(documentData?.progress);

                if (Number.isFinite(numericProgress)) {
                    return Math.max(0, Math.min(100, Math.round(numericProgress)));
                }

                return getStatusMeta(documentData?.status).progress;
            };

            const getPollingDelay = (documentData) => {
                const activeStatuses = ['pending', 'processing'];

                if (activeStatuses.includes(String(documentData?.status || ''))) {
                    return 1000;
                }

                return 2000;
            };

            const setProgressValue = (value) => {
                const progressValue = Math.max(0, Math.min(100, Math.round(Number(value) || 0)));

                if (processProgress) {
                    processProgress.style.width = `${progressValue}%`;
                }

                if (processProgressValue) {
                    processProgressValue.textContent = `${progressValue}%`;
                }
            };

            const getQueueMode = () => {
                const mode = String(queueModeInput?.value || 'local');
                return ['local', 'redis'].includes(mode) ? mode : 'local';
            };

            const setQueueMode = (mode) => {
                const nextMode = ['local', 'redis'].includes(String(mode)) ? String(mode) : 'local';

                if (queueModeInput) {
                    queueModeInput.value = nextMode;
                }

                queueModeButtons.forEach((button) => {
                    button.classList.toggle('is-active', button.dataset.queueModeOption === nextMode);
                });

                if (queueModeHint) {
                    queueModeHint.textContent = queueModeDescriptions[nextMode] || queueModeDescriptions.local;
                }

                return nextMode;
            };

            queueModeButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    setQueueMode(button.dataset.queueModeOption);
                });
            });

            setQueueMode(getQueueMode());
            aiTabButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    setAiPanel(button.dataset.aiTab || 'snapshot');
                });
            });
            aiExportButtons.forEach((button) => {
                button.addEventListener('click', downloadAiQuestionsJson);
            });
            renderQuestionWorkspace(currentAiDocument);
            setOpenAiRetryBadge(currentAiDocument);
            setAiPanel('snapshot');

            const renderModalDocument = (documentData) => {
                if (!documentData) {
                    return;
                }

                currentAiDocument = documentData;
                setOpenAiRetryBadge(documentData);
                const meta = getStatusMeta(documentData.status);
                const failureMessage = getDocumentFailureMessage(documentData);
                const isTerminal = terminalStatuses.includes(documentData.status);
                if (processModalTitle) {
                    processModalTitle.textContent = documentData.source_name || @json(__('Process Document'));
                }

                setProcessingLoading(!isTerminal);
                setText(processStatus, meta.label);
                setText(processState, meta.state);
                setText(processMessage, documentData.status === 'failed' || documentData.status === 'requires_ocr' ? failureMessage : meta.message);
                setText(processResultState, meta.label);
                setText(processPages, documentData.page_count ? Number(documentData.page_count).toLocaleString() : fallbackText);
                setText(processCharacters, documentData.character_count ? Number(documentData.character_count).toLocaleString() : fallbackText);
                setText(processOcr, documentData.status === 'failed' ? @json(__('Unavailable')) : meta.ocr);
                setText(processQuestionCount, documentData.question_count ? Number(documentData.question_count).toLocaleString() : fallbackText);
                setText(processSourceType, documentData.source_type || fallbackText);
                setText(processProcessedAt, formatDateTime(documentData.processed_at));
                setText(processUpdatedAt, formatDateTime(documentData.updated_at));

                setProgressValue(getDocumentProgress(documentData));

                buildLogList(processLogs, documentData.processing_logs || []);
                buildQuestionList(processQuestions, documentData.questions || []);
                renderQuestionWorkspace(documentData);
                setText(processQuestionsState, documentData.question_count ? @json(__('Questions extracted')) : @json(__('No questions extracted yet')));

                const canApply = terminalStatuses.includes(documentData.status) && ['processed', 'requires_ocr'].includes(documentData.status);
                if (processApplyButton) {
                    processApplyButton.disabled = !canApply;
                }

                processOpenAiButtons.forEach((button) => {
                    button.disabled = !terminalStatuses.includes(documentData.status);
                });

                if (documentData.status === 'failed') {
                    setFailureNotice(failureMessage, true);
                    showToast('error', failureMessage);
                } else if (documentData.status === 'requires_ocr') {
                    setFailureNotice(failureMessage, true);
                    showToast('warning', failureMessage);
                } else {
                    setFailureNotice('', false);
                    maybeToastProcessedDocument(documentData);
                }
            };

            const applyDocumentToPage = (documentData) => {
                if (!documentData) {
                    return;
                }

                const meta = getStatusMeta(documentData.status);
                const failureMessage = getDocumentFailureMessage(documentData);

                latestAiDocumentReady = ['processed', 'requires_ocr'].includes(documentData.status);
                fillAiEditableFields(documentData);

                if (processStatusPill) {
                    processStatusPill.className = `paper-pill ${meta.className}`;
                    processStatusPill.textContent = meta.label;
                }

                setText(processSourceTypePage, documentData.source_type || fallbackText);
                setText(processPageCountPage, documentData.page_count ? Number(documentData.page_count).toLocaleString() : fallbackText);
                setText(processCharacterCountPage, documentData.character_count ? Number(documentData.character_count).toLocaleString() : fallbackText);
                setText(processOcrPage, documentData.status === 'failed' ? @json(__('Unavailable')) : meta.ocr);
                setText(processQuestionCountPage, documentData.question_count ? Number(documentData.question_count).toLocaleString() : fallbackText);
                setText(processMethodPage, getProcessingMethodLabel(documentData));
                setText(processExcerptPage, documentData.extracted_text_excerpt || @json(__('No extracted excerpt available yet.')));
                setText(processLastProcessedPage, formatDateTime(documentData.processed_at));
                setText(processUpdatedPage, formatDateTime(documentData.updated_at));
                setProgressValue(getDocumentProgress(documentData));

                buildLogList(processLogsPage, documentData.processing_logs || []);
                buildQuestionList(processQuestions, documentData.questions || []);
                setOpenAiRetryBadge(documentData);
                renderQuestionWorkspace(documentData);
                setText(processQuestionsState, documentData.question_count ? @json(__('Questions extracted')) : @json(__('No questions extracted yet')));
                refreshChecklist();

                if (documentData.status === 'failed') {
                    setFailureNotice(failureMessage, true);
                    showToast('error', failureMessage);
                } else if (documentData.status === 'requires_ocr') {
                    setFailureNotice(failureMessage, true);
                    showToast('warning', failureMessage);
                } else {
                    setFailureNotice('', false);
                }
            };

            const openProcessModal = () => {
                if (!processModal) {
                    return;
                }

                modalOpen = true;
                processModal.hidden = false;
                document.body.classList.add('paper-process-modal-open');
            };

            const closeProcessModal = () => {
                if (!processModal) {
                    return;
                }

                modalOpen = false;
                processModal.hidden = true;
                document.body.classList.remove('paper-process-modal-open');
                if (pollingTimer) {
                    clearTimeout(pollingTimer);
                    pollingTimer = null;
                }
            };

            const fetchAiDocument = async () => {
                if (!processStatusUrl) {
                    return null;
                }

                const response = await fetch(processStatusUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const payload = await response.json();
                return payload?.document || null;
            };

            const pollAiDocument = async () => {
                if (!modalOpen) {
                    return;
                }

                try {
                    const documentData = await fetchAiDocument();
                    if (!documentData) {
                        pollingTimer = window.setTimeout(pollAiDocument, 1000);
                        return;
                    }

                    currentAiDocument = documentData;
                    renderModalDocument(documentData);

                    if (!terminalStatuses.includes(documentData.status)) {
                        pollingTimer = window.setTimeout(pollAiDocument, getPollingDelay(documentData));
                    }
                } catch (error) {
                    setText(processState, @json(__('Unable to refresh document status')));
                    setText(processMessage, error?.message || @json(__('The document status could not be loaded.')));
                    setProgressValue(100);
                    if (processApplyButton) {
                        processApplyButton.disabled = true;
                    }
                }
            };

            const queueProcessing = async (reviewMode = '') => {
                if (!processContextUrl) {
                    return;
                }

                const queueMode = getQueueMode();
                openProcessModal();
                const usingOpenAi = String(reviewMode || '') === 'openai';
                setProcessingLoading(true, usingOpenAi ? 'reviewing' : 'loading');
                setText(processState, usingOpenAi
                    ? @json(__('Reviewing with OpenAI'))
                    : (queueMode === 'redis' ? @json(__('Queueing via Redis')) : @json(__('Processing locally'))));
                setText(processMessage, usingOpenAi
                    ? @json(__('Please wait while OpenAI-assisted review is being prepared for the document.'))
                    : (queueMode === 'redis'
                        ? @json(__('Please wait while the document is queued on Redis for Horizon to pick up.'))
                        : @json(__('Please wait while the document is being processed immediately.'))));
                setProgressValue(0);
                if (processApplyButton) {
                    processApplyButton.disabled = true;
                }
                processOpenAiButtons.forEach((button) => {
                    button.disabled = true;
                });

                try {
                    const requestBody = new FormData();
                    requestBody.append('queue_mode', queueMode);
                    if (usingOpenAi) {
                        requestBody.append('review_mode', 'openai');
                    }

                    const response = await fetch(processContextUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: requestBody,
                    });

                    const payload = await response.json();

                    if (!response.ok || payload.status !== 'success') {
                        throw new Error(payload.message || @json(__('Unable to queue document processing.')));
                    }

                    if (payload.document) {
                        currentAiDocument = payload.document;
                        renderModalDocument(payload.document);
                        maybeToastProcessedDocument(payload.document);
                    }

                    if (queueMode === 'redis' && !usingOpenAi && !terminalStatuses.includes(payload?.document?.status)) {
                        pollingTimer = window.setTimeout(pollAiDocument, 1000);
                    }

                    if (!payload.document || !['failed', 'requires_ocr'].includes(String(payload.document.status || ''))) {
                        showToast('success', payload.message || @json(__('Document processing started.')));
                    }
                } catch (error) {
                    setText(processState, @json(__('Queue failed')));
                    setText(processMessage, error?.message || @json(__('The document could not be queued for processing.')));
                    setProgressValue(100);
                    setProcessingLoading(false);
                    showToast('error', error?.message || @json(__('The document could not be queued for processing.')));
                    if (processApplyButton) {
                        processApplyButton.disabled = true;
                    }
                    processOpenAiButtons.forEach((button) => {
                        button.disabled = false;
                    });
                }
            };

            processButton?.addEventListener('click', function () {
                queueProcessing(defaultReviewMode);
            });
            processOpenAiButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    queueProcessing('openai');
                });
            });
            if (processModal) {
                processModal.querySelectorAll('[data-process-close]').forEach((el) => {
                    el.addEventListener('click', closeProcessModal);
                });
            }
            processModal?.addEventListener('click', function (event) {
                if (event.target === processModal) {
                    closeProcessModal();
                }
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && modalOpen) {
                    closeProcessModal();
                }
            });
            processApplyButton?.addEventListener('click', function () {
                if (!currentAiDocument) {
                    return;
                }

                applyDocumentToPage(currentAiDocument);
                closeProcessModal();
            });

            const refreshChecklist = () => {
                if (!checklistItems.length) {
                    return;
                }

                const titleFilled = Boolean((document.getElementById('title')?.value || '').trim());
                const levelFilled = Boolean(educationLevelSelect?.value);
                const gradeFilled = Boolean(classGradeSelect?.value);
                const subjectFilled = Boolean(subjectSelect?.value);
                const examFilled = Boolean(document.getElementById('exam_category')?.value);
                const yearFilled = Boolean(document.getElementById('year')?.value);
                const languageFilled = Boolean(document.getElementById('language')?.value);
                const accessFilled = Boolean(document.getElementById('access_type')?.value);
                const previewFilled = Boolean(document.getElementById('preview_pages')?.value);
                const fileFilled = Boolean(document.getElementById('file_path')?.value);
                const thumbnailFilled = Boolean(document.getElementById('thumbnail')?.value);
                const extractionConfirmed = Boolean(extractionConfirmedCheckbox?.checked);
                const descriptionFilled = Boolean((document.getElementById('description')?.value || '').trim().length > 20);
                const metadataComplete = titleFilled && levelFilled && gradeFilled && subjectFilled && examFilled && yearFilled && languageFilled && accessFilled && previewFilled;
                const aiReady = Boolean(latestAiDocumentReady);

                const states = [
                    fileFilled,
                    thumbnailFilled,
                    metadataComplete,
                    aiReady,
                    extractionConfirmed,
                    descriptionFilled,
                    fileFilled && thumbnailFilled && metadataComplete && aiReady && extractionConfirmed && descriptionFilled,
                ];

                checklistItems.forEach((item, index) => {
                    const icon = item.querySelector('.paper-checklist__icon');
                    const done = states[index] ?? false;
                    item.classList.toggle('is-done', done);
                    icon?.classList.toggle(checklistIconIsDoneClass, done);
                    if (icon) {
                        icon.innerHTML = done ? '<i class="fas fa-check"></i>' : '<i class="fas fa-circle"></i>';
                    }
                });
            };

            [educationLevelSelect, classGradeSelect, subjectSelect, statusSelect, approvalSelect,
                document.getElementById('title'),
                document.getElementById('exam_category'),
                document.getElementById('year'),
                document.getElementById('language'),
                document.getElementById('access_type'),
                document.getElementById('preview_pages'),
                document.getElementById('file_path'),
                document.getElementById('thumbnail'),
                document.getElementById('description'),
            ].forEach((el) => el?.addEventListener('input', refreshChecklist));

            educationLevelSelect?.addEventListener('change', refreshChecklist);
            classGradeSelect?.addEventListener('change', refreshChecklist);
            subjectSelect?.addEventListener('change', refreshChecklist);
            statusSelect?.addEventListener('change', refreshChecklist);
            approvalSelect?.addEventListener('change', refreshChecklist);
            extractionConfirmedCheckbox?.addEventListener('change', refreshChecklist);

            refreshChecklist();
        });
    </script>
@endpush
@endsection

