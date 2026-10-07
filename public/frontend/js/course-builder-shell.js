(() => {
    const shell = document.querySelector('[data-creator-shell]');
    if (!shell) return;

    const setOpen = open => {
        shell.classList.toggle('is-sidebar-open', open);
        document.body.classList.toggle('sidebar-locked', open);
    };

    document.querySelector('[data-sidebar-open]')?.addEventListener('click', () => setOpen(true));
    document.querySelectorAll('[data-sidebar-close]').forEach(button => button.addEventListener('click', () => setOpen(false)));
    window.addEventListener('keydown', event => {
        if (event.key === 'Escape') setOpen(false);
    });
})();
