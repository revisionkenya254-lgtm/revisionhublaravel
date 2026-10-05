<div class="tab-pane fade" id="mpesa_stk_push_tab" role="tabpanel">
    <form action="{{ route('admin.mpesa-stk-push-update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="form-group col-md-6">
                <label for="mpesa_stk_push_account_mode">{{ __('Account Mode') }}</label>
                <select name="mpesa_stk_push_account_mode" id="mpesa_stk_push_account_mode" class="form-control">
                    <option {{ old('mpesa_stk_push_account_mode', data_get($basic_payment, 'mpesa_stk_push_account_mode', 'production')) == 'sandbox' ? 'selected' : '' }} value="sandbox">
                        {{ __('Sandbox') }}
                    </option>
                    <option {{ old('mpesa_stk_push_account_mode', data_get($basic_payment, 'mpesa_stk_push_account_mode')) == 'production' ? 'selected' : '' }} value="production">
                        {{ __('Production') }}
                    </option>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="mpesa_stk_push_callback_url">{{ __('Callback URL') }}</label>
                <input type="url" name="mpesa_stk_push_callback_url" id="mpesa_stk_push_callback_url" class="form-control"
                    value="{{ old('mpesa_stk_push_callback_url', data_get($basic_payment, 'mpesa_stk_push_callback_url')) }}"
                    placeholder="{{ route('mpesa.stkpush.callback') }}">
                <small class="text-muted">{{ __('Leave blank to use the application callback URL.') }}</small>
            </div>
            <div class="form-group col-md-12">
                <label>{{ __('New Image') }} <code>({{ __('Recommended') }}: 210X100 PX)</code></label>
                <div id="mpesa_stk_push_image_preview" class="image-preview">
                    <label for="mpesa_stk_push_image_upload" id="mpesa_stk_push_image_label">{{ __('Image') }}</label>
                    <input type="file" name="mpesa_stk_push_image" id="mpesa_stk_push_image_upload">
                </div>
            </div>
            <div class="form-group col-md-12">
                <label class="d-flex align-items-center">
                    <input type="hidden" value="inactive" name="mpesa_stk_push_status" class="custom-switch-input">
                    <input type="checkbox" value="active" name="mpesa_stk_push_status" class="custom-switch-input"
                        {{ old('mpesa_stk_push_status', data_get($basic_payment, 'mpesa_stk_push_status', 'inactive')) == 'active' ? 'checked' : '' }}>
                    <span class="custom-switch-indicator"></span>
                    <span class="custom-switch-description">{{ __('Status') }}</span>
                </label>
            </div>
        </div>

        <button class="btn btn-primary">{{ __('Update') }}</button>
    </form>
</div>
