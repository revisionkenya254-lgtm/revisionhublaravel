<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $title }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset($setting->favicon) }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/main.min.css') }}?v={{ $setting?->version }}">
    <style>
        .stk-status-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(circle at top left, rgba(34, 197, 94, 0.16), transparent 32%),
                linear-gradient(135deg, #f8fafc 0%, #eefbf3 100%);
            padding: 32px 12px;
        }
        .stk-status-card {
            width: 100%;
            max-width: 760px;
            border: 1px solid #dbe4dd;
            border-radius: 24px;
            box-shadow: 0 28px 55px rgba(15, 23, 42, 0.12);
            overflow: hidden;
        }
        .stk-status-band {
            padding: 24px 28px;
            color: #fff;
        }
        .stk-status-band.pending {
            background: linear-gradient(135deg, #166534 0%, #22c55e 100%);
        }
        .stk-status-band.failed {
            background: linear-gradient(135deg, #991b1b 0%, #ef4444 100%);
        }
        .stk-status-body {
            padding: 28px;
            background: #fff;
        }
        .stk-status-pill {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 14px;
        }
        .stk-status-pill.pending {
            background: #dcfce7;
            color: #166534;
        }
        .stk-status-pill.failed {
            background: #fee2e2;
            color: #b91c1c;
        }
        .stk-status-meta {
            margin-top: 22px;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: #f8fafc;
            padding: 18px 20px;
        }
        .stk-status-meta dt {
            color: #475569;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 4px;
        }
        .stk-status-meta dd {
            margin-bottom: 14px;
            color: #0f172a;
            word-break: break-word;
        }
        .stk-status-meta dd:last-child {
            margin-bottom: 0;
        }
        .stk-status-countdown {
            margin-top: 18px;
            font-size: 15px;
            color: #334155;
        }
        .stk-status-countdown strong {
            color: #0f172a;
        }
        .stk-spinner {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.28);
            border-top-color: #fff;
            animation: spin 0.85s linear infinite;
        }
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <section class="stk-status-shell">
        <div class="stk-status-card">
            <div class="stk-status-band {{ $status === 'pending' ? 'pending' : 'failed' }}">
                <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                    <div>
                        <h2 class="h3 mb-2 text-white">{{ $title }}</h2>
                        <p class="mb-0 text-white">{{ $message }}</p>
                    </div>
                    @if ($status === 'pending')
                        <div class="stk-spinner" aria-hidden="true"></div>
                    @endif
                </div>
            </div>

            <div class="stk-status-body">
                <span class="stk-status-pill {{ $status === 'pending' ? 'pending' : 'failed' }}">
                    {{ $status === 'pending' ? __('Waiting for Safaricom callback') : __('STK Push not completed') }}
                </span>

                @if ($order)
                    <p class="mb-2"><strong>{{ __('Invoice ID') }}:</strong> {{ $order->invoice_id }}</p>
                @endif

                @if ($status === 'pending' && !empty($expiresAt))
                    <p class="stk-status-countdown mb-0">
                        <strong>{{ __('Time remaining') }}:</strong>
                        <span id="stkCountdown">{{ __('Calculating...') }}</span>
                    </p>
                @endif

                @if (!empty($details))
                    <dl class="stk-status-meta mb-0">
                        @foreach ($details as $key => $value)
                            @continue(blank($value))
                            <dt>{{ str($key)->replace('_', ' ')->title() }}</dt>
                            <dd>{{ is_scalar($value) ? $value : json_encode($value) }}</dd>
                        @endforeach
                    </dl>
                @endif

                @if ($status !== 'pending' && $order)
                    <div class="mt-4">
                        <a href="{{ isset($token) ? route('payment-api.payment', ['token' => $token, 'order_id' => $order->invoice_id], false) : route('payment', ['invoice_id' => $order->invoice_id], false) }}" class="btn btn-primary">
                            {{ __('Back to payment page') }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </section>

    @if ($status === 'pending' && $pollUrl)
        <script>
            (function() {
                const pollUrl = @json($pollUrl);
                const expiresAt = @json($expiresAt ?? null);
                const countdown = document.getElementById('stkCountdown');
                let attempts = 0;
                const maxAttempts = 120;

                const renderCountdown = () => {
                    if (!countdown || !expiresAt) {
                        return;
                    }

                    const remainingMs = new Date(expiresAt).getTime() - Date.now();
                    const remainingSeconds = Math.max(0, Math.floor(remainingMs / 1000));
                    const minutes = Math.floor(remainingSeconds / 60);
                    const seconds = remainingSeconds % 60;

                    countdown.textContent = `${minutes}:${String(seconds).padStart(2, '0')}`;
                };

                const checkStatus = async () => {
                    attempts += 1;

                    try {
                        const response = await fetch(pollUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            credentials: 'same-origin'
                        });

                        const data = await response.json();

                        if ((data.status === 'paid' || data.status === 'failed') && data.redirect_url) {
                            window.location.replace(data.redirect_url);
                            return;
                        }
                    } catch (error) {
                    }

                    if (attempts < maxAttempts) {
                        setTimeout(checkStatus, 3000);
                    }
                };

                renderCountdown();
                setInterval(renderCountdown, 1000);
                setTimeout(checkStatus, 2500);
            })();
        </script>
    @endif
</body>

</html>
