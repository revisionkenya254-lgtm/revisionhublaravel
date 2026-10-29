@php
    $pdfViewerId = 'pdf-reader-'.Str::uuid();
    $pdfSource = ($use_raw_url ?? false) ? $file_path : asset($file_path);
@endphp

<style>
    .revision-pdf-reader { position: relative; width: 100%; height: 100%; min-height: 520px; overflow: hidden; border-radius: inherit; background: #eef2f7; }
    .revision-pdf-reader__viewport { height: calc(100% - 58px); min-height: 462px; overflow: auto; padding: 24px; text-align: center; scrollbar-gutter: stable; }
    .revision-pdf-reader__page { position: relative; display: inline-block; min-width: 1px; min-height: 1px; line-height: 0; background: #fff; box-shadow: 0 14px 35px rgba(15, 23, 42, .16); }
    .revision-pdf-reader__canvas { display: block; max-width: none; }
    .revision-pdf-reader__regions { position: absolute; inset: 0; pointer-events: none; }
    .revision-pdf-reader__toolbar { position: absolute; right: 0; bottom: 0; left: 0; z-index: 5; display: flex; align-items: center; justify-content: center; gap: 8px; min-height: 58px; padding: 8px 12px; border-top: 1px solid rgba(15, 23, 42, .1); background: rgba(255, 255, 255, .96); backdrop-filter: blur(12px); }
    .revision-pdf-reader__toolbar button { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; padding: 0; border: 1px solid rgba(93, 63, 255, .18); border-radius: 12px; background: #fff; color: #312e81; cursor: pointer; }
    .revision-pdf-reader__toolbar button:hover:not(:disabled) { border-color: #5d3fff; background: #f5f3ff; }
    .revision-pdf-reader__toolbar button:disabled { cursor: not-allowed; opacity: .4; }
    .revision-pdf-reader__page-control { display: flex; align-items: center; gap: 6px; color: #475569; font-size: 14px; font-weight: 700; }
    .revision-pdf-reader__page-input { width: 58px; height: 40px; padding: 6px; border: 1px solid rgba(15, 23, 42, .14); border-radius: 10px; background: #fff; color: #0f172a; text-align: center; font-weight: 800; }
    .revision-pdf-reader__status { position: absolute; inset: 0 0 58px; z-index: 4; display: flex; align-items: center; justify-content: center; padding: 32px; background: #f8fafc; color: #475569; text-align: center; }
    .revision-pdf-reader__status[hidden] { display: none; }
    .revision-pdf-reader__status-card { max-width: 420px; }
    .revision-pdf-reader__status-icon { display: inline-flex; align-items: center; justify-content: center; width: 54px; height: 54px; margin-bottom: 14px; border-radius: 50%; background: #ede9fe; color: #5d3fff; font-size: 22px; }
    .revision-pdf-reader__status-title { margin: 0 0 6px; color: #0f172a; font-size: 17px; font-weight: 800; }
    .revision-pdf-reader__status-message { margin: 0; line-height: 1.6; }
    .revision-pdf-reader:fullscreen { height: 100vh; border-radius: 0; }
    @media (max-width: 575.98px) {
        .revision-pdf-reader__viewport { padding: 12px; }
        .revision-pdf-reader__toolbar { gap: 5px; padding-inline: 6px; }
        .revision-pdf-reader__toolbar button { width: 36px; height: 36px; }
        .revision-pdf-reader__page-input { width: 48px; height: 36px; }
    }
</style>

<div class="document-preview-area position-relative w-100">
    <div id="{{ $pdfViewerId }}" class="revision-pdf-reader" data-pdf-url="{{ $pdfSource }}" data-worker-url="{{ asset('frontend/js/pdf.worker.min.js') }}">
        <div class="revision-pdf-reader__viewport" data-pdf-viewport>
            <div class="revision-pdf-reader__page" data-pdf-page>
                <canvas class="revision-pdf-reader__canvas" data-pdf-canvas></canvas>
                <div class="revision-pdf-reader__regions" data-pdf-regions></div>
            </div>
        </div>

        <div class="revision-pdf-reader__status" data-pdf-status role="status" aria-live="polite">
            <div class="revision-pdf-reader__status-card">
                <span class="revision-pdf-reader__status-icon" aria-hidden="true"><i class="fas fa-spinner fa-spin"></i></span>
                <p class="revision-pdf-reader__status-title" data-pdf-status-title>{{ __('Loading document') }}</p>
                <p class="revision-pdf-reader__status-message" data-pdf-status-message>{{ __('Preparing your past paper…') }}</p>
            </div>
        </div>

        <div class="revision-pdf-reader__toolbar" aria-label="{{ __('PDF controls') }}">
            <button type="button" data-pdf-previous aria-label="{{ __('Previous page') }}" title="{{ __('Previous page') }}" disabled><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
            <label class="revision-pdf-reader__page-control">
                <span class="visually-hidden">{{ __('Page') }}</span>
                <input class="revision-pdf-reader__page-input" data-pdf-current-page value="1" min="1" type="number" inputmode="numeric" disabled>
                <span>/ <span data-pdf-page-count>—</span></span>
            </label>
            <button type="button" data-pdf-next aria-label="{{ __('Next page') }}" title="{{ __('Next page') }}" disabled><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
            <button type="button" data-pdf-zoom-out aria-label="{{ __('Zoom out') }}" title="{{ __('Zoom out') }}" disabled><i class="fas fa-search-minus" aria-hidden="true"></i></button>
            <button type="button" data-pdf-zoom-in aria-label="{{ __('Zoom in') }}" title="{{ __('Zoom in') }}" disabled><i class="fas fa-search-plus" aria-hidden="true"></i></button>
            <button type="button" data-pdf-fullscreen aria-label="{{ __('Full screen') }}" title="{{ __('Full screen') }}"><i class="fas fa-expand" aria-hidden="true"></i></button>
        </div>
    </div>
</div>

<script>
    (() => {
        const viewerId = @json($pdfViewerId);
        const questionRegions = @json(collect($questionRegions ?? [])->values());

        const showLibraryError = (root) => {
            if (!root) return;
            root.querySelector('[data-pdf-status-title]').textContent = @json(__('Reader could not start'));
            root.querySelector('[data-pdf-status-message]').textContent = @json(__('PDF.js did not load. Refresh the page and try again.'));
            root.querySelector('.revision-pdf-reader__status-icon').innerHTML = '<i class="fas fa-file-circle-exclamation" aria-hidden="true"></i>';
        };

        const startViewer = () => {
            const root = document.getElementById(viewerId);
            if (!root || root.dataset.pdfInitialized === 'true') return;
            if (typeof window.pdfjsLib === 'undefined') {
                showLibraryError(root);
                return;
            }

            root.dataset.pdfInitialized = 'true';
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = root.dataset.workerUrl;

            const viewportElement = root.querySelector('[data-pdf-viewport]');
            const pageElement = root.querySelector('[data-pdf-page]');
            const canvas = root.querySelector('[data-pdf-canvas]');
            const regionsElement = root.querySelector('[data-pdf-regions]');
            const statusElement = root.querySelector('[data-pdf-status]');
            const statusTitle = root.querySelector('[data-pdf-status-title]');
            const statusMessage = root.querySelector('[data-pdf-status-message]');
            const previousButton = root.querySelector('[data-pdf-previous]');
            const nextButton = root.querySelector('[data-pdf-next]');
            const zoomOutButton = root.querySelector('[data-pdf-zoom-out]');
            const zoomInButton = root.querySelector('[data-pdf-zoom-in]');
            const fullscreenButton = root.querySelector('[data-pdf-fullscreen]');
            const pageInput = root.querySelector('[data-pdf-current-page]');
            const pageCount = root.querySelector('[data-pdf-page-count]');
            const context = canvas.getContext('2d', { alpha: false });
            const state = { pdf: null, page: 1, scale: 1, renderTask: null, renderSequence: 0 };

            const setStatus = (title, message, isError = false) => {
                statusTitle.textContent = title;
                statusMessage.textContent = message;
                statusElement.querySelector('.revision-pdf-reader__status-icon').innerHTML = isError
                    ? '<i class="fas fa-file-circle-exclamation" aria-hidden="true"></i>'
                    : '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i>';
                statusElement.hidden = false;
            };

            const updateControls = () => {
                const ready = Boolean(state.pdf);
                previousButton.disabled = !ready || state.page <= 1;
                nextButton.disabled = !ready || state.page >= (state.pdf?.numPages || 1);
                zoomOutButton.disabled = !ready || state.scale <= .5;
                zoomInButton.disabled = !ready || state.scale >= 3;
                pageInput.disabled = !ready;
                pageInput.value = state.page;
            };

            const renderRegions = () => {
                regionsElement.innerHTML = '';
                const activeRegions = questionRegions.filter((region) => Number(region.page_number) === Number(state.page));
                regionsElement.style.pointerEvents = activeRegions.length ? 'auto' : 'none';

                activeRegions.forEach((region) => {
                    const box = document.createElement('button');
                    const isActive = Number(window.activePdfQuestionRegionId || 0) === Number(region.id);
                    box.type = 'button';
                    box.className = 'pdf-question-region';
                    box.setAttribute('aria-label', region.question_label || @json(__('Question region')));
                    box.dataset.regionId = region.id;
                    box.dataset.questionNumber = region.question_number || '';
                    box.style.cssText = `position:absolute;left:${(parseFloat(region.x) || 0) * 100}%;top:${(parseFloat(region.y) || 0) * 100}%;width:${(parseFloat(region.width) || 0) * 100}%;height:${(parseFloat(region.height) || 0) * 100}%;border:${isActive ? '2px solid rgba(29,78,216,.95)' : '2px solid transparent'};background:${isActive ? 'rgba(59,130,246,.16)' : 'transparent'};border-radius:10px;cursor:pointer;opacity:${isActive ? '.42' : '.03'}`;
                    box.title = `${region.question_label || @json(__('Question'))}${region.question_number ? ` #${region.question_number}` : ''}`;
                    box.addEventListener('click', () => window.dispatchEvent(new CustomEvent('pdf-question-selected', { detail: region })));
                    regionsElement.appendChild(box);
                });
            };

            const renderPage = async () => {
                if (!state.pdf) return;
                const sequence = ++state.renderSequence;
                if (state.renderTask) {
                    state.renderTask.cancel();
                    state.renderTask = null;
                }
                setStatus(@json(__('Loading page')), @json(__('Rendering the selected page…')));

                try {
                    const page = await state.pdf.getPage(state.page);
                    if (sequence !== state.renderSequence) return;
                    const baseViewport = page.getViewport({ scale: 1 });
                    const availableWidth = Math.max(280, viewportElement.clientWidth - 48);
                    const fitScale = Math.min(1.5, availableWidth / baseViewport.width);
                    const viewport = page.getViewport({ scale: fitScale * state.scale });
                    const outputScale = Math.min(window.devicePixelRatio || 1, 2);

                    canvas.width = Math.floor(viewport.width * outputScale);
                    canvas.height = Math.floor(viewport.height * outputScale);
                    canvas.style.width = `${Math.floor(viewport.width)}px`;
                    canvas.style.height = `${Math.floor(viewport.height)}px`;
                    pageElement.style.width = canvas.style.width;
                    pageElement.style.height = canvas.style.height;
                    state.renderTask = page.render({
                        canvasContext: context,
                        viewport,
                        transform: outputScale === 1 ? null : [outputScale, 0, 0, outputScale, 0, 0],
                    });
                    await state.renderTask.promise;
                    if (sequence !== state.renderSequence) return;

                    state.renderTask = null;
                    renderRegions();
                    statusElement.hidden = true;
                    updateControls();
                    window.dispatchEvent(new CustomEvent('pdf-page-changed', { detail: { page: state.page, pages: state.pdf.numPages } }));
                } catch (error) {
                    if (error?.name === 'RenderingCancelledException') return;
                    console.error('PDF page rendering failed.', error);
                    setStatus(@json(__('Page could not be displayed')), @json(__('Please refresh the page and try again.')), true);
                }
            };

            const goToPage = (pageNumber) => {
                if (!state.pdf) return;
                state.page = Math.min(state.pdf.numPages, Math.max(1, Number(pageNumber) || 1));
                pageInput.value = state.page;
                viewportElement.scrollTo({ top: 0, left: 0 });
                updateControls();
                renderPage();
            };

            previousButton.addEventListener('click', () => goToPage(state.page - 1));
            nextButton.addEventListener('click', () => goToPage(state.page + 1));
            pageInput.addEventListener('change', () => goToPage(pageInput.valueAsNumber));
            pageInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') { event.preventDefault(); goToPage(pageInput.valueAsNumber); }
            });
            zoomInButton.addEventListener('click', () => { state.scale = Math.min(3, Number((state.scale + .2).toFixed(1))); updateControls(); renderPage(); });
            zoomOutButton.addEventListener('click', () => { state.scale = Math.max(.5, Number((state.scale - .2).toFixed(1))); updateControls(); renderPage(); });
            fullscreenButton.addEventListener('click', async () => {
                if (!document.fullscreenElement) await root.requestFullscreen?.();
                else await document.exitFullscreen?.();
            });
            document.addEventListener('fullscreenchange', () => {
                fullscreenButton.innerHTML = document.fullscreenElement === root
                    ? '<i class="fas fa-compress" aria-hidden="true"></i>'
                    : '<i class="fas fa-expand" aria-hidden="true"></i>';
            });

            let resizeTimer;
            new ResizeObserver(() => {
                if (!state.pdf || state.scale !== 1) return;
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(renderPage, 120);
            }).observe(viewportElement);

            window.renderPdfQuestionRegions = renderRegions;
            window.pdfjsLib.getDocument({ url: root.dataset.pdfUrl }).promise
                .then((pdf) => {
                    state.pdf = pdf;
                    pageCount.textContent = pdf.numPages;
                    pageInput.max = pdf.numPages;
                    updateControls();
                    return renderPage();
                })
                .catch((error) => {
                    console.error('PDF document loading failed.', error);
                    setStatus(@json(__('Document could not be loaded')), @json(__('The file is unavailable or your access session has expired. Refresh the page and try again.')), true);
                });
        };

        if (typeof window.pdfjsLib !== 'undefined') startViewer();
        else if (document.readyState === 'complete') showLibraryError(document.getElementById(viewerId));
        else window.addEventListener('load', startViewer, { once: true });
    })();
</script>
