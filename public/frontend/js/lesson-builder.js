(function () {
    'use strict';

    const root = document.querySelector('[data-lesson-builder]');
    if (!root) return;

    const form = root.querySelector('[data-lesson-form]');
    const steps = Array.from(root.querySelectorAll('[data-step]'));
    const indicators = Array.from(root.querySelectorAll('[data-step-indicator]'));
    const nextButton = root.querySelector('[data-next-step]');
    const previousButton = root.querySelector('[data-prev-step]');
    const publishButton = root.querySelector('[data-publish]');
    const draftButton = root.querySelector('[data-save-draft]');
    const notice = root.querySelector('[data-form-notice]');
    const publicationStatus = root.querySelector('[data-publication-status]');
    const durationMinutes = root.querySelector('[data-duration-minutes]');
    const durationDisplay = form.elements.duration_display;
    const sourceInputs = Array.from(form.querySelectorAll('input[name="source"]'));
    const videoInput = root.querySelector('[data-video-input]');
    const videoDropzone = root.querySelector('[data-video-dropzone]');
    const videoCard = root.querySelector('[data-video-card]');
    const videoPreview = root.querySelector('[data-video-preview]');
    const phoneVideoPreview = root.querySelector('[data-phone-video-preview]');
    const resourceInput = root.querySelector('[data-resource-input]');
    const resourceList = root.querySelector('[data-resource-list]');
    const resourceEmpty = root.querySelector('[data-resource-empty]');
    let currentStep = 1;
    let highestStep = 1;
    let resourceFiles = [];
    let videoObjectUrl = null;

    function showNotice(message, isError) {
        notice.textContent = message;
        notice.classList.toggle('is-error', Boolean(isError));
        notice.hidden = !message;
        if (message) notice.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function showStep(stepNumber) {
        currentStep = Math.max(1, Math.min(4, stepNumber));
        highestStep = Math.max(highestStep, currentStep);
        steps.forEach((step) => {
            const active = Number(step.dataset.step) === currentStep;
            step.hidden = !active;
            step.classList.toggle('is-active', active);
        });
        indicators.forEach((indicator) => {
            const number = Number(indicator.dataset.stepIndicator);
            indicator.classList.toggle('is-active', number === currentStep);
            indicator.classList.toggle('is-complete', number < currentStep);
            const button = indicator.querySelector('button');
            if (button) button.setAttribute('aria-current', number === currentStep ? 'step' : 'false');
        });
        previousButton.hidden = currentStep === 1;
        nextButton.hidden = currentStep === 4;
        publishButton.hidden = currentStep !== 4;
        if (currentStep === 4) updateSummary();
        const card = steps.find((step) => Number(step.dataset.step) === currentStep);
        if (card && window.matchMedia('(max-width: 700px)').matches) {
            card.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function stepForField(field) {
        return Number(field && field.closest('[data-step]')?.dataset.step) || 1;
    }

    function clearFieldError(field) {
        if (!field) return;
        field.classList.remove('is-invalid');
        const error = root.querySelector(`[data-error-for="${CSS.escape(field.name.replace(/\[\]$/, ''))}"]`);
        if (error) error.textContent = '';
    }

    function setFieldError(name, message) {
        const normalized = name.replace(/\.\d+$/, '').replace(/\.$/, '');
        const candidates = [name, normalized, normalized.replace('resources', 'resources[]')];
        const field = candidates.map((candidate) => form.elements[candidate]).find(Boolean);
        if (field) {
            const target = field.length && !field.tagName ? field[0] : field;
            target.classList?.add('is-invalid');
        }
        const error = root.querySelector(`[data-error-for="${CSS.escape(normalized)}"]`)
            || (normalized.startsWith('resources') ? root.querySelector('[data-error-for="resources"]') : null);
        if (error) error.textContent = Array.isArray(message) ? message[0] : message;
        return field ? stepForField(field.length && !field.tagName ? field[0] : field) : 1;
    }

    function validateStep(stepNumber) {
        const section = steps.find((step) => Number(step.dataset.step) === stepNumber);
        if (!section) return true;
        const source = form.querySelector('input[name="source"]:checked')?.value;
        if (stepNumber === 2) {
            videoInput.required = source === 'bunny_stream';
            form.elements.link_path.required = source === 'youtube';
        }
        const fields = Array.from(section.querySelectorAll('input, select, textarea')).filter((field) => {
            return !field.disabled && field.type !== 'hidden' && !field.closest('[hidden]');
        });
        for (const field of fields) {
            clearFieldError(field);
            if (!field.checkValidity()) {
                field.reportValidity();
                field.focus({ preventScroll: true });
                field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
        }
        if (stepNumber === 1 && !durationMinutes.value) {
            setFieldError('duration_display', 'Use MM:SS or HH:MM:SS for the duration.');
            durationDisplay.focus();
            return false;
        }
        return true;
    }

    function parseDuration(value) {
        const parts = String(value || '').trim().split(':').map(Number);
        if (parts.some(Number.isNaN) || parts.length < 2 || parts.length > 3) return null;
        const seconds = parts.length === 3
            ? parts[0] * 3600 + parts[1] * 60 + parts[2]
            : parts[0] * 60 + parts[1];
        if (seconds <= 0 || parts[parts.length - 1] > 59 || (parts.length === 3 && parts[1] > 59)) return null;
        return seconds;
    }

    function formatDuration(seconds) {
        const value = Math.max(0, Math.round(seconds || 0));
        const hours = Math.floor(value / 3600);
        const minutes = Math.floor((value % 3600) / 60);
        const secs = value % 60;
        return hours > 0
            ? `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
            : `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }

    function syncDuration() {
        const seconds = parseDuration(durationDisplay.value);
        durationMinutes.value = seconds ? (seconds / 60).toFixed(4) : '';
        root.querySelector('[data-phone-duration]').textContent = seconds ? formatDuration(seconds) : '00:00';
        root.querySelector('[data-phone-curriculum-duration]').textContent = seconds ? formatDuration(seconds) : '00:00';
        clearFieldError(durationDisplay);
    }

    function formatBytes(bytes) {
        if (bytes < 1024) return `${bytes} B`;
        if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
        return `${(bytes / (1024 * 1024)).toFixed(bytes >= 10 * 1024 * 1024 ? 1 : 2)} MB`;
    }

    function extensionOf(name) {
        return (name.split('.').pop() || 'FILE').toUpperCase();
    }

    function syncVideo(file) {
        if (!file) return removeVideo();
        if (file.size > 500 * 1024 * 1024) {
            videoInput.value = '';
            setFieldError('video_file', 'The video must be 500MB or smaller.');
            return;
        }
        clearFieldError(videoInput);
        if (videoObjectUrl) URL.revokeObjectURL(videoObjectUrl);
        videoObjectUrl = URL.createObjectURL(file);
        videoPreview.src = videoObjectUrl;
        phoneVideoPreview.src = videoObjectUrl;
        phoneVideoPreview.hidden = false;
        videoDropzone.hidden = true;
        videoCard.hidden = false;
        root.querySelector('[data-video-name]').textContent = file.name;
        root.querySelector('[data-video-meta]').textContent = `${formatBytes(file.size)} · ${extensionOf(file.name)}`;
        videoPreview.onloadedmetadata = function () {
            if (Number.isFinite(videoPreview.duration)) {
                durationDisplay.value = formatDuration(videoPreview.duration);
                syncDuration();
                root.querySelector('[data-video-meta]').textContent = `${formatBytes(file.size)} · ${extensionOf(file.name)} · ${formatDuration(videoPreview.duration)}`;
            }
        };
    }

    function removeVideo() {
        videoInput.value = '';
        videoDropzone.hidden = false;
        videoCard.hidden = true;
        phoneVideoPreview.hidden = true;
        videoPreview.removeAttribute('src');
        phoneVideoPreview.removeAttribute('src');
        if (videoObjectUrl) URL.revokeObjectURL(videoObjectUrl);
        videoObjectUrl = null;
    }

    function syncResourceInput() {
        const transfer = new DataTransfer();
        resourceFiles.forEach((file) => transfer.items.add(file));
        resourceInput.files = transfer.files;
    }

    function renderResources() {
        resourceEmpty.hidden = resourceFiles.length > 0;
        resourceList.querySelectorAll('.lesson-resource-row').forEach((row) => row.remove());
        const phoneResources = root.querySelector('[data-phone-resources]');
        phoneResources.innerHTML = '';

        if (!resourceFiles.length) {
            phoneResources.innerHTML = '<div class="android-empty"><i class="far fa-folder-open"></i><h4>Lesson resources</h4><p>No resources added yet.</p></div>';
            return;
        }

        resourceFiles.forEach((file, index) => {
            const type = extensionOf(file.name);
            const row = document.createElement('div');
            row.className = 'lesson-resource-row';
            row.innerHTML = `<span class="lesson-resource-row__icon"><i class="far fa-file-alt"></i></span><div><strong></strong><small></small></div><button type="button" aria-label="Remove resource"><i class="fas fa-times"></i></button>`;
            row.querySelector('strong').textContent = file.name;
            row.querySelector('small').textContent = `${formatBytes(file.size)} · ${type} · Ready to upload`;
            row.querySelector('button').addEventListener('click', function () {
                resourceFiles.splice(index, 1);
                syncResourceInput();
                renderResources();
            });
            resourceList.appendChild(row);

            const phoneRow = document.createElement('div');
            phoneRow.className = 'android-resource-item';
            phoneRow.innerHTML = '<i class="far fa-file-alt"></i><div><strong></strong><small></small></div><i class="fas fa-download"></i>';
            phoneRow.querySelector('strong').textContent = file.name;
            phoneRow.querySelector('small').textContent = `${formatBytes(file.size)} · ${type}`;
            phoneResources.appendChild(phoneRow);
        });
    }

    function addResources(files) {
        const allowed = ['pdf','doc','docx','ppt','pptx','xls','xlsx','zip','txt','jpg','jpeg','png','webp'];
        const additions = Array.from(files).filter((file) => {
            const extension = (file.name.split('.').pop() || '').toLowerCase();
            if (!allowed.includes(extension) || file.size > 50 * 1024 * 1024) return false;
            return !resourceFiles.some((existing) => existing.name === file.name && existing.size === file.size);
        });
        resourceFiles = resourceFiles.concat(additions).slice(0, 10);
        syncResourceInput();
        renderResources();
    }

    function syncSource() {
        const source = form.querySelector('input[name="source"]:checked')?.value || 'bunny_stream';
        root.querySelectorAll('[data-upload-source]').forEach((section) => {
            section.hidden = section.dataset.uploadSource !== source;
        });
        videoInput.required = source === 'bunny_stream';
        form.elements.link_path.required = source === 'youtube';
    }

    function updateTextPreview(type, value) {
        const fallbacks = {
            title: 'Untitled video lesson',
            overview: 'Your lesson overview will appear here as you type.',
            'lecture-number': '1.1'
        };
        const selectors = {
            title: ['[data-phone-title]', '[data-phone-lesson]'],
            overview: ['[data-phone-overview]'],
            'lecture-number': ['[data-phone-lecture]']
        };
        (selectors[type] || []).forEach((selector) => {
            root.querySelector(selector).textContent = value.trim() || fallbacks[type];
        });
    }

    function updateSummary() {
        const selectedSection = form.elements.chapter_id.options[form.elements.chapter_id.selectedIndex];
        const video = videoInput.files[0]?.name || form.elements.link_path.value || 'Not selected';
        const access = form.querySelector('input[name="access"]:checked')?.value === 'free_preview' ? 'Free preview' : 'Included with course';
        const qna = root.querySelector('[data-qna-master]').checked ? 'Enabled' : 'Disabled';
        const values = {
            section: selectedSection?.textContent.trim() || '—',
            'lecture-number': form.elements.lecture_number.value || '—',
            title: form.elements.title.value || '—',
            overview: form.elements.overview.value || '—',
            video,
            duration: durationDisplay.value || '—',
            resources: resourceFiles.length ? `${resourceFiles.length} file${resourceFiles.length === 1 ? '' : 's'}` : 'None',
            qna,
            access
        };
        Object.entries(values).forEach(([key, value]) => {
            const target = root.querySelector(`[data-summary="${key}"]`);
            if (target) target.textContent = value;
        });
    }

    function setBusy(busy) {
        [nextButton, previousButton, publishButton, draftButton].forEach((button) => button.disabled = busy);
        publishButton.querySelector('span').textContent = busy ? 'Uploading…' : 'Save & Publish';
        draftButton.textContent = busy ? 'Saving…' : 'Save Draft';
    }

    function submitLesson(status) {
        syncDuration();
        for (let step = 1; step <= 4; step += 1) {
            if (!validateStep(step)) {
                showStep(step);
                showNotice('Please complete the highlighted field before saving.', true);
                return;
            }
        }

        publicationStatus.value = status;
        showNotice('', false);
        setBusy(true);
        const progress = root.querySelector('[data-upload-progress]');
        const progressBar = root.querySelector('[data-upload-bar]');
        const progressPercent = root.querySelector('[data-upload-percent]');
        const progressLabel = root.querySelector('[data-upload-label]');
        progress.hidden = false;

        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action, true);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.upload.addEventListener('progress', function (event) {
            if (!event.lengthComputable) return;
            const percent = Math.min(100, Math.round((event.loaded / event.total) * 100));
            progressBar.style.width = `${percent}%`;
            progressPercent.textContent = `${percent}%`;
            progressLabel.textContent = percent === 100 ? 'Processing files' : 'Uploading';
        });
        xhr.addEventListener('load', function () {
            setBusy(false);
            let response = {};
            try { response = JSON.parse(xhr.responseText); } catch (error) { response = {}; }
            if (xhr.status >= 200 && xhr.status < 300) {
                progressBar.style.width = '100%';
                progressPercent.textContent = '100%';
                progressLabel.textContent = 'Ready';
                showNotice(response.message || 'Lesson saved successfully.', false);
                window.setTimeout(function () { window.location.assign(response.redirect_url); }, 650);
                return;
            }
            if (xhr.status === 422 && response.errors) {
                let firstStep = 4;
                Object.entries(response.errors).forEach(([name, messages]) => {
                    firstStep = Math.min(firstStep, setFieldError(name, messages));
                });
                showStep(firstStep);
                showNotice(response.message || 'Please correct the highlighted fields.', true);
            } else {
                showNotice(response.message || 'The lesson could not be saved. Please try again.', true);
            }
        });
        xhr.addEventListener('error', function () {
            setBusy(false);
            showNotice('The upload was interrupted. Check your connection and try again.', true);
        });
        xhr.send(new FormData(form));
    }

    nextButton.addEventListener('click', function () {
        if (validateStep(currentStep)) showStep(currentStep + 1);
    });
    previousButton.addEventListener('click', function () { showStep(currentStep - 1); });
    publishButton.addEventListener('click', function () { submitLesson('active'); });
    draftButton.addEventListener('click', function () { submitLesson('inactive'); });
    form.addEventListener('submit', function (event) { event.preventDefault(); submitLesson('active'); });

    root.querySelectorAll('[data-go-step]').forEach((button) => {
        button.addEventListener('click', function () {
            const target = Number(button.dataset.goStep);
            if (target <= highestStep || target < currentStep) showStep(target);
            else if (validateStep(currentStep)) showStep(target);
        });
    });

    form.querySelectorAll('[data-preview-input]').forEach((input) => {
        input.addEventListener('input', function () {
            if (input.dataset.previewInput === 'duration') syncDuration();
            else if (input.dataset.previewInput === 'qna-instructions') root.querySelector('[data-phone-qna-instructions]').textContent = input.value;
            else updateTextPreview(input.dataset.previewInput, input.value);
            if (input.name === 'overview') root.querySelector('[data-overview-count]').textContent = input.value.length.toLocaleString();
            clearFieldError(input);
        });
    });

    form.elements.chapter_id.addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        root.querySelector('[data-breadcrumb-section]').textContent = option?.textContent.trim() || 'Section';
        clearFieldError(this);
    });
    durationDisplay.addEventListener('blur', syncDuration);
    sourceInputs.forEach((input) => input.addEventListener('change', syncSource));
    videoInput.addEventListener('change', function () { syncVideo(this.files[0]); });
    root.querySelector('[data-replace-video]').addEventListener('click', function () { videoInput.click(); });
    root.querySelector('[data-remove-video]').addEventListener('click', removeVideo);

    ['dragenter', 'dragover'].forEach((eventName) => videoDropzone.addEventListener(eventName, function (event) {
        event.preventDefault();
        videoDropzone.classList.add('is-dragging');
    }));
    ['dragleave', 'drop'].forEach((eventName) => videoDropzone.addEventListener(eventName, function (event) {
        event.preventDefault();
        videoDropzone.classList.remove('is-dragging');
    }));
    videoDropzone.addEventListener('drop', function (event) {
        const file = event.dataTransfer.files[0];
        if (!file) return;
        const transfer = new DataTransfer();
        transfer.items.add(file);
        videoInput.files = transfer.files;
        syncVideo(file);
    });

    root.querySelector('[data-add-resource]').addEventListener('click', function () { resourceInput.click(); });
    resourceInput.addEventListener('change', function () { addResources(this.files); });
    root.querySelector('[data-qna-master]').addEventListener('change', function () {
        root.querySelector('[data-qna-options]').hidden = !this.checked;
        root.querySelector('[data-phone-qna-empty]').textContent = this.checked ? 'No questions yet.' : 'Q&A is disabled for this lesson.';
    });

    root.querySelectorAll('[data-preview-tab]').forEach((button) => {
        button.addEventListener('click', function () {
            root.querySelectorAll('[data-preview-tab]').forEach((tab) => tab.classList.toggle('is-active', tab === button));
            root.querySelectorAll('[data-preview-pane]').forEach((pane) => pane.hidden = pane.dataset.previewPane !== button.dataset.previewTab);
        });
    });
    root.querySelector('[data-preview-toggle]').addEventListener('click', function () {
        const preview = root.querySelector('[data-android-preview]');
        const open = preview.classList.toggle('is-open');
        this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    root.querySelector('[data-overview-count]').textContent = form.elements.overview.value.length.toLocaleString();
    syncSource();
    syncDuration();
    renderResources();
    showStep(1);
})();
