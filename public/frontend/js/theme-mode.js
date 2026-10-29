(() => {
    'use strict';

    const storageKey = 'revisionhub-theme';
    const validThemes = ['bright', 'dark'];

    const readTheme = () => {
        try {
            const savedTheme = window.localStorage.getItem(storageKey);
            return validThemes.includes(savedTheme) ? savedTheme : 'bright';
        } catch (error) {
            return 'bright';
        }
    };

    const updateControls = (theme) => {
        const isDark = theme === 'dark';

        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            const label = button.querySelector('[data-theme-toggle-label]');
            const accessibleLabel = isDark
                ? (button.dataset.themeSwitchBrightLabel || 'Switch to bright mode')
                : (button.dataset.themeSwitchDarkLabel || 'Switch to dark mode');

            button.setAttribute('aria-label', accessibleLabel);
            button.setAttribute('title', accessibleLabel);
            button.setAttribute('aria-pressed', isDark ? 'true' : 'false');

            if (label) {
                label.textContent = isDark
                    ? (button.dataset.themeBrightLabel || 'Bright')
                    : (button.dataset.themeDarkLabel || 'Dark');
            }
        });

        document.querySelectorAll('[data-theme-option]').forEach((button) => {
            const isSelected = button.dataset.themeOption === theme;
            button.classList.toggle('is-selected', isSelected);
            button.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
        });

        document.querySelectorAll('[data-theme-status]').forEach((status) => {
            status.textContent = isDark
                ? (status.dataset.themeDarkStatus || 'Dark mode is active.')
                : (status.dataset.themeBrightStatus || 'Bright mode is active.');
        });
    };

    const applyTheme = (theme, persist = true) => {
        const selectedTheme = validThemes.includes(theme) ? theme : 'bright';

        document.documentElement.dataset.theme = selectedTheme;
        document.documentElement.style.colorScheme = selectedTheme === 'dark' ? 'dark' : 'light';

        const themeColor = document.querySelector('meta[name="theme-color"]');
        themeColor?.setAttribute('content', selectedTheme === 'dark' ? '#111827' : '#ffffff');

        if (persist) {
            try {
                window.localStorage.setItem(storageKey, selectedTheme);
            } catch (error) {
                // The visual choice still applies for the current page.
            }
        }

        updateControls(selectedTheme);
        window.dispatchEvent(new CustomEvent('revisionhub:theme-changed', {
            detail: { theme: selectedTheme },
        }));
    };

    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-theme-toggle]');
        if (toggle) {
            const currentTheme = document.documentElement.dataset.theme === 'dark' ? 'dark' : 'bright';
            applyTheme(currentTheme === 'dark' ? 'bright' : 'dark');
            return;
        }

        const option = event.target.closest('[data-theme-option]');
        if (option) {
            applyTheme(option.dataset.themeOption);
        }
    });

    const initialize = () => applyTheme(readTheme(), false);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }

    window.addEventListener('storage', (event) => {
        if (event.key === storageKey) {
            applyTheme(readTheme(), false);
        }
    });

    window.RevisionHubTheme = { get: readTheme, set: applyTheme };
})();
