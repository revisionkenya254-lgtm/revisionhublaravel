<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Mpesa STK Push Checkout</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset($setting->favicon) }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/main.min.css') }}?v={{ $setting?->version }}">
    <link rel="stylesheet" href="{{ asset('global/toastr/toastr.min.css') }}">
    <style>
        .stk-status-card {
            border-radius: 18px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
        }
        .stk-status-note {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 18px;
        }
        .stk-status-note strong {
            display: block;
            margin-bottom: 4px;
        }
        .stk-status-demo {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 18px;
        }
        .stk-status-demo strong {
            display: block;
            margin-bottom: 4px;
        }
    </style>
</head>

<body>
    <section class="about-area-three section-py-120 vh-100 d-flex align-items-center justify-content-center">
        <div class="container d-flex justify-content-center">
            <div class="col-md-6">
                <div class="text-center mb-3">
                    <a href="{{ route('home') }}">
                        <img src="{{ asset($setting?->logo) }}" alt="{{ $setting?->app_name }}" width="220">
                    </a>
                </div>
                <div class="card singUp-wrap stk-status-card">
                    <div class="card-body">
                        <div class="stk-status-note">
                            <strong>{{ __('M-Pesa STK Push') }}</strong>
                            {{ __('Enter the Safaricom number that should receive the payment prompt, then approve the request on your phone.') }}
                        </div>

                        <form
                            id="mpesaStkPushForm"
                            action="{{ isset($token) ? route('payment-api.mpesa-stk-push-webview', ['bearer_token' => $token, 'order_id' => $order_id], false) : route('pay-via-mpesa-stk-push', [], false) }}"
                            method="post">
                            @csrf
                            <div class="my-1 form-group">
                                <label for="msisdn">{{ __('Phone Number') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="msisdn" name="msisdn" value="{{ old('msisdn', request('msisdn')) }}"
                                    placeholder="07XXXXXXXX or 2547XXXXXXXX">
                                @error('msisdn')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <button id="mpesaStkPushButton" class="mt-2 btn btn-primary">{{ __('Send STK Push') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script src="{{ asset('frontend/js/vendor/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('global/toastr/toastr.min.js') }}"></script>
    @if (session('messege'))
        <script>
            toastr.{{ session('alert-type', 'info') === 'error' ? 'error' : (session('alert-type', 'info') === 'success' ? 'success' : 'info') }}(@json(session('messege')));
        </script>
    @endif
    @if (request('autostk') && request('msisdn') && !$errors->any() && !session('messege'))
        <script>
            $(function () {
                const form = $('#mpesaStkPushForm');
                const button = $('#mpesaStkPushButton');

                button.prop('disabled', true).text(@json(__('Sending STK Push...')));
                form.trigger('submit');
            });
        </script>
    @endif
</body>

</html>
