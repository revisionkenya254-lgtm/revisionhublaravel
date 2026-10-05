@extends('frontend.instructor-dashboard.layouts.master')
@section('dashboard-title', __('Assignment Submissions'))

@section('dashboard-contents')
    <div class="dashboard__content-wrap">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="title">{{ __('Submissions for') }} : {{ $assignment->title }}</h4>
            <a href="{{ url()->previous() }}" class="btn btn-primary"><i class="fas fa-arrow-left"></i> {{ __('Back') }}</a>
        </div>

        <div class="dashboard__review-table mb-4">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <p class="mb-0 text-muted fw-bold">{{ __('Total Submissions') }}</p>
                    <h4 class="text-primary">{{ $submissions->total() }}</h4>
                </div>
                <div class="col-md-3">
                    <p class="mb-0 text-muted fw-bold">{{ __('Max Marks') }}</p>
                    <h4>{{ (int) $assignment->max_marks }}</h4>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="badge bg-secondary p-2">{{ __('Deadline') }} :
                        {{ $assignment->due_date ? formatDate($assignment->due_date) : __('No strict deadline') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="dashboard__review-table table-responsive">
            <table class="table table-borderless">
                <thead>
                    <tr>
                        <th>{{ __('Student') }}</th>
                        <th class="text-center">{{ __('Submitted At') }}</th>
                        <th class="text-center">{{ __('Submission') }}</th>
                        <th class="text-center">{{ __('Status') }}</th>
                        <th class="text-center">{{ __('Marks') }}</th>
                        <th class="text-center">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($submissions as $submission)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2">
                                        <img src="{{ asset($submission->student->image ?? 'uploads/website-images/default-avatar.png') }}"
                                            class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $submission->student->name }}</h6>
                                        <small class="text-muted">{{ $submission->student->email }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                {{ formatDate($submission->created_at) }}
                                @if ($assignment->due_date && $submission->created_at->gt($assignment->due_date))
                                    <br><span class="badge bg-danger text-white"><i class="fas fa-exclamation-circle"></i>
                                        {{ __('Late') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($assignment->submission_type === 'file' && !empty($submission->file_paths))
                                    @php $files = is_string($submission->file_paths) ? json_decode($submission->file_paths) : $submission->file_paths; @endphp
                                    @if (is_array($files))
                                        @foreach ($files as $path)
                                            <a href="{{ route('instructor.assignment.download-submission', ['path' => $path]) }}"
                                                target="_blank" class="assignment_action_btn mt-1"><i class="fas fa-download"></i>
                                                {{ __('Download') }}</a>
                                        @endforeach
                                    @endif
                                @elseif($assignment->submission_type === 'link')
                                    <a href="{{ $submission->link_submission }}" target="_blank"
                                        class="assignment_action_btn mt-1"><i class="fas fa-link"></i>
                                        {{ __('View Link') }}</a>
                                @elseif($assignment->submission_type === 'text')
                                    <a href="javascript:;" class="assignment_action_btn mt-1 load-text-modal"
                                        data-id="{{ $submission->id }}"><i class="fas fa-align-left"></i>
                                        {{ __('Read text') }}</a>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($submission->status === 'graded')
                                    <span class="badge bg-success">{{ __('Graded') }}</span>
                                @elseif($submission->status === 'late')
                                    <span class="badge bg-warning">{{ __('Late') }}</span>
                                @elseif($submission->status === 'resubmission')
                                    <span class="badge bg-primary">{{ __('Resubmitted') }}</span>
                                @else
                                    <span class="badge bg-info">{{ __('Submitted') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span
                                    class="fw-bold">{{ $submission?->feedback?->marks ? number_format($submission->feedback->marks, 0) : '--' }}</span>
                                /
                                {{ (int) $assignment->max_marks }}
                            </td>
                            <td class="text-center">
                                <a href="javascript:;" class="assignment_action_btn mt-1 load-grade-modal"
                                    data-id="{{ $submission->id }}">
                                    @if ($submission->status === 'graded')
                                        <i class="fas fa-edit"></i> {{ __('Update') }}
                                    @else
                                        <i class="fas fa-check-double"></i> {{ __('Grade') }}
                                    @endif
                                </a>
                                <a href="{{ route('instructor.assignment.destroy-submission', $submission->id) }}"
                                    class="assignment_action_btn mt-1 ms-1 text-white bg-danger delete-item"
                                    data-delete-title="{{ $submission->student->name }}"
                                    aria-label="{{ __('Delete') }}"><i
                                        class="fas fa-trash-alt"></i> {{ __('Delete') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">{{ __('No submissions found yet!') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if ($submissions->hasPages())
                {{ $submissions->links() }}
            @endif
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .dashboard__review-table tbody tr td {
            padding: 10px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        "use strict";
        $(document).ready(function () {
            $('.load-text-modal').on('click', function (e) {
                e.preventDefault();
                let submissionId = $(this).data('id');
                $.ajax({
                    type: "GET",
                    url: "{{ url('instructor/assignment/text-modal') }}/" + submissionId,
                    success: function (response) {
                        $(".dynamic-modal .modal-content").html(response);
                        $(".dynamic-modal").modal("show");
                    },
                    error: function (xhr) {
                        toastr.error('Failed to load text submission.');
                    }
                });
            });

            $('.load-grade-modal').on('click', function (e) {
                e.preventDefault();
                let submissionId = $(this).data('id');
                $.ajax({
                    type: "GET",
                    url: "{{ url('instructor/assignment/grade-modal') }}/" + submissionId,
                    success: function (response) {
                        $(".dynamic-modal .modal-content").html(response);
                        $(".dynamic-modal").modal("show");
                    },
                    error: function (xhr) {
                        toastr.error('Failed to load grading interface.');
                    }
                });
            });

            $(document).on('submit', '.grading-form-submit', function (e) {
                e.preventDefault();
                let form = $(this);
                let submitBtn = form.find('.submit-btn');
                let originalText = submitBtn.html();

                submitBtn.prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin"></i> {{ __('Saving...') }}');

                $.ajax({
                    method: 'POST',
                    url: form.attr('action'),
                    data: form.serialize(),
                    success: function (response) {
                        if (response.status === 'success') {
                            toastr.success(response.message);
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.message);
                            submitBtn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function (xhr) {
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            toastr.error(xhr.responseJSON.message);
                        } else {
                            toastr.error('An error occurred during submission.');
                        }
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
        });
    </script>
@endpush
