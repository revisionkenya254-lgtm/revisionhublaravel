<section class="lesson-card lesson-step" data-step="3" hidden>
    <div class="lesson-card__heading"><span class="lesson-card__icon"><i class="fas fa-lock-open"></i></span><div><span>{{ __('Step 3 of 4') }}</span><h2>{{ __('Access & Availability') }}</h2></div></div>
    <p class="lesson-card__intro">{{ __('Choose how this lesson follows the course access rules.') }}</p>
    <div class="lesson-choice-grid">
        <label class="lesson-choice"><input type="radio" name="access" value="included_with_course" checked><span class="lesson-choice__check"><i class="fas fa-check"></i></span><i class="fas fa-layer-group"></i><strong>{{ __('Included with course') }}</strong><small>{{ __('Available to enrolled students through the course.') }}</small></label>
        <label class="lesson-choice"><input type="radio" name="access" value="free_preview"><span class="lesson-choice__check"><i class="fas fa-check"></i></span><i class="fas fa-unlock"></i><strong>{{ __('Free preview') }}</strong><small>{{ __('Anyone can preview this lesson before enrolling.') }}</small></label>
    </div>
    <div class="lesson-setting lesson-setting--curriculum"><div><span class="lesson-setting__icon"><i class="fas fa-list-ul"></i></span><div><strong>{{ __('Include in Curriculum') }}</strong><small>{{ __('If disabled, the lesson will not appear in the student curriculum.') }}</small></div></div><label class="lesson-switch"><input type="hidden" name="include_in_curriculum" value="0"><input type="checkbox" name="include_in_curriculum" value="1" checked data-include-curriculum><span></span></label></div>
    <div class="lesson-divider"></div>
    <x-lesson-builder.qna />
</section>
