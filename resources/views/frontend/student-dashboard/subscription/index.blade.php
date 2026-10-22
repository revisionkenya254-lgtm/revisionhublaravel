@extends('frontend.student-dashboard.layouts.master')

@section('dashboard-contents')
    @php
        $statusLabel = $isActive ? __('Active') : (filled($user->subscription_plan_id) ? __('Expired') : __('No active plan'));
        $statusClass = $isActive ? 'is-active' : 'is-inactive';
        $renewPlanId = $plan['id'] ?? data_get($upgradePlans->first(), 'id', 'month-1');
    @endphp

    <div class="dashboard__content-wrap subscription-details">
        <div class="subscription-details__heading">
            <div>
                <span class="subscription-details__eyebrow">{{ __('Account access') }}</span>
                <h4 class="title">{{ __('My Subscription') }}</h4>
                <p>{{ __('Review your plan, access period, and subscription payment history.') }}</p>
            </div>
            <span class="subscription-status {{ $statusClass }}">
                <i class="fas {{ $isActive ? 'fa-check-circle' : 'fa-info-circle' }}"></i>
                {{ $statusLabel }}
            </span>
        </div>

        @if ($isActive && $plan)
            <section class="subscription-hero">
                <div class="subscription-hero__content">
                    <span class="subscription-hero__label">{{ __('Current plan') }}</span>
                    <h2>{{ $plan['name'] }}</h2>
                    <p>{{ __('Unlimited access to RevisionHub learning resources while your subscription remains active.') }}</p>

                    <div class="subscription-hero__progress-copy">
                        <span>{{ trans_choice(':count day remaining|:count days remaining', $remainingDays, ['count' => $remainingDays]) }}</span>
                        <span>{{ $remainingPercentage }}%</span>
                    </div>
                    <div class="subscription-hero__progress" role="progressbar" aria-valuenow="{{ $remainingPercentage }}" aria-valuemin="0" aria-valuemax="100">
                        <span style="width: {{ $remainingPercentage }}%"></span>
                    </div>
                </div>

                <div class="subscription-hero__price">
                    <span>{{ __('Plan value') }}</span>
                    <strong>KES {{ number_format((int) $plan['amount']) }}</strong>
                    <small>{{ trans_choice(':count month|:count months', (int) $plan['months'], ['count' => (int) $plan['months']]) }}</small>
                </div>
            </section>

            <div class="subscription-summary-grid">
                <article class="subscription-summary-card">
                    <span class="subscription-summary-card__icon is-violet"><i class="fas fa-calendar-check"></i></span>
                    <div>
                        <small>{{ __('Started') }}</small>
                        <strong>{{ $startedAt ? formatDate($startedAt) : __('Not available') }}</strong>
                    </div>
                </article>
                <article class="subscription-summary-card">
                    <span class="subscription-summary-card__icon is-green"><i class="fas fa-hourglass-half"></i></span>
                    <div>
                        <small>{{ __('Expires') }}</small>
                        <strong>{{ $expiresAt ? formatDate($expiresAt) : __('Not available') }}</strong>
                    </div>
                </article>
                <article class="subscription-summary-card">
                    <span class="subscription-summary-card__icon is-blue"><i class="fas fa-layer-group"></i></span>
                    <div>
                        <small>{{ __('Access') }}</small>
                        <strong>{{ __('All learning resources') }}</strong>
                    </div>
                </article>
            </div>

            <section class="subscription-panel subscription-panel--actions">
                <div>
                    <h5>{{ __('Manage your plan') }}</h5>
                    @if ($upgradePlans->isNotEmpty())
                        <p>{{ __('You can upgrade immediately to a longer plan. The value of unused time is credited at checkout.') }}</p>
                    @else
                        <p>{{ __('You are already on the longest available plan. You can review all plans at any time.') }}</p>
                    @endif
                </div>
                <div class="subscription-actions">
                    @if ($upgradePlans->isNotEmpty())
                        <a href="{{ route('subscriptions', ['plan' => data_get($upgradePlans->first(), 'id')]) }}" class="btn btn-primary">
                            <i class="fas fa-arrow-up"></i> {{ __('Upgrade plan') }}
                        </a>
                    @endif
                    <a href="{{ route('subscriptions') }}" class="btn btn-outline-primary">{{ __('View all plans') }}</a>
                </div>
            </section>
        @else
            <section class="subscription-empty">
                <span class="subscription-empty__icon"><i class="fas fa-crown"></i></span>
                <div>
                    <h2>{{ filled($user->subscription_plan_id) ? __('Your subscription has expired') : __('Choose a subscription plan') }}</h2>
                    <p>
                        {{ filled($user->subscription_plan_id)
                            ? __('Renew your subscription to restore unlimited access to courses, notes, past papers, predictions, and quizzes.')
                            : __('Subscribe to unlock unlimited access to all RevisionHub learning resources.') }}
                    </p>
                </div>
                <a href="{{ route('subscriptions', ['plan' => $renewPlanId]) }}" class="btn btn-primary">
                    {{ filled($user->subscription_plan_id) ? __('Renew subscription') : __('Explore plans') }}
                </a>
            </section>
        @endif

        <section class="subscription-panel">
            <div class="subscription-panel__heading">
                <div>
                    <span class="subscription-details__eyebrow">{{ __('Billing') }}</span>
                    <h5>{{ __('Subscription payment history') }}</h5>
                </div>
                <a href="{{ route('student.orders.index') }}">{{ __('View all orders') }} <i class="fas fa-arrow-right"></i></a>
            </div>

            <div class="table-responsive">
                <table class="table subscription-history-table">
                    <thead>
                        <tr>
                            <th>{{ __('Plan') }}</th>
                            <th>{{ __('Invoice') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Payment') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th><span class="visually-hidden">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($subscriptionOrders as $order)
                            @php
                                $orderPlanName = data_get($order->order_details, 'plan_name')
                                    ?? data_get($order->order_details, 'title')
                                    ?? __('Subscription');
                            @endphp
                            <tr>
                                <td><strong>{{ $orderPlanName }}</strong></td>
                                <td>#{{ $order->invoice_id }}</td>
                                <td>{{ formatDate($order->created_at) }}</td>
                                <td>{{ number_format((float) ($order->paid_amount ?: $order->payable_amount), 2) }} {{ $order->payable_currency }}</td>
                                <td class="text-capitalize">{{ str_replace('_', ' ', $order->payment_method) }}</td>
                                <td>
                                    <span class="subscription-payment-status is-{{ $order->payment_status }}">
                                        {{ __(ucfirst($order->payment_status ?: 'pending')) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('student.order.show', $order->id) }}" class="subscription-history-action" title="{{ __('View order') }}">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="subscription-history-empty">{{ __('No subscription payments found yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $subscriptionOrders->links() }}
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .subscription-details {
            display: grid;
            gap: 22px;
        }

        .subscription-details__heading,
        .subscription-panel__heading,
        .subscription-panel--actions,
        .subscription-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }

        .subscription-details__heading p,
        .subscription-panel--actions p {
            margin: 6px 0 0;
            color: #667085;
        }

        .subscription-details__eyebrow {
            display: block;
            margin-bottom: 5px;
            color: #6d43df;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .subscription-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 800;
            white-space: nowrap;
        }

        .subscription-status.is-active {
            color: #087443;
            background: #eafaf2;
            border: 1px solid #bdebd2;
        }

        .subscription-status.is-inactive {
            color: #8a4b0f;
            background: #fff7e8;
            border: 1px solid #f7d9a6;
        }

        .subscription-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 28px;
            padding: 30px;
            border-radius: 24px;
            color: #fff;
            background: linear-gradient(135deg, #4e22bd 0%, #7654ea 58%, #8d70f0 100%);
            box-shadow: 0 22px 50px rgba(78, 34, 189, .22);
        }

        .subscription-hero__label {
            display: inline-block;
            margin-bottom: 7px;
            color: rgba(255, 255, 255, .78);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .subscription-hero h2 {
            margin: 0;
            color: #fff;
            font-size: clamp(28px, 4vw, 42px);
            font-weight: 900;
        }

        .subscription-hero p {
            max-width: 620px;
            margin: 8px 0 24px;
            color: rgba(255, 255, 255, .84);
        }

        .subscription-hero__progress-copy {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 700;
        }

        .subscription-hero__progress {
            height: 8px;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(255, 255, 255, .2);
        }

        .subscription-hero__progress span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: #fff;
        }

        .subscription-hero__price {
            min-width: 190px;
            align-self: center;
            padding: 22px;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 20px;
            background: rgba(255, 255, 255, .12);
            backdrop-filter: blur(8px);
        }

        .subscription-hero__price span,
        .subscription-hero__price small,
        .subscription-hero__price strong {
            display: block;
        }

        .subscription-hero__price span,
        .subscription-hero__price small {
            color: rgba(255, 255, 255, .78);
        }

        .subscription-hero__price strong {
            margin: 6px 0;
            color: #fff;
            font-size: 25px;
        }

        .subscription-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .subscription-summary-card,
        .subscription-panel,
        .subscription-empty {
            border: 1px solid #e8eaf2;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 12px 30px rgba(16, 24, 40, .05);
        }

        .subscription-summary-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 19px;
        }

        .subscription-summary-card__icon,
        .subscription-empty__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            width: 44px;
            height: 44px;
            border-radius: 14px;
        }

        .subscription-summary-card__icon.is-violet { color: #6d43df; background: #f0ebff; }
        .subscription-summary-card__icon.is-green { color: #0b8f55; background: #e8f8f0; }
        .subscription-summary-card__icon.is-blue { color: #2672d8; background: #eaf3ff; }

        .subscription-summary-card small,
        .subscription-summary-card strong {
            display: block;
        }

        .subscription-summary-card small {
            margin-bottom: 3px;
            color: #7c8497;
        }

        .subscription-summary-card strong {
            color: #1d2433;
        }

        .subscription-panel {
            padding: 24px;
        }

        .subscription-panel h5 {
            margin: 0;
            font-size: 19px;
        }

        .subscription-panel__heading {
            margin-bottom: 18px;
        }

        .subscription-panel__heading a {
            color: #6d43df;
            font-size: 13px;
            font-weight: 700;
        }

        .subscription-actions {
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .subscription-actions .btn,
        .subscription-empty .btn {
            white-space: nowrap;
        }

        .subscription-empty {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            align-items: center;
            gap: 20px;
            padding: 30px;
        }

        .subscription-empty__icon {
            width: 58px;
            height: 58px;
            color: #6d43df;
            background: #f0ebff;
            font-size: 22px;
        }

        .subscription-empty h2 {
            margin: 0 0 7px;
            font-size: 24px;
        }

        .subscription-empty p {
            margin: 0;
            color: #667085;
        }

        .subscription-history-table {
            margin: 0;
            vertical-align: middle;
        }

        .subscription-history-table thead th {
            padding: 12px;
            color: #697386;
            background: #f8f9fc;
            border: 0;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .subscription-history-table tbody td {
            padding: 15px 12px;
            border-color: #edf0f5;
            color: #4b5565;
        }

        .subscription-payment-status {
            display: inline-flex;
            padding: 5px 9px;
            border-radius: 999px;
            color: #8a4b0f;
            background: #fff7e8;
            font-size: 11px;
            font-weight: 800;
        }

        .subscription-payment-status.is-paid {
            color: #087443;
            background: #eafaf2;
        }

        .subscription-payment-status.is-cancelled,
        .subscription-payment-status.is-refunded {
            color: #a12828;
            background: #fff0f0;
        }

        .subscription-history-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            color: #6d43df;
            background: #f0ebff;
        }

        .subscription-history-empty {
            padding: 30px !important;
            text-align: center;
            color: #8991a3 !important;
        }

        @media (max-width: 991.98px) {
            .subscription-hero,
            .subscription-empty {
                grid-template-columns: 1fr;
            }

            .subscription-hero__price {
                width: 100%;
            }

            .subscription-summary-grid {
                grid-template-columns: 1fr;
            }

            .subscription-panel--actions {
                align-items: flex-start;
                flex-direction: column;
            }

            .subscription-actions {
                justify-content: flex-start;
            }
        }

        @media (max-width: 575.98px) {
            .subscription-details__heading,
            .subscription-panel__heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .subscription-hero,
            .subscription-panel,
            .subscription-empty {
                padding: 20px;
                border-radius: 16px;
            }
        }
    </style>
@endpush
