<section class="border border-danger rounded p-4 mt-4" aria-labelledby="delete-account-title">
    <h5 id="delete-account-title" class="text-danger">{{ __('Delete account') }}</h5>
    <p>{{ __('Request permanent deletion of your account and associated personal data. We will send a confirmation link to your account email address.') }}</p>
    <form method="POST" action="{{ route('account-deletion.request.authenticated') }}">
        @csrf
        <button type="submit" class="btn btn-outline-danger">{{ __('Request account deletion') }}</button>
    </form>
</section>
