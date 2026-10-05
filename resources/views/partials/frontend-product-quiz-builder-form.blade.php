@php
    $quizFormData = $quizFormData ?? [];
    $questions = old('questions', $quizFormData['questions'] ?? []);
    $selectedTier = old('tier', $quizFormData['tier'] ?? 'short');
    $selectedDifficulty = old('difficulty', $quizFormData['difficulty'] ?? 'intermediate');
    $selectedStatus = old('status', $product->status ?? 'is_draft');
    $selectedEducationLevel = old('education_level', $quizFormData['education_level'] ?? 'Senior School (CBC)');
    $selectedClassGrade = old('class_grade', $quizFormData['class_grade'] ?? 'Grade 10');
    $selectedExamCategory = old('exam_category', $quizFormData['exam_category'] ?? 'KCSE');
    $selectedSubject = old('subject', $quizFormData['subject'] ?? 'Mathematics');
    $quizEducationLevels = $metadataOptions['education_levels'];
    $quizClassGradesByLevel = $metadataOptions['class_grades_by_level'] ?? [];
    $quizSubjectsByLevel = $metadataOptions['subjects_by_education_level'] ?? [];
    $quizClassGrades = $quizClassGradesByLevel[$selectedEducationLevel] ?? ($quizClassGradesByLevel['default'] ?? $metadataOptions['class_grades']);
    if (filled($selectedClassGrade) && ! in_array($selectedClassGrade, $quizClassGrades, true)) {
        $quizClassGrades[] = $selectedClassGrade;
    }
    $quizExamCategories = $metadataOptions['exam_categories'];
    $quizSubjects = $quizSubjectsByLevel[$selectedEducationLevel] ?? $metadataOptions['subjects'];
    $quizYears = $metadataOptions['years'] ?? range((int) now()->year, (int) now()->year - 20);
    $selectedTopic = old('topic', $quizFormData['topic'] ?? __('Algebra'));
    $selectedTags = old('tags', $quizFormData['tags'] ?? 'KCSE, Mathematics, Algebra');
    $selectedTotalQuestions = old('total_questions', $quizFormData['total_questions'] ?? 20);
    $selectedQuestionOrder = old('question_order', $quizFormData['question_order'] ?? 'random');
    $selectedShowResults = old('show_results', $quizFormData['show_results'] ?? 'immediately');
    $selectedEnabled = (bool) old('is_enabled', $quizFormData['is_enabled'] ?? true);
    $selectedApprovalStatus = old('is_approved', $product->is_approved ?? 'pending');
    $resolveClassGradeLabel = static function (?string $educationLevel): string {
        $level = strtolower(trim((string) $educationLevel));

        return preg_match('/tvet|university|college|certificate|diploma|undergraduate|tertiary|higher education/', $level)
            ? __('School of')
            : __('Class / Grade');
    };
    $classGradeLabel = $resolveClassGradeLabel($selectedEducationLevel);
    $sampleTitle = old('title', $product->title ?? ($selectedTier === 'short' ? __('KCSE Mathematics - Short Quiz') : __('KCSE Mathematics - Long Quiz')));
    $sampleDescription = old('description', $product->description ?? ($selectedTier === 'short'
        ? __('Test your knowledge with a focused short quiz designed for quick revision.')
        : __('A longer quiz designed for deeper practice, revision, and exam readiness.')));
    $draftSaving = old('save_mode') === 'draft';
    $questionErrorKeys = collect($errors->keys())->filter(fn ($key) => $key === 'questions' || str_starts_with($key, 'questions.'));
    $initialStep = $questionErrorKeys->isNotEmpty() ? 2 : 1;
    $thumbnailUrl = !empty($product->thumbnail) ? asset($product->thumbnail) : null;
    $showReviewSnapshot = (bool) ($showReviewSnapshot ?? false);
    $reviewSnapshotItems = collect([
        ['label' => __('Title'), 'value' => $sampleTitle],
        ['label' => __('Description'), 'value' => $sampleDescription],
        ['label' => __('Tier'), 'value' => ucfirst((string) $selectedTier)],
        ['label' => __('Education Level'), 'value' => $selectedEducationLevel],
        ['label' => $classGradeLabel, 'value' => $selectedClassGrade],
        ['label' => __('Exam Category'), 'value' => $selectedExamCategory],
        ['label' => __('Subject'), 'value' => $selectedSubject],
        ['label' => __('Topic'), 'value' => $selectedTopic],
        ['label' => __('Tags'), 'value' => $selectedTags],
        ['label' => __('Questions'), 'value' => count($questions) . ' ' . __('items')],
        ['label' => __('Status'), 'value' => ucfirst((string) $selectedStatus)],
        ['label' => __('Approval Status'), 'value' => match ($selectedApprovalStatus) {
            'pending' => __('Pending'),
            'approved' => __('Published'),
            'rejected' => __('Rejected'),
            default => ucfirst((string) $selectedApprovalStatus),
        }],
    ])->filter(fn ($item) => filled($item['value']));
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" id="quiz-builder-form">
    @csrf
    @if (!empty($method) && strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <input type="hidden" name="save_mode" id="quiz-save-mode" value="{{ $draftSaving ? 'draft' : 'publish' }}">
    <input type="hidden" name="tier" id="quiz-tier" value="{{ $selectedTier }}">
    <input type="hidden" name="difficulty" id="quiz-difficulty" value="{{ $selectedDifficulty }}">
    <input type="hidden" name="price" id="quiz-price" value="{{ old('price', $product->price ?? ($selectedTier === 'short' ? 0 : 20)) }}">
    <input type="hidden" name="discount" id="quiz-discount" value="{{ old('discount', $product->discount ?? 0) }}">
    <input type="hidden" name="status" id="status" value="{{ $selectedStatus }}">

    @if ($showReviewSnapshot)
        <div class="quiz-builder-review-banner" style="margin-bottom: 18px;">
            <p>{{ __('Review Snapshot') }}</p>
            <div class="d-flex flex-wrap" style="gap: .5rem;">
                @foreach ($reviewSnapshotItems as $item)
                    <span class="badge badge-light border text-dark d-inline-flex flex-column align-items-start p-2">
                        <small class="text-muted">{{ $item['label'] }}</small>
                        <strong>{{ $item['value'] }}</strong>
                    </span>
                @endforeach
            </div>
            @if (!empty($showApprovalStatus))
                <div class="form-group mt-3 mb-0" style="max-width: 260px;">
                    <label for="is_approved" class="mb-1">{{ __('Approval Status') }}</label>
                    <select id="is_approved" name="is_approved" class="form-control">
                        <option value="pending" @selected($selectedApprovalStatus === 'pending')>{{ __('Pending') }}</option>
                        <option value="approved" @selected($selectedApprovalStatus === 'approved')>{{ __('Published') }}</option>
                        <option value="rejected" @selected($selectedApprovalStatus === 'rejected')>{{ __('Rejected') }}</option>
                    </select>
                </div>
            @endif
        </div>
    @endif

    <style>
        .quiz-builder-stepper {
            display: flex;
            align-items: flex-start;
            gap: 0;
            margin-bottom: 20px;
        }

        .quiz-builder-step {
            flex: 1 1 0;
            border: 0;
            border-radius: 0;
            padding: 0 12px 6px 0;
            background: transparent;
            transition: all .2s ease;
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .quiz-builder-step.is-active {
            transform: none;
        }

        .quiz-builder-step.is-active::after {
            content: '';
            position: absolute;
            right: 10px;
            left: 46px;
            bottom: 0;
            height: 3px;
            border-radius: 999px;
            background: #5751e1;
        }

        .quiz-builder-step.is-complete {
            background: transparent;
        }

        .quiz-builder-step-number {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f4f6fb;
            color: #5751e1;
            font-weight: 700;
            font-size: 13px;
            flex: 0 0 auto;
        }

        .quiz-builder-step.is-complete .quiz-builder-step-number {
            background: #27ae60;
            color: #fff;
        }

        .quiz-builder-step.is-active .quiz-builder-step-number {
            background: #5751e1;
            color: #fff;
        }

        .quiz-builder-step-label {
            display: inline-flex;
            align-items: center;
            font-weight: 700;
            color: #1f2a37;
            font-size: 14px;
            line-height: 1.35;
        }

        .quiz-builder-step-copy {
            display: none;
            color: #6b7280;
            font-size: 12px;
            line-height: 1.4;
        }

        .quiz-builder-panel {
            display: none;
        }

        .quiz-builder-panel.is-active {
            display: block;
        }

        .quiz-builder-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 18px;
        }

        .quiz-builder-actions .btn {
            min-width: 140px;
        }

        .quiz-builder-step-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .quiz-builder-step-panel-header h5 {
            margin: 0 0 6px;
            color: #5b57d6;
            font-size: 18px;
            font-weight: 700;
        }

        .quiz-builder-step-panel-header p {
            margin: 0;
            color: #667085;
            font-size: 13px;
        }

        .quiz-builder-inline-action,
        .quiz-builder-sidebar-button,
        .quiz-builder-preview-full-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid transparent;
            background: linear-gradient(135deg, #5b57d6, #6d4fff);
            color: #fff;
            font-weight: 700;
            font-size: 13px;
        }

        .quiz-builder-question-stage {
            display: grid;
            grid-template-columns: minmax(0, 1.75fr) minmax(280px, 0.8fr);
            gap: 16px;
            align-items: start;
        }

        .quiz-builder-question-editor,
        .quiz-builder-settings-wrap,
        .quiz-builder-review-layout {
            display: grid;
            gap: 14px;
        }

        .quiz-builder-questions-card,
        .quiz-builder-side-card {
            padding: 18px;
            border: 1px solid #e3e8f2;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 12px 26px rgba(20, 33, 61, 0.04);
        }

        .quiz-builder-questions-card {
            display: grid;
            gap: 14px;
        }

        .quiz-builder-questions-card__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .quiz-builder-questions-card__header h5 {
            margin: 0;
            color: #1f2a44;
            font-size: 17px;
            font-weight: 700;
        }

        .quiz-builder-questions-card__header small {
            color: #667085;
            font-size: 12px;
        }

        .quiz-builder-questions-card__actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .quiz-builder-add-question-slab {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            min-height: 66px;
            border: 2px dashed #cfd7f6;
            border-radius: 14px;
            background: linear-gradient(180deg, #fbfcff 0%, #f7f8ff 100%);
            color: #5b57d6;
            font-weight: 700;
        }

        .quiz-builder-add-question-slab i {
            font-size: 16px;
        }

        .quiz-builder-side-card h5 {
            margin: 0 0 18px;
            color: #5b57d6;
            font-size: 17px;
            font-weight: 700;
        }

        .quiz-builder-question-types {
            display: grid;
            gap: 16px;
        }

        .quiz-builder-question-type {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .quiz-builder-question-type__icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            font-size: 18px;
        }

        .quiz-builder-question-type:nth-child(1) .quiz-builder-question-type__icon {
            background: rgba(91, 87, 214, 0.10);
            color: #5b57d6;
        }

        .quiz-builder-question-type:nth-child(2) .quiz-builder-question-type__icon {
            background: rgba(34, 197, 94, 0.10);
            color: #22c55e;
        }

        .quiz-builder-question-type:nth-child(3) .quiz-builder-question-type__icon {
            background: rgba(245, 158, 11, 0.10);
            color: #f59e0b;
        }

        .quiz-builder-question-type:nth-child(4) .quiz-builder-question-type__icon {
            background: rgba(59, 130, 246, 0.10);
            color: #3b82f6;
        }

        .quiz-builder-question-type:nth-child(5) .quiz-builder-question-type__icon {
            background: rgba(236, 72, 153, 0.10);
            color: #ec4899;
        }

        .quiz-builder-question-type__copy strong {
            display: block;
            color: #1f2a44;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .quiz-builder-question-type__copy p {
            margin: 0;
            color: #667085;
            font-size: 12px;
            line-height: 1.5;
        }

        .quiz-builder-tips-list {
            margin: 0;
            padding-left: 18px;
            color: #1f2a44;
            font-size: 13px;
            line-height: 1.75;
        }

        .quiz-builder-question-sidebar h6 {
            margin: 0 0 8px;
            color: #1f2a44;
            font-size: 15px;
            font-weight: 700;
        }

        .quiz-builder-sidebar-button {
            width: 100%;
            margin-top: 12px;
        }

        .quiz-question-card {
            border: 1px solid #e2e8f4;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(20, 33, 61, 0.04);
            overflow: hidden;
            margin-bottom: 12px;
        }

        .quiz-question-card__summary {
            display: grid;
            grid-template-columns: 18px 42px minmax(0, 1fr) auto auto;
            align-items: center;
            gap: 12px;
            padding: 16px 18px;
            cursor: pointer;
        }

        .quiz-question-card__drag {
            border: 0;
            background: transparent;
            color: #98a2b3;
            padding: 0;
        }

        .quiz-question-card__number {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #f4f6fb;
            color: #5b57d6;
            font-size: 13px;
            font-weight: 700;
        }

        .quiz-question-card__text {
            min-width: 0;
        }

        .quiz-question-card__prompt-preview {
            display: block;
            color: #1f2a44;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.35;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .quiz-question-card__meta {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 4px;
            color: #667085;
            font-size: 12px;
        }

        .quiz-question-card__dot {
            font-size: 10px;
            line-height: 1;
        }

        .quiz-question-card__status {
            padding: 6px 12px;
            border-radius: 999px;
            background: #dcfce7;
            color: #16a34a;
            font-size: 12px;
            font-weight: 700;
        }

        .quiz-question-card__icon-btn {
            border: 0;
            background: transparent;
            color: #667085;
            padding: 6px;
        }

        .quiz-question-card__body {
            padding: 0 18px 18px;
        }

        .quiz-question-card__question-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 14px;
            margin-bottom: 8px;
        }

        .quiz-question-card__question-head label {
            color: #1f2a44;
            font-size: 14px;
            font-weight: 700;
        }

        .quiz-question-card__prompt-shell {
            position: relative;
        }

        .quiz-question-card__prompt-shell .quiz-question-card__counter {
            position: absolute;
            right: 10px;
            bottom: 8px;
            margin: 0;
            background: rgba(255, 255, 255, 0.82);
            padding: 0 4px;
        }

        .quiz-question-card__toolbar-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quiz-question-card__preview-link {
            color: #5b57d6;
            font-size: 13px;
            font-weight: 600;
        }

        .quiz-question-card__prompt-input {
            min-height: 92px;
            background: #fff;
            padding-bottom: 26px;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
            border-radius: 0 0 12px 12px;
        }

        .quiz-question-card__editor-tools {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 8px;
            margin-bottom: -1px;
            border: 1px solid #dfe5f3;
            border-bottom: 0;
            border-radius: 12px 12px 0 0;
            background: #fff;
            color: #667085;
            flex-wrap: wrap;
        }

        .quiz-question-card__editor-tools button {
            min-width: 28px;
            height: 28px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: inherit;
            font-size: 13px;
        }

        .quiz-question-card__counter {
            display: block;
            margin-top: 4px;
            color: #98a2b3;
            font-size: 11px;
            text-align: right;
        }

        .quiz-question-card__section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }

        .quiz-question-card__section-head h6 {
            color: #1f2a44;
            font-size: 14px;
            font-weight: 700;
        }

        .quiz-question-card__add-option,
        .quiz-question-card__add-option-link {
            border: 0;
            background: transparent;
            color: #5b57d6;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .quiz-question-card__add-option-wrap {
            margin-top: 8px;
        }

        .quiz-question-card__option-row {
            display: grid;
            grid-template-columns: 18px 24px minmax(0, 1fr) auto 30px 28px;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            padding: 6px 8px;
            border: 1px solid #e6ebf5;
            border-radius: 12px;
            background: #fff;
        }

        .quiz-question-card__option-drag {
            border: 0;
            background: transparent;
            color: #98a2b3;
            padding: 0;
            line-height: 1;
        }

        .quiz-question-card__option-label {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #f4f6fb;
            color: #5b57d6;
            font-size: 12px;
            font-weight: 700;
        }

        .quiz-question-card__option-input {
            border-radius: 10px;
            min-height: 36px;
        }

        .quiz-question-card__correct-pill {
            display: none;
            align-items: center;
            gap: 6px;
            justify-self: end;
            margin-right: 2px;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(22, 163, 74, 0.10);
            color: #16a34a;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .quiz-question-card__correct-pill i {
            font-size: 9px;
        }

        .quiz-question-card__option-row.is-correct {
            border-color: #bfe9c7;
            background: #f4fff7;
        }

        .quiz-question-card__option-row.is-correct .quiz-question-card__correct-pill {
            display: inline-flex;
        }

        .quiz-question-card__option-row.is-correct .quiz-question-card__option-input {
            border-color: #bfe9c7;
            background: #effdf2;
        }

        .quiz-question-card__correct-toggle {
            display: grid;
            place-items: center;
            margin: 0;
            position: relative;
        }

        .quiz-question-card__correct-toggle input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .quiz-question-card__correct-dot {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid #c9d2e3;
            background: #fff;
        }

        .quiz-question-card__correct-toggle input:checked + .quiz-question-card__correct-dot {
            border-color: #16a34a;
            background: #16a34a;
            box-shadow: inset 0 0 0 3px #fff;
        }

        .quiz-question-card__footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid #edf1f7;
        }

        .quiz-question-card__footer-item {
            display: grid;
            gap: 6px;
            min-width: 120px;
        }

        .quiz-question-card__footer-item label,
        .quiz-question-card__shuffle {
            color: #1f2a44;
            font-size: 13px;
            font-weight: 600;
        }

        .quiz-question-card__footer-item .form-control,
        .quiz-question-card__footer-item .form-select {
            min-width: 120px;
        }

        .quiz-question-card__shuffle {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            padding-top: 18px;
        }

        .quiz-builder-settings-wrap {
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr) minmax(300px, 0.82fr);
            gap: 16px;
        }

        .quiz-builder-review-layout {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .quiz-builder-review-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.75fr) minmax(300px, 0.82fr);
            gap: 16px;
            align-items: start;
        }

        .quiz-builder-review-stack {
            display: grid;
            gap: 14px;
        }

        .quiz-builder-review-card {
            padding: 18px;
            border: 1px solid #e3e8f2;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 12px 26px rgba(20, 33, 61, 0.04);
        }

        .quiz-builder-review-card__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .quiz-builder-review-card__header h5 {
            margin: 0;
            color: #5b57d6;
            font-size: 17px;
            font-weight: 700;
        }

        .quiz-builder-review-card__header .btn {
            min-width: 120px;
        }

        .quiz-builder-review-basic {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px 22px;
        }

        .quiz-builder-review-basic__item {
            display: grid;
            gap: 4px;
        }

        .quiz-builder-review-basic__item span {
            color: #1f2a44;
            font-size: 12px;
            font-weight: 700;
        }

        .quiz-builder-review-basic__item strong {
            color: #667085;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.5;
        }

        .quiz-builder-review-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .quiz-builder-review-tags span {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 999px;
            background: rgba(91, 87, 214, 0.10);
            color: #5b57d6;
            font-size: 12px;
            font-weight: 700;
        }

        .quiz-builder-questions-table {
            width: 100%;
            border-collapse: collapse;
        }

        .quiz-builder-questions-table th,
        .quiz-builder-questions-table td {
            padding: 12px 8px;
            border-bottom: 1px solid #edf1f7;
            font-size: 13px;
            vertical-align: top;
        }

        .quiz-builder-questions-table th {
            color: #667085;
            font-weight: 700;
            text-transform: none;
        }

        .quiz-builder-questions-table td {
            color: #1f2a44;
        }

        .quiz-builder-questions-table td:first-child {
            width: 44px;
            color: #5b57d6;
            font-weight: 700;
        }

        .quiz-builder-questions-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .quiz-builder-question-note {
            margin-top: 10px;
            color: #5b57d6;
            font-size: 13px;
            font-weight: 700;
        }

        .quiz-builder-right-summary {
            display: grid;
            gap: 16px;
        }

        .quiz-builder-summary-list--review {
            gap: 12px;
        }

        .quiz-builder-summary-list--review .quiz-builder-summary-item__copy {
            align-items: center;
        }

        .quiz-builder-summary-list--review .quiz-builder-summary-item__copy strong {
            font-weight: 700;
        }

        .quiz-builder-ready-card {
            padding: 18px 18px 20px;
            border: 1px solid #d8eadf;
            border-radius: 14px;
            background: linear-gradient(180deg, #f7fff8 0%, #f3faf4 100%);
            box-shadow: 0 12px 26px rgba(20, 33, 61, 0.04);
            text-align: center;
        }

        .quiz-builder-ready-card__icon {
            width: 54px;
            height: 54px;
            margin: 0 auto 12px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #22c55e;
            color: #fff;
            font-size: 22px;
        }

        .quiz-builder-ready-card h5 {
            margin: 0 0 8px;
            color: #16a34a;
            font-size: 18px;
            font-weight: 700;
        }

        .quiz-builder-ready-card p {
            margin: 0;
            color: #4b5563;
            font-size: 13px;
            line-height: 1.6;
        }

        .quiz-builder-settings-card {
            padding: 18px;
            border: 1px solid #e3e8f2;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 12px 26px rgba(20, 33, 61, 0.04);
        }

        .quiz-builder-settings-card h5 {
            margin: 0 0 16px;
            color: #5b57d6;
            font-size: 16px;
            font-weight: 700;
        }

        .quiz-builder-settings-card--summary {
            background: linear-gradient(180deg, #fbfbff 0%, #f9f8ff 100%);
        }

        .quiz-builder-settings-card--tips {
            background: linear-gradient(180deg, #fffdf7 0%, #fffaf0 100%);
            border-color: #f2dfb8;
        }

        .quiz-builder-settings-card--tips h5 {
            color: #d97706;
        }

        .quiz-builder-summary-list {
            display: grid;
            gap: 14px;
        }

        .quiz-builder-summary-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .quiz-builder-summary-item__icon {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: rgba(91, 87, 214, 0.10);
            color: #5b57d6;
            font-size: 14px;
            flex: 0 0 auto;
        }

        .quiz-builder-summary-item__copy {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 14px;
            flex: 1 1 auto;
            min-width: 0;
        }

        .quiz-builder-summary-item__copy span {
            color: #1f2a44;
            font-size: 13px;
            font-weight: 600;
        }

        .quiz-builder-summary-item__copy strong {
            color: #111827;
            font-size: 13px;
            font-weight: 700;
            text-align: right;
        }

        .quiz-builder-summary-item--muted .quiz-builder-summary-item__icon {
            background: rgba(148, 163, 184, 0.12);
            color: #64748b;
        }

        .quiz-builder-question-settings {
            display: grid;
            gap: 16px;
        }

        .quiz-builder-question-settings__item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding-bottom: 12px;
            border-bottom: 1px dashed #e5e7eb;
        }

        .quiz-builder-question-settings__item:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .quiz-builder-question-settings__item strong {
            display: block;
            color: #1f2a44;
            font-size: 14px;
            font-weight: 700;
        }

        .quiz-builder-question-settings__item p {
            margin: 3px 0 0;
            color: #667085;
            font-size: 12px;
            line-height: 1.45;
        }

        .quiz-builder-question-settings__footer {
            display: grid;
            gap: 12px;
            margin-top: 6px;
        }

        .quiz-builder-question-numbering {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        .quiz-builder-question-numbering .form-select {
            min-width: 200px;
        }

        .quiz-builder-step3-tips {
            display: grid;
            gap: 12px;
            color: #1f2a44;
            font-size: 13px;
            line-height: 1.65;
            padding-left: 18px;
        }

        .quiz-builder-other-settings {
            display: grid;
            gap: 12px;
        }

        .quiz-builder-other-setting {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-bottom: 12px;
            border-bottom: 1px dashed #e5e7eb;
        }

        .quiz-builder-other-setting:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .quiz-builder-other-setting strong {
            display: block;
            color: #1f2a44;
            font-size: 14px;
        }

        .quiz-builder-other-setting p {
            margin: 2px 0 0;
            color: #667085;
            font-size: 12px;
        }

        .quiz-builder-switch--small {
            width: 40px;
            height: 22px;
        }

        .quiz-builder-switch--small span::after {
            width: 16px;
            height: 16px;
        }

        .quiz-builder-switch--small input:checked + span::after {
            transform: translateX(18px);
        }

        .quiz-builder-question-breakdown {
            margin-top: 14px;
        }

        .quiz-builder-publishing-state,
        .quiz-builder-success-state {
            display: grid;
            justify-items: center;
            gap: 14px;
            padding: 38px 18px;
            text-align: center;
        }

        .quiz-builder-publishing-illustration {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: rgba(91, 87, 214, 0.08);
            color: #5b57d6;
            font-size: 52px;
        }

        .quiz-builder-progress {
            width: min(100%, 420px);
            height: 8px;
            border-radius: 999px;
            background: #e5e7eb;
            overflow: hidden;
        }

        .quiz-builder-progress__bar {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(135deg, #5b57d6, #6d4fff);
        }

        .quiz-builder-progress-list {
            width: min(100%, 420px);
            display: grid;
            gap: 8px;
            text-align: left;
            color: #667085;
            font-size: 13px;
        }

        .quiz-builder-progress-list li {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quiz-builder-progress-list li::before {
            content: '\f111';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            font-size: 6px;
            color: #cbd5e1;
        }

        .quiz-builder-progress-list li.is-done {
            color: #16a34a;
        }

        .quiz-builder-progress-list li.is-done::before {
            content: '\f058';
            font-size: 14px;
            color: #16a34a;
        }

        .quiz-builder-progress-list li.is-active {
            color: #5b57d6;
        }

        .quiz-builder-progress-list li.is-active::before {
            content: '\f110';
            font-size: 14px;
            color: #5b57d6;
            animation: quizSpin 1s linear infinite;
        }

        .quiz-builder-success-icon {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #22c55e;
            color: #fff;
            font-size: 52px;
        }

        .quiz-builder-success-state h5,
        .quiz-builder-publishing-state h5 {
            margin: 0;
            color: #1f2a44;
            font-size: 22px;
            font-weight: 700;
        }

        .quiz-builder-success-state p,
        .quiz-builder-publishing-state p {
            margin: 0;
            color: #667085;
            font-size: 13px;
        }

        .quiz-builder-success-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }

        @keyframes quizSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .quiz-builder-summary-card {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 18px;
            background: #fff;
            height: 100%;
        }

        .quiz-builder-summary-card h5,
        .quiz-builder-summary-card h6 {
            margin-bottom: 12px;
            font-size: 16px;
        }

        .quiz-builder-kv {
            display: grid;
            gap: 8px;
        }

        .quiz-builder-kv-item {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #e5e7eb;
            font-size: 14px;
        }

        .quiz-builder-kv-item:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .quiz-builder-kv-item span:first-child {
            color: #6b7280;
        }

        .quiz-builder-preview-question {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px;
            background: #f9fafb;
        }

        .quiz-builder-preview-question + .quiz-builder-preview-question {
            margin-top: 12px;
        }

        .quiz-builder-preview-options {
            margin: 8px 0 0;
            padding-left: 18px;
            font-size: 14px;
        }

        .quiz-builder-review-banner {
            border-radius: 14px;
            padding: 16px 18px;
            background: linear-gradient(135deg, #eef1ff 0%, #f9fbff 100%);
            border: 1px solid #dbe2ff;
            margin-bottom: 16px;
        }

        .quiz-builder-review-banner p {
            font-size: 14px;
            line-height: 1.5;
        }

        .quiz-builder-panel .dashboard__contact-form-wrap {
            margin-bottom: 0;
        }

        .quiz-builder-panel .dashboard__contact-form {
            padding: 22px;
        }

        .quiz-builder-panel h5 {
            font-size: 18px;
            margin-bottom: 14px;
        }

        .quiz-builder-panel label {
            font-size: 14px;
            margin-bottom: 6px;
        }

        .quiz-builder-panel .form-control,
        .quiz-builder-panel .form-select {
            font-size: 14px;
        }

        .quiz-builder-compact-note {
            font-size: 13px;
            color: #6b7280;
        }

        .quiz-builder-question-header {
            margin-bottom: 14px;
        }

        .quiz-builder-page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 18px;
        }

        .quiz-builder-breadcrumb {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #667085;
            font-size: 13px;
            margin-bottom: 10px;
        }

        .quiz-builder-breadcrumb a {
            color: #5b57d6;
            font-weight: 600;
        }

        .quiz-builder-hero h1 {
            margin: 0 0 6px;
            color: #1f2a44;
            font-size: 30px;
            line-height: 1.1;
            font-weight: 700;
        }

        .quiz-builder-hero p {
            margin: 0;
            color: #667085;
            font-size: 13px;
        }

        .quiz-builder-page-header__actions {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .quiz-builder-preview-btn,
        .quiz-builder-draft-btn,
        .quiz-builder-next-btn,
        .quiz-builder-next-banner__button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border-radius: 12px;
            border: 1px solid transparent;
            font-weight: 700;
            font-size: 14px;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .quiz-builder-preview-btn {
            padding: 11px 16px;
            background: linear-gradient(135deg, #5b57d6, #6d4fff);
            color: #fff;
            box-shadow: 0 10px 24px rgba(91, 87, 214, 0.18);
        }

        .quiz-builder-draft-btn {
            padding: 11px 16px;
            border-color: #d7ddea;
            background: #fff;
            color: #24304f;
        }

        .quiz-builder-next-btn,
        .quiz-builder-next-banner__button {
            padding: 12px 18px;
            background: linear-gradient(135deg, #5b57d6, #6d4fff);
            color: #fff;
            box-shadow: 0 12px 24px rgba(91, 87, 214, 0.18);
        }

        .quiz-builder-form-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.9fr) minmax(280px, 0.85fr);
            gap: 16px;
            align-items: start;
        }

        .quiz-builder-card {
            padding: 18px;
            border: 1px solid #e3e8f2;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 12px 26px rgba(20, 33, 61, 0.04);
        }

        .quiz-builder-card h5 {
            margin: 0 0 16px;
            color: #5b57d6;
            font-size: 17px;
            font-weight: 700;
        }

        .quiz-builder-field-grid {
            display: grid;
            gap: 12px;
            margin-bottom: 12px;
        }

        .quiz-builder-field-grid--3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .quiz-builder-thumbnail-block {
            margin-top: 8px;
        }

        .quiz-builder-thumbnail-block > label {
            display: block;
            margin-bottom: 10px;
            color: #1f2a44;
            font-size: 13px;
            font-weight: 700;
        }

        .quiz-builder-thumbnail-grid {
            display: grid;
            grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.1fr);
            gap: 14px;
            align-items: stretch;
        }

        .quiz-builder-thumbnail-dropzone {
            display: grid;
            place-items: center;
            gap: 8px;
            min-height: 196px;
            padding: 20px;
            border: 2px dashed #cfd7f6;
            border-radius: 14px;
            background: linear-gradient(180deg, #fbfcff 0%, #f6f8ff 100%);
            text-align: center;
            cursor: pointer;
        }

        .quiz-builder-thumbnail-dropzone__icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: rgba(91, 87, 214, 0.12);
            color: #5b57d6;
            font-size: 22px;
        }

        .quiz-builder-thumbnail-dropzone strong {
            color: #1f2a44;
            font-size: 14px;
        }

        .quiz-builder-thumbnail-dropzone span,
        .quiz-builder-thumbnail-dropzone small {
            color: #667085;
            font-size: 12px;
        }

        .quiz-builder-thumbnail-preview {
            min-height: 196px;
            border-radius: 14px;
            border: 1px solid #e3e8f2;
            background: #fff;
            overflow: hidden;
            display: grid;
            place-items: center;
        }

        .quiz-builder-thumbnail-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .quiz-builder-thumbnail-preview__empty {
            display: grid;
            justify-items: center;
            gap: 10px;
            padding: 22px;
            text-align: center;
            color: #667085;
        }

        .quiz-builder-thumbnail-preview__empty i {
            font-size: 32px;
            color: #5b57d6;
        }

        .quiz-builder-inline-field {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) 140px;
            gap: 12px;
        }

        .quiz-builder-inline-field .form-grp {
            margin-bottom: 0;
        }

        .quiz-builder-radio-group {
            display: flex;
            align-items: center;
            gap: 22px;
            flex-wrap: wrap;
            min-height: 38px;
        }

        .quiz-builder-radio-group label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            color: #1f2a44;
            font-size: 13px;
            font-weight: 600;
        }

        .quiz-builder-toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 6px;
            padding: 14px 0 0;
            border-top: 1px solid rgba(227, 232, 242, 0.9);
        }

        .quiz-builder-toggle-row strong {
            display: block;
            color: #1f2a44;
            font-size: 14px;
        }

        .quiz-builder-toggle-row span {
            color: #667085;
            font-size: 12px;
        }

        .quiz-builder-switch {
            position: relative;
            width: 46px;
            height: 26px;
            flex: 0 0 auto;
        }

        .quiz-builder-switch input {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .quiz-builder-switch span {
            position: absolute;
            inset: 0;
            border-radius: 999px;
            background: #cfd7f6;
            transition: background .18s ease;
        }

        .quiz-builder-switch span::after {
            content: '';
            position: absolute;
            top: 3px;
            left: 3px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 4px 12px rgba(20, 33, 61, 0.18);
            transition: transform .18s ease;
        }

        .quiz-builder-switch input:checked + span {
            background: linear-gradient(135deg, #5b57d6, #6d4fff);
        }

        .quiz-builder-switch input:checked + span::after {
            transform: translateX(20px);
        }

        .quiz-builder-next-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-top: 16px;
            padding: 16px 18px;
            border: 1px solid #d8def3;
            border-radius: 14px;
            background: linear-gradient(135deg, #eef1ff 0%, #f9fbff 100%);
        }

        .quiz-builder-next-banner__icon {
            display: grid;
            place-items: center;
            width: 54px;
            height: 54px;
            border-radius: 50%;
            background: rgba(91, 87, 214, 0.12);
            color: #5b57d6;
            font-size: 24px;
            flex: 0 0 auto;
        }

        .quiz-builder-next-banner__copy {
            min-width: 0;
            flex: 1 1 auto;
        }

        .quiz-builder-next-banner__copy strong {
            display: block;
            margin-bottom: 4px;
            color: #1f2a44;
            font-size: 15px;
        }

        .quiz-builder-next-banner__copy p {
            margin: 0;
            color: #667085;
            font-size: 12px;
            line-height: 1.5;
        }

        .quiz-builder-actions--bottom {
            justify-content: flex-start;
            margin-top: 14px;
        }

        .quiz-builder-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .quiz-builder-tags span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            border-radius: 999px;
            background: rgba(91, 87, 214, 0.08);
            color: #5b57d6;
            font-size: 12px;
            font-weight: 700;
        }

        .quiz-builder-step-one__main {
            padding: 18px 18px 16px;
        }

        .quiz-builder-step-one__tips {
            padding: 18px;
            background: linear-gradient(180deg, #fbfbff 0%, #f9fbff 100%);
        }

        .quiz-builder-step-one__tips h5 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
        }

        .quiz-builder-step-one__tips h5::before {
            content: '\f0eb';
            font-family: 'Font Awesome 5 Free';
            font-weight: 400;
            display: inline-grid;
            place-items: center;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: rgba(91, 87, 214, 0.08);
            color: #5b57d6;
            font-size: 15px;
            flex: 0 0 auto;
        }

        .quiz-builder-tips {
            display: grid;
            gap: 18px;
        }

        .quiz-builder-tip {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .quiz-builder-tip__icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            font-size: 18px;
        }

        .quiz-builder-tip:nth-child(1) .quiz-builder-tip__icon {
            background: rgba(91, 87, 214, 0.10);
            color: #5b57d6;
        }

        .quiz-builder-tip:nth-child(2) .quiz-builder-tip__icon {
            background: rgba(59, 130, 246, 0.10);
            color: #3b82f6;
        }

        .quiz-builder-tip:nth-child(3) .quiz-builder-tip__icon {
            background: rgba(34, 197, 94, 0.10);
            color: #22c55e;
        }

        .quiz-builder-tip:nth-child(4) .quiz-builder-tip__icon {
            background: rgba(245, 158, 11, 0.10);
            color: #f59e0b;
        }

        .quiz-builder-tip__copy strong {
            display: block;
            color: #1f2a44;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .quiz-builder-tip__copy p {
            margin: 0;
            color: #667085;
            font-size: 13px;
            line-height: 1.55;
        }

        .quiz-builder-step-one__footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-top: 18px;
        }

        .quiz-builder-step-one__footer .btn {
            min-width: 100px;
        }

        @media (max-width: 991px) {
            .quiz-builder-stepper {
                flex-wrap: wrap;
            }

            .quiz-builder-page-header,
            .quiz-builder-next-banner {
                align-items: flex-start;
                flex-direction: column;
            }

            .quiz-builder-form-grid {
                grid-template-columns: 1fr;
            }

            .quiz-builder-step-one__footer {
                flex-direction: column;
                align-items: stretch;
            }

            .quiz-builder-field-grid--3,
            .quiz-builder-thumbnail-grid,
            .quiz-builder-inline-field {
                grid-template-columns: 1fr;
            }

            .quiz-builder-question-stage,
            .quiz-builder-settings-wrap,
            .quiz-builder-review-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575px) {
            .quiz-builder-step {
                flex: 1 1 100%;
            }

            .quiz-builder-page-header__actions {
                width: 100%;
                justify-content: flex-start;
            }

            .quiz-builder-preview-btn,
            .quiz-builder-draft-btn,
            .quiz-builder-next-btn,
            .quiz-builder-next-banner__button {
                width: 100%;
                justify-content: center;
            }

            .quiz-builder-step-one__footer .btn {
                width: 100%;
            }
        }
    </style>

    <div class="quiz-builder-page-header">
        <div>
            <div class="quiz-builder-breadcrumb">
                <a href="{{ route('instructor.dashboard') }}">{{ __('Dashboard') }}</a>
                <span>/</span>
                <a href="{{ route('instructor.products.index', ['type' => 'quiz']) }}">{{ __('Quizzes') }}</a>
                <span>/</span>
                <span>{{ $isEditing ? __('Edit Quiz') : __('Create New Quiz') }}</span>
            </div>
            <div class="quiz-builder-hero">
                <h1 id="quiz-builder-page-title">{{ $isEditing ? __('Edit Quiz') : __('Create New Quiz') }}</h1>
                <p id="quiz-builder-page-description">{{ __('Create a new quiz for your students. Add questions, set the correct answers and configure quiz settings.') }}</p>
            </div>
        </div>

        <div class="quiz-builder-page-header__actions">
            <button type="button" class="quiz-builder-preview-btn" data-go-step="4">
                <i class="far fa-eye"></i>
                <span>{{ __('Preview Quiz') }}</span>
            </button>
        </div>
    </div>

    <div class="quiz-builder-stepper" id="quiz-builder-stepper">
        @foreach ([
            ['title' => __('Basic Information'), 'copy' => __('Quiz details and settings')],
            ['title' => __('Add Questions'), 'copy' => __('Build your question set')],
            ['title' => __('Quiz Settings'), 'copy' => __('Timing and visibility')],
            ['title' => __('Review'), 'copy' => __('Final step')],
        ] as $stepIndex => $step)
            <div class="quiz-builder-step" data-step-indicator="{{ $stepIndex + 1 }}">
                <span class="quiz-builder-step-number">{{ $stepIndex + 1 }}</span>
                <span class="quiz-builder-step-label">{{ $step['title'] }}</span>
                <span class="quiz-builder-step-copy">{{ $step['copy'] }}</span>
            </div>
        @endforeach
    </div>

    <div class="quiz-builder-panel is-active" data-step-panel="1">
        <div class="quiz-builder-form-grid">
            <section class="quiz-builder-card quiz-builder-step-one__main">
                <h5>{{ __('Quiz Basic Information') }}</h5>

                <div class="form-grp">
                    <label for="title">{{ __('Quiz Title') }} <span class="text-danger">*</span></label>
                    <input type="text" id="title" name="title" class="form-control" value="{{ $sampleTitle }}" required>
                    @error('title')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-grp">
                    <label for="description">{{ __('Description') }} <span class="text-danger">*</span></label>
                    <textarea id="description" name="description" class="form-control" rows="4" required>{{ $sampleDescription }}</textarea>
                    @error('description')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="quiz-builder-field-grid quiz-builder-field-grid--3">
                    <div class="form-grp">
                        <label for="education_level">{{ __('Education Level') }} <span class="text-danger">*</span></label>
                        <select id="education_level" name="education_level" class="form-select" required>
                            @foreach ($quizEducationLevels as $option)
                                <option value="{{ $option }}" @selected($selectedEducationLevel === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-grp">
                        <label for="class_grade">{{ $classGradeLabel }} <span class="text-danger">*</span></label>
                        <select id="class_grade" name="class_grade" class="form-select" required>
                            @foreach ($quizClassGrades as $option)
                                <option value="{{ $option }}" @selected($selectedClassGrade === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-grp">
                        <label for="subject">{{ __('Subject') }} <span class="text-danger">*</span></label>
                        <select id="subject" name="subject" class="form-select" required>
                            @foreach ($quizSubjects as $option)
                                <option value="{{ $option }}" @selected($selectedSubject === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="quiz-builder-field-grid quiz-builder-field-grid--4">
                    <div class="form-grp">
                        <label for="topic">{{ __('Topic') }} <span class="text-danger">*</span></label>
                        <input type="text" id="topic" name="topic" class="form-control" value="{{ $selectedTopic }}" required placeholder="{{ __('Algebra') }}">
                    </div>
                    <div class="form-grp">
                        <label for="exam_category">{{ __('Exam Category') }} <span class="text-danger">*</span></label>
                        <select id="exam_category" name="exam_category" class="form-select" required>
                            <option value="" @selected($selectedExamCategory === '')>{{ __('None') }}</option>
                            @foreach ($quizExamCategories as $option)
                                <option value="{{ $option }}" @selected($selectedExamCategory === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-grp">
                        <label for="year">{{ __('Year') }}</label>
                        <select id="year" name="year" class="form-select">
                            <option value="" @selected(old('year', $quizFormData['year'] ?? '') === '')>{{ __('None') }}</option>
                            @foreach ($quizYears as $option)
                                <option value="{{ $option }}" @selected((string) old('year', $quizFormData['year'] ?? '') === (string) $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-grp">
                        <label for="tags">{{ __('Tags') }}</label>
                        <input type="text" id="tags" name="tags" class="form-control" value="{{ $selectedTags }}" placeholder="{{ __('KCSE, Mathematics, Algebra') }}">
                        <div class="quiz-builder-tags">
                            @foreach (collect(explode(',', $selectedTags))->map(fn ($tag) => trim($tag))->filter() as $tag)
                                <span>{{ $tag }} <i class="fas fa-times"></i></span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="quiz-builder-thumbnail-block">
                    <label>{{ __('Quiz Thumbnail') }}</label>
                    <div class="quiz-builder-thumbnail-grid">
                        <label class="quiz-builder-thumbnail-dropzone" for="thumbnail">
                            <input type="file" id="thumbnail" name="thumbnail" class="visually-hidden" accept="image/*">
                            <div class="quiz-builder-thumbnail-dropzone__icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <strong>{{ __('Click to upload image') }}</strong>
                            <span>{{ __('or drag and drop') }}</span>
                            <small>{{ __('Recommended size: 1200 x 675px') }}</small>
                        </label>

                        <div class="quiz-builder-thumbnail-preview {{ $thumbnailUrl ? '' : 'is-empty' }}">
                            @if ($thumbnailUrl)
                                <img src="{{ $thumbnailUrl }}" alt="{{ $sampleTitle }}">
                            @else
                                <div class="quiz-builder-thumbnail-preview__empty">
                                    <i class="fas fa-image"></i>
                                    <span>{{ __('This image will be displayed on the quiz card.') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <aside class="quiz-builder-card quiz-builder-step-one__tips">
                <h5>{{ __('Quick Tips') }}</h5>
                <div class="quiz-builder-tips">
                    <article class="quiz-builder-tip">
                        <div class="quiz-builder-tip__icon"><i class="fas fa-font"></i></div>
                        <div class="quiz-builder-tip__copy">
                            <strong>{{ __('Create clear titles') }}</strong>
                            <p>{{ __('Use descriptive titles that help students understand the quiz content.') }}</p>
                        </div>
                    </article>
                    <article class="quiz-builder-tip">
                        <div class="quiz-builder-tip__icon"><i class="fas fa-list-ul"></i></div>
                        <div class="quiz-builder-tip__copy">
                            <strong>{{ __('Add a detailed description') }}</strong>
                            <p>{{ __('Explain what students will learn or be tested on in this quiz.') }}</p>
                        </div>
                    </article>
                    <article class="quiz-builder-tip">
                        <div class="quiz-builder-tip__icon"><i class="fas fa-tag"></i></div>
                        <div class="quiz-builder-tip__copy">
                            <strong>{{ __('Use relevant tags') }}</strong>
                            <p>{{ __('Tags help students find your quiz easily when browsing.') }}</p>
                        </div>
                    </article>
                    <article class="quiz-builder-tip">
                        <div class="quiz-builder-tip__icon"><i class="far fa-image"></i></div>
                        <div class="quiz-builder-tip__copy">
                            <strong>{{ __('Eye-catching thumbnail') }}</strong>
                            <p>{{ __('A good thumbnail increases your quiz visibility and engagement.') }}</p>
                        </div>
                    </article>
                </div>
            </aside>
        </div>

        <div class="quiz-builder-step-one__footer">
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            <button type="button" class="btn btn-primary ms-auto" data-next-step>
                <span>{{ __('Next: Add Questions') }}</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>

    <div class="quiz-builder-panel" data-step-panel="2">
        @error('questions')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror

        <div class="quiz-builder-question-stage">
            <div class="quiz-builder-questions-card">
                <div class="quiz-builder-questions-card__header">
                    <div>
                        <h5>{{ __('Quiz Questions') }} <span id="quiz-question-count">({{ count($questions) }})</span></h5>
                        <small>{{ __('Questions are listed in the order students will see them.') }}</small>
                    </div>
                    <div class="quiz-builder-questions-card__actions">
                        <button type="button" class="quiz-builder-inline-action" id="add-question-btn-inline">
                            <i class="fas fa-plus"></i>
                            <span>{{ __('Add Question') }}</span>
                        </button>
                    </div>
                </div>
                <div id="question-list">
                    @foreach ($questions as $questionIndex => $question)
                        @include('partials.product-quiz-question-card', ['question' => $question, 'questionIndex' => $questionIndex])
                    @endforeach
                </div>
                <button type="button" class="quiz-builder-add-question-slab" id="add-question-slab">
                    <i class="far fa-plus-circle"></i>
                    <span>{{ __('Add Another Question') }}</span>
                </button>
            </div>

            <aside class="quiz-builder-side-card">
                <h5>{{ __('Question Types') }}</h5>
                <div class="quiz-builder-question-types">
                    <article class="quiz-builder-question-type">
                        <div class="quiz-builder-question-type__icon"><i class="fas fa-list-ul"></i></div>
                        <div class="quiz-builder-question-type__copy">
                            <strong>{{ __('Multiple Choice') }}</strong>
                            <p>{{ __('Students select one correct answer from multiple options.') }}</p>
                        </div>
                    </article>
                    <article class="quiz-builder-question-type">
                        <div class="quiz-builder-question-type__icon"><i class="fas fa-check-double"></i></div>
                        <div class="quiz-builder-question-type__copy">
                            <strong>{{ __('True / False') }}</strong>
                            <p>{{ __('Students choose between true or false.') }}</p>
                        </div>
                    </article>
                    <article class="quiz-builder-question-type">
                        <div class="quiz-builder-question-type__icon"><i class="far fa-comment-dots"></i></div>
                        <div class="quiz-builder-question-type__copy">
                            <strong>{{ __('Short Answer') }}</strong>
                            <p>{{ __('Students type a short text answer.') }}</p>
                        </div>
                    </article>
                    <article class="quiz-builder-question-type">
                        <div class="quiz-builder-question-type__icon"><i class="fas fa-minus"></i></div>
                        <div class="quiz-builder-question-type__copy">
                            <strong>{{ __('Fill in the Blank') }}</strong>
                            <p>{{ __('Students fill in the missing words.') }}</p>
                        </div>
                    </article>
                    <article class="quiz-builder-question-type">
                        <div class="quiz-builder-question-type__icon"><i class="fas fa-circle-nodes"></i></div>
                        <div class="quiz-builder-question-type__copy">
                            <strong>{{ __('Matching') }}</strong>
                            <p>{{ __('Students match items from two lists.') }}</p>
                        </div>
                    </article>
                </div>

                <div class="mt-4">
                    <h6>{{ __('Tips') }}</h6>
                    <ul class="quiz-builder-tips-list">
                        <li>{{ __('Click on a question to edit it.') }}</li>
                        <li>{{ __('Drag and drop to reorder questions.') }}</li>
                        <li>{{ __('Make sure your questions are clear and have one correct answer.') }}</li>
                    </ul>
                </div>
            </aside>
        </div>

        <div class="quiz-builder-actions">
            <button type="button" class="btn btn-secondary" data-prev-step>{{ __('Back to Basic Info') }}</button>
            <button type="button" class="btn btn-primary ms-auto" data-next-step>{{ __('Next: Quiz Settings') }}</button>
        </div>
    </div>

    <div class="quiz-builder-panel" data-step-panel="3">
        <div class="quiz-builder-settings-wrap">
            <section class="quiz-builder-settings-card">
                <h5>{{ __('General Settings') }}</h5>
                <div class="quiz-builder-field-grid">
                    <div class="quiz-builder-inline-field">
                        <div class="form-grp">
                            <label for="duration_minutes">{{ __('Time Limit') }}</label>
                            <input type="number" min="0" id="duration_minutes" name="duration_minutes" class="form-control" value="{{ old('duration_minutes', $quizFormData['duration_minutes'] ?? 30) }}">
                            <small>{{ __('Set 0 for no time limit.') }}</small>
                        </div>
                        <div class="form-grp">
                            <label>&nbsp;</label>
                            <select class="form-select" disabled>
                                <option>{{ __('Minutes') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grp">
                        <label for="pass_mark">{{ __('Passing Score (%)') }} <span class="text-danger">*</span></label>
                        <input type="number" min="0" id="pass_mark" name="pass_mark" class="form-control" value="{{ old('pass_mark', $quizFormData['pass_mark'] ?? 60) }}" required>
                        <small>{{ __('Minimum score required to pass the quiz.') }}</small>
                    </div>

                    <div class="form-grp">
                        <label for="attempt_limit">{{ __('Attempts Allowed') }} <span class="text-danger">*</span></label>
                        <input type="number" min="1" id="attempt_limit" name="attempt_limit" class="form-control" value="{{ old('attempt_limit', $quizFormData['attempt_limit'] ?? 1) }}" required>
                        <small>{{ __('Number of attempts a student is allowed.') }}</small>
                    </div>

                    <div class="form-grp">
                        <label>{{ __('Question Order') }}</label>
                        <div class="quiz-builder-radio-group">
                            <label><input type="radio" name="question_order" value="random" @checked($selectedQuestionOrder === 'random')> {{ __('Random') }}</label>
                            <label><input type="radio" name="question_order" value="fixed" @checked($selectedQuestionOrder === 'fixed')> {{ __('Fixed') }}</label>
                        </div>
                        <small>{{ __('Randomize questions for each attempt.') }}</small>
                    </div>

                    <div class="form-grp">
                        <label>{{ __('Show Results') }}</label>
                        <div class="quiz-builder-radio-group">
                            <label><input type="radio" name="show_results" value="immediately" @checked($selectedShowResults === 'immediately')> {{ __('Immediately') }}</label>
                            <label><input type="radio" name="show_results" value="after_attempt" @checked($selectedShowResults === 'after_attempt')> {{ __('After Attempt') }}</label>
                        </div>
                        <small>{{ __('When to show quiz results to students.') }}</small>
                    </div>

                    <div class="quiz-builder-question-settings__footer">
                        <div class="quiz-builder-question-settings__item">
                            <div>
                                <strong>{{ __('Allow Review') }}</strong>
                                <p>{{ __('Allow students to review answers after submission.') }}</p>
                            </div>
                            <label class="quiz-builder-switch quiz-builder-switch--small">
                                <input type="checkbox" name="allow_review" value="1" @checked(old('allow_review', $quizFormData['allow_review'] ?? true))>
                                <span></span>
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <section class="quiz-builder-settings-card">
                <h5>{{ __('Question Settings') }}</h5>
                <div class="quiz-builder-question-settings">
                    <div class="quiz-builder-question-settings__item">
                        <div>
                            <strong>{{ __('Show Correct Answers') }}</strong>
                            <p>{{ __('Show correct answers after quiz submission.') }}</p>
                        </div>
                        <label class="quiz-builder-switch quiz-builder-switch--small">
                            <input type="checkbox" name="show_correct_answers" value="1" @checked(old('show_correct_answers', $quizFormData['show_correct_answers'] ?? true))>
                            <span></span>
                        </label>
                    </div>
                    <div class="quiz-builder-question-settings__item">
                        <div>
                            <strong>{{ __('Shuffle Options') }}</strong>
                            <p>{{ __('Shuffle answer options for each question.') }}</p>
                        </div>
                        <label class="quiz-builder-switch quiz-builder-switch--small">
                            <input type="checkbox" name="shuffle_options" value="1" @checked(old('shuffle_options', $quizFormData['shuffle_options'] ?? true))>
                            <span></span>
                        </label>
                    </div>
                    <div class="quiz-builder-question-settings__item">
                        <div>
                            <strong>{{ __('One Question Per Page') }}</strong>
                            <p>{{ __('Show one question per page.') }}</p>
                        </div>
                        <label class="quiz-builder-switch quiz-builder-switch--small">
                            <input type="checkbox" name="one_question_per_page" value="1" @checked(old('one_question_per_page', $quizFormData['one_question_per_page'] ?? false))>
                            <span></span>
                        </label>
                    </div>
                    <div class="quiz-builder-question-settings__item">
                        <div>
                            <strong>{{ __('Show Progress Bar') }}</strong>
                            <p>{{ __('Display progress bar during the quiz.') }}</p>
                        </div>
                        <label class="quiz-builder-switch quiz-builder-switch--small">
                            <input type="checkbox" name="show_progress_bar" value="1" @checked(old('show_progress_bar', $quizFormData['show_progress_bar'] ?? true))>
                            <span></span>
                        </label>
                    </div>
                    <div class="quiz-builder-question-numbering">
                        <div>
                            <strong>{{ __('Question Numbering') }}</strong>
                            <small>{{ __('How questions are numbered in the quiz.') }}</small>
                        </div>
                        <select id="question_numbering" name="question_numbering" class="form-select">
                            <option value="continuous" @selected(old('question_numbering', $quizFormData['question_numbering'] ?? 'continuous') === 'continuous')>{{ __('Continuous (1, 2, 3...)') }}</option>
                            <option value="per_page" @selected(old('question_numbering', $quizFormData['question_numbering'] ?? 'continuous') === 'per_page')>{{ __('Reset per page') }}</option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="quiz-builder-settings-card quiz-builder-settings-card--summary">
                <h5>{{ __('Quiz Summary') }}</h5>
                <div class="quiz-builder-kv" id="quiz-preview-overview"></div>
            </section>

            <section class="quiz-builder-settings-card quiz-builder-settings-card--tips">
                <h5>{{ __('Tips') }}</h5>
                <ul class="quiz-builder-step3-tips">
                    <li>{{ __('Set an appropriate time limit for your students.') }}</li>
                    <li>{{ __('Keep passing score fair and achievable.') }}</li>
                    <li>{{ __('Allow multiple attempts for better learning.') }}</li>
                    <li>{{ __('Review settings before publishing your quiz.') }}</li>
                </ul>
            </section>
        </div>

        <div class="quiz-builder-actions">
            <button type="button" class="btn btn-secondary" data-prev-step>{{ __('Back to Questions') }}</button>
            <button type="button" class="btn btn-primary" data-next-step>{{ __('Next: Review') }}</button>
        </div>
    </div>

    <div class="quiz-builder-panel" data-step-panel="4">
        <div class="quiz-builder-review-grid">
            <div class="quiz-builder-review-stack">
                <section class="quiz-builder-review-card">
                    <div class="quiz-builder-review-card__header">
                        <h5>{{ __('Basic Information') }}</h5>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-step-jump="1">
                            <i class="far fa-edit"></i>
                            <span>{{ __('Edit') }}</span>
                        </button>
                    </div>
                    <div class="quiz-builder-review-basic" id="quiz-review-basic"></div>
                </section>

                <section class="quiz-builder-review-card">
                    <div class="quiz-builder-review-card__header">
                        <h5>{{ __('Questions') }} <span id="quiz-review-question-count">({{ count($questions) }})</span></h5>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-step-jump="2">
                            <i class="far fa-edit"></i>
                            <span>{{ __('Edit Questions') }}</span>
                        </button>
                    </div>
                    <table class="quiz-builder-questions-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Question') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Points') }}</th>
                            </tr>
                        </thead>
                        <tbody id="quiz-review-questions"></tbody>
                    </table>
                    <div class="quiz-builder-question-note" id="quiz-review-more-questions"></div>
                </section>

                <section class="quiz-builder-review-card">
                    <div class="quiz-builder-review-card__header">
                        <h5>{{ __('Settings') }}</h5>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-step-jump="3">
                            <i class="far fa-edit"></i>
                            <span>{{ __('Edit Settings') }}</span>
                        </button>
                    </div>
                    <div class="quiz-builder-summary-list quiz-builder-summary-list--review" id="quiz-review-settings"></div>
                </section>
            </div>

            <div class="quiz-builder-right-summary">
                <section class="quiz-builder-review-card">
                    <h5 class="mb-3">{{ __('Quiz Summary') }}</h5>
                    <div class="quiz-builder-summary-list" id="quiz-review-checklist"></div>
                </section>
                <section class="quiz-builder-ready-card">
                    <div class="quiz-builder-ready-card__icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h5>{{ $isEditing ? __('Ready to Update?') : __('Ready to Send for Review?') }}</h5>
                    <p>{{ $isEditing ? __('Please review all details carefully before updating this quiz for admin review.') : __('Please review all details carefully. Once approved, students will be able to access this quiz.') }}</p>
                </section>
            </div>
        </div>

        <div class="quiz-builder-actions">
            <button type="button" class="btn btn-secondary" data-prev-step>{{ __('Back to Settings') }}</button>
            <button type="submit" class="btn btn-primary ms-auto" id="publish-quiz-btn">
                <span>{{ $isEditing ? __('Update & Resubmit') : __('Send for Review') }}</span>
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

    </form>

<template id="question-card-template">
    @include('partials.product-quiz-question-card', ['question' => ['prompt' => '', 'question_type' => 'single_choice', 'marks' => 1, 'correct_text_answer' => '', 'options' => [['option_text' => '', 'is_correct' => false], ['option_text' => '', 'is_correct' => false]]], 'questionIndex' => '__INDEX__'])
</template>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('quiz-builder-form');
        const questionList = document.getElementById('question-list');
        const template = document.getElementById('question-card-template');
        const addQuestionBtn = document.getElementById('add-question-btn');
        const addQuestionBtnInline = document.getElementById('add-question-btn-inline');
        const addQuestionSlab = document.getElementById('add-question-slab');
        const questionCountEl = document.getElementById('quiz-question-count');
        const saveModeInput = document.getElementById('quiz-save-mode');
        const statusField = document.getElementById('status');
        const stepPanels = [...document.querySelectorAll('[data-step-panel]')];
        const stepIndicators = [...document.querySelectorAll('[data-step-indicator]')];
        const previewStepButton = document.querySelector('[data-go-step="4"]');
        const publishButton = document.getElementById('publish-quiz-btn');
        const reviewBasic = document.getElementById('quiz-review-basic');
        const reviewQuestions = document.getElementById('quiz-review-questions');
        const reviewMoreQuestions = document.getElementById('quiz-review-more-questions');
        const reviewSettings = document.getElementById('quiz-review-settings');
        const reviewQuestionCount = document.getElementById('quiz-review-question-count');
        const pageTitle = document.getElementById('quiz-builder-page-title');
        const pageDescription = document.getElementById('quiz-builder-page-description');
        let currentStep = {{ $initialStep }};

        const escapeHtml = (value) => {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        };

        const getStatusLabel = () => {
            switch (statusField?.value) {
                case 'active':
                    return '{{ __('Active') }}';
                case 'inactive':
                    return '{{ __('Inactive') }}';
                default:
                    return '{{ __('Draft') }}';
            }
        };

        const getSelectLabel = (id, fallback = '{{ __('Not selected') }}') => {
            const field = document.getElementById(id);
            if (!field) {
                return fallback;
            }

            if (field.selectedOptions && field.selectedOptions[0]) {
                return field.selectedOptions[0].text;
            }

            return field.value || fallback;
        };

        const classGradeLabel = document.querySelector('label[for="class_grade"]');
        const classGradeSelect = document.getElementById('class_grade');
        const schoolOfLevelPattern = /tvet|university|college|certificate|diploma|undergraduate|tertiary|higher education/i;
        const classGradesByLevel = @json($quizClassGradesByLevel ?? []);
        const subjectsByLevel = @json($quizSubjectsByLevel ?? []);
        const subjectSelect = document.getElementById('subject');
        const getClassGradeLabel = (educationLevel) => schoolOfLevelPattern.test(String(educationLevel || ''))
            ? @json(__('School of'))
            : @json(__('Class / Grade'));
        const resolveClassGrades = (educationLevel) => {
            return classGradesByLevel[educationLevel]
                ?? classGradesByLevel.default
                ?? [];
        };
        const populateClassGradeOptions = (educationLevel, preferredValue = '') => {
            if (!classGradeSelect) {
                return;
            }

            const options = resolveClassGrades(educationLevel);
            const previousValue = preferredValue || classGradeSelect.value;

            classGradeSelect.innerHTML = '';

            options.forEach((option) => {
                const optionEl = document.createElement('option');
                optionEl.value = option;
                optionEl.textContent = option;
                optionEl.selected = previousValue === option;
                classGradeSelect.appendChild(optionEl);
            });

            if (options.includes(previousValue)) {
                classGradeSelect.value = previousValue;
            } else if (previousValue) {
                classGradeSelect.appendChild(new Option(previousValue, previousValue, true, true));
                classGradeSelect.value = previousValue;
            } else if (options.length) {
                classGradeSelect.value = options[0];
            }
        };
        const populateSubjectOptions = (educationLevel, preferredValue = '') => {
            if (!subjectSelect) {
                return;
            }

            const options = subjectsByLevel[educationLevel] ?? @json($metadataOptions['subjects'] ?? []);
            const previousValue = preferredValue || subjectSelect.value;

            subjectSelect.innerHTML = '';
            options.forEach((option) => {
                const optionEl = document.createElement('option');
                optionEl.value = option;
                optionEl.textContent = option;
                optionEl.selected = previousValue === option;
                subjectSelect.appendChild(optionEl);
            });

            if (options.includes(previousValue)) {
                subjectSelect.value = previousValue;
            } else if (previousValue) {
                subjectSelect.appendChild(new Option(previousValue, previousValue, true, true));
                subjectSelect.value = previousValue;
            } else {
                subjectSelect.value = options[0] || '';
            }
        };
        const updateClassGradeLabel = () => {
            if (!classGradeLabel) {
                return;
            }

            classGradeLabel.childNodes[0].textContent = `${getClassGradeLabel(getSelectLabel('education_level', ''))} `;
        };

        const getTagsValue = () => {
            const tags = document.getElementById('tags')?.value?.trim() || '';
            return tags ? tags.split(',').map((tag) => tag.trim()).filter(Boolean) : [];
        };

        const getQuestionTypeLabel = (value) => value === 'short_answer'
            ? '{{ __('Short Answer') }}'
            : '{{ __('Multiple Choice') }}';

        const getQuestionMarksLabel = (value) => {
            const marks = Number(value || 1);
            return marks === 1 ? '{{ __('1 point') }}' : `${marks} {{ __('points') }}`;
        };

        const syncOptionRowState = (row) => {
            const checkbox = row.querySelector('[data-option-field="is_correct"]');
            row.classList.toggle('is-correct', !!checkbox?.checked);
        };

        const refreshQuestionIndexes = () => {
            [...questionList.querySelectorAll('.quiz-question-card')].forEach((card, questionIndex) => {
                card.dataset.questionIndex = questionIndex;
                card.querySelector('.question-number').textContent = questionIndex + 1;

                card.querySelectorAll('[data-field]').forEach((field) => {
                    const fieldName = field.dataset.field;
                    field.name = `questions[${questionIndex}][${fieldName}]`;
                });

                card.querySelectorAll('.question-id-field').forEach((field) => {
                    field.name = `questions[${questionIndex}][id]`;
                });

                card.querySelectorAll('.question-option-row').forEach((row, optionIndex) => {
                    const optionLabel = row.querySelector('.quiz-question-card__option-label');
                    if (optionLabel) {
                        optionLabel.textContent = String.fromCharCode(65 + optionIndex);
                    }

                    row.querySelectorAll('[data-option-field]').forEach((field) => {
                        const optionField = field.dataset.optionField;
                        field.name = `questions[${questionIndex}][options][${optionIndex}][${optionField}]`;
                    });

                    row.querySelectorAll('.option-correct-hidden').forEach((field) => {
                        field.name = `questions[${questionIndex}][options][${optionIndex}][is_correct]`;
                    });

                    row.querySelectorAll('.option-id-field').forEach((field) => {
                        field.name = `questions[${questionIndex}][options][${optionIndex}][id]`;
                    });

                    syncOptionRowState(row);
                });

                const promptField = card.querySelector('[data-field="prompt"]');
                const promptPreview = card.querySelector('.quiz-question-card__prompt-preview');
                if (promptField && promptPreview) {
                    promptPreview.textContent = promptField.value?.trim() || '{{ __('New question') }}';
                }

                const typeField = card.querySelector('.question-type-select');
                const typePreview = card.querySelector('.quiz-question-card__type-preview');
                if (typeField && typePreview) {
                    typePreview.textContent = getQuestionTypeLabel(typeField.value);
                }

                const marksField = card.querySelector('[data-field="marks"]');
                const marksPreview = card.querySelector('.quiz-question-card__marks-preview');
                if (marksField && marksPreview) {
                    marksPreview.textContent = getQuestionMarksLabel(marksField.value);
                }

                const promptCounter = card.querySelector('.quiz-question-card__counter');
                if (promptField && promptCounter) {
                    promptCounter.textContent = `${(promptField.value || '').length}/1000`;
                }
            });

            if (questionCountEl) {
                questionCountEl.textContent = `(${questionList.querySelectorAll('.quiz-question-card').length})`;
            }

            renderPreview();
            renderReview();
        };

        const setQuestionBodyState = (card, expanded) => {
            card.classList.toggle('is-expanded', expanded);
            card.classList.toggle('is-collapsed', !expanded);

            const body = card.querySelector('.quiz-question-card__body');
            if (body) {
                body.hidden = !expanded;
            }

            const toggle = card.querySelector('[data-question-toggle]');
            if (toggle) {
                toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            }
        };

        const toggleQuestionType = (card) => {
            const typeSelect = card.querySelector('.question-type-select');
            const optionWrap = card.querySelector('.single-choice-fields');
            const shortAnswerWrap = card.querySelector('.short-answer-fields');
            const isChoice = !typeSelect || typeSelect.value === 'single_choice';

            if (optionWrap) {
                optionWrap.style.display = isChoice ? '' : 'none';
            }

            if (shortAnswerWrap) {
                shortAnswerWrap.style.display = isChoice ? 'none' : '';
            }
        };

        const bindOptionRow = (row) => {
            const removeBtn = row.querySelector('.remove-option-btn');
            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    row.remove();
                    refreshQuestionIndexes();
                });
            }

            const checkbox = row.querySelector('[data-option-field="is_correct"]');
            checkbox?.addEventListener('change', function() {
                syncOptionRowState(row);
                refreshQuestionIndexes();
            });

            syncOptionRowState(row);
        };

        const bindQuestionCard = (card) => {
            const summaryToggle = card.querySelector('[data-question-toggle]');
            const editToggle = card.querySelector('.question-edit-toggle-btn');
            const caretToggle = card.querySelector('.question-toggle-caret');

            summaryToggle?.addEventListener('click', function() {
                setQuestionBodyState(card, card.classList.contains('is-collapsed'));
            });

            summaryToggle?.addEventListener('keydown', function(event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    setQuestionBodyState(card, card.classList.contains('is-collapsed'));
                }
            });

            editToggle?.addEventListener('click', function(event) {
                event.stopPropagation();
                setQuestionBodyState(card, true);
            });

            caretToggle?.addEventListener('click', function(event) {
                event.stopPropagation();
                setQuestionBodyState(card, card.classList.contains('is-collapsed'));
            });

            card.querySelectorAll('[data-field="prompt"], [data-field="marks"], [data-field="correct_text_answer"], [data-field="question_type"], [data-option-field="option_text"], [data-option-field="is_correct"]').forEach((field) => {
                field.addEventListener('input', refreshQuestionIndexes);
                field.addEventListener('change', refreshQuestionIndexes);
            });

            card.querySelector('.remove-question-btn')?.addEventListener('click', function() {
                card.remove();
                refreshQuestionIndexes();
            });

            card.querySelector('.question-type-select')?.addEventListener('change', function() {
                toggleQuestionType(card);
                refreshQuestionIndexes();
            });

            card.querySelector('.add-option-btn').addEventListener('click', function() {
                const optionsWrap = card.querySelector('.options-list');
                const questionIndex = card.dataset.questionIndex || 0;
                const optionIndex = optionsWrap.querySelectorAll('.question-option-row').length;
                const row = document.createElement('div');
                row.className = 'question-option-row quiz-question-card__option-row';
                row.innerHTML = `
                    <button type="button" class="quiz-question-card__option-drag" aria-label="{{ __('Drag option') }}" title="{{ __('Drag option') }}">
                        <i class="fas fa-grip-vertical"></i>
                    </button>
                    <span class="quiz-question-card__option-label">${String.fromCharCode(65 + optionIndex)}</span>
                    <input type="hidden" class="option-id-field" name="questions[${questionIndex}][options][${optionIndex}][id]" value="">
                    <input type="text" class="form-control quiz-question-card__option-input" data-option-field="option_text" name="questions[${questionIndex}][options][${optionIndex}][option_text]" placeholder="{{ __('Option text') }}">
                    <span class="quiz-question-card__correct-pill">
                        <i class="fas fa-circle"></i>
                        <span>{{ __('Correct answer') }}</span>
                    </span>
                    <label class="quiz-question-card__correct-toggle">
                        <input type="hidden" class="option-correct-hidden" name="questions[${questionIndex}][options][${optionIndex}][is_correct]" value="0">
                        <input class="form-check-input" type="checkbox" data-option-field="is_correct" name="questions[${questionIndex}][options][${optionIndex}][is_correct]" value="1">
                        <span class="quiz-question-card__correct-dot"></span>
                    </label>
                    <button type="button" class="btn btn-link text-danger p-0 remove-option-btn" aria-label="{{ __('Remove option') }}">
                        <i class="far fa-trash-alt"></i>
                    </button>
                `;
                optionsWrap.appendChild(row);
                bindOptionRow(row);
                refreshQuestionIndexes();
            });

            card.querySelectorAll('.question-option-row').forEach((row) => bindOptionRow(row));
            toggleQuestionType(card);
            setQuestionBodyState(card, card.classList.contains('is-expanded'));
        };

        const getQuestions = () => {
            return [...questionList.querySelectorAll('.quiz-question-card')].map((card, index) => {
                const type = card.querySelector('.question-type-select')?.value || 'single_choice';
                const options = [...card.querySelectorAll('.question-option-row')].map((row) => ({
                    text: row.querySelector('[data-option-field="option_text"]')?.value?.trim() || '',
                    isCorrect: !!row.querySelector('[data-option-field="is_correct"]')?.checked,
                })).filter((option) => option.text !== '');

                return {
                    index: index + 1,
                    prompt: card.querySelector('[data-field="prompt"]')?.value?.trim() || '',
                    type,
                    marks: card.querySelector('[data-field="marks"]')?.value || '0',
                    answerKey: card.querySelector('[data-field="correct_text_answer"]')?.value?.trim() || '',
                    options,
                };
            });
        };

        const getOverviewItems = () => {
            const questions = getQuestions();
            const totalMarks = questions.reduce((sum, question) => sum + Number(question.marks || 0), 0);

            return [
                ['{{ __('Title') }}', document.getElementById('title')?.value?.trim() || '{{ __('Untitled quiz') }}'],
                ['{{ __('Education Level') }}', getSelectLabel('education_level')],
                [getClassGradeLabel(getSelectLabel('education_level', '')), getSelectLabel('class_grade')],
                ['{{ __('Subject') }}', getSelectLabel('subject')],
                ['{{ __('Exam Category') }}', getSelectLabel('exam_category')],
                ['{{ __('Topic') }}', document.getElementById('topic')?.value?.trim() || '{{ __('Not selected') }}'],
                ['{{ __('Duration') }}', (document.getElementById('duration_minutes')?.value || '{{ __('No limit') }}') + (document.getElementById('duration_minutes')?.value ? ' {{ __('minutes') }}' : '')],
                ['{{ __('Attempt Limit') }}', document.getElementById('attempt_limit')?.value || '1'],
                ['{{ __('Pass Mark') }}', document.getElementById('pass_mark')?.value || '0'],
                ['{{ __('Total Questions') }}', document.getElementById('total_questions')?.value || '20'],
                ['{{ __('Questions') }}', String(questions.length)],
                ['{{ __('Total Marks') }}', String(totalMarks)],
                ['{{ __('Question Order') }}', document.querySelector('input[name="question_order"]:checked')?.closest('label')?.innerText?.trim() || '{{ __('Random') }}'],
                ['{{ __('Show Results') }}', document.querySelector('input[name="show_results"]:checked')?.closest('label')?.innerText?.trim() || '{{ __('Immediately') }}'],
                ['{{ __('Show Correct Answers') }}', document.querySelector('input[name="show_correct_answers"]')?.checked ? '{{ __('Yes') }}' : '{{ __('No') }}'],
                ['{{ __('Allow Review') }}', document.querySelector('input[name="allow_review"]')?.checked ? '{{ __('Yes') }}' : '{{ __('No') }}'],
                ['{{ __('Shuffle Options') }}', document.querySelector('input[name="shuffle_options"]')?.checked ? '{{ __('Yes') }}' : '{{ __('No') }}'],
                ['{{ __('One Question per Page') }}', document.querySelector('input[name="one_question_per_page"]')?.checked ? '{{ __('Yes') }}' : '{{ __('No') }}'],
                ['{{ __('Show Progress Bar') }}', document.querySelector('input[name="show_progress_bar"]')?.checked ? '{{ __('Yes') }}' : '{{ __('No') }}'],
                ['{{ __('Question Numbering') }}', document.getElementById('question_numbering')?.selectedOptions?.[0]?.text || '{{ __('Continuous (1, 2, 3...)') }}'],
                ['{{ __('Status') }}', getStatusLabel()],
            ];
        };

        const renderKeyValueList = (container, items) => {
            container.innerHTML = items.map(([label, value]) => `
                <div class="quiz-builder-kv-item">
                    <span>${escapeHtml(label)}</span>
                    <strong>${escapeHtml(value)}</strong>
                </div>
            `).join('');
        };

        const renderPreview = () => {
            const overview = document.getElementById('quiz-preview-overview');
            const previewQuestions = document.getElementById('quiz-preview-questions');
            const questions = getQuestions();

            renderKeyValueList(overview, getOverviewItems());

            if (!questions.length) {
                previewQuestions.innerHTML = `<p class="mb-0 text-muted">{{ __('No questions added yet.') }}</p>`;
                return;
            }

            previewQuestions.innerHTML = questions.map((question) => {
                const optionsHtml = question.type === 'single_choice'
                    ? `<ul class="quiz-builder-preview-options">${question.options.map((option) => `<li>${escapeHtml(option.text)}${option.isCorrect ? ' <strong>({{ __('Correct') }})</strong>' : ''}</li>`).join('')}</ul>`
                    : `<p class="mb-0"><strong>{{ __('Answer Key') }}:</strong> ${escapeHtml(question.answerKey || '{{ __('Not set') }}')}</p>`;

                return `
                    <div class="quiz-builder-preview-question">
                        <div class="d-flex justify-content-between gap-3">
                            <strong>{{ __('Question') }} ${question.index}</strong>
                            <span>{{ __('Marks') }}: ${escapeHtml(question.marks)}</span>
                        </div>
                        <p class="mt-2 mb-2">${escapeHtml(question.prompt || '{{ __('No prompt yet.') }}')}</p>
                        <small class="d-block text-muted text-capitalize">${escapeHtml(question.type.replace('_', ' '))}</small>
                        ${optionsHtml}
                    </div>
                `;
            }).join('');
        };

        const renderReview = () => {
            const reviewChecklist = document.getElementById('quiz-review-checklist');
            const questions = getQuestions();
            const activeQuestions = questions.filter((question) => question.prompt !== '');
            const thumbnailSelected = document.getElementById('thumbnail')?.files?.length ? '{{ __('New image selected') }}' : '{{ !empty($product->thumbnail) ? __('Existing image kept') : __('No image selected') }}';
            const totalMarks = questions.reduce((sum, question) => sum + Number(question.marks || 0), 0);
            const visibleQuestions = questions.slice(0, 5);

            if (reviewBasic) {
                reviewBasic.innerHTML = `
                    <div class="quiz-builder-review-basic__item"><span>{{ __('Quiz Title') }}</span><strong>${escapeHtml(document.getElementById('title')?.value?.trim() || '{{ __('Untitled quiz') }}')}</strong></div>
                    <div class="quiz-builder-review-basic__item"><span>{{ __('Description') }}</span><strong>${escapeHtml(document.getElementById('description')?.value?.trim() || '{{ __('Not provided') }}')}</strong></div>
                    <div class="quiz-builder-review-basic__item"><span>{{ __('Education Level') }}</span><strong>${escapeHtml(getSelectLabel('education_level'))}</strong></div>
                    <div class="quiz-builder-review-basic__item"><span>${escapeHtml(getClassGradeLabel(getSelectLabel('education_level', '')))}</span><strong>${escapeHtml(getSelectLabel('class_grade'))}</strong></div>
                    <div class="quiz-builder-review-basic__item"><span>{{ __('Subject') }}</span><strong>${escapeHtml(getSelectLabel('subject'))}</strong></div>
                    <div class="quiz-builder-review-basic__item"><span>{{ __('Exam Category') }}</span><strong>${escapeHtml(getSelectLabel('exam_category'))}</strong></div>
                    <div class="quiz-builder-review-basic__item"><span>{{ __('Topic') }}</span><strong>${escapeHtml(document.getElementById('topic')?.value?.trim() || '{{ __('Not selected') }}')}</strong></div>
                    <div class="quiz-builder-review-basic__item"><span>{{ __('Tags') }}</span><strong>${getTagsValue().length ? `<span class="quiz-builder-review-tags">${getTagsValue().map((tag) => `<span>${escapeHtml(tag)}</span>`).join('')}</span>` : '{{ __('No tags added') }}'}</strong></div>
                `;
            }

            if (reviewQuestions) {
                reviewQuestions.innerHTML = visibleQuestions.map((question, index) => `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${escapeHtml(question.prompt || '{{ __('No prompt yet.') }}')}</td>
                        <td>${escapeHtml(question.type.replace('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase()))}</td>
                        <td>${escapeHtml(question.marks)}</td>
                    </tr>
                `).join('');
            }

            if (reviewMoreQuestions) {
                reviewMoreQuestions.textContent = questions.length > 5
                    ? `... and ${questions.length - 5} more questions`
                    : '';
            }

            if (reviewQuestionCount) {
                reviewQuestionCount.textContent = `(${questions.length})`;
            }

            if (reviewSettings) {
                reviewSettings.innerHTML = `
                    <div class="quiz-builder-summary-item">
                        <div class="quiz-builder-summary-item__icon"><i class="fas fa-clock"></i></div>
                        <div class="quiz-builder-summary-item__copy"><span>{{ __('Time Limit') }}</span><strong>${escapeHtml(document.getElementById('duration_minutes')?.value ? `${document.getElementById('duration_minutes')?.value} {{ __('Minutes') }}` : '{{ __('No limit') }}')}</strong></div>
                    </div>
                    <div class="quiz-builder-summary-item">
                        <div class="quiz-builder-summary-item__icon"><i class="fas fa-percent"></i></div>
                        <div class="quiz-builder-summary-item__copy"><span>{{ __('Passing Score') }}</span><strong>${escapeHtml(document.getElementById('pass_mark')?.value || '0')}%</strong></div>
                    </div>
                    <div class="quiz-builder-summary-item">
                        <div class="quiz-builder-summary-item__icon"><i class="fas fa-redo"></i></div>
                        <div class="quiz-builder-summary-item__copy"><span>{{ __('Attempts Allowed') }}</span><strong>${escapeHtml(document.getElementById('attempt_limit')?.value || '1')}</strong></div>
                    </div>
                    <div class="quiz-builder-summary-item">
                        <div class="quiz-builder-summary-item__icon"><i class="fas fa-sort-amount-down"></i></div>
                        <div class="quiz-builder-summary-item__copy"><span>{{ __('Question Order') }}</span><strong>${escapeHtml(document.querySelector('input[name="question_order"]:checked')?.closest('label')?.innerText?.trim() || '{{ __('Random') }}')}</strong></div>
                    </div>
                    <div class="quiz-builder-summary-item">
                        <div class="quiz-builder-summary-item__icon"><i class="fas fa-eye"></i></div>
                        <div class="quiz-builder-summary-item__copy"><span>{{ __('Show Results') }}</span><strong>${escapeHtml(document.querySelector('input[name="show_results"]:checked')?.closest('label')?.innerText?.trim() || '{{ __('Immediately') }}')}</strong></div>
                    </div>
                    <div class="quiz-builder-summary-item">
                        <div class="quiz-builder-summary-item__icon"><i class="fas fa-check"></i></div>
                        <div class="quiz-builder-summary-item__copy"><span>{{ __('Review Answers') }}</span><strong>${document.querySelector('input[name="allow_review"]')?.checked ? '{{ __('Allowed') }}' : '{{ __('Disabled') }}'}</strong></div>
                    </div>
                    <div class="quiz-builder-summary-item">
                        <div class="quiz-builder-summary-item__icon"><i class="fas fa-sync"></i></div>
                        <div class="quiz-builder-summary-item__copy"><span>{{ __('Shuffle Options') }}</span><strong>${document.querySelector('input[name="shuffle_options"]')?.checked ? '{{ __('Enabled') }}' : '{{ __('Disabled') }}'}</strong></div>
                    </div>
                    <div class="quiz-builder-summary-item">
                        <div class="quiz-builder-summary-item__icon"><i class="fas fa-list-ol"></i></div>
                        <div class="quiz-builder-summary-item__copy"><span>{{ __('Question Numbering') }}</span><strong>${escapeHtml(document.getElementById('question_numbering')?.selectedOptions?.[0]?.text || '{{ __('Continuous (1, 2, 3...)') }}')}</strong></div>
                    </div>
                    <div class="quiz-builder-summary-item">
                        <div class="quiz-builder-summary-item__icon"><i class="fas fa-spinner"></i></div>
                        <div class="quiz-builder-summary-item__copy"><span>{{ __('Show Progress Bar') }}</span><strong>${document.querySelector('input[name="show_progress_bar"]')?.checked ? '{{ __('Enabled') }}' : '{{ __('Disabled') }}'}</strong></div>
                    </div>
                    <div class="quiz-builder-summary-item">
                        <div class="quiz-builder-summary-item__icon"><i class="fas fa-list"></i></div>
                        <div class="quiz-builder-summary-item__copy"><span>{{ __('Total Questions') }}</span><strong>${escapeHtml(document.getElementById('total_questions')?.value || '0')}</strong></div>
                    </div>
                    <div class="quiz-builder-summary-item">
                        <div class="quiz-builder-summary-item__icon"><i class="fas fa-star"></i></div>
                        <div class="quiz-builder-summary-item__copy"><span>{{ __('Total Points') }}</span><strong>${escapeHtml(String(totalMarks))}</strong></div>
                    </div>
                `;
            }

            if (reviewChecklist) {
                reviewChecklist.innerHTML = `
                    <div class="quiz-builder-summary-item">
                        <div class="quiz-builder-summary-item__icon"><i class="fas fa-list"></i></div>
                        <div class="quiz-builder-summary-item__copy"><span>{{ __('Total Questions') }}</span><strong>${escapeHtml(document.getElementById('total_questions')?.value || '0')}</strong></div>
                    </div>
                    <div class="quiz-builder-summary-item">
                        <div class="quiz-builder-summary-item__icon"><i class="fas fa-star"></i></div>
                        <div class="quiz-builder-summary-item__copy"><span>{{ __('Total Points') }}</span><strong>${escapeHtml(String(totalMarks))}</strong></div>
                    </div>
                    <div class="quiz-builder-summary-item">
                        <div class="quiz-builder-summary-item__icon"><i class="fas fa-clock"></i></div>
                        <div class="quiz-builder-summary-item__copy"><span>{{ __('Time Limit') }}</span><strong>${escapeHtml(document.getElementById('duration_minutes')?.value ? `${document.getElementById('duration_minutes')?.value} {{ __('Minutes') }}` : '{{ __('No limit') }}')}</strong></div>
                    </div>
                    <div class="quiz-builder-summary-item">
                        <div class="quiz-builder-summary-item__icon"><i class="fas fa-check"></i></div>
                        <div class="quiz-builder-summary-item__copy"><span>{{ __('Passing Score') }}</span><strong>${escapeHtml(document.getElementById('pass_mark')?.value || '0')}%</strong></div>
                    </div>
                    <div class="quiz-builder-summary-item">
                        <div class="quiz-builder-summary-item__icon"><i class="fas fa-redo"></i></div>
                        <div class="quiz-builder-summary-item__copy"><span>{{ __('Attempts Allowed') }}</span><strong>${escapeHtml(document.getElementById('attempt_limit')?.value || '1')}</strong></div>
                    </div>
                    <div class="quiz-builder-summary-item">
                        <div class="quiz-builder-summary-item__icon"><i class="fas fa-eye"></i></div>
                        <div class="quiz-builder-summary-item__copy"><span>{{ __('Show Results') }}</span><strong>${escapeHtml(document.querySelector('input[name="show_results"]:checked')?.closest('label')?.innerText?.trim() || '{{ __('Immediately') }}')}</strong></div>
                    </div>
                `;
            }
        };

        const updateStepper = () => {
            const stepCopy = {
                1: ['{{ __('Create New Quiz') }}', '{{ __('Create a new quiz for your students. Add questions, set the correct answers and configure quiz settings.') }}'],
                2: ['{{ __('Create New Quiz') }}', '{{ __('Add questions to your quiz. You can add multiple choice, true/false, short answer and more.') }}'],
                3: ['{{ __('Create New Quiz') }}', '{{ __('Configure quiz settings such as time limit, passing score, attempts and result preferences.') }}'],
                4: ['{{ __('Create New Quiz') }}', '{{ __('Review all details of your quiz before publishing. You can go back and edit anything if needed.') }}'],
            };

            if (pageTitle && pageDescription && stepCopy[currentStep]) {
                pageTitle.textContent = stepCopy[currentStep][0];
                pageDescription.textContent = stepCopy[currentStep][1];
            }

            stepPanels.forEach((panel) => {
                panel.classList.toggle('is-active', Number(panel.dataset.stepPanel) === currentStep);
            });

            stepIndicators.forEach((indicator) => {
                const stepNumber = Number(indicator.dataset.stepIndicator);
                indicator.classList.toggle('is-active', stepNumber === currentStep);
                indicator.classList.toggle('is-complete', stepNumber < currentStep);
            });

            if (currentStep === 3) {
                renderPreview();
            }

            if (currentStep === 4) {
                renderReview();
            }

        };

        const goToStep = (step) => {
            currentStep = Math.max(1, Math.min(4, step));
            updateStepper();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        };

        const addQuestion = function() {
            const questionIndex = questionList.querySelectorAll('.quiz-question-card').length;
            const html = template.innerHTML.replaceAll('__INDEX__', questionIndex);
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            const card = wrapper.firstElementChild;
            questionList.appendChild(card);
            bindQuestionCard(card);
            setQuestionBodyState(card, true);
            refreshQuestionIndexes();
            card.scrollIntoView({ behavior: 'smooth', block: 'start' });
        };

        addQuestionBtn?.addEventListener('click', addQuestion);
        addQuestionBtnInline?.addEventListener('click', addQuestion);
        addQuestionSlab?.addEventListener('click', addQuestion);

        questionList.querySelectorAll('.quiz-question-card').forEach((card) => bindQuestionCard(card));
        questionList.addEventListener('input', function() {
            renderPreview();
            renderReview();
        });
        questionList.addEventListener('change', function() {
            renderPreview();
            renderReview();
        });

        document.querySelectorAll('input, textarea, select').forEach((field) => {
            field.addEventListener('input', function() {
                updateClassGradeLabel();
                renderPreview();
                renderReview();
            });
            field.addEventListener('change', function() {
                updateClassGradeLabel();
                renderPreview();
                renderReview();
            });
        });

        const educationLevelSelect = document.getElementById('education_level');
        if (educationLevelSelect) {
            populateClassGradeOptions(educationLevelSelect.value, classGradeSelect?.value || '');
            populateSubjectOptions(educationLevelSelect.value, subjectSelect?.value || '');
            educationLevelSelect.addEventListener('change', function() {
                populateClassGradeOptions(this.value);
                populateSubjectOptions(this.value);
                if (classGradeSelect) {
                    classGradeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }

        document.querySelectorAll('[data-next-step]').forEach((button) => {
            button.addEventListener('click', function() {
                goToStep(currentStep + 1);
            });
        });

        document.querySelectorAll('[data-step-jump]').forEach((button) => {
            button.addEventListener('click', function() {
                goToStep(Number(this.dataset.stepJump || currentStep));
            });
        });

        previewStepButton?.addEventListener('click', function() {
            goToStep(3);
        });

        document.querySelectorAll('[data-prev-step]').forEach((button) => {
            button.addEventListener('click', function() {
                goToStep(currentStep - 1);
            });
        });

        publishButton?.addEventListener('click', function() {
            isPublishing = true;
            saveModeInput.value = 'publish';
            statusField.value = 'active';
        });

        document.querySelectorAll('[data-save-draft-trigger]').forEach((button) => {
            button.addEventListener('click', function() {
                const originalStatus = statusField.value;
                saveModeInput.value = 'draft';
                statusField.value = 'is_draft';
                form.submit();
                statusField.value = originalStatus;
            });
        });

        form.addEventListener('submit', function(event) {
            if (document.activeElement?.matches?.('[data-save-draft-trigger]')) {
                saveModeInput.value = 'draft';
                return;
            }

            if (isPublishing) {
                event.preventDefault();
                goToStep(5);
                setTimeout(() => {
                    isPublishing = false;
                    form.requestSubmit(publishButton ?? undefined);
                }, 850);
                return;
            }

            saveModeInput.value = 'publish';
        });

        refreshQuestionIndexes();
        updateClassGradeLabel();
        updateStepper();
    });
</script>
