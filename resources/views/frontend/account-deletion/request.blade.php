@extends('frontend.layouts.master')

@section('meta_title', __('Delete your RevisionHub account'))

@section('contents')
    <section class="container py-5">
        <div class="mx-auto" style="max-width: 720px;">
            <h1>{{ __('Delete your RevisionHub account') }}</h1>
            <p>{{ __('Enter the email address associated with your account. We will email you a secure link to confirm deletion of your account and associated personal data.') }}</p>
            <p>{{ __('The link expires after 60 minutes. Completed order records may be retained in anonymized form for accounting purposes. Published instructor courses and products may remain available with the instructor identity anonymized.') }}</p>

            @if (session('status'))
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('account-deletion.request') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">{{ __('Account email address') }}</label>
                    <input id="email" name="email" type="email" class="form-control" value="{{ old('email') }}" required maxlength="255" autocomplete="email">
                </div>
                <button type="submit" class="btn btn-danger">{{ __('Send confirmation link') }}</button>
            </form>
        </div>
    </section>
@endsection
