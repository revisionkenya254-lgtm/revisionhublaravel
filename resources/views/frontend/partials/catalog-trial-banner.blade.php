@if ($trialActive ?? false)
    <div class="catalog-trial-banner" role="status">
        <span class="catalog-trial-banner__icon"><i class="fas fa-gift" aria-hidden="true"></i></span>
        <div class="catalog-trial-banner__copy">
            <strong>{{ __('Your free trial is active') }}</strong>
            <span>
                {{ trans_choice('You have full access for :count more day.|You have full access for :count more days.', $trialRemainingDays, ['count' => $trialRemainingDays]) }}
                {{ $trialExpiresAt ? __('Trial ends :date.', ['date' => formatDate($trialExpiresAt)]) : '' }}
            </span>
        </div>
        <a href="{{ route('student.subscription') }}">{{ __('View trial') }} <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </div>
@endif
