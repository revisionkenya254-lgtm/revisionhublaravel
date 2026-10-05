@props(['course'])

<section class="lesson-card lesson-step" data-step="5" hidden>
    <div class="lesson-card__heading"><span class="lesson-card__icon"><i class="fas fa-check-circle"></i></span><div><span>{{ __('Step 5 of 5') }}</span><h2>{{ __('Publish Lesson') }}</h2></div></div>
    <p class="lesson-card__intro">{{ __('Review the mobile app data before publishing. You can also keep the lesson inactive as a draft.') }}</p>
    <div class="lesson-review">
        <div><span>{{ __('Course') }}</span><strong>{{ $course->title }}</strong></div>
        <div><span>{{ __('Section') }}</span><strong data-summary="section">—</strong></div>
        <div><span>{{ __('Lecture Number') }}</span><strong data-summary="lecture-number">—</strong></div>
        <div><span>{{ __('Title') }}</span><strong data-summary="title">—</strong></div>
        <div class="lesson-review__wide"><span>{{ __('Overview') }}</span><strong data-summary="overview">—</strong></div>
        <div><span>{{ __('Video') }}</span><strong data-summary="video">{{ __('Not selected') }}</strong></div>
        <div><span>{{ __('Duration') }}</span><strong data-summary="duration">—</strong></div>
        <div><span>{{ __('Resources') }}</span><strong data-summary="resources">{{ __('None') }}</strong></div>
        <div><span>{{ __('Q&A') }}</span><strong data-summary="qna">{{ __('Enabled') }}</strong></div>
        <div><span>{{ __('Access') }}</span><strong data-summary="access">{{ __('Included with course') }}</strong></div>
    </div>
    <div class="lesson-publish-note"><i class="fas fa-info-circle"></i><div><strong>{{ __('Ready when you are') }}</strong><p>{{ __('Publishing uses the existing Active status. Saving a draft uses Inactive, so it will not appear to students.') }}</p></div></div>
</section>
