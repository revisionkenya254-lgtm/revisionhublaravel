@extends('frontend.student-dashboard.layouts.master')

@section('dashboard-contents')
    <div class="dashboard__content-wrap">
        <div class="dashboard__content-title">
            <h4 class="title">{{ __('My Quiz Attempts') }}</h4>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="dashboard__review-table table-responsive">
                    <h5 class="mb-3">{{ __('Standalone Quiz Products') }}</h5>
                    <table class="table table-borderless mb-5">
                        <thead>
                            <tr>
                                <th>{{ __('No') }}</th>
                                <th>{{ __('Quiz Product') }}</th>
                                <th>{{ __('Tier') }}</th>
                                <th>{{ __('Score') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Attempt') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($productQuizAttempts as $index => $attempt)
                                <tr>
                                    <td>{{ $productQuizAttempts->firstItem() + $index }}</td>
                                    <td>{{ $attempt->product?->title }}</td>
                                    <td class="text-capitalize">{{ $attempt->quiz?->tier }}</td>
                                    <td>{{ $attempt->score }} / {{ $attempt->total_marks }}</td>
                                    <td>
                                        <span class="badge {{ $attempt->status === 'pass' ? 'bg-success' : 'bg-danger' }}">{{ $attempt->status }}</span>
                                    </td>
                                    <td>{{ $attempt->attempt_number }}</td>
                                    <td>{{ formatDate($attempt->submitted_at) }}</td>
                                    <td>
                                        <a href="{{ route('product.quiz.result', [$attempt->product?->slug, $attempt->id]) }}"><i class="fas fa-eye"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">{{ __('No standalone quiz attempts found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $productQuizAttempts->links() }}

                    <h5 class="mb-3 mt-5">{{ __('Course Quizzes') }}</h5>
                    <table class="table table-borderless">
                        <thead>
                            <tr>
                                <th>{{ __('No') }}</th>
                                <th>{{ __('Course') }}</th>
                                <th>{{ __('Quiz') }}</th>
                                <th>{{ __('Quiz Grade') }}</th>
                                <th>{{ __('My Grade') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>

                            @forelse ($quizAttempts as $index => $attempt)
                                <tr>
                                    <td>{{ $quizAttempts->firstItem() + $index }}</td>
                                    <td>{{ $attempt->quiz->course?->title }}</td>
                                    <td>{{ $attempt->quiz->title }}</td>
                                    <td>{{ $attempt->quiz->total_mark }}</td>
                                    <td>{{ $attempt->user_grade }}</td>
                                    <td>
                                        @if($attempt->status == 'pass')
                                            <span class="badge bg-success">{{ $attempt->status }}</span>
                                        @else
                                            <span class="badge bg-danger">{{ $attempt->status }}</span>
                                        @endif 
                                    </td>

                                    <td>
                                        {{ formatDate($attempt->created_at) }}
                                    </td>
                                    <td>
                                        <a href="{{ route('student.quiz.result', ['id' => $attempt->quiz->id, 'result_id' => $attempt->id]) }}"><i class="fas fa-eye"></i></a>
                                    </td>
                                </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center">{{ __('No data found!') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $quizAttempts->links() }}
            </div>
        </div>
    </div>
@endsection
