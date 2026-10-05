@extends('frontend.layouts.master')
@section('meta_title', $product->title . ' || ' . $setting->app_name)

@section('contents')
    <x-frontend.breadcrumb :title="$product->title" :links="[['url' => route('home'), 'text' => __('Home')], ['url' => route('catalog'), 'text' => __('Catalog')], ['url' => route('product.show', $product->slug), 'text' => $product->title], ['url' => '', 'text' => __('Quiz Result')]]" />

    <section class="section-py-120">
        <div class="container">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <h2 class="mb-1">{{ __('Quiz Result') }}</h2>
                            <p class="mb-0">{{ __('Attempt') }} {{ $attempt->attempt_number }}</p>
                        </div>
                        <div class="text-end">
                            <h3 class="mb-1">{{ $attempt->score }} / {{ $attempt->total_marks }}</h3>
                            <p class="mb-1">{{ number_format($attempt->percentage, 2) }}%</p>
                            <span class="badge {{ $attempt->status === 'pass' ? 'bg-success' : 'bg-danger' }}">{{ strtoupper($attempt->status) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @foreach ($attempt->answers as $index => $answer)
                @php
                    $question = $answer->question;
                    $snapshot = $answer->snapshotQuestion();
                    $questionType = $question?->question_type ?? ($snapshot['question_type'] ?? null);
                    $questionPrompt = $question?->prompt ?? ($snapshot['prompt'] ?? __('Question removed from current quiz version.'));
                    $questionMarks = $question?->marks ?? ($snapshot['marks'] ?? 0);
                    $selectedOption = $answer->selectedOption;
                    $snapshotSelectedOption = $answer->snapshotSelectedOption();
                    $correctOption = $questionType === 'single_choice'
                        ? ($question?->options?->firstWhere('is_correct', true)
                            ?? collect($snapshot['options'] ?? [])->firstWhere('is_correct', true))
                        : null;
                    $studentAnswer = $questionType === 'single_choice'
                        ? ($selectedOption?->option_text ?? ($snapshotSelectedOption['option_text'] ?? __('No answer')))
                        : ($answer->typed_answer ?: __('No answer'));
                    $instructorAnswer = $questionType === 'single_choice'
                        ? (is_array($correctOption) ? ($correctOption['option_text'] ?? '--') : ($correctOption?->option_text ?? '--'))
                        : ($question?->correct_text_answer ?? ($snapshot['correct_text_answer'] ?? '--'));
                @endphp
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <h5 class="mb-0">{{ __('Question') }} {{ $index + 1 }}</h5>
                            <span class="badge {{ $answer->is_correct ? 'bg-success' : 'bg-danger' }}">
                                {{ $answer->is_correct ? __('Correct') : __('Incorrect') }}
                            </span>
                        </div>
                        <p>{{ $questionPrompt }}</p>
                        <p><strong>{{ __('Your Answer:') }}</strong> {{ $studentAnswer }}</p>
                        <p><strong>{{ __('Instructor Answer:') }}</strong> {{ $instructorAnswer }}</p>
                        <p class="mb-0"><strong>{{ __('Marks Awarded:') }}</strong> {{ $answer->awarded_marks }} / {{ $questionMarks }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endsection
