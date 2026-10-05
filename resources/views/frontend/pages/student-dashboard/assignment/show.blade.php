@extends('frontend.student-dashboard.layouts.master')
@section('meta_title', __('Assignment') . ' || ' . $setting->app_name)

@section('dashboard-contents')
    <div class="dashboard__content-wrap">
        <!-- Top Header & Breadcrumbs -->
        <div class="mb-4 d-flex flex-wrap justify-content-end align-items-center gap-3">
            <a href="{{ route('student.enrolled-courses') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="fas fa-chevron-left me-2"></i>{{ __('Back to Courses') }}
            </a>
        </div>

        <div class="row g-4">
            <!-- Left Primary Column -->
            <div class="col-lg-8">
                <!-- Task Content Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body px-4">
                        <div class="d-flex align-items-center mb-4">
                            <h4 class="mb-0 fw-bold text-dark">{{ __('Assignment Overview') }}</h4>
                        </div>

                        <div class="assignment-content">
                            <h6 class="text-muted text-uppercase small ls-1 mb-3 fw-bold">{{ __('Description') }}</h6>
                            <div class="p-4 bg-light rounded text-dark mb-5 border-start border-primary border-4 text-break lh-lg rich-content">
                                {!! clean($assignment->description) !!}
                            </div>

                            @if ($assignment->instructions)
                                <h6 class="text-muted text-uppercase small ls-1 mb-3 fw-bold">{{ __('Instructions') }}</h6>
                                <div class="p-4 bg-light rounded text-dark border-start border-warning border-4 text-break lh-lg rich-content">
                                    {!! clean($assignment->instructions) !!}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Submission Activity Card -->
                @if ($submission)
                    <div class="card border-0 shadow-sm mb-4">
                        @if($submission->is_late)
                        <div class="card-header bg-white border-bottom-0 pt-4 px-4 px-md-5 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-history me-3 text-success"></i>{{ __('Submission Activity') }}</h5>
                            <span class="badge bg-danger px-3 py-2 shadow-sm animate__animated animate__pulse animate__infinite"><i class="fas fa-clock me-1"></i> {{ __('Late') }}</span>
                        </div>
                        @endif
                        <div class="card-body px-4">
                            @if ($submission->status == 'graded')
                                <div class="alert alert-success border-0 bg-success-soft text-dark p-4 rounded-4 position-relative">
                                    <div class="row align-items-center">
                                        <div class="col-md-9 border-end">
                                            <h5 class="fw-bold text-success mb-2"><i class="fas fa-trophy me-2"></i>{{ __('Congratulations! Assignment Graded') }}</h5>
                                            <p class="mb-0 opacity-75">{{ __('Your work has been reviewed and assessed by the instructor.') }}</p>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <div class="display-6 fw-bold text-primary">{{ (int) $submission->feedback->marks ?? 0 }}</div>
                                            <div class="text-muted small fw-bold mt-1 text-uppercase ls-1">/ {{ (int) $assignment->max_marks }} {{ __('Marks') }}</div>
                                        </div>
                                    </div>
                                </div>
                            @elseif($submission->status == 'resubmission')
                                <div class="alert alert-primary border-0 bg-primary-soft text-dark p-4 rounded-4 shadow-sm">
                                    <h5 class="fw-bold text-primary mb-2"><i class="fas fa-sync-alt me-2"></i>{{ __('Resubmitted Successfully') }}</h5>
                                    <p class="mb-0 opacity-75 text-dark fw-medium">{{ __('Your new work is pending assessment.') }}</p>
                                </div>
                            @else
                                <div class="alert alert-warning border-0 bg-warning-soft text-dark p-4 rounded-4 shadow-sm">
                                    <h5 class="fw-bold mb-2"><i class="fas fa-hourglass-half me-2"></i>{{ __('Submission Received') }}</h5>
                                    <p class="mb-0 opacity-75 text-dark fw-medium">{{ __('Handed in on') }} {{ formatDate($submission->created_at) }}. {{ __('Pending Assessment.') }}</p>
                                </div>
                            @endif
                            
                            @if($submission->feedback?->feedback)
                                <div class="mt-4 p-4 rounded bg-white shadow-sm border">
                                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fas fa-comment-dots me-2 text-primary"></i>{{ __('Instructor Feedback') }}</h6>
                                    <div class="text-muted lh-base fst-italic">"{!! nl2br(e($submission->feedback->feedback)) !!}"</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Submission Form Area -->
                @php
                    $showForm = false;
                    if (!$submission) {
                        if (!($assignment->due_date && now()->isAfter($assignment->due_date) && !$assignment->late_submission_allowed)) {
                            $showForm = true;
                        }
                    } else {
                        if (!in_array($submission->status, ['graded', 'resubmission','submitted']) || ($submission->feedback && $submission->feedback->allow_resubmission)) {
                            $showForm = true;
                        }
                    }
                @endphp

                @if ($showForm)
                    <div class="card border-0 shadow-sm @if($submission) border-top border-primary border-5 @endif">
                        <div class="card-body px-4">
                            <h5 class="mb-4 fw-bold text-dark border-bottom pb-3">
                                <i class="fas @if ($submission) fa-redo @else fa-paper-plane @endif me-3 text-primary"></i>
                                {{ $submission ? __('Resubmit Assignment') : __('Submit Your Work') }}
                            </h5>
                            
                            <form id="assignmentSubmitForm" action="{{ route('student.assignment.submit') }}" method="POST">
                                @csrf
                                <input type="hidden" name="assignment_id" value="{{ $assignment->id }}">
                                <div id="submissionAlert"></div>

                                @if ($assignment->submission_type === 'file')
                                    <div class="upload-wrapper bg-light p-4 rounded-4 text-center border-2 border-dashed border-primary position-relative" id="fileCustomDrop" style="cursor: pointer;">
                                        <div class="py-5" id="dropZoneContent">
                                            <div class="display-5 text-primary mb-3"><i class="fas fa-cloud-upload-alt shadow-sm bg-white p-3 rounded-circle"></i></div>
                                            <h5 class="fw-bold">{{ __('Drag and Drop Files') }}</h5>
                                            <p class="text-muted px-4">{{ __('Upload your work using the button below or drag it here.') }}</p>
                                            <button type="button" class="btn btn-primary px-4 py-2 mt-2 shadow-sm fw-bold" id="fileBrowseBtn">
                                                <i class="fas fa-plus me-2"></i>{{ __('Add Files') }}
                                            </button>
                                        </div>
                                        <div class="progress mt-4 d-none" id="uploadProgressContainer" style="height: 10px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary shadow-sm"
                                                id="uploadProgressBar" role="progressbar" aria-valuenow="0" aria-valuemin="0"
                                                aria-valuemax="100" style="width: 0%;"></div>
                                        </div>
                                    </div>
                                    <div id="uploadedFilesList" class="mt-4 row g-3"></div>
                                @elseif($assignment->submission_type === 'text')
                                    <div class="form-group mb-4">
                                        <label for="text_submission" class="form-label h6 fw-bold opacity-75 mb-3">{{ __('Your Answer') }}</label>
                                        <textarea name="text_submission" id="text_submission" class="text-editor"
                                            placeholder="{{ __('Provide your detailed answer here...') }}">{{ $submission->text_submission ?? '' }}</textarea>
                                    </div>
                                @elseif($assignment->submission_type === 'link')
                                    <div class="form-group mb-4">
                                        <label for="link_submission" class="form-label h6 fw-bold opacity-75 mb-3">{{ __('Submission Link') }}</label>
                                        <div class="input-group input-group-lg shadow-sm">
                                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-link text-primary"></i></span>
                                            <input type="url" name="link_submission" id="link_submission" class="form-control border-start-0 py-3"
                                                placeholder="{{ __('https://example.com/share-link') }}" value="{{ $submission->link_submission ?? '' }}" required>
                                        </div>
                                    </div>
                                @endif

                                <div class="d-grid mt-5">
                                    <button type="submit" id="submitAssignmentBtn" class="btn btn-primary btn-lg shadow-sm py-3 fw-bold">
                                        {{ $submission ? __('Resubmit Assignment') : __('Hand In Assignment') }} <i class="fas fa-paper-plane ms-2"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @elseif(!$submission && $assignment->due_date && now()->isAfter($assignment->due_date) && !$assignment->late_submission_allowed)
                    <div class="card border-0 shadow-sm bg-danger-soft border-start border-danger border-5">
                        <div class="card-body px-4 text-center py-5">
                            <div class="display-4 text-danger mb-3"><i class="fas fa-lock"></i></div>
                            <h4 class="fw-bold text-dark">{{ __('Submission Window Closed') }}</h4>
                            <p class="text-muted mb-0 mx-auto" style="max-width: 500px;">
                                {{ __('This assignment reached its deadline on') }} <strong>{{ formatDate($assignment->due_date) }}</strong>. 
                                {{ __('Late submissions are not accepted for this task.') }}
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Right Sidebar Column -->
            <div class="col-lg-4">
                <div class="card border-0">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0 fw-bold">{{ __('Quick Summary') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-4">
                            <!-- Max Marks -->
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 bg-warning-soft rounded p-3 text-warning me-3">
                                    <i class="fas fa-star fa-lg"></i>
                                </div>
                                <div>
                                    <div class="text-muted small fw-bold text-uppercase ls-1">{{ __('Maximum Marks') }}</div>
                                    <h5 class="mb-0 text-dark fw-bold">{{ (int) $assignment->max_marks }}</h5>
                                </div>
                            </div>
                            
                            <!-- Due Date -->
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 bg-primary-soft rounded p-3 text-primary me-3">
                                    <i class="fas fa-calendar-check fa-lg"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="text-muted small fw-bold text-uppercase ls-1">{{ __('Hand-in Date') }}</div>
                                    <h5 class="mb-0 text-dark fw-bold">{{ $assignment->due_date ? formatDate($assignment->due_date) : __('Ongoing') }}</h5>
                                    @if($assignment->due_date)
                                        <div class="small fw-bold mt-1 @if(now()->isAfter($assignment->due_date)) text-danger @else text-primary @endif">
                                            @if(now()->isAfter($assignment->due_date))
                                                <i class="fas fa-exclamation-circle me-1"></i> {{ __('Deadline Reached') }}
                                            @else
                                                <i class="fas fa-clock me-1"></i> {{ now()->diffForHumans($assignment->due_date, true) }} {{ __('Remaining') }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Submission Format -->
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 bg-info-soft rounded p-3 text-info me-3">
                                    <i class="fas fa-file-invoice fa-lg"></i>
                                </div>
                                <div>
                                    <div class="text-muted small fw-bold text-uppercase ls-1">{{ __('Required Format') }}</div>
                                    <span class="badge bg-info text-dark fw-bold text-uppercase ls-1 p-2">{{ __($assignment->submission_type) }}</span>
                                </div>
                            </div>

                            <!-- Late Submission -->
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 bg-danger-soft rounded p-3 text-danger me-3">
                                    <i class="fas fa-exclamation-triangle fa-lg"></i>
                                </div>
                                <div>
                                    <div class="text-muted small fw-bold text-uppercase ls-1">{{ __('Late Policy') }}</div>
                                    @if ($assignment->late_submission_allowed)
                                        <h6 class="mb-0 text-dark fw-bold">
                                            {{ __('Accepted') }} 
                                            <span class="text-danger small fst-italic">({{ $assignment->late_penalty ?? 0 }}% {{ __('Penalty') }})</span>
                                        </h6>
                                    @else
                                        <h6 class="mb-0 text-danger fw-bold">{{ __('Lock after deadline') }}</h6>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        <div class="bg-light p-3 rounded text-center">
                            <p class="small text-muted mb-0">
                                {{ __('Need help? Contact your instructor via the Course Portal.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('frontend/js/tinymce/js/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('frontend/js/custom-tinymce.js') }}"></script>
    <script src="{{ asset('backend/js/resumable.min.js') }}"></script>
    <script>
        "use strict";
        $(document).ready(function () {
            let filePaths = [];
            const submitBtn = $('#submitAssignmentBtn');
            const form = $('#assignmentSubmitForm');

            @if ($assignment->submission_type === 'file' && $showForm)
                submitBtn.prop('disabled', true);

                let resumable = new Resumable({
                    target: '{{ route('student.assignment.upload-chunk') }}',
                    query: {
                        _token: '{{ csrf_token() }}',
                        assignment_id: '{{ $assignment->id }}'
                    },
                    chunkSize: 1 * 1024 * 1024,
                    headers: {
                        'Accept': 'application/json'
                    },
                    testChunks: false,
                    throttleProgressCallbacks: 1,
                });

                resumable.assignBrowse(document.getElementById('fileBrowseBtn'));
                resumable.assignDrop(document.getElementById('fileCustomDrop'));

                resumable.on('fileAdded', function (file) {
                    $('#uploadProgressContainer').removeClass('d-none');
                    $('#uploadProgressBar').css('width', '0%').html('0%').removeClass('bg-success');
                    submitBtn.prop('disabled', true);
                    resumable.upload();
                });

                resumable.on('fileProgress', function (file) {
                    let progress = Math.floor(file.progress() * 100);
                    $('#uploadProgressBar').css('width', `${progress}%`).html(`${progress}%`);
                });

                resumable.on('fileSuccess', function (file, response) {
                    let res = JSON.parse(response);
                    if (res.status) {
                        $('#uploadProgressBar').addClass('bg-success').html('Upload Complete');
                        filePaths.push(res.file_path);

                        // Append hidden input for form submission
                        form.append(`<input type="hidden" name="file_paths[]" value="${res.file_path}">`);

                        // Show visual confirmation
                        $('#uploadedFilesList').append(`
                            <div class="col-md-6 animate__animated animate__fadeInUp">
                                <div class="card border shadow-sm h-100 overflow-hidden">
                                    <div class="card-body p-3 d-flex align-items-center">
                                        <div class="flex-shrink-0 bg-success-soft rounded p-2 text-success me-3">
                                            <i class="fas fa-check-circle fa-lg"></i>
                                        </div>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <div class="text-dark fw-bold text-truncate small">${file.fileName}</div>
                                            <div class="text-success small fw-bold">{{ __('Upload Ready') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `);

                        submitBtn.prop('disabled', false);
                        toastr.success("{{ __('File uploaded successfully.') }}");

                        // Hide progress bar shortly after
                        setTimeout(() => {
                            $('#uploadProgressContainer').addClass('d-none');
                        }, 2000);
                    }
                });

                resumable.on('fileError', function (file, response) {
                    toastr.error("{{ __('Upload failed') }}");
                    $('#uploadProgressContainer').addClass('d-none');
                });

                // Drop zone effects
                const dropZone = document.getElementById('fileCustomDrop');
                dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.style.backgroundColor = '#f1f8ff'; });
                dropZone.addEventListener('dragleave', (e) => { e.preventDefault(); dropZone.style.backgroundColor = ''; });
                dropZone.addEventListener('drop', (e) => { dropZone.style.backgroundColor = ''; });
            @endif

            // Form Submit Intercepter
            form.on('submit', function (e) {
                e.preventDefault();

                if (typeof tinymce !== 'undefined') {
                    tinymce.triggerSave();
                }

                @if ($assignment->submission_type === 'text')
                    if (!$('#text_submission').val().trim()) {
                        toastr.error("{{ __('Please enter your assignment answer.') }}");
                        return false;
                    }
                @endif

                let formData = new FormData(this);
                const originalBtnHtml = submitBtn.html();

                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> {{ __('Handing in') }}...');

                $.ajax({
                    method: 'POST',
                    url: $(this).attr('action'),
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.status === 'success') {
                            toastr.success(response.message);
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            toastr.error(response.message);
                            submitBtn.prop('disabled', false).html(originalBtnHtml);
                        }
                    },
                    error: function (xhr) {
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            toastr.error(xhr.responseJSON.message);
                        } else {
                            toastr.error('An error occurred during submission.');
                        }
                        submitBtn.prop('disabled', false).html(originalBtnHtml);
                    }
                });
            });
        });
    </script>
@endpush