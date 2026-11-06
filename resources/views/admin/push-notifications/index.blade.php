@extends('admin.master_layout')

@section('title')
    <title>{{ __('Push Notifications') }}</title>
@endsection

@section('admin-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>{{ __('Push Notifications') }}</h1>
                <div class="section-header-breadcrumb">
                    <a href="{{ route('admin.push-notifications.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        {{ __('Create campaign') }}
                    </a>
                </div>
            </div>

            <div class="section-body">
                @if (session('messege'))
                    <div class="alert alert-{{ session('alert-type', 'success') }}">{{ session('messege') }}</div>
                @endif

                @unless ($isConfigured)
                    <div class="alert alert-warning">
                        {{ __('FCM delivery is not configured. Set FIREBASE_PROJECT_ID and FIREBASE_CREDENTIALS on the server before creating campaigns.') }}
                    </div>
                @endunless

                <div class="card">
                    <div class="card-header"><h4>{{ __('Campaign history') }}</h4></div>
                    <div class="card-body table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('Notification') }}</th>
                                    <th>{{ __('Audience') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Delivery') }}</th>
                                    <th>{{ __('Created') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($campaigns as $campaign)
                                    <tr>
                                        <td>
                                            <strong>{{ $campaign->title }}</strong>
                                            <div class="small text-muted">{{ $campaign->body }}</div>
                                            @if ($campaign->failure_reason)
                                                <div class="small text-danger">{{ $campaign->failure_reason }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $campaign->target_type === 'all' ? __('All eligible students') : __('Education level') }}
                                            @if ($campaign->education_level)
                                                <div class="small text-muted">{{ $campaign->education_level }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $statusTone = match ($campaign->status) {
                                                    'sent' => 'success',
                                                    'failed' => 'danger',
                                                    'no_recipients' => 'secondary',
                                                    'preparing', 'sending' => 'warning',
                                                    default => 'light',
                                                };
                                            @endphp
                                            <span class="badge badge-{{ $statusTone }}">{{ __(str_replace('_', ' ', ucfirst($campaign->status))) }}</span>
                                        </td>
                                        <td>
                                            {{ number_format($campaign->sent_count) }} {{ __('sent') }}
                                            <div class="small text-muted">
                                                {{ number_format($campaign->failed_count) }} {{ __('failed') }}
                                                / {{ number_format($campaign->recipient_count) }} {{ __('devices') }}
                                            </div>
                                        </td>
                                        <td>
                                            {{ $campaign->created_at->format('d M Y, g:i A') }}
                                            <div class="small text-muted">{{ $campaign->admin?->name }}</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">{{ __('No campaigns have been sent yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        {{ $campaigns->links() }}
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
