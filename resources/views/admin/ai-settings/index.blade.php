@extends('admin.master_layout')
@section('title')
    <title>{{ __('AI Settings') }}</title>
@endsection
@section('admin-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>{{ __('AI Settings') }}</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('admin.settings') }}">{{ __('Settings') }}</a></div>
                    <div class="breadcrumb-item">{{ __('AI Settings') }}</div>
                </div>
            </div>

            <div class="section-body">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="mb-0">{{ __('Provider Overview') }}</h4>
                                <button type="button" class="btn btn-primary" id="ai-health-check">{{ __('Run Health Check') }}</button>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                        <strong>{{ __('Document Queue Mode') }}:</strong>
                                        <span>{{ strtoupper($settings->document_queue_mode ?? config('ai.document_processing.default_mode', 'local')) }}</span>
                                        <div class="small text-muted">
                                            @if (($settings->document_queue_mode ?? config('ai.document_processing.default_mode', 'local')) === 'redis')
                                                {{ __('Redis mode is ready for Horizon on the production server.') }}
                                            @else
                                                {{ __('Local mode processes document jobs immediately on click.') }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-secondary">{{ __('AI Docs') }}</span>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-3"><div class="border rounded p-3"><small>{{ __('Requests') }}</small><h4>{{ $stats['request_count'] }}</h4></div></div>
                                    <div class="col-md-3"><div class="border rounded p-3"><small>{{ __('Successful') }}</small><h4>{{ $stats['success_count'] }}</h4></div></div>
                                    <div class="col-md-3"><div class="border rounded p-3"><small>{{ __('Failed') }}</small><h4>{{ $stats['failed_count'] }}</h4></div></div>
                                    <div class="col-md-3"><div class="border rounded p-3"><small>{{ __('Estimated Cost') }}</small><h4>{{ number_format($stats['estimated_cost'], 4) }}</h4></div></div>
                                </div>

                                <form id="ai-settings-form">
                                    @csrf
                                    @method('PUT')
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="form-label">{{ __('Default Provider') }}</label>
                                    <select name="default_provider" class="form-control">
                                                @foreach (config('ai.providers', []) as $providerKey => $definition)
                                                    <option value="{{ $providerKey }}" @selected(($settings->default_provider ?? config('ai.default')) === $providerKey)>{{ data_get($definition, 'label', ucfirst($providerKey)) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">{{ __('Fallback Provider') }}</label>
                                            <select name="fallback_provider" class="form-control">
                                                @foreach (config('ai.providers', []) as $providerKey => $definition)
                                                    <option value="{{ $providerKey }}" @selected(($settings->fallback_provider ?? config('ai.fallback')) === $providerKey)>{{ data_get($definition, 'label', ucfirst($providerKey)) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">{{ __('Routing Mode') }}</label>
                                            <select name="routing_mode" class="form-control">
                                                <option value="auto" @selected(($settings->routing_mode ?? config('ai.routing_mode')) === 'auto')>{{ __('Auto') }}</option>
                                                <option value="openai_only" @selected(($settings->routing_mode ?? config('ai.routing_mode')) === 'openai_only')>{{ __('ChatGPT Only') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">{{ __('Document Queue Mode') }}</label>
                                            <select name="document_queue_mode" class="form-control">
                                                <option value="local" @selected(($settings->document_queue_mode ?? config('ai.document_processing.default_mode', 'local')) === 'local')>{{ __('Local') }}</option>
                                                <option value="redis" @selected(($settings->document_queue_mode ?? config('ai.document_processing.default_mode', 'local')) === 'redis')>{{ __('Redis / Horizon') }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    <h5>{{ __('Providers') }}</h5>
                                    @php
                                        $providerMap = $providers->keyBy('name');
                                    @endphp
                                    <div class="row">
                                        @foreach (config('ai.providers') as $name => $definition)
                                            @php($provider = $providerMap->get($name))
                                            <div class="col-lg-6 mb-3">
                                                <div class="border rounded p-3 h-100">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <h6 class="mb-0">{{ data_get($definition, 'label', ucfirst($name)) }}</h6>
                                                        <label class="mb-0">
                                                            <input type="checkbox" name="providers[{{ $name }}][enabled]" value="1" @checked(old("providers.$name.enabled", $provider?->enabled ?? data_get($definition, 'enabled', false)))>
                                                            {{ __('Enabled') }}
                                                        </label>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <label class="form-label">{{ __('Priority') }}</label>
                                                            <input type="number" class="form-control" name="providers[{{ $name }}][priority]" value="{{ old("providers.$name.priority", $provider?->priority ?? 0) }}">
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label">{{ __('Status') }}</label>
                                                            <input type="text" class="form-control" name="providers[{{ $name }}][status]" value="{{ old("providers.$name.status", $provider?->status ?? 'configured') }}">
                                                        </div>
                                                        <div class="col-12 mt-3">
                                                            <label class="form-label">{{ __('Model') }}</label>
                                                            <input type="text" class="form-control" name="providers[{{ $name }}][model]" value="{{ old("providers.$name.model", $provider?->model ?? data_get($definition, 'model')) }}">
                                                        </div>
                                                        <div class="col-12 mt-3">
                                                            <label class="form-label">{{ __('Default Model') }}</label>
                                                            <input type="text" class="form-control" name="providers[{{ $name }}][default_model]" value="{{ old("providers.$name.default_model", $provider?->default_model ?? data_get($definition, 'model')) }}">
                                                        </div>
                                                        <div class="col-6 mt-3">
                                                            <label class="form-label">{{ __('Timeout') }}</label>
                                                            <input type="number" class="form-control" name="providers[{{ $name }}][timeout]" value="{{ old("providers.$name.timeout", $provider?->timeout ?? data_get($definition, 'timeout', 45)) }}">
                                                        </div>
                                                        <div class="col-6 mt-3">
                                                            <label class="form-label">{{ __('Base URL') }}</label>
                                                            <input type="text" class="form-control" name="providers[{{ $name }}][base_url]" value="{{ old("providers.$name.base_url", $provider?->base_url ?? data_get($definition, 'base_url')) }}">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="text-end mt-3">
                                        <button type="submit" class="btn btn-primary">{{ __('Save AI Settings') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('ai-settings-form');
            const button = document.getElementById('ai-health-check');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            form?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const payload = new FormData(form);
                const response = await fetch(@json(route('admin.ai-settings.update')), {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: payload,
                });

                if (window.toastr) {
                    const data = await response.json().catch(() => ({}));
                    if (response.ok) {
                        toastr.success(data.message || @json(__('AI settings updated successfully.')));
                    } else {
                        toastr.error(data.message || @json(__('Unable to save AI settings.')));
                    }
                }
            });

            button?.addEventListener('click', async () => {
                const response = await fetch(@json(route('admin.ai-settings.health-check')), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json().catch(() => ({}));
                if (window.toastr) {
                    if (response.ok) {
                        toastr.success(@json(__('Health check completed.'))); 
                    } else {
                        toastr.error(data.message || @json(__('Unable to run health checks.')));
                    }
                }
            });
        })();
    </script>
@endpush
