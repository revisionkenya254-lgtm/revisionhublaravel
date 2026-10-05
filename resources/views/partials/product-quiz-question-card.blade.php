@php
    $hasPrompt = filled(trim((string) ($question['prompt'] ?? '')));
    $questionType = $question['question_type'] ?? 'single_choice';
    $isOpen = ! $hasPrompt || (is_numeric($questionIndex) && (int) $questionIndex === 0);
    $typeLabel = $questionType === 'short_answer' ? __('Short Answer') : __('Multiple Choice');
    $marksValue = (int) ($question['marks'] ?? 1);
    $marksLabel = $marksValue === 1 ? __('1 point') : __(':count points', ['count' => $marksValue]);
    $difficultyValue = $question['difficulty'] ?? 'easy';
@endphp

<div class="quiz-question-card {{ $isOpen ? 'is-expanded' : 'is-collapsed' }}" data-question-index="{{ $questionIndex }}" data-question-card>
    <div class="quiz-question-card__summary" role="button" tabindex="0" data-question-toggle aria-expanded="{{ $isOpen ? 'true' : 'false' }}">
        <button type="button" class="quiz-question-card__drag" aria-label="{{ __('Drag question') }}" title="{{ __('Drag question') }}">
            <i class="fas fa-grip-vertical"></i>
        </button>

        <span class="quiz-question-card__number question-number">{{ is_numeric($questionIndex) ? $questionIndex + 1 : 1 }}</span>

        <div class="quiz-question-card__text">
            <strong class="quiz-question-card__prompt-preview">{{ $question['prompt'] ?? __('New question') }}</strong>
            <div class="quiz-question-card__meta">
                <span class="quiz-question-card__type-preview">{{ $typeLabel }}</span>
                <span class="quiz-question-card__dot">&#8226;</span>
                <span class="quiz-question-card__marks-preview">{{ $marksLabel }}</span>
            </div>
        </div>

        <span class="quiz-question-card__status">{{ __('Saved') }}</span>

        <button type="button" class="quiz-question-card__icon-btn question-edit-toggle-btn" aria-label="{{ __('Edit question') }}" title="{{ __('Edit question') }}">
            <i class="far fa-edit"></i>
        </button>
        <button type="button" class="quiz-question-card__icon-btn question-toggle-caret" aria-label="{{ __('Collapse question') }}" title="{{ __('Collapse question') }}">
            <i class="fas fa-chevron-{{ $isOpen ? 'up' : 'down' }}"></i>
        </button>
    </div>

    <div class="quiz-question-card__body" @if(! $isOpen) hidden @endif>
        <input type="hidden" class="question-id-field" name="questions[{{ $questionIndex }}][id]" value="{{ $question['id'] ?? '' }}">
        <input type="hidden" class="question-type-select" data-field="question_type" name="questions[{{ $questionIndex }}][question_type]" value="{{ $questionType }}">

        <div class="quiz-question-card__question-head">
            <label>{{ __('Question') }}</label>
            <a href="javascript:;" class="quiz-question-card__preview-link">{{ __('Preview') }}</a>
        </div>

        <div class="quiz-question-card__prompt-shell">
            <div class="quiz-question-card__editor-tools" aria-hidden="true">
                <button type="button"><strong>B</strong></button>
                <button type="button"><em>I</em></button>
                <button type="button"><span>U</span></button>
                <button type="button" aria-label="{{ __('Bulleted list') }}">
                    <i class="fas fa-list-ul"></i>
                </button>
                <button type="button" aria-label="{{ __('Numbered list') }}">
                    <i class="fas fa-list-ol"></i>
                </button>
                <button type="button" aria-label="{{ __('Equation') }}"><span>Σ</span></button>
                <button type="button" aria-label="{{ __('Link') }}">
                    <i class="fas fa-link"></i>
                </button>
                <button type="button" aria-label="{{ __('Image') }}">
                    <i class="far fa-image"></i>
                </button>
            </div>
            <textarea class="form-control quiz-question-card__prompt-input" rows="3" data-field="prompt" name="questions[{{ $questionIndex }}][prompt]" required>{{ $question['prompt'] ?? '' }}</textarea>
            <small class="quiz-question-card__counter">{{ strlen((string) ($question['prompt'] ?? '')) }}/1000</small>
        </div>

        <div class="single-choice-fields mt-3">
            <div class="quiz-question-card__section-head">
                <h6 class="mb-0">{{ __('Answer Options') }}</h6>
            </div>

            <div class="options-list">
                @foreach (($question['options'] ?? []) as $optionIndex => $option)
                    @php
                        $optionLabel = chr(65 + (int) $optionIndex);
                    @endphp
                    <div class="question-option-row quiz-question-card__option-row {{ !empty($option['is_correct']) ? 'is-correct' : '' }}">
                        <button type="button" class="quiz-question-card__option-drag" aria-label="{{ __('Drag option') }}" title="{{ __('Drag option') }}">
                            <i class="fas fa-grip-vertical"></i>
                        </button>
                        <span class="quiz-question-card__option-label">{{ $optionLabel }}</span>
                        <input type="hidden" class="option-id-field" name="questions[{{ $questionIndex }}][options][{{ $optionIndex }}][id]" value="{{ $option['id'] ?? '' }}">
                        <input type="text" class="form-control quiz-question-card__option-input" data-option-field="option_text" name="questions[{{ $questionIndex }}][options][{{ $optionIndex }}][option_text]" value="{{ $option['option_text'] ?? '' }}" placeholder="{{ __('Option text') }}">
                        <span class="quiz-question-card__correct-pill">
                            <i class="fas fa-circle"></i>
                            <span>{{ __('Correct answer') }}</span>
                        </span>
                        <label class="quiz-question-card__correct-toggle">
                            <input type="hidden" class="option-correct-hidden" name="questions[{{ $questionIndex }}][options][{{ $optionIndex }}][is_correct]" value="0">
                            <input class="form-check-input" type="checkbox" data-option-field="is_correct" name="questions[{{ $questionIndex }}][options][{{ $optionIndex }}][is_correct]" value="1" @checked(!empty($option['is_correct']))>
                            <span class="quiz-question-card__correct-dot"></span>
                        </label>
                        <button type="button" class="btn btn-link text-danger p-0 remove-option-btn" aria-label="{{ __('Remove option') }}">
                            <i class="far fa-trash-alt"></i>
                        </button>
                    </div>
                @endforeach
            </div>

            <div class="quiz-question-card__add-option-wrap">
                <button type="button" class="quiz-question-card__add-option-link add-option-btn">
                    <i class="fas fa-plus"></i>
                    <span>{{ __('Add Option') }}</span>
                </button>
            </div>
        </div>

        <div class="short-answer-fields mt-3">
            <div class="form-grp">
                <label>{{ __('Instructor Answer Key') }}</label>
                <input type="text" class="form-control" data-field="correct_text_answer" name="questions[{{ $questionIndex }}][correct_text_answer]" value="{{ $question['correct_text_answer'] ?? '' }}" placeholder="{{ __('Exact answer for auto-marking') }}">
            </div>
        </div>

        <div class="quiz-question-card__footer">
            <div class="quiz-question-card__footer-item">
                <label>{{ __('Points') }}</label>
                <input type="number" min="1" class="form-control" data-field="marks" name="questions[{{ $questionIndex }}][marks]" value="{{ $question['marks'] ?? 1 }}" required>
            </div>
            <div class="quiz-question-card__footer-item">
                <label>{{ __('Difficulty') }}</label>
                <select class="form-select" data-field="difficulty" name="questions[{{ $questionIndex }}][difficulty]">
                    <option value="easy" @selected($difficultyValue === 'easy')>{{ __('Easy') }}</option>
                    <option value="medium" @selected($difficultyValue === 'medium')>{{ __('Medium') }}</option>
                    <option value="hard" @selected($difficultyValue === 'hard')>{{ __('Hard') }}</option>
                </select>
            </div>
            <label class="quiz-question-card__shuffle">
                <input type="checkbox" data-field="shuffle_options" name="questions[{{ $questionIndex }}][shuffle_options]" value="1" @checked(!empty($question['shuffle_options']))>
                <span>{{ __('Shuffle options') }}</span>
            </label>
        </div>
    </div>
</div>
