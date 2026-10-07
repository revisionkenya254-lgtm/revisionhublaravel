@extends($isAdmin ? 'layouts.course-builder' : 'frontend.instructor-dashboard.layouts.master')

@section('title', __('Create Course'))

@section($isAdmin ? 'content' : 'dashboard-contents')
@php
    $storeRoute = $isAdmin ? route('admin.courses.store') : route('instructor.courses.store');
    $coursesRoute = $isAdmin ? route('admin.courses.index') : route('instructor.courses.index');
    $taxonomyDefaults = $categorySelection['defaults'] ?? [];
    $selectedEducationLevel = old('education_level', $taxonomyDefaults['education_level'] ?? '');
    $selectedClassGrade = old('class_grade', $taxonomyDefaults['class_grade'] ?? '');
    $selectedSubject = old('subject', $taxonomyDefaults['subject'] ?? '');
    $selectedExamCategory = old('exam_category', $taxonomyDefaults['exam_category'] ?? '');
    $selectedYear = old('year', $taxonomyDefaults['year'] ?? now()->year);
@endphp
<div class="rh-page">
    <nav class="rh-breadcrumb"><a href="{{ $coursesRoute }}">{{ __('Courses') }}</a><span>›</span><strong>{{ __('Create Course') }}</strong></nav>
    <header class="rh-page-head">
        <div><span class="rh-eyebrow">{{ __('COURSE CREATOR') }}</span><h1 class="rh-page-title">{{ __('Create a new course') }}</h1><p class="rh-page-subtitle">{{ __('Build the course foundation, then add lessons to its curriculum.') }}</p></div>
        <a class="rh-close" href="{{ $coursesRoute }}" aria-label="{{ __('Close') }}">×</a>
    </header>

    <ol class="rh-stepper" aria-label="{{ __('Course creation progress') }}">
        <li class="rh-step active" data-step-marker="1"><span class="rh-step-dot">1</span><span class="rh-step-name">{{ __('Course Details') }}</span></li>
        <li class="rh-step" data-step-marker="2"><span class="rh-step-dot">2</span><span class="rh-step-name">{{ __('Video & Media') }}</span></li>
        <li class="rh-step" data-step-marker="3"><span class="rh-step-dot">3</span><span class="rh-step-name">{{ __('Set Access') }}</span></li>
        <li class="rh-step" data-step-marker="4"><span class="rh-step-dot">4</span><span class="rh-step-name">{{ __('Review') }}</span></li>
    </ol>

    <div class="rh-alert" data-form-alert hidden></div>
    <form class="rh-builder" action="{{ $storeRoute }}" method="POST" enctype="multipart/form-data" data-course-builder novalidate>
        @csrf
        <input type="hidden" name="builder_flow" value="1">
        <input type="hidden" name="next_step" value="3">
        <input type="hidden" name="edit_mode" value="0">
        <input type="hidden" name="thumbnail" value="">
        <input type="hidden" id="category" name="category" value="{{ old('category') }}" required>

        <section class="rh-panel active" data-step="1">
            <div class="rh-card"><h2 class="rh-section-title">{{ __('Course details') }}</h2><p class="rh-muted">{{ __('Give students a clear picture of what they will learn.') }}</p>
                <div class="rh-field"><label class="rh-label" for="title">{{ __('Course title') }} *</label><input class="rh-input" id="title" name="title" maxlength="255" required value="{{ old('title') }}"><div class="rh-help">{{ __('Use a specific, outcome-focused title.') }}</div></div>
                <div class="rh-field"><label class="rh-label" for="description">{{ __('Overview') }} *</label><textarea class="rh-textarea" id="description" name="description" maxlength="5000" required>{{ old('description') }}</textarea></div>
                <div class="rh-field"><label class="rh-label" for="seo_description">{{ __('Short description') }}</label><input class="rh-input" id="seo_description" name="seo_description" maxlength="255" value="{{ old('seo_description') }}"></div>
                <div class="rh-taxonomy-block">
                    <div class="rh-section-title">{{ __('Education category') }}</div>
                    <p class="rh-muted">{{ __('Classify this course using the same education structure as Past Papers.') }}</p>
                    <div class="rh-grid-3">
                        <div class="rh-field"><label class="rh-label" for="education_level">{{ __('Education Level') }} *</label><select class="rh-select" id="education_level" name="education_level" required><option value="">{{ __('Choose level') }}</option>@foreach($categorySelection['education_levels'] ?? [] as $option)<option value="{{ $option }}" @selected($selectedEducationLevel === $option)>{{ $option }}</option>@endforeach</select></div>
                        <div class="rh-field"><label class="rh-label" for="class_grade" data-class-grade-label>{{ __('Class / Grade') }} *</label><select class="rh-select" id="class_grade" name="class_grade" required><option value="">{{ __('Choose class or grade') }}</option></select></div>
                        <div class="rh-field"><label class="rh-label" for="subject" data-subject-label>{{ __('Subject') }} *</label><select class="rh-select" id="subject" name="subject" required><option value="">{{ __('Choose subject') }}</option></select></div>
                    </div>
                    <div class="rh-grid-2">
                        <div class="rh-field"><label class="rh-label" for="exam_category">{{ __('Exam Category') }}</label><select class="rh-select" id="exam_category" name="exam_category"><option value="">{{ __('Choose exam category') }}</option></select></div>
                        <div class="rh-field"><label class="rh-label" for="year">{{ __('Year') }}</label><select class="rh-select" id="year" name="year"><option value="">{{ __('Choose year') }}</option>@foreach($categorySelection['years'] ?? [] as $year)<option value="{{ $year }}" @selected((string) $selectedYear === (string) $year)>{{ $year }}</option>@endforeach</select></div>
                    </div>
                </div>
                <div class="rh-grid-2">
                    <div class="rh-field"><label class="rh-label" for="course_duration">{{ __('Estimated duration (minutes)') }} *</label><input class="rh-input" id="course_duration" name="course_duration" type="number" min="1" required value="{{ old('course_duration') }}"></div>
                    @if($isAdmin)<div class="rh-field"><label class="rh-label" for="instructor">{{ __('Instructor') }} *</label><select class="rh-select" id="instructor" name="instructor" required><option value="">{{ __('Choose an instructor') }}</option>@foreach($instructors as $instructor)<option value="{{ $instructor->id }}">{{ $instructor->name }} — {{ $instructor->email }}</option>@endforeach</select></div>@endif
                </div>
            </div>
        </section>

        <section class="rh-panel" data-step="2" hidden>
            <div class="rh-card"><h2 class="rh-section-title">{{ __('Course thumbnail') }}</h2><p class="rh-muted">{{ __('Upload the artwork students will see in the catalog.') }}</p><label class="rh-upload" for="thumbnail_file"><span class="rh-upload-icon">↑</span><strong>{{ __('Choose a thumbnail') }}</strong><small>{{ __('JPG, PNG or WebP · maximum 2 MB · 16:9 recommended') }}</small><input id="thumbnail_file" name="thumbnail_file" type="file" accept="image/jpeg,image/png,image/webp" required></label><div class="rh-file-name" data-thumbnail-name></div></div>
            <div class="rh-card"><h2 class="rh-section-title">{{ __('Preview video') }}</h2><p class="rh-muted">{{ __('Add an optional hosted preview or upload it to Bunny Stream.') }}</p>
                <div class="rh-grid-2"><div class="rh-field"><label class="rh-label" for="demo_video_storage">{{ __('Video source') }}</label><select class="rh-select" id="demo_video_storage" name="demo_video_storage" data-video-source><option value="youtube">YouTube</option><option value="vimeo">Vimeo</option><option value="external_link">{{ __('External link') }}</option><option value="bunny_stream">Bunny Stream</option></select></div><div class="rh-field" data-video-link><label class="rh-label" for="external_path">{{ __('Video link') }}</label><input class="rh-input" id="external_path" name="external_path" type="url" placeholder="https://"></div><div class="rh-field" data-video-upload hidden><label class="rh-label" for="demo_video_file">{{ __('Video file') }}</label><input class="rh-input" id="demo_video_file" name="demo_video_file" type="file" accept="video/mp4,video/webm,video/quicktime"></div></div>
            </div>
        </section>

        <section class="rh-panel" data-step="3" hidden>
            <div class="rh-card"><h2 class="rh-section-title">{{ __('Pricing and access') }}</h2><p class="rh-muted">{{ __('Set the audience and learning options for this course.') }}</p>
                <div class="rh-access-grid"><label class="rh-access selected"><input type="radio" name="access_type" value="free" checked><span class="rh-access-icon">○</span><strong>{{ __('Free course') }}</strong><small>{{ __('Available to every student') }}</small></label><label class="rh-access"><input type="radio" name="access_type" value="paid"><span class="rh-access-icon">◇</span><strong>{{ __('Paid course') }}</strong><small>{{ __('Students purchase access') }}</small></label></div>
                <div class="rh-grid-2" data-pricing-fields hidden><div class="rh-field"><label class="rh-label" for="price">{{ __('Price') }} *</label><input class="rh-input" id="price" name="price" type="number" min="0" step="0.01" value="0" required></div><div class="rh-field"><label class="rh-label" for="discount_price">{{ __('Discount price') }}</label><input class="rh-input" id="discount_price" name="discount_price" type="number" min="0" step="0.01"></div></div>
                <div class="rh-grid-2"><div class="rh-field"><label class="rh-label" for="capacity">{{ __('Student capacity') }}</label><input class="rh-input" id="capacity" name="capacity" type="number" min="1" placeholder="{{ __('Unlimited') }}"></div><div class="rh-option-list"><label class="rh-switch"><input type="checkbox" name="qna" value="1" checked><span></span><b>{{ __('Enable Q&A') }}</b></label><label class="rh-switch"><input type="checkbox" name="certificate" value="1"><span></span><b>{{ __('Completion certificate') }}</b></label></div></div>
            </div>
            <div class="rh-grid-2"><div class="rh-card"><h3 class="rh-card-title">{{ __('Levels') }}</h3><div class="rh-check-grid">@forelse($levels as $level)<label><input type="checkbox" name="levels[]" value="{{ $level->id }}"> {{ $level->translation?->name }}</label>@empty<span class="rh-muted">{{ __('No levels configured') }}</span>@endforelse</div></div><div class="rh-card"><h3 class="rh-card-title">{{ __('Languages') }}</h3><div class="rh-check-grid">@forelse($languages as $language)<label><input type="checkbox" name="languages[]" value="{{ $language->id }}"> {{ $language->name }}</label>@empty<span class="rh-muted">{{ __('No languages configured') }}</span>@endforelse</div></div></div>
        </section>

        <section class="rh-panel" data-step="4" hidden>
            <div class="rh-card"><h2 class="rh-section-title">{{ __('Ready to build your curriculum?') }}</h2><p class="rh-muted">{{ __('Review the course foundation. After saving, you will add the first video lesson in the same creator experience.') }}</p><div class="rh-summary"><div class="rh-summary-media" data-review-image><span>▶</span></div><div><h3 data-review-title>{{ __('Untitled course') }}</h3><span class="rh-pill" data-review-access>{{ __('Free course') }}</span><p><b>{{ __('Category') }}:</b> <span data-review-category>—</span><br><b>{{ __('Duration') }}:</b> <span data-review-duration>—</span><br><b>{{ __('Overview') }}:</b> <span data-review-description>—</span></p></div></div></div>
            <div class="rh-card"><h3 class="rh-card-title">{{ __('What happens next') }}</h3><div class="rh-checklist"><div>{{ __('The course is saved safely as a draft') }}</div><div>{{ __('A first curriculum section is created') }}</div><div>{{ __('You continue directly to the video lesson builder') }}</div></div></div>
        </section>

        <footer class="rh-actions"><button type="button" class="rh-btn rh-btn-outline" data-back hidden>← {{ __('Back') }}</button><span></span><button type="button" class="rh-btn rh-btn-primary" data-next>{{ __('Save & Continue') }} →</button><button type="submit" class="rh-btn rh-btn-primary" data-submit hidden>{{ __('Create Course & Add Lesson') }} →</button></footer>
    </form>
</div>
<script type="application/json" data-course-taxonomy>@json([
    'tree' => $categorySelection['tree'] ?? [],
    'classGradesByLevel' => $categorySelection['class_grades_by_level'] ?? [],
    'subjectsByLevel' => $categorySelection['subjects_by_education_level'] ?? [],
    'subjects' => $categorySelection['subjects'] ?? [],
    'examCategoriesByLevel' => $categorySelection['exam_categories_by_level'] ?? [],
    'selected' => [
        'educationLevel' => $selectedEducationLevel,
        'classGrade' => $selectedClassGrade,
        'subject' => $selectedSubject,
        'examCategory' => $selectedExamCategory,
        'categoryId' => old('category'),
    ],
])</script>
@endsection

@if(! $isAdmin)
    @push('styles')
        <link rel="stylesheet" href="{{ asset('frontend/css/course-builder.css') }}?v={{ config('app.asset_version', '1') }}">
        <style>
            .instructor-dashboard-content .rh-page {
                width: 100%;
                padding: 0 0 52px;
            }

            .instructor-dashboard-content .rh-page-head {
                margin-top: 4px;
            }

            .instructor-dashboard-content .rh-stepper {
                border: 1px solid rgba(98, 117, 157, 0.14);
                border-radius: 18px;
                background: rgba(255, 255, 255, 0.88);
                box-shadow: 0 12px 30px rgba(20, 33, 61, 0.05);
                padding: 8px 0;
            }

            .instructor-dashboard-content .rh-card {
                box-shadow: 0 12px 30px rgba(20, 33, 61, 0.05);
            }
        </style>
    @endpush
@endif

@push('scripts')
<script src="{{ asset('frontend/js/course-builder.js') }}?v={{ config('app.asset_version', '1') }}" defer></script>
@endpush
