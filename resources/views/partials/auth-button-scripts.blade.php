<script>
    window.AuthSubmitButton = window.AuthSubmitButton || {
        set(button, loading) {
            if (!button) {
                return;
            }

            button.classList.toggle('is-loading', loading);
            button.disabled = loading;
            button.setAttribute('aria-busy', loading ? 'true' : 'false');
        },
    };
</script>
