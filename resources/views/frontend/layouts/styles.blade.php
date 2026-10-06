<link rel="stylesheet" href="{{ asset('frontend/css/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/animate.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/magnific-popup.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/fontawesome-all.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/flaticon-revisionhubkenya.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/swiper-bundle.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/default-icons.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/odometer.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/aos.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/plyr.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/spacing.css') }}">
@if ($setting?->cursor_dot_status == 'active')
    <link rel="stylesheet" href="{{ asset('frontend/css/tg-cursor.css') }}">
@endif
<link rel="stylesheet" href="{{ asset('frontend/css/bootstrap-datepicker.min.css') }}">
<link rel="stylesheet" href="{{ asset('global/toastr/toastr.min.css') }}">
<style>
    #toast-container {
        z-index: 2147483647 !important;
        pointer-events: none;
    }

    #toast-container,
    #toast-container.toast-top-center,
    #toast-container.toast-bottom-center,
    #toast-container.toast-top-right,
    #toast-container.toast-bottom-right {
        width: min(360px, calc(100vw - 24px));
    }

    #toast-container.toast-top-center,
    #toast-container.toast-bottom-center {
        left: 50% !important;
        right: auto !important;
        transform: translateX(-50%);
    }

    #toast-container > div {
        width: 100% !important;
        margin: 0 0 10px !important;
        padding: 15px 16px 15px 56px !important;
        border-radius: 18px !important;
        border: 1px solid rgba(255, 255, 255, 0.16) !important;
        box-shadow:
            0 22px 50px rgba(15, 23, 42, 0.18),
            inset 0 1px 0 rgba(255, 255, 255, 0.16) !important;
        backdrop-filter: blur(18px);
        opacity: 1 !important;
        overflow: hidden;
        color: #fff !important;
        position: relative;
    }

    #toast-container > div:hover {
        box-shadow: 0 20px 48px rgba(15, 23, 42, 0.22) !important;
    }

    #toast-container > .revision-toast::before {
        content: "";
        position: absolute;
        inset: 12px auto 12px 12px;
        width: 6px;
        border-radius: 999px;
        background: var(--toast-accent, linear-gradient(180deg, #315efb, #8f7bff));
        box-shadow: 0 0 0 6px rgba(255, 255, 255, 0.07);
    }

    #toast-container > .revision-toast::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.12), transparent 40%);
        pointer-events: none;
    }

    #toast-container > .toast-success {
        --toast-accent: linear-gradient(180deg, #34d399, #059669);
        background: linear-gradient(135deg, #0f7a43, #0b5d56) !important;
    }

    #toast-container > .toast-error {
        --toast-accent: linear-gradient(180deg, #fb7185, #e11d48);
        background: linear-gradient(135deg, #be123c, #7f1d1d) !important;
    }

    #toast-container > .toast-warning {
        --toast-accent: linear-gradient(180deg, #fbbf24, #d97706);
        background: linear-gradient(135deg, #b45309, #92400e) !important;
    }

    #toast-container > .toast-info {
        --toast-accent: linear-gradient(180deg, #60a5fa, #2563eb);
        background: linear-gradient(135deg, #1d4ed8, #1e3a8a) !important;
    }

    #toast-container .toast-title,
    #toast-container .toast-message {
        color: inherit !important;
    }

    #toast-container .toast-title {
        margin-bottom: 4px;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        opacity: 0.92;
    }

    #toast-container .toast-message {
        line-height: 1.45;
        font-size: 14px;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    #toast-container .toast-close-button {
        position: absolute;
        top: 10px;
        right: 12px;
        opacity: 0.72;
        color: #fff !important;
        text-shadow: none;
        line-height: 1;
    }

    #toast-container .toast-progress {
        height: 3px;
        background-color: rgba(255, 255, 255, 0.44);
    }

    @media (max-width: 767.98px) {
        #toast-container.toast-top-center,
        #toast-container.toast-bottom-center,
        #toast-container.toast-top-right,
        #toast-container.toast-bottom-right {
            width: calc(100vw - 16px);
        }

        #toast-container > div {
            border-radius: 16px !important;
            padding: 12px 14px 12px 46px !important;
        }
    }
</style>
<link rel="stylesheet" href="{{ asset('global/nice-select/nice-select.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/main.min.css') }}?v={{ $setting?->version }}">
<link rel="stylesheet" href="{{ asset('frontend/css/frontend.min.css') }}?v={{ $setting?->version }}">
<style>
    @media (min-width: 992px) {
        .tgmobile__menu,
        .tgmobile__menu-backdrop {
            display: none !important;
        }

        body.mobile-menu-visible {
            overflow: auto !important;
        }
    }
</style>

@if (Session::has('text_direction') && Session::get('text_direction') == 'rtl')
    <!-- RTL CSS -->
    <link rel="stylesheet" href="{{ asset('frontend/css/rtl.css') }}?v={{ $setting?->version }}">
@endif

{{-- Dynamic root colors --}}
<style>
    :root {
        --tg-theme-primary: {{ $setting->primary_color }};
        --tg-theme-secondary: {{ $setting->secondary_color }};
        --tg-common-color-blue: {{ $setting->common_color_one }};
        --tg-common-color-blue-2: {{ $setting->common_color_two }};
        --tg-common-color-dark: {{ $setting->common_color_three }};
        --tg-common-color-black: {{ $setting->common_color_four }};
        --tg-common-color-dark-2: {{ $setting->common_color_five }};
    }

    .breadcrumb__bg {
        padding: 34px 0;
    }

    .breadcrumb__bg-two {
        padding: 18px 0;
    }

    .breadcrumb__bg-three {
        min-height: 105px;
    }

    .breadcrumb__content .title {
        font-size: 18px;
    }

    .slider__area .slider__bg {
        min-height: 230px;
    }

    .slider__content {
        padding-top: 12px;
        padding-bottom: 12px;
    }

    .banner__content-three {
        padding-top: 18px;
        padding-bottom: 18px;
    }

    .banner-bg-three .banner__content-three .title {
        font-size: 19px;
    }

    .subcategory-card-list {
        display: grid;
        gap: 10px;
    }

    .subcategory-card {
        position: relative;
    }

    .subcategory-card-input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .subcategory-card-label {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        min-height: 54px;
        padding: 12px 14px;
        border: 1px solid #d9d9e6;
        border-radius: 8px;
        background: #fff;
        color: #161439;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
    }

    .subcategory-card-label:hover {
        border-color: #111;
    }

    .subcategory-card-check {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: 2px solid #c5c8da;
        flex: 0 0 16px;
        position: relative;
        transition: border-color 0.2s ease, background-color 0.2s ease;
    }

    .subcategory-card-input:checked + .subcategory-card-label {
        background: #161439;
        border-color: #161439;
        color: #fff;
        box-shadow: 0 10px 22px rgba(22, 20, 57, 0.15);
    }

    .subcategory-card-input:checked + .subcategory-card-label .subcategory-card-check {
        border-color: #fff;
        background: #fff;
    }

    .subcategory-card-input:checked + .subcategory-card-label .subcategory-card-check::after {
        content: "";
        position: absolute;
        inset: 3px;
        border-radius: 50%;
        background: #161439;
    }

    .section-py-120 {
        padding-top: 60px;
        padding-bottom: 60px;
    }

    .section-py-140 {
        padding-top: 70px;
        padding-bottom: 70px;
    }

    .purchase-btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        min-height: 52px;
        padding: 14px 18px;
        border-radius: 999px;
        font-weight: 700;
        letter-spacing: 0.01em;
        text-align: center;
        transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
    }

    .purchase-btn:hover {
        transform: translateY(-1px);
    }

    .purchase-btn--cart {
        background: #ffffff;
        border: 1.5px solid #d6dbeb;
        color: #1d275f;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    }

    .purchase-btn--cart:hover,
    .purchase-btn--cart:focus-visible {
        background: #f8faff;
        border-color: #b8c3e6;
        color: #111a4d;
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.08);
    }

    .purchase-btn--cart.is-added-to-cart {
        background: #eefbf3;
        border-color: #9fd8b2;
        color: #166534;
        box-shadow: 0 12px 26px rgba(22, 101, 52, 0.1);
    }

    .purchase-btn--buy {
        background: linear-gradient(135deg, #151b3b 0%, #25346e 100%);
        border: 1.5px solid #151b3b;
        color: #ffffff;
        box-shadow: 0 16px 34px rgba(21, 27, 59, 0.24);
    }

    .purchase-btn--buy:hover,
    .purchase-btn--buy:focus-visible {
        background: linear-gradient(135deg, #111733 0%, #1f2d63 100%);
        border-color: #111733;
        color: #ffffff;
        box-shadow: 0 20px 36px rgba(21, 27, 59, 0.28);
    }

    .purchase-btn--buy i,
    .purchase-btn--cart i {
        font-size: 14px;
    }

    .courses__item-content .title {
        margin-bottom: 10px;
    }

    .courses__item-author-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
    }

    .courses__item-author-row .author {
        margin-bottom: 0;
        min-width: 0;
        font-size: 14px;
        line-height: 1.45;
    }

    .courses__item-author-row .price {
        margin-bottom: 0;
        flex: 0 0 auto;
        font-size: 16px;
        line-height: 1;
        color: #18214d;
        white-space: nowrap;
    }

    .catalog-grid-item .courses__item {
        border-radius: 18px;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06);
    }

    .catalog-grid-item .courses__item-thumb img {
        aspect-ratio: 16 / 10;
        object-fit: cover;
    }

    .catalog-grid-item .courses__item-content {
        padding: 18px 18px 16px;
    }

    .catalog-grid-item .courses__item-meta {
        margin-bottom: 8px;
        gap: 8px;
    }

    .catalog-grid-item .courses__item-meta li,
    .catalog-grid-item .courses__item-meta a,
    .catalog-grid-item .courses__item-meta .catalog-product-badge {
        font-size: 12px;
    }

    .catalog-grid-item .courses__item-content .title {
        font-size: 17px;
        line-height: 1.4;
    }

    .catalog-grid-item .courses__item-author-row {
        margin-bottom: 10px;
    }

    .catalog-grid-item .courses__item-author-row .author {
        font-size: 13px;
    }

    .catalog-grid-item .courses__item-author-row .price {
        font-size: 15px;
    }

    .catalog-grid-item .courses__item-bottom .button.d-flex {
        width: min(176px, 100%);
        gap: 8px !important;
    }

    .courses__item-bottom .button.d-flex {
        width: min(210px, 100%);
    }

    .catalog-grid-item .purchase-btn {
        min-height: 44px;
        padding: 11px 14px;
        font-size: 13px;
        letter-spacing: 0;
    }

    .courses__details-enroll .tg-button-wrap {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }

    .courses__details-sidebar > .purchase-btn + .purchase-btn {
        margin-top: 12px !important;
    }

    .file-type-badge {
        --file-badge-bg: rgba(115, 128, 155, 0.12);
        --file-badge-color: #4b5563;
        --file-badge-border: rgba(115, 128, 155, 0.22);
        --file-badge-icon-bg: #73809b;
        --file-badge-icon-color: #ffffff;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px 6px 7px;
        border: 1px solid var(--file-badge-border);
        border-radius: 999px;
        background: var(--file-badge-bg);
        color: var(--file-badge-color);
        font-weight: 800;
        line-height: 1;
        white-space: nowrap;
    }

    .file-type-badge__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 999px;
        background: var(--file-badge-icon-bg);
        color: var(--file-badge-icon-color);
        font-size: 11px;
        flex: 0 0 22px;
    }

    .file-type-badge--pdf {
        --file-badge-bg: rgba(220, 38, 38, 0.16);
        --file-badge-color: #991b1b;
        --file-badge-border: rgba(220, 38, 38, 0.30);
        --file-badge-icon-bg: #dc2626;
    }

    .file-type-badge--doc {
        --file-badge-bg: rgba(37, 99, 235, 0.14);
        --file-badge-color: #1d4ed8;
        --file-badge-border: rgba(37, 99, 235, 0.28);
        --file-badge-icon-bg: #2563eb;
    }

    .file-type-badge--docx {
        --file-badge-bg: rgba(22, 163, 74, 0.14);
        --file-badge-color: #166534;
        --file-badge-border: rgba(22, 163, 74, 0.28);
        --file-badge-icon-bg: #16a34a;
    }

    @media (max-width: 1199.98px) {
        .breadcrumb__bg {
            padding: 30px 0;
        }

        .slider__area .slider__bg {
            min-height: 200px;
        }

        .banner-bg-three .banner__content-three .title {
            font-size: 17px;
        }
    }

    @media (max-width: 767.98px) {
        .breadcrumb__bg {
            padding: 22px 0;
        }

        .breadcrumb__bg-two {
            padding: 14px 0;
        }

        .breadcrumb__bg-three {
            min-height: 90px;
        }

        .breadcrumb__content .title {
            font-size: 15px;
        }

        .slider__area .slider__bg {
            min-height: auto;
            padding: 11px 0;
        }

        .slider__content {
            padding-top: 7px;
            padding-bottom: 7px;
        }

        .banner__content-three {
            padding-top: 14px;
            padding-bottom: 14px;
        }

        .banner-bg-three .banner__content-three .title {
            font-size: 13px;
        }

        .section-py-120 {
            padding-top: 40px;
            padding-bottom: 40px;
        }

        .section-py-140 {
            padding-top: 48px;
            padding-bottom: 48px;
        }

        .courses__item-bottom .button.d-flex {
            width: 100%;
        }

        .courses__item-author-row {
            align-items: flex-start;
        }

        .courses__item-author-row .price {
            font-size: 15px;
        }
    }
</style>
