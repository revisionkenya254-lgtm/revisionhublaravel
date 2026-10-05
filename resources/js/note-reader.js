const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('note-topic-search');
    const topicLinks = document.querySelectorAll('[data-topic-link]');
    const currentTopic = document.getElementById('current-note-topic');
    const groupToggles = document.querySelectorAll('[data-group-toggle]');
    const bookmarkButton = document.getElementById('bookmark-current-topic');
    const previewMode = currentTopic?.dataset.previewMode === '1';
    const currentNodeType = currentTopic?.dataset.nodeType;

    groupToggles.forEach(toggle => {
        const children = toggle.parentElement?.querySelector('.tutorial-note__group-children');

        if (children) {
            children.style.display = 'grid';
        }

        toggle.addEventListener('click', function () {
            const target = this.parentElement?.querySelector('.tutorial-note__group-children');

            if (!target) return;

            const icon = this.querySelector('.tutorial-note__group-icon');
            const isOpen = target.style.display !== 'none';

            target.style.display = isOpen ? 'none' : 'grid';

            if (icon) {
                icon.textContent = isOpen ? '+' : '-';
            }
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();

            topicLinks.forEach(link => {
                link.style.display = !query || link.textContent.toLowerCase().includes(query) ? '' : 'none';
            });
        });
    }

    if (currentTopic && !previewMode && currentNodeType === 'reading') {
        const progressUrl = currentTopic.dataset.progressUrl;

        if (progressUrl && csrfToken) {
            fetch(progressUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    node_id: currentTopic.dataset.nodeId,
                }),
            }).catch(() => {});
        }
    }

    if (bookmarkButton) {
        bookmarkButton.addEventListener('click', function () {
            const bookmarkUrl = this.dataset.bookmarkUrl;

            if (!bookmarkUrl || !csrfToken) return;

            fetch(bookmarkUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    node_id: this.dataset.nodeId,
                }),
            })
                .then(response => response.json())
                .then(() => window.location.reload())
                .catch(() => {});
        });
    }
});
