<style>
    .auth-submit-btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        overflow: hidden;
        min-height: 56px !important;
        padding: 0 24px !important;
        border: 2px solid #111827 !important;
        border-radius: 999px !important;
        background: #ffc221 !important;
        color: #111827 !important;
        font-size: 16px;
        font-weight: 700;
        box-shadow: 4px 4px 0 #111827 !important;
        transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
    }

    .auth-submit-btn:hover {
        filter: brightness(1.02) !important;
        transform: translate(-1px, -1px) !important;
        box-shadow: 5px 5px 0 #111827 !important;
    }

    .auth-submit-btn__content {
        display: inline-flex;
        align-items: center;
        gap: 12px;
    }

    .auth-submit-btn__loading {
        display: none;
        align-items: center;
        gap: 10px;
        min-width: 92px;
    }

    .auth-submit-btn__spinner {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, 0.45);
        border-top-color: #fff;
        animation: authSubmitSpin 0.8s linear infinite;
    }

    .auth-submit-btn__progress {
        position: relative;
        flex: 0 0 60px;
        height: 4px;
        overflow: hidden;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.32);
    }

    .auth-submit-btn__bar {
        position: absolute;
        inset: 0;
        width: 45%;
        border-radius: inherit;
        background: rgba(255, 255, 255, 0.98);
        animation: authSubmitProgress 1.1s ease-in-out infinite;
    }

    .auth-submit-btn.is-loading,
    .auth-submit-btn[aria-busy="true"] {
        cursor: progress !important;
        filter: none !important;
        transform: none !important;
        box-shadow: 4px 4px 0 #111827 !important;
    }

    .auth-submit-btn.is-loading .auth-submit-btn__label,
    .auth-submit-btn[aria-busy="true"] .auth-submit-btn__label {
        display: none;
    }

    .auth-submit-btn.is-loading .auth-submit-btn__loading,
    .auth-submit-btn[aria-busy="true"] .auth-submit-btn__loading {
        display: inline-flex;
    }

    .auth-submit-btn.is-loading > i,
    .auth-submit-btn[aria-busy="true"] > i,
    .auth-submit-btn.is-loading > img,
    .auth-submit-btn[aria-busy="true"] > img {
        display: none;
    }

    @keyframes authSubmitSpin {
        to {
            transform: rotate(360deg);
        }
    }

    @keyframes authSubmitProgress {
        0% {
            transform: translateX(-120%);
        }
        100% {
            transform: translateX(220%);
        }
    }
</style>
