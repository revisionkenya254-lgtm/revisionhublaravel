(() => {
    const form = document.querySelector('[data-course-builder]');
    if (!form) return;

    let step = 1;
    const panels = [...form.querySelectorAll('[data-step]')];
    const markers = [...document.querySelectorAll('[data-step-marker]')];
    const back = form.querySelector('[data-back]');
    const next = form.querySelector('[data-next]');
    const submit = form.querySelector('[data-submit]');
    const alert = document.querySelector('[data-form-alert]');

    const show = value => {
        step = value;
        panels.forEach(panel => {
            const active = Number(panel.dataset.step) === step;
            panel.hidden = !active;
            panel.classList.toggle('active', active);
        });
        markers.forEach(marker => {
            const number = Number(marker.dataset.stepMarker);
            marker.classList.toggle('active', number === step);
            marker.classList.toggle('done', number < step);
        });
        back.hidden = step === 1;
        next.hidden = step === 4;
        submit.hidden = step !== 4;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const validate = () => {
        let valid = true;
        const current = panels.find(panel => Number(panel.dataset.step) === step);
        current.querySelectorAll('[required]').forEach(field => {
            field.classList.remove('is-invalid');
            if (!field.checkValidity()) {
                valid = false;
                field.classList.add('is-invalid');
            }
        });
        if (!valid) current.querySelector('.is-invalid')?.focus();
        return valid;
    };

    const updateReview = () => {
        document.querySelector('[data-review-title]').textContent = form.title.value.trim() || 'Untitled course';
        document.querySelector('[data-review-description]').textContent = form.description.value.trim() || '—';
        document.querySelector('[data-review-duration]').textContent = form.course_duration.value ? `${form.course_duration.value} minutes` : '—';
        document.querySelector('[data-review-category]').textContent = form.category.options[form.category.selectedIndex]?.text || '—';
        document.querySelector('[data-review-access]').textContent = form.access_type.value === 'paid' ? 'Paid course' : 'Free course';
    };

    next.addEventListener('click', () => {
        if (!validate()) return;
        if (step === 3) updateReview();
        show(Math.min(4, step + 1));
    });
    back.addEventListener('click', () => show(Math.max(1, step - 1)));

    form.querySelectorAll('[name="access_type"]').forEach(radio => radio.addEventListener('change', () => {
        form.querySelectorAll('.rh-access').forEach(card => card.classList.toggle('selected', card.querySelector('input').checked));
        const paid = form.access_type.value === 'paid';
        form.querySelector('[data-pricing-fields]').hidden = !paid;
        form.price.value = paid ? (form.price.value || '') : '0';
    }));

    form.querySelector('[data-video-source]').addEventListener('change', event => {
        const upload = event.target.value === 'bunny_stream';
        form.querySelector('[data-video-upload]').hidden = !upload;
        form.querySelector('[data-video-link]').hidden = upload;
    });
    form.thumbnail_file.addEventListener('change', event => {
        document.querySelector('[data-thumbnail-name]').textContent = event.target.files[0]?.name || '';
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (!validate()) return;
        submit.disabled = true;
        alert.hidden = true;
        try {
            const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
            const data = await response.json();
            if (!response.ok) {
                const messages = data.errors ? Object.values(data.errors).flat() : [data.message || 'Unable to create the course.'];
                throw new Error(messages.join(' '));
            }
            alert.classList.add('success');
            alert.textContent = data.message;
            alert.hidden = false;
            window.location.assign(data.redirect);
        } catch (error) {
            alert.classList.remove('success');
            alert.textContent = error.message;
            alert.hidden = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
            submit.disabled = false;
        }
    });
})();
