<script src="{{ asset('global/js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('frontend/js/proper.min.js') }}"></script>
<script src="{{ asset('frontend/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('frontend/js/imagesloaded.pkgd.min.js') }}"></script>
<script src="{{ asset('frontend/js/jquery.magnific-popup.min.js') }}"></script>
<script src="{{ asset('frontend/js/jquery.odometer.min.js') }}"></script>
<script src="{{ asset('frontend/js/jquery.appear.js') }}"></script>
<script src="{{ asset('frontend/js/tween-max.min.js') }}"></script>
<script src="{{ asset('frontend/js/select2.min.js') }}"></script>
<script src="{{ asset('frontend/js/swiper-bundle.min.js') }}"></script>
<script src="{{ asset('frontend/js/jquery.marquee.min.js') }}"></script>
@if ($setting?->cursor_dot_status == 'active')
    <script src="{{ asset('frontend/js/tg-cursor.min.js') }}"></script>
@endif
<script src="{{ asset('frontend/js/svg-inject.min.js') }}"></script>
<script src="{{ asset('frontend/js/jquery.circleType.js') }}"></script>
<script src="{{ asset('frontend/js/jquery.lettering.min.js') }}"></script>
<script src="{{ asset('frontend/js/bootstrap-datepicker.min.js') }}"></script>
<script src="{{ asset('frontend/js/plyr.min.js') }}"></script>
<script src="{{ asset('frontend/js/wow.min.js') }}"></script>
<script src="{{ asset('frontend/js/aos.js') }}"></script>
<script src="{{ asset('frontend/js/vivus.min.js') }}"></script>
<script src="{{ asset('global/toastr/toastr.min.js') }}"></script>
<script src="{{ asset('frontend/js/sweetalert.js') }}"></script>
<script src="{{ asset('frontend/js/default/frontend.js') }}?v={{ $setting?->version }}"></script>
<script src="{{ asset('frontend/js/default/cart.js') }}?v={{ $setting?->version }}"></script>
<script src="{{ asset('global/nice-select/jquery.nice-select.min.js') }}"></script>
<!-- File Manager js-->
<script src="{{ url('/vendor/laravel-filemanager/js/stand-alone-button.js') }}"></script>


<script src="{{ asset('frontend/js/main.js') }}?v={{ $setting?->version }}"></script>

<script>
    $('.file-manager').filemanager('file', {
        prefix: '{{ url('/frontend-filemanager') }}'
    });
    $('.file-manager-image').filemanager('image', {
        prefix: '{{ url('/frontend-filemanager') }}'
    });

    SVGInject(document.querySelectorAll("img.injectable"));
</script>

<script>
    (() => {
        const modalSelector = '#aiChatOverlayModal';
        const frameSelector = '#aiChatOverlayFrame';
        const chatModalEl = document.querySelector(modalSelector);
        const chatFrameEl = document.querySelector(frameSelector);
        let chatModalInstance = null;

        if (!chatModalEl || !chatFrameEl || !window.bootstrap?.Modal) {
            return;
        }

        const loadChatUrl = (url) => {
            chatFrameEl.src = url;
        };

        const openChatModal = (url) => {
            const normalizedUrl = url ? String(url) : '';

            if (!normalizedUrl) {
                return;
            }

            loadChatUrl(normalizedUrl);
            chatModalInstance = bootstrap.Modal.getOrCreateInstance(chatModalEl);
            chatModalInstance.show();
        };

        const closeChatModal = () => {
            chatFrameEl.src = 'about:blank';

            if (chatModalInstance) {
                chatModalInstance.hide();
                return;
            }

            bootstrap.Modal.getOrCreateInstance(chatModalEl).hide();
        };

        window.revisionHubOpenAiChatModal = openChatModal;
        window.revisionHubCloseAiChatModal = closeChatModal;

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-open-ai-chat-modal]');
            if (!trigger) {
                return;
            }

            event.preventDefault();
            const url = trigger.getAttribute('data-ai-chat-url') || trigger.getAttribute('href');
            openChatModal(url);
        });

        chatModalEl.addEventListener('hidden.bs.modal', () => {
            chatFrameEl.src = 'about:blank';
        });

        window.addEventListener('message', (event) => {
            if (event.origin !== window.location.origin) {
                return;
            }

            if (event.data?.type === 'close-ai-chat-modal') {
                closeChatModal();
            }
        });
    })();
</script>

<!-- dynamic Toastr Notification -->
<script>
    "use strict";
    const normalizeToastMessage = (value, fallback = @json(__('Something went wrong.'))) => {
        if (value == null) {
            return fallback;
        }

        if (Array.isArray(value)) {
            const message = value
                .flat(Infinity)
                .map((item) => normalizeToastMessage(item, ''))
                .filter(Boolean)
                .join(' ')
                .replace(/\s+/g, ' ')
                .trim();

            return message || fallback;
        }

        if (typeof value === 'object') {
            if (typeof value.message === 'string' && value.message.trim()) {
                return normalizeToastMessage(value.message, fallback);
            }

            if (Array.isArray(value.message)) {
                return normalizeToastMessage(value.message, fallback);
            }

            if (value.errors && typeof value.errors === 'object') {
                const firstError = Object.values(value.errors)
                    .flat()
                    .find((entry) => typeof entry === 'string' && entry.trim());

                if (firstError) {
                    return normalizeToastMessage(firstError, fallback);
                }
            }

            try {
                return normalizeToastMessage(JSON.stringify(value), fallback);
            } catch (error) {
                return fallback;
            }
        }

        const message = String(value)
            .replace(/<[^>]*>/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();

        if (!message) {
            return fallback;
        }

        return message.length > 240 ? `${message.slice(0, 237)}...` : message;
    };

    const isNetworkRelatedError = (value) => {
        const message = normalizeToastMessage(value, '').toLowerCase();
        return [
            'internet connection lost',
            'connection refused',
            'failed to connect',
            'networkerror',
            'fetch failed',
            'failed to fetch',
            'load failed',
            'timed out',
            'timeout',
            'err_connection_refused',
            'err_network_changed',
            'api.openai.com/v1/chat/completions',
        ].some((needle) => message.includes(needle));
    };

    const prefixNetworkErrorMessage = (value, fallback = @json(__('Something went wrong.'))) => {
        const message = normalizeToastMessage(value, fallback);
        if (!message) {
            return fallback;
        }

        if (message.toLowerCase().startsWith('internet connection lost')) {
            return message;
        }

        return isNetworkRelatedError(message)
            ? `Internet connection lost. ${message}`
            : message;
    };

    window.revisionHubToast = (type, message, options = {}) => {
        if (!window.toastr || typeof toastr[type] !== 'function') {
            return;
        }

        const brandName = @json($setting->app_name);
        const normalizedMessage = normalizeToastMessage(message, options.fallback);
        if (!normalizedMessage) {
            return;
        }

        const toastOptions = {
            closeButton: true,
            escapeHtml: true,
            extendedTimeOut: 2000,
            title: brandName,
            preventDuplicates: true,
            positionClass: 'toast-bottom-right',
            progressBar: true,
            timeOut: 5000,
            toastClass: 'toast revision-toast',
            ...options,
        };

        delete toastOptions.fallback;

        toastr.options.closeButton = toastOptions.closeButton;
        toastr.options.progressBar = toastOptions.progressBar;
        toastr.options.positionClass = toastOptions.positionClass;
        toastr.options.preventDuplicates = toastOptions.preventDuplicates;
        toastr.options.escapeHtml = toastOptions.escapeHtml;
        toastr.options.timeOut = toastOptions.timeOut;
        toastr.options.extendedTimeOut = toastOptions.extendedTimeOut;

        const title = toastOptions.title ?? null;
        delete toastOptions.title;
        delete toastOptions.toastClass;

        toastr[type](normalizedMessage, title, toastOptions);
    };

    toastr.options.closeButton = true;
    toastr.options.progressBar = true;
    toastr.options.positionClass = 'toast-bottom-right';
    toastr.options.preventDuplicates = true;
    toastr.options.escapeHtml = true;
    toastr.options.toastClass = 'toast revision-toast';

    @session('messege')
    var type = "{{ Session::get('alert-type', 'info') }}"
    switch (type) {
        case 'info':
            window.revisionHubToast('info', @json($value));
            break;
        case 'success':
            window.revisionHubToast('success', @json($value));
            break;
        case 'warning':
            window.revisionHubToast('warning', @json($value));
            break;
        case 'error':
            window.revisionHubToast('error', @json($value));
            break;
    }
    @endsession

    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        orientation: "bottom auto"
    });
</script>


<!-- Toastr -->
@if (isset($errors) && $errors->any())
    @foreach ($errors->all() as $error)
        <script>
            window.revisionHubToast('error', @json($error), {
                timeOut: 10000
            });
        </script>
    @endforeach
@endif


<!-- Google reCAPTCHA -->
@if ($setting?->recaptcha_status === 'active')
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endif

<!-- tawk -->
@if ($setting->tawk_status == 'active')
    <script type="text/javascript">
        "use strict";
        var Tawk_API = Tawk_API || {},
            Tawk_LoadStart = new Date();
        (function() {
            var s1 = document.createElement("script"),
                s0 = document.getElementsByTagName("script")[0];
            s1.async = true;
            s1.src = '{{ $setting->tawk_chat_link }}';
            s1.charset = 'UTF-8';
            s1.setAttribute('crossorigin', '*');
            s0.parentNode.insertBefore(s1, s0);
        })();
    </script>
@endif

<!-- Cookie Consent -->
@if ($setting->cookie_status == 'active')
    <script src="{{ asset('frontend/js/cookieconsent.min.js') }}"></script>

    <script>
        "use strict";
        window.addEventListener("load", function() {
            window.wpcc.init({
                "border": "{{ $setting->border }}",
                "corners": "{{ $setting->corners }}",
                "colors": {
                    "popup": {
                        "background": "{{ $setting->background_color }}",
                        "text": "{{ $setting->text_color }} !important",
                        "border": "{{ $setting->border_color }}"
                    },
                    "button": {
                        "background": "{{ $setting->btn_bg_color }}",
                        "text": "{{ $setting->btn_text_color }}"
                    }
                },
                "content": {
                    "href": "{{ filled($setting?->link) ? url($setting->link) : url('/') }}",
                    "message": "{{ $setting->message }}",
                    "link": "{{ $setting->link_text }}",
                    "button": "{{ $setting->btn_text }}"
                }
            })
        });
    </script>
@endif

<script>
    if ($(".marquee_mode").length) {
        $('.marquee_mode').marquee({
            speed: 20,
            gap: 35,
            delayBeforeStart: 0,
            direction: "{{ Session::has('text_direction') && Session::get('text_direction') == 'rtl' ? 'right' : 'left' }}",
            duplicated: true,
            pauseOnHover: true,
            startVisible: true,
        });
    }
</script>

<script>
    $(document).on("click", '.wpcc-btn', function() {
        $('.wpcc-container').fadeOut(1000);
    });
</script>
