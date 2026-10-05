@extends('frontend.layouts.master')
@section('meta_title', $product->title . ' || ' . $setting->app_name)

@section('contents')
    <x-frontend.breadcrumb :title="$product->title" :links="[['url' => route('home'), 'text' => __('Home')], ['url' => route('catalog'), 'text' => __('Catalog')], ['url' => route('product.show', $product->slug), 'text' => $product->title], ['url' => '', 'text' => __('Take Quiz')]]" />

    <section class="section-py-120">
        <div class="container">
            <form action="{{ route('product.quiz.submit', $product->slug) }}" method="POST">
                @csrf

                <div class="mb-4">
                    <h2>{{ $product->title }}</h2>
                    <p class="mb-0">{{ __('Attempt') }} {{ $attemptsUsed + 1 }} {{ __('of') }} {{ $product->quiz->attempt_limit }}</p>
                </div>

                @foreach ($product->quiz->questions as $questionIndex => $question)
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <h5 class="mb-0">{{ __('Question') }} {{ $questionIndex + 1 }}</h5>
                                <span class="badge bg-light text-dark">{{ $question->marks }} {{ __('marks') }}</span>
                            </div>
                            <p>{{ $question->prompt }}</p>
                            <p class="text-muted small">{{ ucfirst(str_replace('_', ' ', $question->question_type)) }}</p>

                            @if ($question->question_type === 'single_choice')
                                @foreach ($question->options as $option)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" id="option-{{ $option->id }}" name="answers[{{ $question->id }}][selected_option_id]" value="{{ $option->id }}">
                                        <label class="form-check-label" for="option-{{ $option->id }}">{{ $option->option_text }}</label>
                                    </div>
                                @endforeach
                            @else
                                <textarea class="form-control" rows="3" name="answers[{{ $question->id }}][typed_answer]" placeholder="{{ __('Type your answer') }}"></textarea>
                            @endif
                        </div>
                    </div>
                @endforeach

                <button type="submit" class="btn btn-two arrow-btn">{{ __('Submit Quiz') }}</button>
            </form>
        </div>
    </section>
@endsection
