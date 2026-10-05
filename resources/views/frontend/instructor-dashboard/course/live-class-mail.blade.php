@extends('frontend.instructor-dashboard.layouts.master')

@section('dashboard-contents')
    <div class="dashboard__content-wrap pb-0 instructor-course-list">
        <div class="dashboard__content-title d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h4 class="title">{{ __('All Video Lessons') }}</h4>
            <a href="{{ route('instructor.courses.create') }}"
                class="btn btn-primary btn-hight-basic">{{ __('Add Video Lesson') }}</a>
        </div>
        <div class="row g-3">
            <div class="col-12">
                <div class="dashboard__review-table dash_instructor_course instructor-course-list__table">
                    <div class="tab-content" id="courseTabContent">
                        @forelse($courses as $course)
                            <div class="tab-pane fade show active" id="all-tab-pane" role="tabpanel"
                                aria-labelledby="all-tab" tabindex="0">
                                @php
                                    $courseLectureCount = App\Models\CourseChapterItem::whereHas(
                                        'chapter',
                                        function ($q) use ($course) {
                                            $q->where('course_id', $course->id);
                                        },
                                    )->count();
                                @endphp

                                <article class="instructor-course-row">
                                    <a href="{{ route('instructor.courses.edit-view', $course->id) }}" class="instructor-course-row__thumb shine__animate-link">
                                        <img src="{{ asset($course->thumbnail) }}" alt="img">
                                        @if ($course->is_approved == 'pending')
                                            <span class="instructor-course-row__badge instructor-course-row__badge--warning">{{ __('Pending') }}</span>
                                        @elseif($course->is_approved == 'rejected')
                                            <span class="instructor-course-row__badge instructor-course-row__badge--danger">{{ __('Rejected') }}</span>
                                        @elseif($course->status == 'active')
                                            <span class="instructor-course-row__badge instructor-course-row__badge--success">{{ __('Published') }}</span>
                                        @elseif($course->status == 'inactive')
                                            <span class="instructor-course-row__badge instructor-course-row__badge--danger">{{ __('Unpublished') }}</span>
                                        @else
                                            <span class="instructor-course-row__badge instructor-course-row__badge--danger">{{ __('Draft') }}</span>
                                        @endif
                                    </a>

                                    <div class="instructor-course-row__body">
                                        <div class="instructor-course-row__top">
                                            <div class="instructor-course-row__meta">
                                                @if (@$course->category->translation->name)
                                                    <span>{{ @$course->category->translation->name }}</span>
                                                @endif
                                            </div>
                                            <div class="instructor-course-row__actions">
                                                <a href="{{ route('instructor.courses.edit-view', $course->id) }}" aria-label="{{ __('Edit') }}">
                                                    <i class="far fa-edit"></i>
                                                </a>
                                                <a type="button" class="course-delete-request" href="{{ route('instructor.course.delete-request.show', $course->id) }}" aria-label="{{ __('Delete') }}">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </div>

                                        <div class="instructor-course-row__middle">
                                            <h5 class="title"><a href="{{ route('instructor.courses.edit-view', $course->id) }}">{{ $course->title }}</a></h5>
                                            <div class="instructor-course-row__bottom">
                                                <div class="author-two">
                                                    <a href="javascript:;"><img src="{{ asset($course->instructor->image) }}" alt="img">{{ $course->instructor->name }}</a>
                                                </div>
                                                <div class="avg-rating">
                                                    <i class="fas fa-star"></i>
                                                    {{ number_format($course->reviews()->avg('rating') ?? 0, 1) }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="instructor-course-row__stats">
                                            <ul class="list-wrap">
                                                <li><i class="flaticon-book"></i>{{ $courseLectureCount }}</li>
                                                <li><i class="flaticon-clock"></i>{{ minutesToHours($course->duration) }}</li>
                                                <li><i class="flaticon-mortarboard"></i>{{ $course->enrollments()->count() }}</li>
                                            </ul>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="text-center py-4">
                                    <h6>{{ __('No Course Found') }}</h6>
                                </div>
                            </div>
                        @endforelse
                        {{ $courses->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .instructor-course-list .dashboard__content-title {
            margin-bottom: 14px;
        }

        .instructor-course-list .dashboard__content-title .title {
            margin-bottom: 0;
            font-size: 22px;
            line-height: 1.15;
        }

        .instructor-course-list__table .tab-content {
            display: grid;
            gap: 10px;
        }

        .instructor-course-list__table .tab-pane {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        .instructor-course-row {
            display: grid;
            grid-template-columns: 88px minmax(0, 1fr);
            gap: 14px;
            align-items: center;
            padding: 12px;
            border: 1px solid rgba(216, 222, 234, 0.95);
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(20, 33, 61, 0.04);
        }

        .instructor-course-row__thumb {
            position: relative;
            display: block;
            width: 88px;
            height: 88px;
            border-radius: 12px;
            overflow: hidden;
            background: #f4f7fb;
            flex: 0 0 auto;
        }

        .instructor-course-row__thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .instructor-course-row__badge {
            position: absolute;
            left: 8px;
            bottom: 8px;
            padding: 4px 8px;
            border-radius: 999px;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
        }

        .instructor-course-row__badge--warning { background: #f59e0b; }
        .instructor-course-row__badge--success { background: #16a34a; }
        .instructor-course-row__badge--danger { background: #ef4444; }

        .instructor-course-row__top,
        .instructor-course-row__bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .instructor-course-row__top {
            margin-bottom: 8px;
        }

        .instructor-course-row__meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .instructor-course-row__meta span {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            padding: 0 10px;
            border-radius: 999px;
            background: rgba(91, 87, 214, 0.08);
            color: #5b57d6;
            font-size: 11px;
            font-weight: 700;
        }

        .instructor-course-row__actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .instructor-course-row__actions a {
            display: grid;
            place-items: center;
            width: 32px;
            height: 32px;
            border: 1px solid #e3e8f2;
            border-radius: 10px;
            color: #5a6788;
            background: #fff;
        }

        .instructor-course-row__middle .title {
            margin: 0 0 8px;
            font-size: 17px;
            line-height: 1.25;
        }

        .instructor-course-row__middle .title a {
            color: #16213f;
        }

        .instructor-course-row .author-two a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #5a6788;
            font-size: 13px;
            font-weight: 600;
        }

        .instructor-course-row .author-two img {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
        }

        .instructor-course-row .avg-rating {
            color: #16213f;
            font-size: 14px;
            font-weight: 700;
        }

        .instructor-course-row__stats {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(216, 222, 234, 0.7);
        }

        .instructor-course-row__stats ul {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            color: #5a6788;
            font-size: 12px;
            font-weight: 600;
        }

        .instructor-course-row__stats li {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        @media (max-width: 575.98px) {
            .instructor-course-row {
                grid-template-columns: 72px minmax(0, 1fr);
                gap: 12px;
                padding: 10px;
            }

            .instructor-course-row__thumb {
                width: 72px;
                height: 72px;
            }

            .instructor-course-row__middle .title {
                font-size: 15px;
            }
        }
    </style>
@endpush

