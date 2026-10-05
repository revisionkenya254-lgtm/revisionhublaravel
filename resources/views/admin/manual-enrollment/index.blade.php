@php use Illuminate\Support\Str; @endphp
@extends('admin.master_layout')
@section('title')
    <title>{{ __('Manual Enrollment') }}</title>
@endsection

@section('admin-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <div class="section-header-back">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-icon"><i class="fas fa-arrow-left"></i></a>
                </div>
                <h1>{{ __('Manual Enrollment') }}</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active">
                        <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a>
                    </div>
                    <div class="breadcrumb-item">{{ __('Manual Enrollment') }}</div>
                </div>
            </div>

            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4>{{ __('Manual Enrollment List') }}</h4>
                                @adminCan('enrollment.manual.store')
                                    <button type="button" class="btn btn-primary" data-toggle="modal"
                                        data-target="#createEnrollmentModal">
                                        <i class="fa fa-plus"></i> {{ __('Enroll Student') }}
                                    </button>
                                @endadminCan
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th width="5%">#</th>
                                                <th>{{ __('Student') }}</th>
                                                <th>{{ __('Course') }}</th>
                                                <th>{{ __('Payment Method') }}</th>
                                                <th>{{ __('Amount') }}</th>
                                                <th>{{ __('Date') }}</th>
                                                @adminCan('enrollment.manual.delete')
                                                    <th>{{ __('Action') }}</th>
                                                @endadminCan
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($enrollments as $enrollment)
                                                <tr>
                                                    <td>{{ $loop->index + 1 }}</td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div>
                                                                <div class="font-weight-bold">{{ $enrollment->user?->name ?? '—' }}</div>
                                                                <small class="text-muted">{{ $enrollment->user?->email }}</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('course.show', $enrollment->course?->slug) }}"
                                                            target="_blank" class="text-dark">
                                                            {{ $enrollment->course?->title ?? '—' }}
                                                        </a>
                                                    </td>
                                                    <td>
                                                        @if($enrollment->order?->payment_method === 'Manual Free')
                                                            <span class="badge badge-success">{{ __('Free') }}</span>
                                                        @else
                                                            <span class="badge badge-info">{{ $enrollment->order?->payment_method ?? '—' }}</span>
                                                        @endif
                                                    </td>
                                                    <td width="15%">{{ $enrollment->order?->paid_amount ?? 0 }} {{ $enrollment->order?->payable_currency }}</td>
                                                    <td>{{ $enrollment->created_at->format('d M Y') }}</td>
                                                    @adminCan('enrollment.manual.delete')
                                                        <td>
                                                            <a href="javascript:;" data-toggle="modal"
                                                                data-target="#deleteModal"
                                                                onclick="deleteEnrollment('{{ $enrollment->id }}')"
                                                                class="btn btn-danger btn-sm">
                                                                <i class="fa fa-trash"></i>
                                                            </a>
                                                        </td>
                                                    @endadminCan
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted py-4">
                                                        {{ __('No manual enrollments found.') }}
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                    <div class="float-right">
                                        {{ $enrollments->links() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- Create Modal --}}
    @adminCan('enrollment.manual.store')
        @include('admin.manual-enrollment.partials.create-modal')
    @endadminCan

    {{-- Reuse standard delete modal component --}}
    <x-admin.delete-modal />
@endsection

@push('css')
    <style>
        .form-control:not(.form-control-sm):not(.form-control-lg) { height: 45px; }
    </style>
@endpush

@push('js')
    <script>
        "use strict";

        var enrolledCourseIds = [];

        // ── Select2 config ────────────────────────────────────────────────────────
        var courseSelect2Options = {
            dropdownParent: $('#createEnrollmentModal'),
            width: '100%',
            templateResult: function (option) {
                if (!option.id) return option.text;
                // Grey out already-enrolled courses
                if (enrolledCourseIds.includes(String(option.id))) {
                    return $('<span style="color:#aaa;font-style:italic;">' +
                        option.text + ' &mdash; {{ __("Already Enrolled") }}</span>');
                }
                return option.text;
            },
            // Prevent selecting already-enrolled courses
            templateSelection: function (option) {
                return option.text || '{{ __("Select Course") }}';
            }
        };

        // ── Initialize all Select2 in modal ──────────────────────────────────────
        function initSelect2() {
            $('#user_id, #payment_method, #currency').each(function () {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({ dropdownParent: $('#createEnrollmentModal'), width: '100%' });
                }
            });

            // Always destroy + reinit course select so templateResult gets fresh data
            if ($('#course_id').hasClass('select2-hidden-accessible')) {
                $('#course_id').select2('destroy');
            }
            $('#course_id').select2(courseSelect2Options);

            // Bind course change directly on the element (required by Select2)
            $('#course_id').off('change.enrollment').on('change.enrollment', function () {
                var selected = $(this).find('option:selected');
                var price    = selected.data('price');

                if (price !== undefined && price !== '' && price !== null) {
                    $('#amount').val(parseFloat(price).toFixed(2));
                    if (parseFloat(price) === 0) {
                        $('#type_free').prop('checked', true).trigger('change');
                    } else {
                        $('#type_paid').prop('checked', true).trigger('change');
                    }
                } else {
                    $('#amount').val('');
                }
            });
        }

        // ── Reload the course Select2 to reflect disabled state ──────────────────
        function refreshCourseSelect2() {
            $('#course_id option[value]').each(function () {
                $(this).prop('disabled', enrolledCourseIds.includes(String($(this).val())));
            });

            // Must destroy + reinit otherwise Select2 won't pick up changed disabled attrs
            $('#course_id').select2('destroy').select2(courseSelect2Options);

            // Re-bind change event since destroy removes it
            $('#course_id').off('change.enrollment').on('change.enrollment', function () {
                var selected = $(this).find('option:selected');
                var price    = selected.data('price');

                if (price !== undefined && price !== '' && price !== null) {
                    $('#amount').val(parseFloat(price).toFixed(2));
                    if (parseFloat(price) === 0) {
                        $('#type_free').prop('checked', true).trigger('change');
                    } else {
                        $('#type_paid').prop('checked', true).trigger('change');
                    }
                } else {
                    $('#amount').val('');
                }
            });
        }

        // ── Modal open ────────────────────────────────────────────────────────────
        $('#createEnrollmentModal').on('shown.bs.modal', function () {
            enrolledCourseIds = [];
            // Re-enable all options first (clean state)
            $('#course_id option[value]').prop('disabled', false);
            initSelect2();
        });

        // ── Modal close: reset state ──────────────────────────────────────────────
        $('#createEnrollmentModal').on('hidden.bs.modal', function () {
            $('#user_id').val('').trigger('change');
            $('#course_id').val('').trigger('change');
            $('#amount').val('');
            enrolledCourseIds = [];
        });

        // ── Student change → fetch enrolled course IDs → filter dropdown ─────────
        $(document).on('change', '#user_id', function () {
            var userId = $(this).val();
            enrolledCourseIds = [];
            $('#course_id option[value]').prop('disabled', false);

            if (!userId) {
                refreshCourseSelect2();
                return;
            }

            $.ajax({
                url: '{{ route("admin.manual-enrollment.student-enrolled-courses") }}',
                method: 'GET',
                data: { user_id: userId },
                success: function (response) {
                    enrolledCourseIds = (response.enrolled_course_ids || []).map(String);
                    refreshCourseSelect2();
                },
                error: function () {
                    refreshCourseSelect2();
                }
            });
        });

        // ── Enrollment type toggle ─────────────────────────────────────────────────
        $('input[name="type"]').on('change', function () {
            if ($(this).val() === 'paid') {
                $('#paid_fields').removeClass('d-none');
            } else {
                $('#paid_fields').addClass('d-none');
            }
        });

        // ── Delete modal ───────────────────────────────────────────────────────────
        function deleteEnrollment(id) {
            var baseUrl = '{{ url("admin/manual-enrollment") }}';
            $('#deleteForm').attr('action', baseUrl + '/' + id);
        }

        // ── Tooltips ───────────────────────────────────────────────────────────────
        $('[data-toggle="tooltip"]').tooltip();
    </script>
@endpush
