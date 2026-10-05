<section class="lesson-card lesson-step" data-step="3" hidden>
    <div class="lesson-card__heading">
        <span class="lesson-card__icon"><i class="far fa-comments"></i></span>
        <div><span>{{ __('Step 3 of 5') }}</span><h2>{{ __('Q&A') }}</h2></div>
    </div>
    <p class="lesson-card__intro">{{ __('Control how students can ask and view questions related to this lesson.') }}</p>

    <div class="lesson-setting lesson-setting--primary">
        <div><span class="lesson-setting__icon"><i class="far fa-comments"></i></span><div><strong>{{ __('Enable Q&A') }}</strong><small>{{ __('Show the Questions & Answers tab for this lesson.') }}</small></div></div>
        <label class="lesson-switch"><input type="hidden" name="qna_enabled" value="0"><input type="checkbox" name="qna_enabled" value="1" checked data-qna-master><span></span></label>
    </div>
    <div data-qna-options>
        <div class="lesson-setting"><div><strong>{{ __('Allow students to ask questions') }}</strong><small>{{ __('Students can start new lesson discussions.') }}</small></div><label class="lesson-switch"><input type="hidden" name="qna_allow_questions" value="0"><input type="checkbox" name="qna_allow_questions" value="1" checked><span></span></label></div>
        <div class="lesson-setting"><div><strong>{{ __('Allow students to reply to questions') }}</strong><small>{{ __('Students can contribute answers and follow-ups.') }}</small></div><label class="lesson-switch"><input type="hidden" name="qna_allow_replies" value="0"><input type="checkbox" name="qna_allow_replies" value="1" checked><span></span></label></div>
        <div class="lesson-setting"><div><strong>{{ __('Notify instructor when a new question is posted') }}</strong><small>{{ __('Use the existing instructor Q&A workflow for new questions.') }}</small></div><label class="lesson-switch"><input type="hidden" name="qna_notify_instructor" value="0"><input type="checkbox" name="qna_notify_instructor" value="1" checked><span></span></label></div>
        <div class="lesson-field lesson-field--spaced"><label for="qna-instructions">{{ __('Q&A Instructions') }} <span>{{ __('Optional') }}</span></label><textarea id="qna-instructions" name="qna_instructions" rows="5" maxlength="2000" placeholder="{{ __('Ask questions about the concepts covered in this lesson. Include the question number where applicable.') }}" data-preview-input="qna-instructions"></textarea><span class="lesson-field__error" data-error-for="qna_instructions"></span></div>
    </div>
</section>
