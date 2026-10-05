<div class="modal-header">
    <h5 class="modal-title fs-5">{{ __('Text Submission') }} : {{ $submission->student->name }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="p-3 text-start">
    <div class="p-3 bg-light rounded text-dark">
        {!! clean($submission->text_submission) !!}
    </div>
</div>