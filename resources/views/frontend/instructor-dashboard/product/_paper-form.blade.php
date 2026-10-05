@php
    $productRecord = $product ?? null;
    $isEdit = (bool) $productRecord;
    $resourceType = old('type', $selectedType ?? ($productRecord?->type ?? 'past_paper'));
    $resourceTypeLabel = match ($resourceType) {
        'prediction' => __('Prediction Pack'),
        default => __('Past Paper'),
    };
    $resourceTypePlural = match ($resourceType) {
        'prediction' => __('Prediction Packs'),
        default => __('Past Papers'),
    };
    $sampleTitle = $resourceType === 'prediction'
        ? __('Mathematics Prediction Pack :year', ['year' => now()->year])
        : __('Mathematics Past Paper :year', ['year' => now()->year]);
    $sampleDescription = $resourceType === 'prediction'
        ? __('Mathematics prediction pack for :year with likely topics, focus areas, and exam hints.', ['year' => now()->year])
        : __('Mathematics paper - :year official past paper.', ['year' => now()->year]);
    $titleHint = $resourceType === 'prediction'
        ? __('Example: Mathematics Prediction Pack :year', ['year' => now()->year])
        : __('Example: Mathematics Past Paper :year', ['year' => now()->year]);
    $descriptionHint = $resourceType === 'prediction'
        ? __('Summarise likely topics, exam hints, and revision guidance for the pack.')
        : __('Describe the paper clearly, including the year, paper number, and exam level.');
    $paper = $paperForm ?? [];
    $rootCategories = collect($paperEducationCategories ?? $educationCategories ?? $categories ?? [])
        ->values();
    $educationLevelRawValue = old(
        'education_level',
        $paper['education_level'] ?? ($rootCategories->first()?->translation?->name ?? $rootCategories->first()?->name ?? '')
    );
    $classGradeRawValue = old('class_grade', $paper['class_grade'] ?? '');
    $educationLevelValue = match ($educationLevelRawValue) {
        'Primary', 'primary' => preg_match('/\bgrade\s*[4-6]\b/i', (string) $classGradeRawValue)
            ? 'Upper Primary'
            : 'Lower Primary',
        'lower-primary' => 'Lower Primary',
        'upper-primary' => 'Upper Primary',
        default => $educationLevelRawValue,
    };
    $classGradeValue = match ($classGradeRawValue) {
        'Nursing' => 'Health',
        'Microsoft', 'Azure' => 'Microsoft Azure',
        default => $classGradeRawValue,
    };
    $examCategoryValue = old('exam_category', $paper['exam_category'] ?? '');
    if ($educationLevelValue === 'Certificate Courses') {
        $examCategoryValue = match ($examCategoryValue) {
            'Year 1' => 'Semister 1',
            'Year 2' => 'Semister 2',
            'Year 3' => 'Semister 3',
            default => $examCategoryValue,
        };
    }
    $subjectValue = old('subject', old('course', $paper['subject'] ?? ($paper['course'] ?? '')));
    $yearValue = old('year', $paper['year'] ?? '');
    $languageValue = old('language', $paper['language'] ?? '');
    $previewPagesValue = old('preview_pages', $paper['preview_pages'] ?? '');
    $accessTypeValue = old('access_type', $paper['access_type'] ?? '');
    $priceValue = old('price', $productRecord?->price ?? '');
    $discountValue = old('discount', $productRecord?->discount ?? '');
    $action = $isEdit ? route('instructor.products.update', $productRecord->id) : route('instructor.products.store');
    $backUrl = route('instructor.products.index', ['type' => $resourceType]);
    $statusValue = old('status', $productRecord?->status ?? 'is_draft');
    $resourceLabel = $productRecord?->file_path ? basename($productRecord->file_path) : __('No file attached yet');
    $resourceSize = $productRecord?->file_path ? __('Attached file') : __('PDF and Word files are allowed. Max file size: 50MB');
    $hasAttachedResource = (bool) $productRecord?->file_path;
    $paperExamCategoriesByLevel = $metadataOptions['exam_categories_by_level'];
    $paperSubjectOverridesByClassGradeAndExamCategory = $metadataOptions['subject_overrides_by_class_grade_and_exam_category'];
    $paperClassGrades = $metadataOptions['class_grades'];
    $paperYears = $metadataOptions['years'];
    $paperPreviewPages = $metadataOptions['preview_pages'];
    $paperLanguages = $metadataOptions['languages'];
    $resolveClassGradeLabel = static function (?string $educationLevel): string {
        $level = strtolower(trim((string) $educationLevel));

        return preg_match('/tvet|university|college|certificate|diploma|undergraduate|tertiary|higher education/', $level)
            ? __('School of')
            : __('Class / Grade');
    };
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
    $classGradeLabel = $resolveClassGradeLabel($educationLevelValue);
    $subjectLabel = $resolveSubjectLabel($educationLevelValue);
    $subjectFieldName = $resolveSubjectFieldName($educationLevelValue);
@endphp

<div class="paper-editor-page">
    <div class="paper-editor-page__breadcrumb">
        <nav aria-label="{{ __('Breadcrumb') }}" class="paper-breadcrumb">
            <a href="{{ route('instructor.dashboard') }}">{{ __('Dashboard') }}</a>
            <span>/</span>
            <a href="{{ $backUrl }}">{{ $resourceTypePlural }}</a>
            <span>/</span>
            <span>{{ $isEdit ? __('Edit') : __('Create New') }} {{ $resourceTypeLabel }}</span>
        </nav>

        <div class="paper-editor-page__actions">
            <a href="{{ $backUrl }}" class="paper-action-button paper-action-button--ghost">
                <i class="fas fa-chevron-left"></i>
                <span>{{ __('Back') }}</span>
            </a>
        </div>
    </div>

    <div class="paper-editor-page__hero">
        <div>
            <h1>{{ $isEdit ? __('Edit :type', ['type' => $resourceTypeLabel]) : __('Create :type', ['type' => $resourceTypeLabel]) }}</h1>
            <p>{{ __('Add a new :type for students to purchase and access.', ['type' => strtolower($resourceTypeLabel)]) }}</p>
        </div>
    </div>

    <div class="paper-stepper" role="tablist" aria-label="{{ __('Product creation steps') }}">
        <div class="paper-stepper__item is-active" data-step="1" role="tab" tabindex="0" aria-selected="true" aria-label="{{ __('Go to Basic Information') }}">
            <span class="paper-stepper__index">1</span>
            <div>
                <strong>{{ __('Basic Information') }}</strong>
                <span>{{ __('Title, description, and level') }}</span>
            </div>
        </div>
        <div class="paper-stepper__item" data-step="2" role="tab" tabindex="-1" aria-selected="false" aria-label="{{ __('Go to File Upload') }}">
            <span class="paper-stepper__index">2</span>
            <div>
                <strong>{{ __('File Upload') }}</strong>
                <span>{{ __('Attach your PDF or Word file') }}</span>
            </div>
        </div>
        <div class="paper-stepper__item" data-step="3" role="tab" tabindex="-1" aria-selected="false" aria-label="{{ __('Go to Pricing & Access') }}">
            <span class="paper-stepper__index">3</span>
            <div>
                <strong>{{ __('Pricing & Access') }}</strong>
                <span>{{ __('Set price and preview rules') }}</span>
            </div>
        </div>
        <div class="paper-stepper__item" data-step="4" role="tab" tabindex="-1" aria-selected="false" aria-label="{{ __('Go to Review') }}">
            <span class="paper-stepper__index">4</span>
            <div>
                <strong>{{ __('Review') }}</strong>
                <span>{{ __('Add details and send for review') }}</span>
            </div>
        </div>
    </div>

    <form id="paper-form" action="{{ $action }}" method="POST" enctype="multipart/form-data" class="paper-editor-grid">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <input type="hidden" name="type" value="{{ $resourceType }}">
        <input type="hidden" name="status" id="paper-status" value="{{ $statusValue }}">

        <section class="paper-card paper-card--left paper-card--basic" data-step-section="1" id="step-basic-information">
            <h2>1. {{ __('Basic Information') }}</h2>

            <div class="paper-field">
                <label for="title">{{ __('Title') }} <span>*</span></label>
                <input id="title" name="title" type="text" required value="{{ old('title', $productRecord?->title ?? '') }}" placeholder="{{ $sampleTitle }}">
                @if (! $isEdit)
                    <small class="paper-help">{{ $titleHint }}</small>
                @endif
                @error('title')
                    <small class="paper-error">{{ $message }}</small>
                @enderror
            </div>

            <div class="paper-field">
                <label for="description">{{ __('Short Description') }} <span>*</span></label>
                <textarea id="description" name="description" rows="4" required placeholder="{{ $sampleDescription }}">{{ old('description', $productRecord?->description ?? '') }}</textarea>
                @if (! $isEdit)
                    <small class="paper-help">{{ $descriptionHint }}</small>
                @endif
                @error('description')
                    <small class="paper-error">{{ $message }}</small>
                @enderror
            </div>

            <div class="paper-field-grid paper-field-grid--3">
                <div class="paper-field">
                    <label for="education_level">{{ __('Education Level') }} <span>*</span></label>
                    <select id="education_level" name="education_level" required>
                        <option value="" @selected($educationLevelValue === '')>{{ __('None') }}</option>
                        @foreach ($rootCategories as $category)
                            @php
                                $categoryLabel = $category->translation?->name ?? $category->name ?? $category->slug;
                            @endphp
                            <option value="{{ $categoryLabel }}" @selected($educationLevelValue === $categoryLabel)>{{ $categoryLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="paper-field">
                    <label for="class_grade">{{ $classGradeLabel }} <span>*</span></label>
                    <select id="class_grade" name="class_grade" required>
                        <option value="" @selected($classGradeValue === '')>{{ __('None') }}</option>
                        @foreach ($paperClassGrades as $option)
                            <option value="{{ $option }}" @selected($classGradeValue === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="paper-field">
                    <label for="subject">{{ $subjectLabel }}</label>
                    <select id="subject" name="{{ $subjectFieldName }}">
                        <option value="" @selected($subjectValue === '')>{{ __('None') }}</option>
                    </select>
                </div>
            </div>

            <div class="paper-field-grid paper-field-grid--2">
                <div class="paper-field">
                    <label for="exam_category">{{ __('Exam Category') }}</label>
                    <select id="exam_category" name="exam_category">
                        <option value="" @selected($examCategoryValue === '')>{{ __('None') }}</option>
                        @foreach ($paperExamCategoriesByLevel['default'] as $option)
                            <option value="{{ $option }}" @selected($examCategoryValue === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="paper-field">
                    <label for="year">{{ __('Year') }}</label>
                    <select id="year" name="year">
                        <option value="" @selected($yearValue === '')>{{ __('None') }}</option>
                        @foreach ($paperYears as $year)
                            <option value="{{ $year }}" @selected((string) $yearValue === (string) $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

        </section>

        <section class="paper-card paper-card--left paper-card--upload" data-step-section="2" id="step-file-upload">
            <h2>2. {{ __('File Upload') }}</h2>

            <label class="paper-dropzone" for="resource_file">
                <input id="resource_file" name="resource_file" type="file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                <div class="paper-dropzone__icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <strong>{{ __('Drag & drop your file here') }}</strong>
                <span>{{ __('or') }}</span>
                <span class="paper-dropzone__button" data-default-label="{{ __('Choose File') }}">
                    {{ $hasAttachedResource ? $resourceLabel : __('Choose File') }}
                </span>
            </label>
            <small class="paper-help">{{ __('PDF and Word files are allowed. Max file size: 50MB') }}</small>
            <small class="paper-error paper-upload-feedback" data-upload-feedback hidden></small>
            @error('resource_file')
                <small class="paper-error">{{ $message }}</small>
            @enderror

            <div class="paper-file-card {{ $hasAttachedResource ? '' : 'is-hidden' }}" aria-hidden="{{ $hasAttachedResource ? 'false' : 'true' }}">
                <div class="paper-file-card__icon">
                    <i class="fas fa-file-pdf" data-file-icon></i>
                </div>
                <div class="paper-file-card__body">
                    <strong>{{ $resourceLabel }}</strong>
                    <span>{{ $resourceSize }}</span>
                    <div class="paper-file-card__progress-wrap">
                        <div class="paper-file-card__progress-meta">
                            <span class="paper-file-card__progress-label">{{ __('Attachment') }}</span>
                            <span class="paper-file-card__progress-value" data-progress-value>{{ $hasAttachedResource ? '100%' : '0%' }}</span>
                        </div>
                        <div class="paper-file-card__progress">
                            <div class="paper-file-card__progress-bar" data-progress-bar style="width: {{ $hasAttachedResource ? '100%' : '0%' }}"></div>
                        </div>
                    </div>
                </div>
                <div class="paper-file-card__status">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </section>

        <section class="paper-card paper-card--right paper-card--pricing" data-step-section="3" id="step-pricing-access">
            <h2>3. {{ __('Pricing & Access') }}</h2>

            <div class="paper-field">
                <label for="type_label">{{ __('Product Type') }}</label>
                <input id="type_label" type="text" value="{{ $resourceTypeLabel }}" disabled>
            </div>

            <div class="paper-field">
                <label for="price">{{ __('Price (KES)') }} <span>*</span></label>
                <input id="price" name="price" type="number" min="0" step="0.01" required value="{{ $priceValue }}" placeholder="{{ __('e.g. 120.00') }}">
                @if (! $isEdit)
                    <small class="paper-help">{{ __('Use this field as a guide price. You can adjust it before publishing.') }}</small>
                @endif
                @error('price')
                    <small class="paper-error">{{ $message }}</small>
                @enderror
            </div>

            <div class="paper-field">
                <label for="discount">{{ __('Discount Price (KES)') }}</label>
                <input id="discount" name="discount" type="number" min="0" step="0.01" value="{{ $discountValue }}" placeholder="{{ __('Leave blank if none') }}">
                <small class="paper-help">{{ __('Leave blank if you are not offering a discount.') }}</small>
            </div>

            <div class="paper-field">
                <label>{{ __('Access Type') }}</label>
                <div class="paper-radios">
                    <label><input type="radio" name="access_type" value="paid" @checked($accessTypeValue === 'paid')> {{ __('Paid (Premium)') }}</label>
                    <label><input type="radio" name="access_type" value="free" @checked($accessTypeValue === 'free')> {{ __('Free') }}</label>
                </div>
                @if (! $isEdit)
                    <small class="paper-help">{{ __('Choose the access level students should see on the product page.') }}</small>
                @endif
            </div>

            <div class="paper-field">
                <label for="preview_pages">{{ __('Preview Pages') }}</label>
                <select id="preview_pages" name="preview_pages">
                    <option value="" disabled @selected($previewPagesValue === '')>{{ __('Select preview option') }}</option>
                    @foreach ($paperPreviewPages as $option)
                        <option value="{{ $option }}" @selected($previewPagesValue === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <small class="paper-help">{{ __('Students can preview selected pages before purchase, including a single page.') }}</small>
            </div>
        </section>

        <section class="paper-card paper-card--right paper-card--info" data-step-section="4" id="step-publish">
            <h2>4. {{ __('Additional Information and Review') }}</h2>

            <div class="paper-field">
                <label for="language">{{ __('Language') }}</label>
                <select id="language" name="language">
                    <option value="" disabled @selected($languageValue === '')>{{ __('Select language') }}</option>
                    @foreach ($paperLanguages as $option)
                        <option value="{{ $option }}" @selected($languageValue === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>

            @if ($resourceType !== 'past_paper')
                <div class="paper-field">
                    <label for="tags">{{ __('Tags') }}</label>
                    <input id="tags" name="tags" type="text" value="{{ old('tags', $paper['tags'] ?? '') }}" placeholder="{{ __('Mathematics, prediction pack, revision') }}">
                    @if (! $isEdit)
                        <small class="paper-help">{{ __('Use comma-separated tags to help students find this product faster.') }}</small>
                    @endif
                    <div class="paper-tags">
                        @foreach (collect(explode(',', old('tags', $paper['tags'] ?? '')))->map(fn ($tag) => trim($tag))->filter() as $tag)
                            <span>{{ $tag }} <i class="fas fa-xmark"></i></span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="paper-field">
                <label>{{ __('Status') }}</label>
                <div class="paper-radios paper-radios--stacked">
                    <label><input type="radio" name="status_choice" value="is_draft" @checked($statusValue === 'is_draft')> {{ __('Draft') }}</label>
                    <label><input type="radio" name="status_choice" value="active" @checked($statusValue === 'active')> {{ __('Submit for Review') }}</label>
                </div>
            </div>

        </section>
    </form>

    <div class="paper-editor-page__action-bar" aria-label="{{ __('Review actions') }}">
        <button type="button" class="paper-action-button paper-action-button--indigo" data-paper-submit="draft" form="paper-form">
            <i class="far fa-file"></i>
            <span>{{ __('Save Draft') }}</span>
        </button>

        <button type="button" class="paper-action-button paper-action-button--green" data-paper-submit="publish" form="paper-form">
            <i class="fas fa-check"></i>
            <span>{{ $isEdit ? __('Update & Resubmit') : __('Send for Review') }}</span>
            <i class="fas fa-chevron-down paper-action-button__caret"></i>
        </button>
    </div>
</div>

@push('styles')
    <style>
        .paper-editor-page {
            padding-top: 2px;
            padding-bottom: 96px;
        }

        .paper-editor-page__breadcrumb {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .paper-breadcrumb {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #667085;
            font-size: 13px;
        }

        .paper-breadcrumb a {
            color: #5b57d6;
            font-weight: 600;
        }

        .paper-editor-page__actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .paper-action-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 9px;
            border: 1px solid transparent;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
        }

        .paper-action-button--ghost {
            background: #fff;
            border-color: #d7ddea;
            color: #24304f;
        }

        .paper-action-button--indigo {
            background: linear-gradient(135deg, #5b57d6, #6d4fff);
            color: #fff;
        }

        .paper-action-button--green {
            background: linear-gradient(135deg, #27ae60, #1ebd73);
            color: #fff;
        }

        .paper-action-button__caret {
            margin-left: 4px;
        }

        .paper-editor-page__hero {
            margin-bottom: 16px;
        }

        .paper-editor-page__hero h1 {
            margin: 0 0 6px;
            color: #1f2a44;
            font-size: 26px;
            line-height: 1.12;
            font-weight: 700;
        }

        .paper-editor-page__hero p {
            margin: 0;
            color: #667085;
            font-size: 13px;
        }

        .paper-stepper {
            display: flex;
            align-items: stretch;
            gap: 0;
            margin-bottom: 18px;
            padding: 8px;
            border: 1px solid rgba(91, 87, 214, 0.14);
            border-radius: 22px;
            background: linear-gradient(180deg, rgba(248, 249, 255, 0.96), rgba(255, 255, 255, 0.94));
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.06);
            z-index: 12;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .paper-stepper::-webkit-scrollbar {
            display: none;
        }

        .paper-stepper__item {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            padding: 14px 16px 13px;
            border: 1px solid transparent;
            border-radius: 18px;
            color: #64748b;
            cursor: pointer;
            transition: color 0.18s ease, border-color 0.18s ease, background 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
            flex: 1 1 0;
        }

        .paper-stepper__item:hover {
            color: #24304f;
            background: rgba(91, 87, 214, 0.06);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
            transform: translateY(-1px);
        }

        .paper-stepper__item.is-active {
            border-color: rgba(91, 87, 214, 0.18);
            color: #24304f;
            background: linear-gradient(135deg, rgba(91, 87, 214, 0.18), rgba(109, 79, 255, 0.08));
            box-shadow: 0 14px 30px rgba(91, 87, 214, 0.16);
            transform: translateY(-1px);
        }

        .paper-stepper__index {
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            flex: 0 0 auto;
            background: #eef2ff;
            color: #5b57d6;
            font-size: 13px;
            font-weight: 800;
        }

        .paper-stepper__item.is-active .paper-stepper__index {
            background: linear-gradient(135deg, #5b57d6, #6d4fff);
            color: #fff;
        }

        .paper-stepper__item strong,
        .paper-stepper__item span {
            display: block;
        }

        .paper-stepper__item strong {
            font-size: 13px;
            font-weight: 800;
            color: inherit;
        }

        .paper-stepper__item span {
            margin-top: 4px;
            font-size: 12px;
            line-height: 1.35;
            color: #94a3b8;
        }

        .paper-stepper__item.is-active span {
            color: #5b57d6;
        }

        .paper-editor-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 12px;
            align-items: start;
        }

        .paper-card {
            padding: 16px 16px 14px;
            border: 1px solid #e4e8f2;
            border-radius: 11px;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 10px 24px rgba(20, 33, 61, 0.035);
        }

        .paper-card--left {
            grid-column: auto;
        }

        .paper-card--right {
            grid-column: auto;
        }

        .paper-card--info {
            display: flex;
            flex-direction: column;
            min-height: 0;
            padding-bottom: 18px;
        }

        .paper-card--basic {
            min-height: 440px;
        }

        .paper-card--upload {
            min-height: 260px;
        }

        .paper-card h2 {
            margin: 0 0 16px;
            color: #5b57d6;
            font-size: 15px;
            line-height: 1.2;
            font-weight: 700;
        }

        .paper-field {
            display: grid;
            gap: 7px;
            margin-bottom: 14px;
        }

        .paper-field label {
            color: #1f2a44;
            font-size: 13px;
            font-weight: 700;
        }

        .paper-field label span {
            color: #ef4444;
        }

        .paper-field input,
        .paper-field textarea,
        .paper-field select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d8deea;
            border-radius: 8px;
            background: #fff;
            color: #1f2a44;
            font-size: 13px;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .paper-field input:focus,
        .paper-field textarea:focus,
        .paper-field select:focus {
            border-color: #5b57d6;
            box-shadow: 0 0 0 4px rgba(91, 87, 214, 0.08);
            outline: 0;
        }

        .paper-field input[disabled] {
            background: #f6f7fb;
            color: #8a94ad;
        }

        .paper-field textarea {
            resize: vertical;
            min-height: 112px;
        }

        .paper-field-grid {
            display: grid;
            gap: 12px;
            margin-bottom: 2px;
        }

        .paper-field-grid--3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .paper-field-grid--2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .paper-help,
        .paper-error {
            display: block;
            margin-top: 2px;
            font-size: 11px;
        }

        .paper-help {
            color: #667085;
        }

        .paper-error {
            color: #ef4444;
        }

        .paper-upload-feedback[hidden] {
            display: none;
        }

        .paper-dropzone {
            display: grid;
            place-items: center;
            gap: 6px;
            min-height: 126px;
            padding: 18px;
            border: 2px dashed #d8deea;
            border-radius: 13px;
            background: linear-gradient(180deg, #fcfdff, #f8faff);
            text-align: center;
            cursor: pointer;
        }

        .paper-dropzone.is-dragover {
            border-color: #5b57d6;
            background: linear-gradient(180deg, #f6f7ff, #eef1ff);
            box-shadow: 0 0 0 4px rgba(91, 87, 214, 0.08);
        }

        .paper-dropzone input {
            display: none;
        }

        .paper-dropzone__icon {
            display: grid;
            place-items: center;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(91, 87, 214, 0.12);
            color: #5b57d6;
            font-size: 21px;
        }

        .paper-dropzone__button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            padding: 0 14px;
            border-radius: 8px;
            background: linear-gradient(135deg, #5b57d6, #6d4fff);
            color: #fff;
            font-weight: 700;
            font-size: 13px;
        }

        .paper-file-card {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            margin-top: 12px;
            padding: 11px 12px;
            border: 1px solid #dfe5f1;
            border-radius: 9px;
            background: #fff;
        }

        .paper-file-card.is-hidden {
            display: none;
        }

        .paper-file-card__icon {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            font-size: 18px;
        }

        .paper-file-card__icon.is-word {
            background: rgba(24, 90, 189, 0.12);
            color: #185abd;
        }

        .paper-file-card__body strong,
        .paper-file-card__body span {
            display: block;
        }

        .paper-file-card__body strong {
            color: #1f2a44;
            font-size: 13px;
        }

        .paper-file-card__body span {
            color: #667085;
            font-size: 11px;
        }

        .paper-file-card__progress-wrap {
            margin-top: 8px;
        }

        .paper-file-card__progress-meta {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
            margin-bottom: 6px;
            font-size: 11px;
            color: #667085;
        }

        .paper-file-card__progress-label {
            font-weight: 700;
            color: #5b57d6;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .paper-file-card__progress {
            width: 100%;
            height: 8px;
            border-radius: 999px;
            background: #edf2f7;
            overflow: hidden;
        }

        .paper-file-card__progress-bar {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #5b57d6, #8b5cf6);
            transition: width 0.2s ease;
        }

        .paper-file-card__status {
            color: #27ae60;
            font-size: 16px;
        }

        .paper-radios {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .paper-radios label {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #1f2a44;
            font-size: 13px;
            font-weight: 600;
        }

        .paper-radios input[type="radio"] {
            width: 16px;
            height: 16px;
            accent-color: #5b57d6;
        }

        .paper-radios--stacked {
            flex-direction: column;
            gap: 14px;
        }

        .paper-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .paper-tags span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 9px;
            border-radius: 999px;
            background: rgba(91, 87, 214, 0.08);
            color: #5b57d6;
            font-size: 11px;
            font-weight: 700;
        }

        .paper-editor-page__action-bar {
            position: fixed;
            right: 24px;
            bottom: 20px;
            z-index: 60;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border: 1px solid rgba(216, 222, 234, 0.95);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 18px 36px rgba(20, 33, 61, 0.12);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }

        .paper-editor-page__action-bar .paper-action-button {
            min-width: 0;
        }

        @media (max-width: 991.98px) {
            .paper-stepper {
                padding: 6px;
                border-radius: 18px;
            }

            .paper-stepper__item {
                flex: 0 0 auto;
                min-width: 220px;
            }

            .paper-editor-grid {
                grid-template-columns: 1fr;
            }

            .paper-card--left,
            .paper-card--right,
            .paper-editor-grid .paper-card--right.paper-card--pricing,
            .paper-editor-grid .paper-card--right.paper-card--info,
            .paper-editor-grid .paper-card--left.paper-card--upload {
                grid-column: auto;
                grid-row: auto;
            }

            .paper-field-grid--3 {
                grid-template-columns: 1fr;
            }

            .paper-editor-page__breadcrumb {
                flex-direction: column;
                align-items: flex-start;
            }

            .paper-editor-page__action-bar {
                left: 16px;
                right: 16px;
                bottom: 16px;
                justify-content: stretch;
            }

            .paper-editor-page__action-bar .paper-action-button {
                flex: 1 1 0;
                justify-content: center;
            }
        }

        @media (max-width: 575.98px) {
            .paper-editor-page {
                padding-bottom: 112px;
            }

            .paper-editor-page__action-bar {
                left: 12px;
                right: 12px;
                bottom: 12px;
                padding: 10px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const educationTree = @json($educationTree ?? []);
            const stepperItems = Array.from(document.querySelectorAll('.paper-stepper__item[data-step]'));
            const stepSections = Array.from(document.querySelectorAll('[data-step-section]'));
            const statusInput = document.getElementById('paper-status');
            const statusChoiceInputs = document.querySelectorAll('input[name="status_choice"]');
            const submitButtons = document.querySelectorAll('[data-paper-submit]');
            const fileInput = document.getElementById('resource_file');
            const fileCard = document.querySelector('.paper-file-card');
            const fileCardIconWrap = document.querySelector('.paper-file-card__icon');
            const fileCardIcon = document.querySelector('[data-file-icon]');
            const fileCardName = document.querySelector('.paper-file-card__body strong');
            const fileCardMeta = document.querySelector('.paper-file-card__body span');
            const fileDropzoneButton = document.querySelector('.paper-dropzone__button');
            const fileDropzone = document.querySelector('.paper-dropzone');
            const uploadFeedback = document.querySelector('[data-upload-feedback]');
            const progressBar = document.querySelector('[data-progress-bar]');
            const progressValue = document.querySelector('[data-progress-value]');
            const educationLevelSelect = document.getElementById('education_level');
            const classGradeSelect = document.getElementById('class_grade');
            const examCategorySelect = document.getElementById('exam_category');
            const subjectSelect = document.getElementById('subject');
            const classGradeLabelEl = document.querySelector('label[for="class_grade"]');
            const subjectLabelEl = document.querySelector('label[for="subject"]');
            let activeStep = 1;
            let stepSpyRaf = null;
            const examCategoryOptionsByLevel = @json($paperExamCategoriesByLevel);
            const subjectOptionsByClassGradeAndExamCategory = @json($paperSubjectOverridesByClassGradeAndExamCategory);
            const schoolOfLevelPattern = /tvet|university|college|certificate|diploma|undergraduate|tertiary|higher education/i;
            const coursesLabelPattern = /tvet|university|college|certificate|diploma|undergraduate|professional|tertiary|higher education/i;
            const getClassGradeLabel = (educationLevel) => schoolOfLevelPattern.test(String(educationLevel || ''))
                ? @json(__('School of'))
                : @json(__('Class / Grade'));
            const getSubjectLabel = (educationLevel) => coursesLabelPattern.test(String(educationLevel || ''))
                ? @json(__('Courses'))
                : @json(__('Subject'));
            const getSubjectFieldName = (educationLevel) => coursesLabelPattern.test(String(educationLevel || ''))
                ? 'course'
                : 'subject';
            const syncSubjectFieldName = (educationLevel) => {
                if (subjectSelect) {
                    subjectSelect.name = getSubjectFieldName(educationLevel);
                }
            };
            const normalizeExamCategory = (levelLabel, value) => {
                if (levelLabel === 'Certificate Courses') {
                    if (value === 'Year 1') {
                        return 'Semister 1';
                    }

                    if (value === 'Year 2') {
                        return 'Semister 2';
                    }

                    if (value === 'Year 3') {
                        return 'Semister 3';
                    }
                }

                return value;
            };

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

            const fileLooksLikeAttachment = (file) => {
                if (!file) {
                    return false;
                }

                const name = (file.name || '').toLowerCase();
                const type = (file.type || '').toLowerCase();

                return name.endsWith('.pdf')
                    || name.endsWith('.doc')
                    || name.endsWith('.docx')
                    || type === 'application/pdf'
                    || type === 'application/x-pdf'
                    || type === 'application/msword'
                    || type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            };

            const setDropzoneDragging = (isDragging) => {
                if (!fileDropzone) {
                    return;
                }

                fileDropzone.classList.toggle('is-dragover', Boolean(isDragging));
            };

            const setAttachmentProgress = (percent) => {
                const safePercent = Math.max(0, Math.min(100, Number(percent) || 0));

                if (progressBar) {
                    progressBar.style.width = `${safePercent}%`;
                }

                if (progressValue) {
                    progressValue.textContent = `${safePercent}%`;
                }
            };

            const showUploadFeedback = (message) => {
                if (uploadFeedback) {
                    uploadFeedback.textContent = message;
                    uploadFeedback.hidden = false;
                }

                if (window.revisionHubToast) {
                    window.revisionHubToast('error', message, { fallback: message });
                } else if (window.toastr && typeof toastr.error === 'function') {
                    toastr.error(message);
                }
            };

            const clearUploadFeedback = () => {
                if (uploadFeedback) {
                    uploadFeedback.textContent = '';
                    uploadFeedback.hidden = true;
                }
            };

            const setActiveStep = (stepNumber) => {
                const safeStep = Number(stepNumber) || 1;

                activeStep = safeStep;

                stepperItems.forEach((item) => {
                    const isActive = Number(item.dataset.step || 0) === safeStep;
                    item.classList.toggle('is-active', isActive);
                    item.setAttribute('aria-current', isActive ? 'step' : 'false');
                    item.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    item.setAttribute('tabindex', isActive ? '0' : '-1');
                });
            };

            const updateActiveStepFromScroll = () => {
                stepSpyRaf = null;

                if (!stepSections.length) {
                    return;
                }

                const offset = 180;
                let nextStep = 1;

                for (const section of stepSections) {
                    const rect = section.getBoundingClientRect();
                    const sectionStep = Number(section.dataset.stepSection || 1);

                    if (rect.top - offset <= 0) {
                        nextStep = sectionStep;
                    }
                }

                setActiveStep(nextStep);
            };

            const scheduleStepUpdate = () => {
                if (stepSpyRaf !== null) {
                    return;
                }

                stepSpyRaf = window.requestAnimationFrame(updateActiveStepFromScroll);
            };

            const scrollToStep = (stepNumber) => {
                const target = document.querySelector(`[data-step-section="${stepNumber}"]`);

                if (!target) {
                    return;
                }

                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start',
                });
            };

            const updateFileUI = (file) => {
                if (!fileCard || !fileCardName || !fileCardMeta) {
                    return;
                }

                const fileName = (file?.name || '').toLowerCase();
                const isWordFile = fileName.endsWith('.doc') || fileName.endsWith('.docx');

                if (fileCardIconWrap) {
                    fileCardIconWrap.classList.toggle('is-word', isWordFile);
                }

                if (fileCardIcon) {
                    fileCardIcon.classList.toggle('fa-file-word', isWordFile);
                    fileCardIcon.classList.toggle('fa-file-pdf', !isWordFile);
                }

                if (!file) {
                    fileCard.classList.add('is-hidden');
                    fileCard.setAttribute('aria-hidden', 'true');
                    setAttachmentProgress(0);
                    if (fileDropzoneButton) {
                        fileDropzoneButton.textContent = fileDropzoneButton.dataset.defaultLabel || 'Choose File';
                    }
                    return;
                }

                fileCard.classList.remove('is-hidden');
                fileCard.setAttribute('aria-hidden', 'false');
                fileCardName.textContent = file.name;
                fileCardMeta.textContent = new Intl.NumberFormat().format((file.size || 0) / 1024 / 1024) + ' MB';
                setAttachmentProgress(100);
                if (fileDropzoneButton) {
                    fileDropzoneButton.textContent = file.name;
                }
            };

            const syncFileInput = (file) => {
                if (!fileInput || !file) {
                    return;
                }

                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput.files = dataTransfer.files;
                fileInput.dispatchEvent(new Event('change', { bubbles: true }));
            };

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

            const populateExamCategories = (levelLabel, preferredValue = '') => {
                if (!examCategorySelect) {
                    return;
                }

                const options = examCategoryOptionsByLevel[levelLabel] ?? examCategoryOptionsByLevel.default;
                const previousValue = normalizeExamCategory(levelLabel, preferredValue || examCategorySelect.value);
                const placeholderText = @json(__('None'));

                examCategorySelect.innerHTML = '';
                examCategorySelect.appendChild(createOption('', placeholderText, false, !previousValue));

                let matched = false;

                options.forEach((option) => {
                    const isSelected = previousValue === option;
                    const categoryOption = createOption(option, option, false, isSelected);
                    if (isSelected) {
                        matched = true;
                    }
                    examCategorySelect.appendChild(categoryOption);
                });

                if (!matched && options.length > 0) {
                    examCategorySelect.value = options[0];
                }

                if (options.length === 0) {
                    examCategorySelect.value = '';
                }
            };

            const populateSubjects = (classGradeLabel, examCategoryLabel = '', preferredValue = '') => {
                if (!subjectSelect) {
                    return;
                }

                const compoundKey = `${classGradeLabel}|${examCategoryLabel}`;
                const levelNode = findNodeByLabel(educationTree, educationLevelSelect?.value || '');
                const classGradeNode = findNodeByLabel(levelNode?.children ?? [], classGradeLabel);
                const overrideChildren = subjectOptionsByClassGradeAndExamCategory[compoundKey] ?? null;
                const children = (overrideChildren ?? (classGradeNode?.children ?? []))
                    .map((label) => typeof label === 'string' ? { label } : label);
                const previousValue = preferredValue || subjectSelect.value;
                const normalizedPreviousValue = normalizeLookupLabel(previousValue);
                const placeholderText = @json(__('None'));

                subjectSelect.innerHTML = '';
                subjectSelect.appendChild(createOption('', placeholderText, false, !previousValue));

                let matched = false;

                children.forEach((child) => {
                    const label = child.label || child.name || child.value || '';
                    const isSelected = normalizeLookupLabel(label) === normalizedPreviousValue;
                    const option = createOption(label, label, false, isSelected);
                    if (isSelected) {
                        matched = true;
                    }
                    subjectSelect.appendChild(option);
                });

                if (!matched && children.length > 0) {
                    subjectSelect.value = children[0].label;
                } else {
                    subjectSelect.value = previousValue;
                }

            };

            const populateClassGrades = (levelLabel, preferredValue = '') => {
                if (!classGradeSelect) {
                    return;
                }

                const levelNode = findNodeByLabel(educationTree, levelLabel);
                const children = levelNode?.children ?? [];
                const previousValue = preferredValue || classGradeSelect.value;
                const normalizedPreviousValue = normalizeLookupLabel(previousValue);
                const placeholderText = @json(__('None'));

                classGradeSelect.innerHTML = '';
                classGradeSelect.appendChild(createOption('', placeholderText, false, !previousValue));

                let matched = false;

                children.forEach((child) => {
                    const isSelected = normalizeLookupLabel(child.label) === normalizedPreviousValue;
                    const option = createOption(child.label, child.label, false, isSelected);
                    if (isSelected) {
                        matched = true;
                    }
                    classGradeSelect.appendChild(option);
                });

                if (!matched && children.length > 0) {
                    classGradeSelect.value = children[0].label;
                }

                if (children.length === 0) {
                    classGradeSelect.value = '';
                }

                populateSubjects(
                    classGradeSelect.value || previousValue,
                    examCategorySelect?.value || @json($examCategoryValue ?? ''),
                    @json($subjectValue ?? '')
                );
            };

            submitButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const status = this.getAttribute('data-paper-submit') === 'publish' ? 'active' : 'is_draft';
                    if (statusInput) {
                        statusInput.value = status;
                    }
                    const radio = document.querySelector(`input[name="status_choice"][value="${status}"]`);
                    if (radio) {
                        radio.checked = true;
                    }
                    document.getElementById('paper-form')?.requestSubmit();
                });
            });

            if (educationLevelSelect) {
                populateExamCategories(educationLevelSelect.value, @json($examCategoryValue ?? ''));
                populateClassGrades(educationLevelSelect.value, @json($classGradeValue ?? ''));
                if (classGradeLabelEl) {
                    classGradeLabelEl.childNodes[0].textContent = getClassGradeLabel(educationLevelSelect.value) + ' ';
                }
                if (subjectLabelEl) {
                    subjectLabelEl.textContent = getSubjectLabel(educationLevelSelect.value);
                }
                syncSubjectFieldName(educationLevelSelect.value);

                educationLevelSelect.addEventListener('change', function () {
                    populateExamCategories(this.value);
                    populateClassGrades(this.value);
                    if (classGradeLabelEl) {
                        classGradeLabelEl.childNodes[0].textContent = getClassGradeLabel(this.value) + ' ';
                    }
                    if (subjectLabelEl) {
                        subjectLabelEl.textContent = getSubjectLabel(this.value);
                    }
                    syncSubjectFieldName(this.value);
                });
            }

            stepperItems.forEach((item) => {
                item.addEventListener('click', function () {
                    scrollToStep(this.dataset.step);
                });

                item.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        scrollToStep(this.dataset.step);
                    }
                });
            });

            updateActiveStepFromScroll();
            window.addEventListener('scroll', scheduleStepUpdate, { passive: true });
            window.addEventListener('resize', scheduleStepUpdate);

            if (classGradeSelect) {
                classGradeSelect.addEventListener('change', function () {
                    populateSubjects(this.value, examCategorySelect?.value || '');
                });
            }

            if (examCategorySelect) {
                examCategorySelect.addEventListener('change', function () {
                    populateSubjects(classGradeSelect?.value || '', this.value);
                });
            }

            statusChoiceInputs.forEach((radio) => {
                radio.addEventListener('change', function () {
                    if (statusInput) {
                        statusInput.value = this.value;
                    }
                });
            });

            fileInput?.addEventListener('change', function () {
                const selectedFile = this.files?.[0] || null;

                if (!selectedFile) {
                    clearUploadFeedback();
                    updateFileUI(null);
                    return;
                }

                if (!fileLooksLikeAttachment(selectedFile)) {
                    showUploadFeedback(@json(__('Unsupported file type. Please upload PDF, DOC, or DOCX.')));
                    this.value = '';
                    updateFileUI(null);
                    return;
                }

                clearUploadFeedback();
                updateFileUI(selectedFile);
            });

            if (fileInput?.files?.[0] && fileDropzoneButton) {
                updateFileUI(fileInput.files[0]);
            }

            if (fileDropzone && fileInput) {
                fileDropzone.addEventListener('dragenter', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    setDropzoneDragging(true);
                });

                fileDropzone.addEventListener('dragover', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    event.dataTransfer.dropEffect = 'copy';
                    setDropzoneDragging(true);
                });

                fileDropzone.addEventListener('dragleave', function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    if (event.target === fileDropzone) {
                        setDropzoneDragging(false);
                    }
                });

                fileDropzone.addEventListener('drop', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    setDropzoneDragging(false);

                    const droppedFile = event.dataTransfer?.files?.[0];

                    if (!droppedFile) {
                        return;
                    }

                    if (!fileLooksLikeAttachment(droppedFile)) {
                        showUploadFeedback(@json(__('Unsupported file type. Please upload PDF, DOC, or DOCX.')));
                        updateFileUI(null);
                        fileInput.value = '';
                        return;
                    }

                    clearUploadFeedback();
                    setAttachmentProgress(65);
                    syncFileInput(droppedFile);
                });
            }

            @if ($errors->has('resource_file'))
                showUploadFeedback(@json($errors->first('resource_file')));
            @endif
        });
    </script>
@endpush
