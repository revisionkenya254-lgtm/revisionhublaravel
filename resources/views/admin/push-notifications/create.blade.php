@extends('admin.master_layout')

@section('title')
    <title>{{ __('Create Push Campaign') }}</title>
@endsection

@section('admin-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>{{ __('Create Push Campaign') }}</h1>
                <div class="section-header-breadcrumb">
                    <a href="{{ route('admin.push-notifications.index') }}" class="btn btn-outline-primary">
                        {{ __('Campaign History') }}
                    </a>
                </div>
            </div>

            <div class="section-body">
                @if (! $isConfigured)
                    <div class="alert alert-warning">
                        {{ __('FCM is not configured. Set FIREBASE_PROJECT_ID and FIREBASE_CREDENTIALS on the server before sending campaigns.') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row">
                    <div class="col-12 col-lg-8">
                        <div class="card">
                            <div class="card-header"><h4>{{ __('Compose notification') }}</h4></div>
                            <div class="card-body">
                                <form action="{{ route('admin.push-notifications.store') }}" method="POST" id="push-campaign-form">
                                    @csrf
                                    <div class="form-group">
                                        <label for="title">{{ __('Title') }}</label>
                                        <input id="title" name="title" class="form-control" maxlength="100" required value="{{ old('title') }}">
                                        <small class="form-text text-muted">{{ __('Keep it short and recognizable.') }}</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="body">{{ __('Message') }}</label>
                                        <textarea id="body" name="body" class="form-control" rows="4" maxlength="500" required>{{ old('body') }}</textarea>
                                        <small class="form-text text-muted">{{ __('Maximum 500 characters. Do not include sensitive account information.') }}</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="target_type">{{ __('Audience') }}</label>
                                        <select id="target_type" name="target_type" class="form-control" required>
                                            <option value="all" @selected(old('target_type', 'all') === 'all')>
                                                {{ __('All eligible students') }}
                                            </option>
                                            <option value="education_level" @selected(old('target_type') === 'education_level')>
                                                {{ __('One education level') }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="form-group" id="education-level-group" @if (old('target_type') !== 'education_level') hidden @endif>
                                        <label for="education_level">{{ __('Education level') }}</label>
                                        <select id="education_level" name="education_level" class="form-control">
                                            <option value="">{{ __('Select an education level') }}</option>
                                            @foreach ($educationLevels as $level)
                                                <option value="{{ $level['slug'] }}" @selected(old('education_level') === $level['slug'])>
                                                    {{ $level['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="target_url">{{ __('Open this link when tapped (optional)') }}</label>
                                        <input id="target_url" name="target_url" type="url" class="form-control" maxlength="2048" value="{{ old('target_url') }}" placeholder="https://revisionhubkenya.com/...">
                                    </div>

                                    <div class="alert alert-light border">
                                        <strong>{{ __('Estimated recipients') }}:</strong>
                                        <span id="push-recipient-count">{{ number_format($audienceCounts['all']) }}</span>
                                        <div class="small text-muted mt-1">
                                            {{ __('Counts are approximate and include active students with registered Android push tokens. For a level-targeted campaign, students without a level are excluded.') }}
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary" @disabled(! $isConfigured)>
                                        <i class="fas fa-paper-plane"></i>
                                        {{ __('Send campaign') }}
                                    </button>
                                    <a href="{{ route('admin.push-notifications.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="card card-primary">
                            <div class="card-header"><h4>{{ __('Before sending') }}</h4></div>
                            <div class="card-body">
                                <ul class="mb-0 pl-3">
                                    <li class="mb-2">{{ __('Campaigns are sent immediately to registered Android devices.') }}</li>
                                    <li class="mb-2">{{ __('Android must register a device token after notification permission is granted.') }}</li>
                                    <li class="mb-2">{{ __('Use education-level targeting only for content relevant to that level.') }}</li>
                                    <li>{{ __('Avoid excessive or promotional pushes; users can disable notifications in Android settings.') }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const target = document.getElementById('target_type');
            const level = document.getElementById('education_level');
            const group = document.getElementById('education-level-group');
            const recipientCount = document.getElementById('push-recipient-count');
            const allCount = @json($audienceCounts['all']);
            const levelCounts = @json($audienceCounts['by_level']);

            function updateAudience() {
                const byLevel = target.value === 'education_level';
                group.hidden = !byLevel;
                level.required = byLevel;
                recipientCount.textContent = new Intl.NumberFormat().format(
                    byLevel ? (levelCounts[level.value] || 0) : allCount
                );
            }

            target.addEventListener('change', updateAudience);
            level.addEventListener('change', updateAudience);
            document.getElementById('push-campaign-form').addEventListener('submit', function (event) {
                if (!window.confirm(@json(__('Send this notification to the estimated audience now?')))) {
                    event.preventDefault();
                }
            });
            updateAudience();
        });
    </script>
@endpush
