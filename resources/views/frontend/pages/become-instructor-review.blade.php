@extends('frontend.student-dashboard.layouts.master')

@section('meta_title', __('Instructor Request Review'))

@push('styles')
    <style>
        .instructor-request-review {
            display: grid;
            gap: 24px;
        }

        .instructor-request-card {
            border: 1px solid #e6e8ef;
            border-radius: 8px;
            background: #fff;
            padding: 24px;
        }

        .instructor-request-card__title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 18px;
        }

        .instructor-request-card__title h4 {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            color: #12172f;
        }

        .instructor-request-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .instructor-request-field {
            padding: 14px 16px;
            border-radius: 6px;
            background: #f7f8fb;
        }

        .instructor-request-field span {
            display: block;
            margin-bottom: 4px;
            font-size: 13px;
            font-weight: 700;
            color: #697089;
        }

        .instructor-request-field strong,
        .instructor-request-field p {
            margin: 0;
            color: #111827;
            font-size: 15px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .instructor-request-field--wide {
            grid-column: 1 / -1;
        }

        .instructor-request-badge {
            display: inline-flex;
            align-items: center;
            min-height: 30px;
            padding: 0 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 800;
        }

        .instructor-request-badge--pending {
            background: #fff4d7;
            color: #946200;
        }

        .instructor-request-badge--approved {
            background: #dbf7e8;
            color: #0d7a43;
        }

        .instructor-request-badge--rejected {
            background: #ffe2e2;
            color: #a81414;
        }

        .instructor-request-note {
            margin: 0;
            color: #5b6277;
        }

        .instructor-request-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .instructor-request-form .form-grp {
            margin: 0;
        }

        .instructor-request-form .form-grp--wide {
            grid-column: 1 / -1;
        }

        .instructor-request-form select,
        .instructor-request-form input,
        .instructor-request-form textarea {
            width: 100%;
        }

        @media (max-width: 767px) {
            .instructor-request-grid,
            .instructor-request-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('dashboard-contents')
    <div class="dashboard__content-wrap">
        <div class="dashboard__content-title">
            <h4 class="title">{{ __('Instructor Request Review') }}</h4>
        </div>

        <div class="instructor-request-review">
            <div class="instructor-request-card">
                <div class="instructor-request-card__title">
                    <h4>{{ __('Request Status') }}</h4>
                    <span class="instructor-request-badge instructor-request-badge--{{ $instructorRequest->status }}">
                        {{ __(ucfirst($instructorRequest->status)) }}
                    </span>
                </div>
                <p class="instructor-request-note">
                    {{ $canUpdate ? __('Review your submitted details below. You can update this request once if you made a mistake.') : __('Your request update option is no longer available.') }}
                </p>
            </div>

            <div class="instructor-request-card">
                <div class="instructor-request-card__title">
                    <h4>{{ __('Bio Data') }}</h4>
                </div>
                <div class="instructor-request-grid">
                    <div class="instructor-request-field">
                        <span>{{ __('Name') }}</span>
                        <strong>{{ html_decode($user->name) }}</strong>
                    </div>
                    <div class="instructor-request-field">
                        <span>{{ __('Email') }}</span>
                        <strong>{{ $user->email }}</strong>
                    </div>
                    <div class="instructor-request-field">
                        <span>{{ __('Phone') }}</span>
                        <strong>{{ $user->phone ?: ($instructorRequest->payout_information ?: __('N/A')) }}</strong>
                    </div>
                    <div class="instructor-request-field">
                        <span>{{ __('Joined') }}</span>
                        <strong>{{ $user->created_at?->format('d M Y') }}</strong>
                    </div>
                    <div class="instructor-request-field instructor-request-field--wide">
                        <span>{{ __('Biography') }}</span>
                        <p>{{ $user->bio ?: __('N/A') }}</p>
                    </div>
                </div>
            </div>

            <div class="instructor-request-card">
                <div class="instructor-request-card__title">
                    <h4>{{ __('Submitted Details') }}</h4>
                </div>
                <div class="instructor-request-grid">
                    @foreach ($submittedDetails as $label => $value)
                        <div class="instructor-request-field {{ $label === __('Extra Information') ? 'instructor-request-field--wide' : '' }}">
                            <span>{{ $label }}</span>
                            <p>{{ $value }}</p>
                        </div>
                    @endforeach
                    <div class="instructor-request-field">
                        <span>{{ __('Payout Account') }}</span>
                        <strong>{{ $instructorRequest->payout_account }}</strong>
                    </div>
                    <div class="instructor-request-field">
                        <span>{{ __('Phone Number') }}</span>
                        <strong>{{ $instructorRequest->payout_information }}</strong>
                    </div>
                    <div class="instructor-request-field">
                        <span>{{ __('Certificate / Document') }}</span>
                        @if ($instructorRequest->certificate)
                            <a href="{{ asset($instructorRequest->certificate) }}" target="_blank">{{ __('View uploaded file') }}</a>
                        @else
                            <strong>{{ __('N/A') }}</strong>
                        @endif
                    </div>
                    <div class="instructor-request-field">
                        <span>{{ __('Identity Scan') }}</span>
                        @if ($instructorRequest->identity_scan)
                            <a href="{{ asset($instructorRequest->identity_scan) }}" target="_blank">{{ __('View uploaded file') }}</a>
                        @else
                            <strong>{{ __('N/A') }}</strong>
                        @endif
                    </div>
                </div>
            </div>

            <div class="instructor-request-card">
                <div class="instructor-request-card__title">
                    <h4>{{ __('Update Request') }}</h4>
                </div>

                @if ($canUpdate)
                    <form action="{{ route('become-instructor.review.update') }}" method="POST" enctype="multipart/form-data" class="instructor-request-form">
                        @csrf
                        @method('PUT')

                        <div class="form-grp">
                            <label>{{ __('Teaching Experience') }} <code>*</code></label>
                            <select name="teaching_experience" class="form-select">
                                @foreach ($answerOptions['teaching_experience'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('teaching_experience', $selectedAnswers['teaching_experience']) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-grp">
                            <label>{{ __('Video Experience') }} <code>*</code></label>
                            <select name="video_experience" class="form-select">
                                @foreach ($answerOptions['video_experience'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('video_experience', $selectedAnswers['video_experience']) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-grp">
                            <label>{{ __('Audience') }} <code>*</code></label>
                            <select name="audience_level" class="form-select">
                                @foreach ($answerOptions['audience_level'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('audience_level', $selectedAnswers['audience_level']) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-grp">
                            <label>{{ __('Payout Account') }} <code>*</code></label>
                            <select name="payout_account" class="form-select">
                                @foreach ($withdrawMethods as $method)
                                    <option value="{{ $method->name }}" @selected(old('payout_account', $instructorRequest->payout_account) === $method->name)>{{ $method->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-grp">
                            <label>{{ __('Phone Number') }} <code>*</code></label>
                            <input type="text" class="form-control" name="phone_number" value="{{ old('phone_number', $instructorRequest->payout_information) }}" placeholder="{{ __('Phone Number') }}">
                        </div>

                        <div class="form-grp">
                            <label>{{ __('Certificate and documents') }}</label>
                            <input type="file" class="form-control" name="certificate">
                        </div>

                        <div class="form-grp">
                            <label>{{ __('Identity scan') }}</label>
                            <input type="file" class="form-control" name="identity_scan">
                        </div>

                        <div class="form-grp form-grp--wide">
                            <label>{{ __('Extra Information') }}</label>
                            <textarea name="extra_information" placeholder="{{ __('Extra Information') }}">{{ old('extra_information', $submittedDetails[__('Extra Information')] ?? '') }}</textarea>
                        </div>

                        <div class="form-grp form-grp--wide">
                            <button type="submit" class="btn btn-two arrow-btn">
                                {{ __('Update Request') }}
                                <img src="{{ asset('frontend/img/icons/right_arrow.svg') }}" alt="img" class="injectable">
                            </button>
                        </div>
                    </form>
                @else
                    <p class="instructor-request-note">{{ __('You have already used your one request update, or this request has been approved.') }}</p>
                @endif
            </div>
        </div>
    </div>
@endsection
