<div class="modal-header">
    <h5 class="modal-title fs-5">{{ __('Evaluate Submission') }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="p-3">
    <form action="{{ route('instructor.assignment.grade') }}" class="instructor__profile-form grading-form-submit" method="POST">
        @csrf
        <input type="hidden" name="submission_id" value="{{ $submission->id }}">
        
        <div class="col-md-12">
            <div class="form-grp mb-3">
                <label class="form-label fw-bold" for="marks{{ $submission->id }}">{{ __('Marks') }} ({{ __('Max') }}: {{ (int)$assignment->max_marks }}) <code>*</code></label>
                <input type="number" id="marks{{ $submission->id }}" name="marks" value="{{ $submission->feedback->marks ?? '' }}" max="{{ $assignment->max_marks }}" min="0" required>
            </div>
            <div class="form-grp mb-3 mt-3">
                <label class="form-label fw-bold" for="written_feedback{{ $submission->id }}">{{ __('Written Feedback') }}</label>
                <textarea id="written_feedback{{ $submission->id }}" class="form-control" name="written_feedback" rows="4">{{ $submission->feedback->written_feedback ?? '' }}</textarea>
            </div>
            <div class="form-check mb-3 mt-3">
                <input class="form-check-input" type="checkbox" value="1" name="allow_resubmission" id="allowResubmission{{ $submission->id }}" {{ ($submission->feedback->allow_resubmission ?? false) ? 'checked' : '' }}>
                <label class="form-check-label fw-bold ms-2" for="allowResubmission{{ $submission->id }}">
                {{ __('Allow Re-submission') }}
                </label>
            </div>
            <div class="text-end mt-4">
                <button type="submit" class="btn btn-primary submit-btn">{{ __('Save Evaluation') }}</button>
            </div>
        </div>
    </form>
</div>
