@extends('frontend.layouts.master')

@section('meta_title', __('Confirm account deletion'))

@section('contents')
    <section class="container py-5">
        <div class="mx-auto" style="max-width: 720px;">
            <h1>{{ __('Confirm account deletion') }}</h1>
            <p>{{ __('This permanently deletes your RevisionHub account and personal account data. This action cannot be undone.') }}</p>
            @if ($account->role === 'instructor')
                <p>{{ __('Published courses and products, together with related student learning records, will remain available. Your instructor profile will be anonymized.') }}</p>
            @endif
            <p>{{ __('Completed order records may be retained in anonymized form for accounting purposes.') }}</p>
            <form method="POST" action="{{ $confirmationUrl }}">
                @csrf
                <button type="submit" class="btn btn-danger">{{ __('Permanently delete my account') }}</button>
                <a href="{{ route('home') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            </form>
        </div>
    </section>
@endsection
