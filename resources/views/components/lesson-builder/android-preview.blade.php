@props(['course', 'chapter'])

<aside class="android-preview" data-android-preview>
    <button type="button" class="android-preview__mobile-toggle" data-preview-toggle aria-expanded="false"><span><i class="fas fa-mobile-alt"></i> {{ __('Android App Preview') }}</span><i class="fas fa-chevron-down"></i></button>
    <div class="android-preview__panel" data-preview-panel>
        <div class="android-preview__heading"><div><span class="android-preview__live"></span><strong>{{ __('Android App Preview') }}</strong></div><small>{{ __('Live preview') }}</small></div>
        <div class="android-phone">
            <div class="android-phone__speaker"></div>
            <div class="android-phone__screen">
                <div class="android-phone__status"><strong>7:48</strong><span><i class="fas fa-wifi"></i> <i class="fas fa-signal"></i> 50%</span></div>
                <div class="android-phone__bar"><i class="fas fa-arrow-left"></i><strong>{{ $course->title }}</strong><i class="fas fa-share-alt"></i></div>
                <div class="android-phone__video" @if($course->thumbnail) style="background-image:url('{{ asset($course->thumbnail) }}')" @endif data-phone-video>
                    <video muted playsinline data-phone-video-preview hidden></video><span><i class="fas fa-play"></i></span>
                    <div class="android-phone__controls"><b></b><small><i class="fas fa-play"></i> <span data-phone-time>00:00</span> / <span data-phone-duration>00:00</span></small></div>
                </div>
                <div class="android-phone__course"><strong data-phone-title>{{ __('Untitled video lesson') }}</strong><small>{{ $course->instructor?->name ?: __('Course instructor') }}</small><div><b>{{ __('Course Progress:') }} 35% {{ __('completed') }}</b><span>35/100%</span></div><i><span></span></i></div>
                <div class="android-phone__tabs" role="tablist">
                    @foreach (['curriculum' => __('Curriculum'), 'overview' => __('Overview'), 'qna' => __('Q&A'), 'resources' => __('Resources')] as $tab => $label)
                        <button type="button" class="{{ $tab === 'curriculum' ? 'is-active' : '' }}" data-preview-tab="{{ $tab }}">{{ $label }}</button>
                    @endforeach
                </div>
                <div class="android-phone__body">
                    <div data-preview-pane="curriculum"><div class="android-curriculum"><strong>{{ __('Section :number: :title', ['number' => $chapter?->order ?: 1, 'title' => $chapter?->title ?: __('New section')]) }}</strong><div><i class="fas fa-check"></i><span><b><em data-phone-lecture>1.1</em> <span data-phone-lesson>{{ __('Untitled video lesson') }}</span></b><small data-phone-curriculum-duration>00:00</small></span><button type="button"><i class="fas fa-play"></i></button></div></div></div>
                    <div data-preview-pane="overview" hidden><h4>{{ __('Lesson Overview') }}</h4><p data-phone-overview>{{ __('Your lesson overview will appear here as you type.') }}</p></div>
                    <div data-preview-pane="qna" hidden><div class="android-empty"><i class="far fa-comments"></i><h4>{{ __('Questions & Answers') }}</h4><p data-phone-qna-empty>{{ __('No questions yet.') }}</p><small data-phone-qna-instructions></small></div></div>
                    <div data-preview-pane="resources" hidden><div class="android-resource-preview" data-phone-resources><div class="android-empty"><i class="far fa-folder-open"></i><h4>{{ __('Lesson resources') }}</h4><p>{{ __('No resources added yet.') }}</p></div></div></div>
                </div>
            </div>
        </div>
        <p class="android-preview__hint"><i class="fas fa-sync-alt"></i> {{ __('Updates as you complete the lesson details') }}</p>
    </div>
</aside>
