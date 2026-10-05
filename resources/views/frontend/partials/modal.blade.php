<!-- Modal -->
<div class="modal fade dynamic-modal modal-lg" tabindex="-1" aria-labelledby="dynamic-modalLabel" aria-hidden="true" data-bs-backdrop='static'>
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="d-flex justify-content-center align-items:center p-3">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">{{ __('Loading') }}...</span>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade bd-example-modal-lg" id="iframeModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <h5>{{__('Your are using this website under an external iframe')}}</h5>
                <p>{{__('For a better experience please browse directly instead of an external iframe')}}</p>
            </div>
            <div class="modal-footer justify-content-center">
                <a target="_blank" href="{{url('/')}}" class="btn btn-sm btn-primary">{{__('Browse Directly')}}</a>
            </div>
        </div>
    </div>
</div>

@auth
    <div class="modal fade ai-chat-overlay-modal" id="aiChatOverlayModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable ai-chat-overlay-modal__dialog">
            <div class="modal-content ai-chat-overlay-modal__content">
                <div class="ai-chat-overlay-modal__header">
                    <div>
                        <p class="ai-chat-overlay-modal__eyebrow">{{ __('AI Assistant') }}</p>
                        <h5>{{ __('AI Chat') }}</h5>
                    </div>
                    <button type="button" class="btn-close ai-chat-overlay-modal__close" data-bs-dismiss="modal" aria-label="{{ __('Close AI chat') }}"></button>
                </div>
                <div class="ai-chat-overlay-modal__body">
                    <iframe
                        id="aiChatOverlayFrame"
                        title="{{ __('AI Chat') }}"
                        loading="eager"
                        referrerpolicy="same-origin"
                        class="ai-chat-overlay-modal__frame"
                        src="about:blank"></iframe>
                </div>
            </div>
        </div>
    </div>

    <style>
        .ai-chat-overlay-modal .modal-dialog {
            width: min(1560px, calc(100vw - 20px));
            max-width: none;
            margin: 10px auto;
        }

        .ai-chat-overlay-modal__content {
            height: min(94vh, 1000px);
            border: 0;
            border-radius: 26px;
            overflow: hidden;
            background: #f8fafc;
            box-shadow: 0 28px 80px rgba(15, 23, 42, 0.22);
        }

        .ai-chat-overlay-modal__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 18px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
            flex: 0 0 auto;
        }

        .ai-chat-overlay-modal__eyebrow {
            margin: 0 0 4px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #6b7280;
            font-weight: 800;
        }

        .ai-chat-overlay-modal__header h5 {
            margin: 0;
            color: #111827;
            font-size: 18px;
            font-weight: 800;
        }

        .ai-chat-overlay-modal__close {
            box-shadow: none;
        }

        .ai-chat-overlay-modal__body {
            min-height: 0;
            flex: 1 1 auto;
            background: #fff;
        }

        .ai-chat-overlay-modal__frame {
            width: 100%;
            height: 100%;
            min-height: calc(94vh - 58px);
            border: 0;
            display: block;
            background: #fff;
        }

        @media (max-width: 767.98px) {
            .ai-chat-overlay-modal .modal-dialog {
                width: calc(100vw - 10px);
                margin: 5px auto;
            }

            .ai-chat-overlay-modal__content {
                height: calc(100vh - 10px);
                border-radius: 18px;
            }

            .ai-chat-overlay-modal__header {
                padding: 12px 14px;
            }

            .ai-chat-overlay-modal__frame {
                min-height: calc(100vh - 66px);
            }
        }
    </style>
@endauth
