<div class="modal-header">
    <h1 class="modal-title fs-5" id="">{{ __('Import Quiz Questions') }}</h1>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="p-3">
    <form action="{{ route('instructor.quiz.import-csv', $quizId) }}" method="POST"
        class="instructor__profile-form" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-md-12">
                <div class="form-grp mb-3">
                    <label class="form-file-manager-label" for="quiz_csv">{{ __('Upload CSV File') }} <code>*</code></label>
                    <div class="input-group">
                        <div class="input-group">
                            <input type="file" class="form-control" id="quiz_csv" name="quiz_csv" accept=".csv" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">{{ __('Upload') }}</button>
        </div>
    </form>
</div>
