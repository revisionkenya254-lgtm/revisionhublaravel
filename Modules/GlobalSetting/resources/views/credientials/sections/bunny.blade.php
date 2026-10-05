<div class="tab-pane fade" id="bunny_storage_tab" role="tabpanel">
    <form action="{{ route('admin.update-bunny-cloud') }}" method="POST" id="bunny-settings-form">
        @csrf
        @method('PUT')

        @php
            $bunnyTests = [
                'core' => !empty($setting->bunny_connection_test_core) ? json_decode($setting->bunny_connection_test_core, true) : null,
                'cdn' => !empty($setting->bunny_connection_test_cdn) ? json_decode($setting->bunny_connection_test_cdn, true) : null,
                'storage' => !empty($setting->bunny_connection_test_storage) ? json_decode($setting->bunny_connection_test_storage, true) : null,
                'storage_public' => !empty($setting->bunny_connection_test_storage_public) ? json_decode($setting->bunny_connection_test_storage_public, true) : null,
                'stream' => !empty($setting->bunny_connection_test_stream) ? json_decode($setting->bunny_connection_test_stream, true) : null,
            ];
        @endphp

        <div class="alert alert-info">
            <strong>{{ __('Bunny auth') }}:</strong>
            {{ __('Core API key uses the AccessKey header for Bunny account actions. Storage uses the storage zone password. Stream uses the library API key.') }}
        </div>

        <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn btn-sm btn-light" data-bunny-clear-history>
                <i class="fas fa-eraser"></i> {{ __('Clear test history') }}
            </button>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('Bunny Core API') }}</h5>
                <div class="d-flex align-items-center">
                    @if (isset($bunnyTests['core']))
                        <span id="bunny_core_badge" class="badge badge-{{ !empty($bunnyTests['core']['status']) ? 'success' : 'danger' }} mr-2">
                            {{ !empty($bunnyTests['core']['status']) ? __('Connected') : __('Failed') }}
                        </span>
                    @endif
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bunny-test="core">
                        {{ __('Test Core') }}
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="bunny_core_api_key" class="mb-0">{{ __('Bunny Account API Key') }}</label>
                        <button type="submit" class="btn btn-sm btn-primary" name="bunny_save_field" value="bunny_core_api_key"
                            data-bunny-inline-save="bunny_core_api_key" data-bunny-save-button>
                            {{ __('Save') }}
                        </button>
                    </div>
                    @if (env('APP_MODE') == 'DEMO')
                        <input type="password" class="form-control" name="bunny_core_api_key" id="bunny_core_api_key" value="account_api_key">
                    @else
                        <input type="password" class="form-control" name="bunny_core_api_key" id="bunny_core_api_key" value="{{ data_get($setting, 'bunny_core_api_key', '') }}">
                    @endif
                    <small class="text-muted">{{ __('Required when your admin tools need to call Bunny Core API endpoints like Pull Zone, Storage Zone, or Stream Library management.') }}</small>
                @if (isset($bunnyTests['core']) && !empty($bunnyTests['core']['tested_at']))
                    <div class="small mt-2 text-muted">{{ __('Last test') }}: {{ $bunnyTests['core']['tested_at'] }}</div>
                @endif
                <small class="text-muted d-block mt-1" id="bunny_core_api_key_status"></small>
                <small class="text-muted d-block mt-1" id="bunny_core_test_status"></small>
            </div>
        </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('Bunny CDN') }}</h5>
                <div class="d-flex align-items-center">
                    @if (isset($bunnyTests['cdn']))
                        <span id="bunny_cdn_badge" class="badge badge-{{ !empty($bunnyTests['cdn']['status']) ? 'success' : 'danger' }} mr-2">
                            {{ !empty($bunnyTests['cdn']['status']) ? __('Connected') : __('Failed') }}
                        </span>
                    @endif
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bunny-test="cdn">
                        {{ __('Test CDN') }}
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="small text-uppercase text-muted font-weight-bold mb-3">{{ __('CDN delivery settings') }}</div>
                <div class="alert alert-light border mb-4">
                    {{ __('These values control the public Bunny pull zone that serves your approved files to users.') }}
                </div>
                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="bunny_cdn_pull_zone_name" class="mb-0">{{ __('Pull Zone Name') }}</label>
                        <button type="submit" class="btn btn-sm btn-primary" name="bunny_save_field" value="bunny_cdn_pull_zone_name"
                            data-bunny-inline-save="bunny_cdn_pull_zone_name" data-bunny-save-button>
                            {{ __('Save') }}
                        </button>
                    </div>
                    <input type="text" class="form-control" name="bunny_cdn_pull_zone_name" id="bunny_cdn_pull_zone_name"
                        value="{{ data_get($setting, 'bunny_cdn_pull_zone_name', '') }}" placeholder="my-cdn-zone">
                    <small class="text-muted d-block mt-1" id="bunny_cdn_pull_zone_name_status"></small>
                </div>

                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="bunny_cdn_hostname" class="mb-0">{{ __('CDN Hostname') }}</label>
                        <button type="submit" class="btn btn-sm btn-primary" name="bunny_save_field" value="bunny_cdn_hostname"
                            data-bunny-inline-save="bunny_cdn_hostname" data-bunny-save-button>
                            {{ __('Save') }}
                        </button>
                    </div>
                    <input type="text" class="form-control" name="bunny_cdn_hostname" id="bunny_cdn_hostname"
                        value="{{ data_get($setting, 'bunny_cdn_hostname', '') }}" placeholder="cdn.example.com">
                    <small class="text-muted">{{ __('This is the public hostname generated by your Bunny Pull Zone.') }}</small>
                    <small class="text-muted d-block mt-1" id="bunny_cdn_hostname_status"></small>
                </div>

                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="mb-0">{{ __('Status') }}</label>
                        <button type="submit" class="btn btn-sm btn-primary" name="bunny_save_field" value="bunny_cdn_status"
                            data-bunny-inline-save="bunny_cdn_status" data-bunny-save-button>
                            {{ __('Save') }}
                        </button>
                    </div>
                    <label class="d-flex align-items-center">
                            <input type="hidden" value="inactive" name="bunny_cdn_status" class="custom-switch-input">
                            <input type="checkbox" value="active" name="bunny_cdn_status" class="custom-switch-input"
                            {{ data_get($setting, 'bunny_cdn_status', 'inactive') == 'active' ? 'checked' : '' }}>
                        <span class="custom-switch-indicator"></span>
                        <span class="custom-switch-description">{{ __('Status') }}</span>
                    </label>
                    <small class="text-muted d-block mt-1" id="bunny_cdn_status_status"></small>
                </div>
                @if (isset($bunnyTests['cdn']) && !empty($bunnyTests['cdn']['tested_at']))
                    <div class="small mt-2 text-muted">{{ __('Last test') }}: {{ $bunnyTests['cdn']['tested_at'] }}</div>
                @endif
                <small class="text-muted d-block mt-1" id="bunny_cdn_test_status"></small>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('Bunny Storage') }}</h5>
                <div class="d-flex align-items-center">
                    @if (isset($bunnyTests['storage']))
                        <span id="bunny_storage_badge" class="badge badge-{{ !empty($bunnyTests['storage']['status']) ? 'success' : 'danger' }} mr-2">
                            {{ !empty($bunnyTests['storage']['status']) ? __('Connected') : __('Failed') }}
                        </span>
                    @endif
                    @if (isset($bunnyTests['storage_public']))
                        <span id="bunny_storage_public_badge" class="badge badge-{{ !empty($bunnyTests['storage_public']['status']) ? 'success' : 'danger' }} mr-2">
                            {{ !empty($bunnyTests['storage_public']['status']) ? __('URL OK') : __('URL Err') }}
                        </span>
                    @endif
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bunny-test="storage">
                        {{ __('Test Storage') }}
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="small text-uppercase text-muted font-weight-bold mb-3">{{ __('Storage origin settings') }}</div>
                <div class="alert alert-light border mb-4">
                    {{ __('These values point to Bunny Storage, which keeps the source file and powers the public CDN URL.') }}
                </div>
                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="bunny_storage_zone_name" class="mb-0">{{ __('Storage Zone Name') }}</label>
                        <button type="submit" class="btn btn-sm btn-primary" name="bunny_save_field" value="bunny_storage_zone_name"
                            data-bunny-inline-save="bunny_storage_zone_name" data-bunny-save-button>
                            {{ __('Save') }}
                        </button>
                    </div>
                    @if (env('APP_MODE') == 'DEMO')
                        <input type="text" class="form-control" name="bunny_storage_zone_name" id="bunny_storage_zone_name" value="my-storage-zone">
                    @else
                        <input type="text" class="form-control" name="bunny_storage_zone_name" id="bunny_storage_zone_name"
                            value="{{ data_get($setting, 'bunny_storage_zone_name', '') }}">
                    @endif
                    <small class="text-muted d-block mt-1" id="bunny_storage_zone_name_status"></small>
                </div>

                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="bunny_storage_access_key" class="mb-0">{{ __('Storage Zone Access Key') }}</label>
                        <button type="submit" class="btn btn-sm btn-primary" name="bunny_save_field" value="bunny_storage_access_key"
                            data-bunny-inline-save="bunny_storage_access_key" data-bunny-save-button>
                            {{ __('Save') }}
                        </button>
                    </div>
                    @if (env('APP_MODE') == 'DEMO')
                        <input type="password" class="form-control" name="bunny_storage_access_key" id="bunny_storage_access_key" value="access_key">
                    @else
                        <input type="password" class="form-control" name="bunny_storage_access_key" id="bunny_storage_access_key"
                            value="{{ data_get($setting, 'bunny_storage_access_key', '') }}">
                    @endif
                    <small class="text-muted">{{ __('Bunny Storage API uses the storage zone password in the AccessKey header.') }}</small>
                    <small class="text-muted d-block mt-1" id="bunny_storage_access_key_status"></small>
                </div>

                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="bunny_storage_api_endpoint" class="mb-0">{{ __('API Endpoint') }}</label>
                        <button type="submit" class="btn btn-sm btn-primary" name="bunny_save_field" value="bunny_storage_api_endpoint"
                            data-bunny-inline-save="bunny_storage_api_endpoint" data-bunny-save-button>
                            {{ __('Save') }}
                        </button>
                    </div>
                    <input type="text" class="form-control" name="bunny_storage_api_endpoint" id="bunny_storage_api_endpoint"
                        value="{{ data_get($setting, 'bunny_storage_api_endpoint', '') }}" placeholder="https://de.storage.bunnycdn.com">
                    <small class="text-muted d-block mt-1">
                        {{ __('Use the regional Bunny Storage endpoint in the format https://{region}.storage.bunnycdn.com, for example https://de.storage.bunnycdn.com.') }}
                    </small>
                    <small class="text-muted d-block mt-1" id="bunny_storage_api_endpoint_status"></small>
                </div>

                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="bunny_storage_cdn_url" class="mb-0">{{ __('CDN URL') }}</label>
                        <div class="d-flex align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-primary mr-2" data-bunny-test="storage_public">
                                {{ __('Test Public URL') }}
                            </button>
                            <button type="submit" class="btn btn-sm btn-primary" name="bunny_save_field" value="bunny_storage_cdn_url"
                                data-bunny-inline-save="bunny_storage_cdn_url" data-bunny-save-button>
                                {{ __('Save') }}
                            </button>
                        </div>
                    </div>
                    <input type="url" class="form-control" name="bunny_storage_cdn_url" id="bunny_storage_cdn_url"
                        value="{{ data_get($setting, 'bunny_storage_cdn_url', '') }}" placeholder="https://cdn.example.com">
                    <small class="text-muted">{{ __('Public CDN domain used to serve approved files.') }}</small>
                    <small class="text-muted d-block mt-1" id="bunny_storage_cdn_url_status"></small>
                </div>

                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="mb-0">{{ __('Status') }}</label>
                        <button type="submit" class="btn btn-sm btn-primary" name="bunny_save_field" value="bunny_storage_status"
                            data-bunny-inline-save="bunny_storage_status" data-bunny-save-button>
                            {{ __('Save') }}
                        </button>
                    </div>
                    <label class="d-flex align-items-center">
                        <input type="hidden" value="inactive" name="bunny_storage_status" class="custom-switch-input">
                        <input type="checkbox" value="active" name="bunny_storage_status" class="custom-switch-input"
                            {{ data_get($setting, 'bunny_storage_status', 'inactive') == 'active' ? 'checked' : '' }}>
                        <span class="custom-switch-indicator"></span>
                        <span class="custom-switch-description">{{ __('Status') }}</span>
                    </label>
                    <small class="text-muted d-block mt-1" id="bunny_storage_status_status"></small>
                </div>
                @if (isset($bunnyTests['storage']) && !empty($bunnyTests['storage']['tested_at']))
                    <div class="small mt-2 text-muted">{{ __('Last test') }}: {{ $bunnyTests['storage']['tested_at'] }}</div>
                @endif
                <small class="text-muted d-block mt-1" id="bunny_storage_test_status"></small>
                @if (isset($bunnyTests['storage_public']) && !empty($bunnyTests['storage_public']['tested_at']))
                    <div class="small mt-2 text-muted">{{ __('Last public URL test') }}: {{ $bunnyTests['storage_public']['tested_at'] }}</div>
                @endif
                <small class="text-muted d-block mt-1" id="bunny_storage_public_test_status"></small>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('Bunny Stream') }}</h5>
                <div class="d-flex align-items-center">
                    @if (isset($bunnyTests['stream']))
                        <span id="bunny_stream_badge" class="badge badge-{{ !empty($bunnyTests['stream']['status']) ? 'success' : 'danger' }} mr-2">
                            {{ !empty($bunnyTests['stream']['status']) ? __('Connected') : __('Failed') }}
                        </span>
                    @endif
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bunny-test="stream">
                        {{ __('Test Stream') }}
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="bunny_stream_library_id" class="mb-0">{{ __('Library ID') }}</label>
                        <button type="submit" class="btn btn-sm btn-primary" name="bunny_save_field" value="bunny_stream_library_id"
                            data-bunny-inline-save="bunny_stream_library_id" data-bunny-save-button>
                            {{ __('Save') }}
                        </button>
                    </div>
                    <input type="text" class="form-control" name="bunny_stream_library_id" id="bunny_stream_library_id"
                        value="{{ data_get($setting, 'bunny_stream_library_id', '') }}" placeholder="123456">
                    <small class="text-muted">{{ __('Stream API requests use the library ID together with the library API key.') }}</small>
                    <small class="text-muted d-block mt-1" id="bunny_stream_library_id_status"></small>
                </div>

                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="bunny_stream_api_key" class="mb-0">{{ __('Library API Key') }}</label>
                        <button type="submit" class="btn btn-sm btn-primary" name="bunny_save_field" value="bunny_stream_api_key"
                            data-bunny-inline-save="bunny_stream_api_key" data-bunny-save-button>
                            {{ __('Save') }}
                        </button>
                    </div>
                    @if (env('APP_MODE') == 'DEMO')
                        <input type="password" class="form-control" name="bunny_stream_api_key" id="bunny_stream_api_key" value="stream_api_key">
                    @else
                        <input type="password" class="form-control" name="bunny_stream_api_key" id="bunny_stream_api_key"
                            value="{{ data_get($setting, 'bunny_stream_api_key', '') }}">
                    @endif
                    <small class="text-muted d-block mt-1" id="bunny_stream_api_key_status"></small>
                </div>

                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="bunny_stream_cdn_hostname" class="mb-0">{{ __('CDN Hostname') }}</label>
                        <button type="submit" class="btn btn-sm btn-primary" name="bunny_save_field" value="bunny_stream_cdn_hostname"
                            data-bunny-inline-save="bunny_stream_cdn_hostname" data-bunny-save-button>
                            {{ __('Save') }}
                        </button>
                    </div>
                    <input type="text" class="form-control" name="bunny_stream_cdn_hostname" id="bunny_stream_cdn_hostname"
                        value="{{ data_get($setting, 'bunny_stream_cdn_hostname', '') }}" placeholder="iframe.mediadelivery.net">
                    <small class="text-muted">{{ __('Shown in Bunny Stream dashboard under API credentials.') }}</small>
                    <small class="text-muted d-block mt-1" id="bunny_stream_cdn_hostname_status"></small>
                </div>

                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="bunny_stream_pull_zone_name" class="mb-0">{{ __('Pull Zone Name') }}</label>
                        <button type="submit" class="btn btn-sm btn-primary" name="bunny_save_field" value="bunny_stream_pull_zone_name"
                            data-bunny-inline-save="bunny_stream_pull_zone_name" data-bunny-save-button>
                            {{ __('Save') }}
                        </button>
                    </div>
                    <input type="text" class="form-control" name="bunny_stream_pull_zone_name" id="bunny_stream_pull_zone_name"
                        value="{{ data_get($setting, 'bunny_stream_pull_zone_name', '') }}" placeholder="my-stream-pull-zone">
                    <small class="text-muted d-block mt-1" id="bunny_stream_pull_zone_name_status"></small>
                </div>

                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="mb-0">{{ __('Status') }}</label>
                        <button type="submit" class="btn btn-sm btn-primary" name="bunny_save_field" value="bunny_stream_status"
                            data-bunny-inline-save="bunny_stream_status" data-bunny-save-button>
                            {{ __('Save') }}
                        </button>
                    </div>
                    <label class="d-flex align-items-center">
                        <input type="hidden" value="inactive" name="bunny_stream_status" class="custom-switch-input">
                        <input type="checkbox" value="active" name="bunny_stream_status" class="custom-switch-input"
                            {{ data_get($setting, 'bunny_stream_status', 'inactive') == 'active' ? 'checked' : '' }}>
                        <span class="custom-switch-indicator"></span>
                        <span class="custom-switch-description">{{ __('Status') }}</span>
                    </label>
                    <small class="text-muted d-block mt-1" id="bunny_stream_status_status"></small>
                </div>
                @if (isset($bunnyTests['stream']) && !empty($bunnyTests['stream']['tested_at']))
                    <div class="small mt-2 text-muted">{{ __('Last test') }}: {{ $bunnyTests['stream']['tested_at'] }}</div>
                @endif
                <small class="text-muted d-block mt-1" id="bunny_stream_test_status"></small>
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary" name="bunny_save_scope" value="all" data-bunny-save-button>
                {{ __('Update') }}
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('bunny-settings-form');
    if (!form) return;

    const fieldButtons = form.querySelectorAll('[data-bunny-inline-save]');
    const testButtons = form.querySelectorAll('[data-bunny-test]');
    const clearButton = form.querySelector('[data-bunny-clear-history]');
    const token = form.querySelector('input[name="_token"]')?.value || '';

    const escapeHtml = function (value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    const setStatus = function (targetId, message, isError, kind) {
        const statusEl = targetId ? document.getElementById(targetId) : null;
        if (!statusEl) {
            return;
        }

        const safeMessage = escapeHtml(message);
        let icon = 'fas fa-check-circle';
        let className = 'badge badge-success badge-pill mt-1';

        if (isError) {
            icon = 'fas fa-exclamation-circle';
            className = 'badge badge-danger badge-pill mt-1';
        } else if (kind === 'test') {
            icon = 'fas fa-vial';
            className = 'badge badge-info badge-pill mt-1';
        } else if (kind === 'clear') {
            icon = 'fas fa-eraser';
            className = 'badge badge-warning badge-pill mt-1';
        }

        statusEl.className = className;
        statusEl.innerHTML = `<i class="${icon} mr-1"></i>${safeMessage}`;
    };

    const setBadge = function (type, isSuccess) {
        const badgeEl = document.getElementById(`bunny_${type}_badge`);
        if (!badgeEl) {
            return;
        }

        badgeEl.className = `badge badge-${isSuccess ? 'success' : 'danger'} mr-2`;
        badgeEl.textContent = isSuccess ? '{{ __('Connected') }}' : '{{ __('Failed') }}';
    };

    const toggleButton = function (button, loadingText, isLoading) {
        if (!button) {
            return '';
        }

        if (isLoading) {
            const originalHtml = button.innerHTML;
            button.dataset.originalHtml = originalHtml;
            button.disabled = true;
            button.innerHTML = `<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span>${loadingText}`;
            return originalHtml;
        }

        const originalHtml = button.dataset.originalHtml;
        if (originalHtml) {
            button.innerHTML = originalHtml;
        }
        button.disabled = false;
        delete button.dataset.originalHtml;
        return originalHtml || '';
    };

    const postAction = async function ({ button, url, data, statusTargetId, loadingText, methodOverride }) {
        if (!button || button.dataset.loadingState === '1') {
            return;
        }

        button.dataset.loadingState = '1';
        toggleButton(button, loadingText, true);

        if (statusTargetId) {
            setStatus(statusTargetId, loadingText, false, data?.type ? 'test' : 'save');
        }

        try {
            const payload = new FormData();
            payload.append('_token', token);
            if (methodOverride) {
                payload.append('_method', methodOverride);
            }
            Object.entries(data || {}).forEach(function ([key, value]) {
                payload.append(key, value);
            });

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: payload,
            });

            const result = await response.json().catch(() => ({}));
            const message = result?.messege || result?.message || '{{ __('Saved successfully') }}';
            const isSuccess = !!result?.status;

            if (!response.ok) {
                if (statusTargetId) {
                    setStatus(statusTargetId, message, true, data?.type ? 'test' : 'save');
                }
                return;
            }

            if (statusTargetId) {
                setStatus(statusTargetId, message, !isSuccess, data?.type ? 'test' : 'save');
            }

            if (data?.type) {
                setBadge(data.type, isSuccess);
            }
        } catch (error) {
            if (statusTargetId) {
                setStatus(statusTargetId, '{{ __('Save failed') }}', true, data?.type ? 'test' : 'save');
            }
        } finally {
            button.dataset.loadingState = '0';
            toggleButton(button, loadingText, false);
        }
    };

    fieldButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            const fieldName = button.getAttribute('data-bunny-inline-save');
            const formData = new FormData(form);
            let fieldValue = formData.get(fieldName);

            if (fieldName.endsWith('_status')) {
                const checked = form.querySelector(`input[name="${fieldName}"][type="checkbox"]:checked`);
                fieldValue = checked ? checked.value : 'inactive';
            }

            postAction({
                button,
                url: form.action,
                data: {
                    bunny_save_field: fieldName,
                    [fieldName]: fieldValue,
                },
                statusTargetId: `${fieldName}_status`,
                loadingText: '{{ __('Saving...') }}',
                methodOverride: 'PUT',
            });
        });
    });

    testButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            postAction({
                button,
                url: @json(route('admin.bunny-connection-test')),
                data: {
                    type: button.getAttribute('data-bunny-test'),
                },
                statusTargetId: `bunny_${button.getAttribute('data-bunny-test')}_test_status`,
                loadingText: '{{ __('Testing...') }}',
            });
        });
    });

    if (clearButton) {
        clearButton.addEventListener('click', function (event) {
            event.preventDefault();
            postAction({
                button: clearButton,
                url: @json(route('admin.bunny-connection-test.clear')),
                data: {},
                statusTargetId: null,
                loadingText: '{{ __('Clearing...') }}',
            });
        });
    }
});
</script>
