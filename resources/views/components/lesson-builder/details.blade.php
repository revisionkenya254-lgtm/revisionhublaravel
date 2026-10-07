@props(['chapters', 'selectedChapterId', 'defaultLectureNumber'])

<section class="lesson-card lesson-step is-active" data-step="1">
    <div class="lesson-card__heading">
        <span class="lesson-card__icon"><i class="fas fa-align-left"></i></span>
        <div><span>{{ __('Step 1 of 4') }}</span><h2>{{ __('Lesson Details') }}</h2></div>
    </div>

    <div class="lesson-field">
        <label for="lesson-section">{{ __('Section') }} <em>*</em></label>
        <select id="lesson-section" name="chapter_id" required data-section-select>
            <option value="">{{ __('Choose a section') }}</option>
            @foreach ($chapters as $chapter)
                <option value="{{ $chapter->id }}" data-order="{{ $chapter->order }}" @selected((int) $selectedChapterId === (int) $chapter->id)>
                    {{ __('Section :number: :title', ['number' => $chapter->order, 'title' => $chapter->title]) }}
                </option>
            @endforeach
        </select>
        <small>{{ __('Choose where this lesson appears in the course curriculum.') }}</small>
        <span class="lesson-field__error" data-error-for="chapter_id"></span>
    </div>

    <div class="lesson-field-grid lesson-field-grid--compact">
        <div class="lesson-field">
            <label for="lecture-number">{{ __('Lecture Number') }} <em>*</em></label>
            <input id="lecture-number" name="lecture_number" type="text" maxlength="40" value="{{ old('lecture_number', $defaultLectureNumber) }}" placeholder="1.1" required data-preview-input="lecture-number">
            <small>{{ __('Used to order this lesson in the curriculum.') }}</small>
            <span class="lesson-field__error" data-error-for="lecture_number"></span>
        </div>
        <div class="lesson-field">
            <label for="duration-display">{{ __('Duration') }} <em>*</em></label>
            <div class="lesson-field__with-icon"><i class="far fa-clock"></i><input id="duration-display" name="duration_display" type="text" value="{{ old('duration_display') }}" placeholder="12:25" required data-preview-input="duration"></div>
            <small>{{ __('Detected from the uploaded video, or enter MM:SS.') }}</small>
            <span class="lesson-field__error" data-error-for="duration_display"></span>
        </div>
    </div>

    <div class="lesson-field">
        <label for="lesson-title">{{ __('Lesson Title') }} <em>*</em></label>
        <input id="lesson-title" name="title" type="text" maxlength="255" value="{{ old('title') }}" placeholder="{{ __('Introduction to Quadratic Expressions & Formulas') }}" required data-preview-input="title">
        <span class="lesson-field__error" data-error-for="title"></span>
    </div>

    <div class="lesson-field">
        <div class="lesson-field__label-row"><label for="lesson-overview">{{ __('Overview') }} <em>*</em></label><span><b data-overview-count>0</b>/10,000</span></div>
        <textarea id="lesson-overview" name="overview" rows="7" maxlength="10000" required data-preview-input="overview" placeholder="{{ __('In this lesson, we will cover the basics of quadratic expressions, their formulas and how to identify them. We will also look at key properties and examples.') }}">{{ old('overview') }}</textarea>
        <small>{{ __('This appears in the Overview tab in the mobile app.') }}</small>
        <span class="lesson-field__error" data-error-for="overview"></span>
    </div>
</section>
