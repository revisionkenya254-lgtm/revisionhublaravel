@extends('admin.master_layout')
@section('title')
    <title>{{ __('Bunny Usage Dashboard') }}</title>
@endsection
@section('admin-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <div class="section-header-back">
                    <a href="{{ route('admin.settings') }}" class="btn btn-icon"><i class="fas fa-arrow-left"></i></a>
                </div>
                <h1>{{ __('Bunny Usage Dashboard') }}</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active">
                        <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a>
                    </div>
                    <div class="breadcrumb-item active">
                        <a href="{{ route('admin.settings') }}">{{ __('Settings') }}</a>
                    </div>
                    <div class="breadcrumb-item">{{ __('Bunny Usage Dashboard') }}</div>
                </div>
            </div>

            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info alert-has-icon">
                            <div class="alert-icon"><i class="fas fa-info-circle"></i></div>
                            <div class="alert-body">
                                <div class="alert-title">{{ __('Bunny usage overview') }}</div>
                                {{ __('This dashboard combines the configured Bunny products and pulls the latest usage data from Bunny APIs when your Bunny core credentials are available.') }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-primary">
                                <i class="fas fa-broadcast-tower"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>{{ __('CDN Zones') }}</h4>
                                </div>
                                <div class="card-body">{{ $summary['cdn_count'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-success">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>{{ __('Storage Zones') }}</h4>
                                </div>
                                <div class="card-body">{{ $summary['storage_count'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-warning">
                                <i class="fas fa-film"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>{{ __('Stream Libraries') }}</h4>
                                </div>
                                <div class="card-body">{{ $summary['stream_count'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-danger">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>{{ __('Pending Requests') }}</h4>
                                </div>
                                <div class="card-body">{{ $summary['pending_requests_count'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="mb-0">{{ __('Estimated Monthly Due') }}</h4>
                                <a href="{{ route('admin.bunny-dashboard') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-sync-alt"></i> {{ __('Refresh') }}
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0">{{ __('CDN Usage Chart') }}</h6>
                                        <small class="text-muted">{{ __('Top pull zones by monthly Bunny usage') }}</small>
                                    </div>
                                    <div style="height: 260px;">
                                        <canvas id="bunnyCdnChart"></canvas>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="border rounded p-3 h-100">
                                            <div class="text-muted small text-uppercase">{{ __('Current usage') }}</div>
                                            <div class="h4 mb-0">{{ $summary['estimated_monthly_due'] ?? '$0.00' }}</div>
                                            <small class="text-muted">{{ __('Based on Bunny billing summary for CDN pull zones.') }}</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="border rounded p-3 h-100">
                                            <div class="text-muted small text-uppercase">{{ __('Amount due') }}</div>
                                            <div class="h4 mb-0">{{ $summary['billing_balance_human'] ?? '$0.00' }}</div>
                                            <small class="text-muted">{{ __('Pulled from Bunny billing details when available.') }}</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="border rounded p-3 h-100">
                                            <div class="text-muted small text-uppercase">{{ __('Billing status') }}</div>
                                            <div class="h4 mb-0">{{ $summary['billing_status'] ?? __('Unknown') }}</div>
                                            <small class="text-muted">{{ __('Cached for 10 minutes to reduce API calls.') }}</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-muted small mb-3">
                                    {{ __('Last refreshed at') }} {{ optional($generated_at)->format('M d, Y H:i') }}
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Item') }}</th>
                                                <th>{{ __('Amount') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th>{{ __('Due Date') }}</th>
                                                <th>{{ __('Invoice') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($pending_requests as $request)
                                                <tr>
                                                    <td>
                                                        <div class="font-weight-bold">{{ $request['title'] ?? __('Payment Request') }}</div>
                                                        <small class="text-muted">#{{ $request['id'] ?? '---' }}</small>
                                                    </td>
                                                    <td>{{ $request['amount_human'] ?? '$0.00' }}</td>
                                                    <td>{{ $request['status'] ?? __('Pending') }}</td>
                                                    <td>{{ $request['due_date'] ?? __('Not available') }}</td>
                                                    <td>
                                                        @if (!empty($request['id']))
                                                            <a class="btn btn-sm btn-primary"
                                                                href="{{ route('admin.bunny-invoice.download', $request['id']) }}">
                                                                <i class="fas fa-download"></i> {{ __('Download') }}
                                                            </a>
                                                        @else
                                                            <span class="text-muted">{{ __('N/A') }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">
                                                        {{ __('No pending payment requests were returned by Bunny for this account.') }}
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0">{{ __('Credentials') }}</h4>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span>{{ __('Core API Key') }}</span>
                                        <span class="badge badge-{{ !empty($credentials['core_api_key']) ? 'success' : 'danger' }}">
                                            {{ !empty($credentials['core_api_key']) ? __('Ready') : __('Missing') }}
                                        </span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span>{{ __('Storage Access Key') }}</span>
                                        <span class="badge badge-{{ !empty($credentials['storage_access_key']) ? 'success' : 'danger' }}">
                                            {{ !empty($credentials['storage_access_key']) ? __('Ready') : __('Missing') }}
                                        </span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span>{{ __('Stream API Key') }}</span>
                                        <span class="badge badge-{{ !empty($credentials['stream_api_key']) ? 'success' : 'danger' }}">
                                            {{ !empty($credentials['stream_api_key']) ? __('Ready') : __('Missing') }}
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0">{{ __('Notes') }}</h4>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">
                                    {{ __('CDN usage is derived from Bunny billing summary entries for pull zones.') }}
                                </p>
                                <p class="text-muted mb-3">
                                    {{ __('Storage and Stream show zone/library usage from Bunny core resource endpoints.') }}
                                </p>
                                <p class="text-muted mb-0">
                                    {{ __('If Bunny does not return pending requests, the dashboard will still show usage data and you can review open billing in Bunny directly.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0">{{ __('CDN Usage') }}</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Pull Zone') }}</th>
                                                <th>{{ __('Bandwidth Used') }}</th>
                                                <th>{{ __('Estimated Usage') }}</th>
                                                <th>{{ __('Limit') }}</th>
                                                <th>{{ __('Hostname') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($cdn as $row)
                                                <tr>
                                                    <td>
                                                        <div class="font-weight-bold">{{ $row['name'] ?? __('Unknown') }}</div>
                                                        <small class="text-muted">#{{ $row['id'] ?? '---' }}</small>
                                                    </td>
                                                    <td>{{ $row['monthly_bandwidth_used_human'] ?? '0 B' }}</td>
                                                    <td>{{ $row['monthly_usage_human'] ?? '$0.00' }}</td>
                                                    <td>{{ $row['monthly_bandwidth_limit_human'] ?? __('Not set') }}</td>
                                                    <td>
                                                        @if (!empty($row['hostnames']))
                                                            {{ $row['hostnames'][0] }}
                                                        @else
                                                            {{ $setting->bunny_cdn_hostname ?? __('Not set') }}
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">
                                                        {{ __('No CDN usage data was returned. Check the Bunny core API key and the configured pull zone.') }}
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0">{{ __('Storage Usage') }}</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Storage Zone') }}</th>
                                                <th>{{ __('Used') }}</th>
                                                <th>{{ __('Files') }}</th>
                                                <th>{{ __('Region') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($storage as $row)
                                                <tr>
                                                    <td>
                                                        <div class="font-weight-bold">{{ $row['name'] ?? __('Unknown') }}</div>
                                                        <small class="text-muted">#{{ $row['id'] ?? '---' }}</small>
                                                    </td>
                                                    <td>{{ $row['storage_used_human'] ?? '0 B' }}</td>
                                                    <td>{{ $row['files_stored'] ?? 0 }}</td>
                                                    <td>{{ $row['region'] ?? __('Not set') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-4">
                                                        {{ __('No storage zone data was returned. Check the Bunny core API key and storage configuration.') }}
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0">{{ __('Stream Usage') }}</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Library') }}</th>
                                                <th>{{ __('Videos') }}</th>
                                                <th>{{ __('Traffic') }}</th>
                                                <th>{{ __('Storage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($stream as $row)
                                                <tr>
                                                    <td>
                                                        <div class="font-weight-bold">{{ $row['name'] ?? __('Unknown') }}</div>
                                                        <small class="text-muted">#{{ $row['id'] ?? '---' }}</small>
                                                    </td>
                                                    <td>{{ $row['video_count'] ?? 0 }}</td>
                                                    <td>{{ $row['traffic_usage_human'] ?? '0 B' }}</td>
                                                    <td>{{ $row['storage_usage_human'] ?? '0 B' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-4">
                                                        {{ __('No stream library data was returned. Check the Bunny core API key and the stream configuration.') }}
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if (!empty($bunny_errors['cdn']) || !empty($bunny_errors['storage']) || !empty($bunny_errors['stream']) || !empty($bunny_errors['billing']))
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-warning alert-has-icon">
                                <div class="alert-icon"><i class="fas fa-exclamation-triangle"></i></div>
                                <div class="alert-body">
                                    <div class="alert-title">{{ __('Some Bunny API calls were unavailable') }}</div>
                                    {{ __('The dashboard loaded, but one or more Bunny endpoints did not return data. Double-check the Bunny credentials and active zones/libraries.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection

@push('js')
    <script src="{{ asset('backend/js/chart.umd.min.js') }}"></script>
    <script>
        (function($) {
            "use strict";

            $(document).ready(function() {
                const chartCanvas = document.getElementById('bunnyCdnChart');

                if (!chartCanvas) {
                    return;
                }

                const chartData = @json($cdn_chart ?? ['labels' => [], 'values' => []]);

                new Chart(chartCanvas, {
                    type: 'bar',
                    data: {
                        labels: chartData.labels || [],
                        datasets: [{
                            label: "{{ __('Monthly Usage') }}",
                            data: chartData.values || [],
                            backgroundColor: 'rgba(54, 162, 235, 0.35)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1,
                            borderRadius: 8,
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '{{ session()->get('currency_icon') }}' + Number(value).toLocaleString(undefined, {
                                            minimumFractionDigits: 0,
                                            maximumFractionDigits: 2
                                        });
                                    }
                                }
                            }
                        }
                    }
                });
            });
        })(jQuery);
    </script>
@endpush
