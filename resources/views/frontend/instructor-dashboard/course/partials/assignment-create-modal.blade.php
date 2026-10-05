<div class="modal-header">
    <h1 class="modal-title fs-5" id="">{{ __('Add Assignment') }}</h1>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="p-3">
    <form action="{{ route('instructor.course-chapter.lesson.store') }}" method="POST"
        class="add_lesson_form instructor__profile-form">
        @csrf
        <input type="hidden" name="course_id" value="{{ $courseId }}">
        <input type="hidden" name="chapter_id" value="{{ $chapterId }}">
        <input type="hidden" name="type" value="{{ $type }}">

        <div class="col-md-12">
            <div class="form-grp">
                <label for="chapter">{{ __('Chapter') }} <code>*</code></label>
                <select name="chapter" id="chapter" class="chapter from-select">
                    <option value="">{{ __('Select') }}</option>
                    @foreach ($chapters as $chapter)
                        <option @selected($chapterId == $chapter->id) value="{{ $chapter->id }}">{{ $chapter->title }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-grp">
                <label for="title">{{ __('Title') }} <code>*</code></label>
                <input id="title" name="title" type="text" value="">
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-grp">
                <label for="description">{{ __('Description') }}</label>
                <textarea id="description" name="description" class="text-editor" rows="3"></textarea>
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-grp">
                <label for="instructions">{{ __('Instructions') }}</label>
                <textarea id="instructions" name="instructions" class="text-editor" rows="3"></textarea>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-grp">
                    <label for="submission_type">{{ __('Submission Type') }} <code>*</code></label>
                    <select name="submission_type" id="submission_type" class="from-select">
                        <option value="file">{{ __('File Upload') }}</option>
                        <option value="text">{{ __('Text Submission') }}</option>
                        <option value="link">{{ __('External Link') }}</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-grp">
                    <label for="due_date">{{ __('Due Date') }}</label>
                    <input id="due_date" name="due_date" type="datetime-local" value="">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-grp">
                    <label for="max_marks">{{ __('Max Marks') }} <code>*</code></label>
                    <input id="max_marks" name="max_marks" type="number" step="0.01" value="100">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-grp">
                    <label for="late_penalty">{{ __('Late Penalty') }} (%)</label>
                    <input id="late_penalty" name="late_penalty" type="number" step="1" value="">
                </div>
            </div>
            <div class="col-md-12 mt-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="late_submission_allowed"
                        id="late_submission_allowed" value="1">
                    <label class="form-check-label" for="late_submission_allowed">
                        {{ __('Allow Late Submissions') }}
                    </label>
                </div>
            </div>
        </div>

        <div class="modal-footer mt-4">
            <button type="submit" class="btn btn-primary submit-btn">{{ __('Create') }}</button>
        </div>
    </form>
</div>