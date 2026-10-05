@php
    $questions = old('questions', $quizFormData['questions'] ?? []);
    $selectedEducationLevel = old('education_level', $quizFormData['education_level'] ?? 'Senior School (CBC)');
    $selectedClassGrade = old('class_grade', $quizFormData['class_grade'] ?? 'Grade 10');
    $selectedSubject = old('subject', $quizFormData['subject'] ?? 'Mathematics');
    $quizEducationLevels = $metadataOptions['education_levels'] ?? [];
    $quizClassGradesByLevel = $metadataOptions['class_grades_by_level'] ?? [];
    $quizSubjectsByLevel = $metadataOptions['subjects_by_education_level'] ?? [];
    $quizClassGrades = $quizClassGradesByLevel[$selectedEducationLevel] ?? ($quizClassGradesByLevel['default'] ?? []);
    $quizSubjects = $quizSubjectsByLevel[$selectedEducationLevel] ?? ($metadataOptions['subjects'] ?? []);
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if (!empty($method) && strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="dashboard__contact-form-wrap">
                <div class="dashboard__contact-form">
                    <h5 class="mb-3">{{ __('Quiz Information') }}</h5>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-grp">
                                <label for="title">{{ __('Title') }} <span class="text-danger">*</span></label>
                                <input type="text" id="title" name="title" class="form-control" value="{{ old('title', $product->title) }}" required>
                                @error('title')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="form-grp">
                                <label for="description">{{ __('Description') }}</label>
                                <textarea id="description" name="description" class="form-control" rows="5">{{ old('description', $product->description) }}</textarea>
                                @error('description')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-grp">
                                <label for="tier">{{ __('Tier') }} <span class="text-danger">*</span></label>
                                <select id="tier" name="tier" class="form-select" required>
                                    @foreach (['short' => __('Short'), 'long' => __('Long')] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('tier', $quizFormData['tier'] ?? 'short') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('tier')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-grp">
                                <label for="education_level">{{ __('Education Level') }}</label>
                                <select id="education_level" name="education_level" class="form-select">
                                    @foreach ($quizEducationLevels as $option)
                                        <option value="{{ $option }}" @selected($selectedEducationLevel === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-grp">
                                <label for="class_grade">{{ __('Class / Grade') }}</label>
                                <select id="class_grade" name="class_grade" class="form-select">
                                    @foreach ($quizClassGrades as $option)
                                        <option value="{{ $option }}" @selected($selectedClassGrade === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-grp">
                                <label for="subject">{{ __('Subject') }}</label>
                                <select id="subject" name="subject" class="form-select">
                                    @foreach ($quizSubjects as $option)
                                        <option value="{{ $option }}" @selected($selectedSubject === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-grp">
                                <label for="difficulty">{{ __('Difficulty') }} <span class="text-danger">*</span></label>
                                <select id="difficulty" name="difficulty" class="form-select" required>
                                    @foreach (['beginner', 'intermediate', 'advanced'] as $difficulty)
                                        <option value="{{ $difficulty }}" @selected(old('difficulty', $quizFormData['difficulty'] ?? 'intermediate') === $difficulty)>{{ ucfirst($difficulty) }}</option>
                                    @endforeach
                                </select>
                                @error('difficulty')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-grp">
                                <label for="duration_minutes">{{ __('Duration (Minutes)') }}</label>
                                <input type="number" min="1" id="duration_minutes" name="duration_minutes" class="form-control" value="{{ old('duration_minutes', $quizFormData['duration_minutes'] ?? '') }}">
                                @error('duration_minutes')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-grp">
                                <label for="attempt_limit">{{ __('Attempt Limit') }} <span class="text-danger">*</span></label>
                                <input type="number" min="1" id="attempt_limit" name="attempt_limit" class="form-control" value="{{ old('attempt_limit', $quizFormData['attempt_limit'] ?? 1) }}" required>
                                @error('attempt_limit')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-grp">
                                <label for="pass_mark">{{ __('Pass Mark') }} <span class="text-danger">*</span></label>
                                <input type="number" min="0" id="pass_mark" name="pass_mark" class="form-control" value="{{ old('pass_mark', $quizFormData['pass_mark'] ?? 0) }}" required>
                                @error('pass_mark')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard__contact-form-wrap mt-4">
                <div class="dashboard__contact-form">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">{{ __('Questions') }}</h5>
                        <button type="button" class="btn btn-sm btn-primary" id="add-question-btn">{{ __('Add Question') }}</button>
                    </div>

                    @error('questions')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror

                    <div id="question-list">
                        @foreach ($questions as $questionIndex => $question)
                            @include('partials.product-quiz-question-card', ['question' => $question, 'questionIndex' => $questionIndex])
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="dashboard__contact-form-wrap">
                <div class="dashboard__contact-form">
                    <h5 class="mb-3">{{ __('Product Settings') }}</h5>

                    <div class="form-grp">
                        <label for="category_id">{{ __('Category') }}</label>
                        <select id="category_id" name="category_id" class="form-select">
                            <option value="">{{ __('Select Category') }}</option>
                            @foreach ($categories as $category)
                                @if (isset($category->subCategories) && $category->subCategories->count())
                                    <optgroup label="{{ $category->translation?->name ?? $category->name }}">
                                        @foreach ($category->subCategories as $subCategory)
                                            <option value="{{ $subCategory->id }}" @selected((string) old('category_id', $product->category_id) === (string) $subCategory->id)>
                                                {{ $subCategory->translation?->name ?? $subCategory->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @else
                                    <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>
                                        {{ $category->translation?->name ?? $category->name }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        @error('category_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-grp">
                        <label for="price">{{ __('Price') }} <span class="text-danger">*</span></label>
                        <input type="number" min="0" step="0.01" id="price" name="price" class="form-control" value="{{ old('price', $product->price ?? 0) }}" required>
                        <small class="text-muted">{{ __('Short quizzes max KES 50. Long quizzes min KES 20.') }}</small>
                        @error('price')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-grp">
                        <label for="discount">{{ __('Discount Price') }}</label>
                        <input type="number" min="0" step="0.01" id="discount" name="discount" class="form-control" value="{{ old('discount', $product->discount) }}">
                        <small class="text-muted">{{ __('If set, this becomes the actual sale price.') }}</small>
                        @error('discount')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-grp">
                        <label for="status">{{ __('Status') }} <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="is_draft" @selected(old('status', $product->status ?? 'is_draft') === 'is_draft')>{{ __('Draft') }}</option>
                            <option value="active" @selected(old('status', $product->status ?? 'is_draft') === 'active')>{{ __('Active') }}</option>
                            <option value="inactive" @selected(old('status', $product->status ?? 'is_draft') === 'inactive')>{{ __('Inactive') }}</option>
                        </select>
                        @error('status')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    @if (!empty($showApprovalStatus))
                        <div class="form-grp">
                            <label for="is_approved">{{ __('Approval Status') }}</label>
                            <select id="is_approved" name="is_approved" class="form-select">
                                @foreach (['approved' => __('Approved'), 'pending' => __('Pending'), 'rejected' => __('Rejected')] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('is_approved', $product->is_approved ?? 'approved') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    @elseif (!empty($isEditing))
                        <div class="alert alert-info mt-3">
                            <small>{{ __('Saving changes will send the quiz back for admin approval.') }}</small>
                        </div>
                    @endif

                    <div class="form-grp">
                        <label for="thumbnail">{{ __('Thumbnail Image') }}</label>
                        @if (!empty($product->thumbnail))
                            <div class="mb-2">
                                <img src="{{ asset($product->thumbnail) }}" alt="{{ $product->title }}" style="max-width: 180px;">
                            </div>
                        @endif
                        <input type="file" id="thumbnail" name="thumbnail" class="form-control" accept="image/*">
                        @error('thumbnail')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-grp mt-4">
                        <button type="submit" class="btn btn-primary w-100">{{ $submitLabel }}</button>
                    </div>
                    <div class="form-grp mt-2">
                        <a href="{{ $backUrl }}" class="btn btn-secondary w-100">{{ __('Back') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<template id="question-card-template">
    @include('partials.product-quiz-question-card', ['question' => ['prompt' => '', 'question_type' => 'single_choice', 'marks' => 1, 'correct_text_answer' => '', 'options' => [['option_text' => '', 'is_correct' => false], ['option_text' => '', 'is_correct' => false]]], 'questionIndex' => '__INDEX__'])
</template>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const questionList = document.getElementById('question-list');
        const template = document.getElementById('question-card-template');
        const addQuestionBtn = document.getElementById('add-question-btn');
        const educationLevelSelect = document.getElementById('education_level');
        const classGradeSelect = document.getElementById('class_grade');
        const subjectSelect = document.getElementById('subject');
        const classGradesByLevel = @json($quizClassGradesByLevel);
        const subjectsByLevel = @json($quizSubjectsByLevel);

        const populateDependentOptions = (educationLevel, preferredGrade = '', preferredSubject = '') => {
            const grades = classGradesByLevel[educationLevel] ?? classGradesByLevel.default ?? [];
            const subjects = subjectsByLevel[educationLevel] ?? @json($metadataOptions['subjects'] ?? []);
            const selectedGrade = grades.includes(preferredGrade) ? preferredGrade : (preferredGrade || grades[0] || '');
            const selectedSubject = subjects.includes(preferredSubject) ? preferredSubject : (preferredSubject || subjects[0] || '');

            if (classGradeSelect) {
                const gradeOptions = grades.includes(selectedGrade) || !selectedGrade
                    ? grades
                    : [...grades, selectedGrade];
                classGradeSelect.innerHTML = gradeOptions.map((option) => `<option value="${option}">${option}</option>`).join('');
                classGradeSelect.value = selectedGrade;
            }

            if (subjectSelect) {
                const subjectOptions = subjects.includes(selectedSubject) || !selectedSubject
                    ? subjects
                    : [...subjects, selectedSubject];
                subjectSelect.innerHTML = subjectOptions.map((option) => `<option value="${option}">${option}</option>`).join('');
                subjectSelect.value = selectedSubject;
            }
        };

        educationLevelSelect?.addEventListener('change', function () {
            populateDependentOptions(this.value);
        });

        if (educationLevelSelect) {
            populateDependentOptions(educationLevelSelect.value, classGradeSelect?.value || '', subjectSelect?.value || '');
        }

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
                });
            });
        };

        const toggleQuestionType = (card) => {
            const typeSelect = card.querySelector('.question-type-select');
            const optionWrap = card.querySelector('.single-choice-fields');
            const shortAnswerWrap = card.querySelector('.short-answer-fields');
            const isChoice = typeSelect.value === 'single_choice';

            optionWrap.style.display = isChoice ? '' : 'none';
            shortAnswerWrap.style.display = isChoice ? 'none' : '';
        };

        const bindQuestionCard = (card) => {
            card.querySelector('.remove-question-btn').addEventListener('click', function() {
                card.remove();
                refreshQuestionIndexes();
            });

            card.querySelector('.move-up-question-btn').addEventListener('click', function() {
                const previous = card.previousElementSibling;
                if (previous) {
                    questionList.insertBefore(card, previous);
                    refreshQuestionIndexes();
                }
            });

            card.querySelector('.move-down-question-btn').addEventListener('click', function() {
                const next = card.nextElementSibling;
                if (next) {
                    questionList.insertBefore(next, card);
                    refreshQuestionIndexes();
                }
            });

            card.querySelector('.question-type-select').addEventListener('change', function() {
                toggleQuestionType(card);
            });

            card.querySelector('.add-option-btn').addEventListener('click', function() {
                const optionsWrap = card.querySelector('.options-list');
                const questionIndex = card.dataset.questionIndex || 0;
                const optionIndex = optionsWrap.querySelectorAll('.question-option-row').length;
                const row = document.createElement('div');
                row.className = 'question-option-row row g-2 align-items-center mt-2';
                row.innerHTML = `
                    <input type="hidden" class="option-id-field" name="questions[${questionIndex}][options][${optionIndex}][id]" value="">
                    <div class="col-md-7">
                        <input type="text" class="form-control" data-option-field="option_text" name="questions[${questionIndex}][options][${optionIndex}][option_text]" placeholder="{{ __('Option text') }}">
                    </div>
                    <div class="col-md-3">
                        <div class="form-check mt-2">
                            <input type="hidden" class="option-correct-hidden" name="questions[${questionIndex}][options][${optionIndex}][is_correct]" value="0">
                            <input class="form-check-input" type="checkbox" data-option-field="is_correct" name="questions[${questionIndex}][options][${optionIndex}][is_correct]" value="1">
                            <label class="form-check-label">{{ __('Correct') }}</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-option-btn w-100">{{ __('Remove') }}</button>
                    </div>
                `;
                optionsWrap.appendChild(row);
                bindOptionRow(row, card);
                refreshQuestionIndexes();
            });

            card.querySelectorAll('.question-option-row').forEach((row) => bindOptionRow(row, card));
            toggleQuestionType(card);
        };

        const bindOptionRow = (row) => {
            const removeBtn = row.querySelector('.remove-option-btn');
            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    row.remove();
                    refreshQuestionIndexes();
                });
            }
        };

        addQuestionBtn.addEventListener('click', function() {
            const questionIndex = questionList.querySelectorAll('.quiz-question-card').length;
            const html = template.innerHTML.replaceAll('__INDEX__', questionIndex);
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            const card = wrapper.firstElementChild;
            questionList.appendChild(card);
            bindQuestionCard(card);
            refreshQuestionIndexes();
        });

        questionList.querySelectorAll('.quiz-question-card').forEach((card) => bindQuestionCard(card));
        refreshQuestionIndexes();
    });
</script>
