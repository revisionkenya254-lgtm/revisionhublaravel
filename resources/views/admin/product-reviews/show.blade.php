@extends('admin.master_layout')

@section('title')
    <title>{{ __('Review Details') }}</title>
@endsection

@section('admin-content')
    @php
        $product = $review->product;
        $productMetadata = $product?->metadata ?? [];
        $latestAiDocument = $product?->latestAiDocument;
        $isPaperType = in_array($product?->type, ['past_paper', 'prediction'], true);
        $isNoteType = $product?->type === 'note';
        $isQuizType = $product?->type === 'quiz';

        $reviewStatusBadge = match ((string) $review->status) {
            '0' => ['label' => __('Pending'), 'class' => 'badge-warning'],
            '1' => ['label' => __('Approved'), 'class' => 'badge-success'],
            default => ['label' => ucfirst((string) $review->status), 'class' => 'badge-secondary'],
        };

        $productStatusBadge = match ((string) ($product->status ?? '')) {
            'active' => ['label' => __('Published'), 'class' => 'badge-success'],
            'inactive' => ['label' => __('Unpublished'), 'class' => 'badge-warning'],
            'is_draft' => ['label' => __('Drafted'), 'class' => 'badge-info'],
            default => ['label' => ucfirst((string) ($product->status ?? '')), 'class' => 'badge-secondary'],
        };

        $productApprovalBadge = match ((string) ($product->is_approved ?? '')) {
            'pending' => ['label' => __('Pending'), 'class' => 'badge-warning'],
            'approved' => ['label' => __('Published'), 'class' => 'badge-success'],
            'rejected' => ['label' => __('Rejected'), 'class' => 'badge-danger'],
            default => ['label' => ucfirst((string) ($product->is_approved ?? '')), 'class' => 'badge-secondary'],
        };

        $formatValue = function ($value) {
            if (is_array($value)) {
                return implode(', ', collect($value)->filter()->map(function ($item) {
                    return is_array($item) ? json_encode($item) : (string) $item;
                })->all());
            }

            if (is_bool($value)) {
                return $value ? __('Yes') : __('No');
            }

            return trim((string) $value);
        };
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
        $paperClassGradeLabel = $resolveClassGradeLabel($productMetadata['education_level'] ?? null);
        $noteClassGradeLabel = $resolveClassGradeLabel($productMetadata['education_level'] ?? null);
        $quizClassGradeLabel = $resolveClassGradeLabel($productMetadata['education_level'] ?? null);
        $paperSubjectLabel = $resolveSubjectLabel($productMetadata['education_level'] ?? null);
        $noteSubjectLabel = $resolveSubjectLabel($productMetadata['education_level'] ?? null);
        $quizSubjectLabel = $resolveSubjectLabel($productMetadata['education_level'] ?? null);

        $metadataBadges = collect($productMetadata)->map(function ($value, $key) use ($formatValue) {
            return [
                'label' => \Illuminate\Support\Str::headline(str_replace('_', ' ', (string) $key)),
                'value' => $formatValue($value),
            ];
        })->filter(fn ($item) => filled($item['value']))->values();

        $reviewOnlyFields = collect([
            ['label' => __('Reviewer'), 'value' => $review->user?->name ?? __('Unknown')],
            ['label' => __('Rating'), 'value' => str_repeat('★', (int) $review->rating)],
            ['label' => __('Review Status'), 'value' => $reviewStatusBadge['label']],
            ['label' => __('Reviewed At'), 'value' => formatDate($review->created_at)],
        ])->filter(fn ($item) => filled($item['value']));

        $commonUploadBadges = collect([
            ['label' => __('Title'), 'value' => $product?->title],
            ['label' => __('Category'), 'value' => $product?->category?->translation?->name ?? '-'],
            ['label' => __('Product Type'), 'value' => $product?->type_label ?? ucfirst((string) $product?->type)],
            ['label' => __('Status'), 'value' => $productStatusBadge['label']],
            ['label' => __('Approval'), 'value' => $productApprovalBadge['label']],
            ['label' => __('Price'), 'value' => $product?->price == 0 ? __('Free') : currency($product?->discount > 0 ? $product?->discount : $product?->price)],
            ['label' => __('File Type'), 'value' => $product?->file_type ?: ($product?->file_path ? __('Attached') : null)],
        ])->filter(fn ($item) => filled($item['value']));

        $aiExtractionBadge = match ((string) ($latestAiDocument?->status ?? '')) {
            'pending' => ['label' => __('Waiting for admin'), 'class' => 'badge-warning'],
            'processing' => ['label' => __('Extracting'), 'class' => 'badge-info'],
            'processed' => ['label' => __('Extracted'), 'class' => 'badge-success'],
            'requires_ocr' => ['label' => __('Needs OCR'), 'class' => 'badge-dark'],
            'failed' => ['label' => __('Extraction failed'), 'class' => 'badge-danger'],
            default => ['label' => __('Not started'), 'class' => 'badge-secondary'],
        };
        $canStartExtraction = filled($product?->file_path) && in_array(strtolower((string) $product?->file_type), ['pdf', 'doc', 'docx'], true);

        $paperBadges = collect([
            ['label' => __('Education Level'), 'value' => $productMetadata['education_level'] ?? null],
            ['label' => $paperClassGradeLabel, 'value' => $productMetadata['class_grade'] ?? null],
            ['label' => $paperSubjectLabel, 'value' => $productMetadata['course'] ?? $productMetadata['subject'] ?? null],
            ['label' => __('Exam Category'), 'value' => $productMetadata['exam_category'] ?? null],
            ['label' => __('Paper'), 'value' => $productMetadata['paper'] ?? null],
            ['label' => __('Year'), 'value' => $productMetadata['year'] ?? null],
            ['label' => __('Language'), 'value' => $productMetadata['language'] ?? null],
            ['label' => __('Access Type'), 'value' => $productMetadata['access_type'] ?? null],
            ['label' => __('Preview Pages'), 'value' => $productMetadata['preview_pages'] ?? null],
            ['label' => __('Tags'), 'value' => isset($productMetadata['tags']) ? implode(', ', (array) $productMetadata['tags']) : null],
        ])->filter(fn ($item) => filled($item['value']));

        $note = $product?->note;
        $noteBadges = collect([
            ['label' => __('Education Level'), 'value' => $productMetadata['education_level'] ?? null],
            ['label' => $noteClassGradeLabel, 'value' => $productMetadata['class_grade'] ?? null],
            ['label' => $noteSubjectLabel, 'value' => $productMetadata['course'] ?? $productMetadata['subject'] ?? null],
            ['label' => __('Exam Category'), 'value' => $productMetadata['exam_category'] ?? null],
            ['label' => __('Topic'), 'value' => $productMetadata['topic'] ?? null],
            ['label' => __('Sub Topic'), 'value' => $productMetadata['sub_topic'] ?? null],
            ['label' => __('Tags'), 'value' => isset($productMetadata['tags']) ? implode(', ', (array) $productMetadata['tags']) : null],
            ['label' => __('Access Type'), 'value' => $productMetadata['access_type'] ?? null],
            ['label' => __('Preview Pages'), 'value' => $productMetadata['preview_pages'] ?? null],
            ['label' => __('Language'), 'value' => $productMetadata['language'] ?? null],
            ['label' => __('Note Visibility'), 'value' => $productMetadata['note_visibility'] ?? null],
        ])->filter(fn ($item) => filled($item['value']));

        $noteFields = collect([
            ['label' => __('Excerpt'), 'value' => $note?->excerpt],
            ['label' => __('Estimated Read Minutes'), 'value' => $note?->estimated_read_minutes ? $note?->estimated_read_minutes . ' ' . __('minutes') : null],
            ['label' => __('Difficulty'), 'value' => $note?->difficulty],
            ['label' => __('Show Resources'), 'value' => is_null($note?->show_resources) ? null : ($note?->show_resources ? __('Yes') : __('No'))],
            ['label' => __('Show Discussion'), 'value' => is_null($note?->show_discussion) ? null : ($note?->show_discussion ? __('Yes') : __('No'))],
            ['label' => __('Prerequisites'), 'value' => isset($note?->prerequisites) ? implode(', ', (array) $note?->prerequisites) : null],
        ])->filter(fn ($item) => filled($item['value']));

        $quiz = $product?->quiz;
        $quizSettings = collect([
            ['label' => __('Tier'), 'value' => $quiz?->tier],
            ['label' => __('Difficulty'), 'value' => $quiz?->difficulty],
            ['label' => __('Duration'), 'value' => $quiz?->duration_minutes ? $quiz?->duration_minutes . ' ' . __('minutes') : __('No limit')],
            ['label' => __('Attempt Limit'), 'value' => $quiz?->attempt_limit],
            ['label' => __('Pass Mark'), 'value' => $quiz?->pass_mark],
            ['label' => __('Question Count'), 'value' => $quiz?->question_count_cache ?? 0],
            ['label' => __('Published At'), 'value' => $quiz?->published_at ? formatDate($quiz->published_at) : null],
        ])->filter(fn ($item) => filled($item['value']));

        $quizMetadata = collect([
            ['label' => __('Education Level'), 'value' => $productMetadata['education_level'] ?? null],
            ['label' => $quizClassGradeLabel, 'value' => $productMetadata['class_grade'] ?? null],
            ['label' => $quizSubjectLabel, 'value' => $productMetadata['course'] ?? $productMetadata['subject'] ?? $productMetadata['quiz_subject'] ?? null],
            ['label' => __('Exam Category'), 'value' => $productMetadata['exam_category'] ?? null],
            ['label' => __('Topic'), 'value' => $productMetadata['topic'] ?? null],
            ['label' => __('Tags'), 'value' => isset($productMetadata['tags']) ? implode(', ', (array) $productMetadata['tags']) : null],
            ['label' => __('Total Questions'), 'value' => $productMetadata['total_questions'] ?? null],
            ['label' => __('Question Order'), 'value' => $productMetadata['question_order'] ?? null],
            ['label' => __('Show Results'), 'value' => $productMetadata['show_results'] ?? null],
            ['label' => __('Enabled'), 'value' => array_key_exists('is_enabled', $productMetadata) ? ($productMetadata['is_enabled'] ? __('Yes') : __('No')) : null],
            ['label' => __('Show Correct Answers'), 'value' => array_key_exists('show_correct_answers', $productMetadata) ? ($productMetadata['show_correct_answers'] ? __('Yes') : __('No')) : null],
            ['label' => __('Allow Review'), 'value' => array_key_exists('allow_review', $productMetadata) ? ($productMetadata['allow_review'] ? __('Yes') : __('No')) : null],
            ['label' => __('Shuffle Options'), 'value' => array_key_exists('shuffle_options', $productMetadata) ? ($productMetadata['shuffle_options'] ? __('Yes') : __('No')) : null],
            ['label' => __('One Question Per Page'), 'value' => array_key_exists('one_question_per_page', $productMetadata) ? ($productMetadata['one_question_per_page'] ? __('Yes') : __('No')) : null],
            ['label' => __('Show Progress Bar'), 'value' => array_key_exists('show_progress_bar', $productMetadata) ? ($productMetadata['show_progress_bar'] ? __('Yes') : __('No')) : null],
            ['label' => __('Question Numbering'), 'value' => $productMetadata['question_numbering'] ?? null],
        ])->filter(fn ($item) => filled($item['value']));

        $quizQuestions = $quiz?->relationLoaded('questions') ? $quiz->questions : collect();
        $quizQuestionPreview = $quizQuestions->take(5);
    @endphp

    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <div>
                    <h1 class="text-primary">{{ __('Review Details') }}</h1>
                    <p class="text-muted mb-0">{{ $product?->type_label ?? __('Product') }} {{ __('approval review') }}</p>
                </div>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active">
                        <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a>
                    </div>
                    <div class="breadcrumb-item">
                        <a href="{{ route($meta['route'] . '.reviews.index') }}">{{ __($meta['title']) }}</a>
                    </div>
                    <div class="breadcrumb-item">{{ __('Review Details') }}</div>
                </div>
            </div>

            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3" style="gap: 12px;">
                                    <div>
                                        <h4 class="mb-1">{{ $product?->title }}</h4>
                                        <div class="d-flex flex-wrap" style="gap: .5rem;">
                                            <span class="badge {{ $productStatusBadge['class'] }}">{{ $productStatusBadge['label'] }}</span>
                                            <span class="badge {{ $productApprovalBadge['class'] }}">{{ $productApprovalBadge['label'] }}</span>
                                            <span class="badge badge-secondary">{{ $product?->type_label ?? __('Product') }}</span>
                                        </div>
                                    </div>
                                    <a href="{{ route($meta['route'] . '.reviews.index') }}" class="btn btn-primary">{{ __('Review List') }}</a>
                                </div>

                                <div class="alert alert-info mb-4">
                                    <strong>{{ __('Admin-only review fields') }}:</strong>
                                    <span>{{ __('Rating, review text, review status, reviewer, and review date are moderation-only fields and do not come from the instructor upload form.') }}</span>
                                </div>

                                <div class="card border mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">{{ __('Instructor Upload Snapshot') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex flex-wrap" style="gap: .5rem;">
                                            @foreach ($commonUploadBadges as $item)
                                                <span class="badge badge-light border text-dark d-inline-flex flex-column align-items-start p-2">
                                                    <small class="text-muted">{{ $item['label'] }}</small>
                                                    <strong>{{ $item['value'] }}</strong>
                                                </span>
                                            @endforeach
                                        </div>

                                        @if (filled($product?->description))
                                            <hr>
                                            <h6 class="mb-2">{{ __('Description') }}</h6>
                                            <p class="mb-0 text-muted">{!! nl2br(e(\Illuminate\Support\Str::limit(strip_tags((string) $product->description), 500))) !!}</p>
                                        @endif

                                        @if (filled($product?->thumbnail))
                                            <hr>
                                            <h6 class="mb-2">{{ __('Thumbnail') }}</h6>
                                            <a href="{{ asset($product->thumbnail) }}" target="_blank" rel="noopener">
                                                {{ asset($product->thumbnail) }}
                                            </a>
                                        @endif
                                    </div>
                                </div>

                                <div class="card border mb-4">
                                    <div class="card-header d-flex align-items-center justify-content-between">
                                        <h5 class="mb-0">{{ __('Content Extraction') }}</h5>
                                        <span class="badge {{ $aiExtractionBadge['class'] }}">{{ $aiExtractionBadge['label'] }}</span>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-3">
                                            {{ __('Content extraction is optional. You can approve the product without extracting content, or start extraction later from this review screen.') }}
                                        </p>

                                        @if ($latestAiDocument)
                                            <div class="d-flex flex-wrap mb-3" style="gap: .5rem;">
                                                <span class="badge badge-light border text-dark d-inline-flex flex-column align-items-start p-2">
                                                    <small class="text-muted">{{ __('Source') }}</small>
                                                    <strong>{{ $latestAiDocument->source_name }}</strong>
                                                </span>
                                                <span class="badge badge-light border text-dark d-inline-flex flex-column align-items-start p-2">
                                                    <small class="text-muted">{{ __('Status') }}</small>
                                                    <strong>{{ ucfirst(str_replace('_', ' ', (string) $latestAiDocument->status)) }}</strong>
                                                </span>
                                            </div>
                                        @endif

                                        @if ($canStartExtraction)
                                            <form action="{{ route($meta['route'] . '.ai-reprocess', $product->id) }}" method="POST" class="mb-0">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-primary">
                                                    {{ __('Extract content now') }}
                                                </button>
                                            </form>
                                        @else
                                            <div class="alert alert-light border mb-0">
                                                {{ __('No extractable source file is attached yet. If you keep the product as-is, it will remain available without content extraction.') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <div class="card border mb-4">
                                            <div class="card-header">
                                                <h5 class="mb-0">{{ __('Uploaded Metadata') }}</h5>
                                            </div>
                                            <div class="card-body">
                                                @if ($metadataBadges->isNotEmpty())
                                                    <div class="d-flex flex-wrap" style="gap: .5rem;">
                                                        @foreach ($metadataBadges as $item)
                                                            <span class="badge badge-light border text-dark d-inline-flex flex-column align-items-start p-2">
                                                                <small class="text-muted">{{ $item['label'] }}</small>
                                                                <strong>{{ $item['value'] }}</strong>
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <p class="text-muted mb-0">{{ __('No metadata has been uploaded yet.') }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if ($isPaperType)
                                    <div class="card border mb-4">
                                        <div class="card-header">
                                            <h5 class="mb-0">{{ __('Past Paper / Prediction Details') }}</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex flex-wrap" style="gap: .5rem;">
                                                @foreach ($paperBadges as $item)
                                                    <span class="badge badge-light border text-dark d-inline-flex flex-column align-items-start p-2">
                                                        <small class="text-muted">{{ $item['label'] }}</small>
                                                        <strong>{{ $item['value'] }}</strong>
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if ($isNoteType)
                                    <div class="card border mb-4">
                                        <div class="card-header">
                                            <h5 class="mb-0">{{ __('Note Details') }}</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex flex-wrap mb-3" style="gap: .5rem;">
                                                @foreach ($noteBadges as $item)
                                                    <span class="badge badge-light border text-dark d-inline-flex flex-column align-items-start p-2">
                                                        <small class="text-muted">{{ $item['label'] }}</small>
                                                        <strong>{{ $item['value'] }}</strong>
                                                    </span>
                                                @endforeach
                                            </div>

                                            <div class="row">
                                                @foreach ($noteFields as $item)
                                                    <div class="col-md-4 mb-3">
                                                        <div class="border rounded p-3 h-100">
                                                            <small class="text-muted d-block mb-1">{{ $item['label'] }}</small>
                                                            <strong>{{ $item['value'] }}</strong>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            @if (filled($note?->excerpt))
                                                <hr>
                                                <h6 class="mb-2">{{ __('Excerpt') }}</h6>
                                                <p class="mb-0 text-muted">{!! nl2br(e(\Illuminate\Support\Str::limit(strip_tags((string) $note->excerpt), 500))) !!}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                @if ($isQuizType)
                                    <div class="card border mb-4">
                                        <div class="card-header">
                                            <h5 class="mb-0">{{ __('Quiz Details') }}</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex flex-wrap mb-3" style="gap: .5rem;">
                                                @foreach ($quizSettings as $item)
                                                    <span class="badge badge-light border text-dark d-inline-flex flex-column align-items-start p-2">
                                                        <small class="text-muted">{{ $item['label'] }}</small>
                                                        <strong>{{ $item['value'] }}</strong>
                                                    </span>
                                                @endforeach
                                            </div>

                                            <div class="d-flex flex-wrap" style="gap: .5rem;">
                                                @foreach ($quizMetadata as $item)
                                                    <span class="badge badge-light border text-dark d-inline-flex flex-column align-items-start p-2">
                                                        <small class="text-muted">{{ $item['label'] }}</small>
                                                        <strong>{{ $item['value'] }}</strong>
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card border mb-4">
                                        <div class="card-header">
                                            <h5 class="mb-0">{{ __('Quiz Questions Preview') }}</h5>
                                        </div>
                                        <div class="card-body table-responsive">
                                            <table class="table table-sm table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>{{ __('#') }}</th>
                                                        <th>{{ __('Prompt') }}</th>
                                                        <th>{{ __('Type') }}</th>
                                                        <th>{{ __('Marks') }}</th>
                                                        <th>{{ __('Options') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($quizQuestionPreview as $question)
                                                        <tr>
                                                            <td>{{ $loop->iteration }}</td>
                                                            <td>{{ \Illuminate\Support\Str::limit($question->prompt, 90) }}</td>
                                                            <td>{{ ucfirst(str_replace('_', ' ', (string) $question->question_type)) }}</td>
                                                            <td>{{ $question->marks }}</td>
                                                            <td>{{ $question->relationLoaded('options') ? $question->options->count() : 0 }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="text-center text-muted">{{ __('No questions found.') }}</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                            @if ($quizQuestions->count() > $quizQuestionPreview->count())
                                                <p class="text-muted mb-0">
                                                    {{ __('Showing the first :shown of :total questions.', ['shown' => $quizQuestionPreview->count(), 'total' => $quizQuestions->count()]) }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <div class="card border mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">{{ __('Moderation Only') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-light border">
                                            <strong>{{ __('Admin-only review fields') }}:</strong>
                                            <span>{{ __('Rating, review text, review status, reviewer, and review date are moderation-only fields and do not come from the instructor upload form.') }}</span>
                                        </div>

                                        <div class="d-flex flex-wrap mb-3" style="gap: .5rem;">
                                            @foreach ($reviewOnlyFields as $item)
                                                <span class="badge badge-light border text-dark d-inline-flex flex-column align-items-start p-2">
                                                    <small class="text-muted">{{ $item['label'] }}</small>
                                                    <strong>{{ $item['value'] }}</strong>
                                                </span>
                                            @endforeach
                                        </div>

                                        <form action="{{ route($meta['route'] . '.reviews.update', $review->id) }}" method="POST" class="mb-4">
                                            @csrf
                                            @method('PUT')
                                            <div class="form-group mb-3">
                                                <label for="status">{{ __('Review Status') }}</label>
                                                <select name="status" id="status" class="form-control">
                                                    <option value="0" @selected($review->status == 0)>{{ __('Pending') }}</option>
                                                    <option value="1" @selected($review->status == 1)>{{ __('Approved') }}</option>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-primary">{{ __('Update Review') }}</button>
                                        </form>

                                        <hr>

                                        <table class="table mb-0">
                                            <tbody>
                                                <tr>
                                                    <td>{{ __('Product') }}</td>
                                                    <td>{{ $product?->title }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{ __('By') }}</td>
                                                    <td>{{ $review->user?->name }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{ __('Rating') }}</td>
                                                    <td>
                                                        @for ($i = 0; $i < (int) $review->rating; $i++)
                                                            <i class="fa fa-star text-warning"></i>
                                                        @endfor
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>{{ __('Review') }}</td>
                                                    <td>{{ $review->review }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{ __('Date') }}</td>
                                                    <td>{{ formatDate($review->created_at) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="card border mb-0">
                                    <div class="card-header">
                                        <h5 class="mb-0">{{ __('Review Record') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-0 text-muted">{{ __('This section is retained for quick reference only. Use the moderation panel above to update the review status.') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
