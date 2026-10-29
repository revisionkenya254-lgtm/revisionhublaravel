<div class="tab-pane fade" id="appearance-tab-pane" role="tabpanel" aria-labelledby="appearance-tab" tabindex="0">
    <section class="theme-settings" aria-labelledby="theme-settings-title">
        <div class="theme-settings__intro">
            <span class="theme-settings__eyebrow">{{ __('Display') }}</span>
            <h5 id="theme-settings-title">{{ __('Choose your theme') }}</h5>
            <p>{{ __('Your choice is saved on this device. Bright mode is used by default and your phone setting will not change it automatically.') }}</p>
        </div>

        <div class="theme-settings__choices" role="group" aria-label="{{ __('Theme preference') }}">
            <button type="button" class="theme-choice" data-theme-option="bright" aria-pressed="false">
                <span class="theme-choice__preview theme-choice__preview--bright" aria-hidden="true">
                    <span></span><span></span><span></span>
                </span>
                <span class="theme-choice__copy">
                    <strong><i class="far fa-sun" aria-hidden="true"></i> {{ __('Bright') }}</strong>
                    <small>{{ __('A clear, light background for daytime use.') }}</small>
                </span>
                <i class="fas fa-check-circle theme-choice__check" aria-hidden="true"></i>
            </button>

            <button type="button" class="theme-choice" data-theme-option="dark" aria-pressed="false">
                <span class="theme-choice__preview theme-choice__preview--dark" aria-hidden="true">
                    <span></span><span></span><span></span>
                </span>
                <span class="theme-choice__copy">
                    <strong><i class="far fa-moon" aria-hidden="true"></i> {{ __('Dark') }}</strong>
                    <small>{{ __('A softer, low-light theme for evening study.') }}</small>
                </span>
                <i class="fas fa-check-circle theme-choice__check" aria-hidden="true"></i>
            </button>
        </div>

        <p
            class="theme-settings__status"
            data-theme-status
            data-theme-bright-status="{{ __('Bright mode is active.') }}"
            data-theme-dark-status="{{ __('Dark mode is active.') }}"
            aria-live="polite"
        ></p>
    </section>
</div>
