@props(['course', 'chapters', 'selectedChapterId', 'isAdmin' => false])

@php
    $storeRoute = $isAdmin
        ? route('admin.lessons.store', $course)
        : route('instructor.lessons.store', $course);
    $coursesRoute = $isAdmin ? route('admin.courses.index') : route('instructor.courses.index');
    $contentRoute = $isAdmin
        ? route('admin.courses.edit', ['id' => $course->id, 'step' => 3])
        : route('instructor.courses.edit', ['id' => $course->id, 'step' => 3]);
    $defaultChapter = $chapters->firstWhere('id', $selectedChapterId) ?? $chapters->first();
    $defaultLectureNumber = ($defaultChapter?->order ?: 1) . '.' . (($defaultChapter?->chapter_items_count ?? 0) + 1);
@endphp

<div class="lesson-builder" data-lesson-builder data-course-title="{{ $course->title }}">
    <nav class="lesson-builder__breadcrumbs" aria-label="Breadcrumb">
        <a href="{{ $coursesRoute }}">{{ __('My Courses') }}</a><span>›</span>
        <a href="{{ $contentRoute }}">{{ $course->title }}</a><span>›</span>
        <span data-breadcrumb-section>{{ $defaultChapter?->title ?: __('Section') }}</span><span>›</span>
        <strong>{{ __('Add Lesson') }}</strong>
    </nav>

    <header class="lesson-builder__header">
        <div>
            <span class="lesson-builder__eyebrow"><i class="fas fa-video"></i> {{ __('Course creator') }}</span>
            <h1>{{ __('Add Video Lesson') }}</h1>
            <p>{{ __('Upload a video lesson and configure the details. This information will be shown in the mobile app.') }}</p>
        </div>
        <a href="{{ $contentRoute }}" class="lesson-builder__close" aria-label="{{ __('Back to curriculum') }}">
            <i class="fas fa-times"></i>
        </a>
    </header>

    <div class="lesson-builder__notice" data-form-notice hidden role="alert"></div>

    <x-lesson-builder.stepper />

    <div class="lesson-builder__workspace">
        <form class="lesson-builder__form" action="{{ $storeRoute }}" method="POST" enctype="multipart/form-data" data-lesson-form novalidate>
            @csrf
            <input type="hidden" name="publication_status" value="inactive" data-publication-status>
            <input type="hidden" name="duration" value="" data-duration-minutes>

            <x-lesson-builder.details :chapters="$chapters" :selected-chapter-id="$selectedChapterId" :default-lecture-number="$defaultLectureNumber" />
            <x-lesson-builder.media />
            <x-lesson-builder.access />
            <x-lesson-builder.publish :course="$course" />

            <footer class="lesson-builder__actions">
                <button type="button" class="lesson-button lesson-button--ghost" data-prev-step hidden>
                    <i class="fas fa-arrow-left"></i> {{ __('Back') }}
                </button>
                <div class="lesson-builder__actions-right">
                    <button type="button" class="lesson-button lesson-button--secondary" data-save-draft>
                        {{ __('Save Draft') }}
                    </button>
                    <button type="button" class="lesson-button lesson-button--primary" data-next-step>
                        <span>{{ __('Save & Continue') }}</span> <i class="fas fa-arrow-right"></i>
                    </button>
                    <button type="button" class="lesson-button lesson-button--primary" data-publish hidden>
                        <span>{{ __('Save & Publish') }}</span> <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </footer>
        </form>

        <x-lesson-builder.android-preview :course="$course" :chapter="$defaultChapter" />
    </div>
</div>
