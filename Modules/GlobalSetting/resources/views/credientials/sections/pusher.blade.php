<div class="tab-pane fade active show" id="pusher_tab" role="tabpanel">
    <form action="{{ route('admin.update-pusher') }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="">{{ __('Pusher App ID') }}</label>
            <input type="text" class="form-control" name="pusher_app_id" value="{{ $setting?->pusher_app_id }}">
        </div>
        <div class="form-group">
            <label for="">{{ __('Pusher App Key') }}</label>
            <input type="text" class="form-control" name="pusher_app_key" value="{{ $setting?->pusher_app_key }}">
        </div>
        <div class="form-group">
            <label for="">{{ __('Pusher App Secret') }}</label>
            @if (env('APP_MODE') == 'DEMO')
                <input type="text" class="form-control" name="pusher_app_secret" value="ZXN39334XKF-SITE-KEY-TEST">
            @else
                <input type="text" class="form-control" name="pusher_app_secret"
                    value="{{ $setting?->pusher_app_secret }}">
            @endif
        </div>

        <div class="form-group">
            <label for="">{{ __('Pusher App Cluster') }}</label>
            <input type="text" class="form-control" name="pusher_app_cluster" value="{{ $setting?->pusher_app_cluster }}">
        </div>
        <div class="form-group">
            <label class="d-flex align-items-center">
                <input type="hidden" value="inactive" name="pusher_status" class="custom-switch-input">
                <input type="checkbox" value="active" name="pusher_status" class="custom-switch-input"
                    {{ $setting?->pusher_status == 'active' ? 'checked' : '' }}>
                <span class="custom-switch-indicator"></span>
                <span class="custom-switch-description">{{ __('Status') }}</span>
            </label>
        </div>

        <button class="btn btn-primary">{{ __('Update') }}</button>

    </form>
</div>

