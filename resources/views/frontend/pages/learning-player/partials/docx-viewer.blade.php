@php
    $docUrl = ($use_raw_url ?? false) ? $file_path : asset($file_path);
    $fileType = strtolower((string) ($file_type ?? pathinfo(parse_url($docUrl, PHP_URL_PATH) ?? $docUrl, PATHINFO_EXTENSION)));
    $officeSourceUrl = $office_viewer_url ?? $docUrl;
    $officeViewerUrl = 'https://view.officeapps.live.com/op/embed.aspx?src=' . urlencode($officeSourceUrl);
@endphp

<div class="document-preview-area overflow-auto">
    <div id="docx-preview-container"></div>
</div>

<script>
    (function () {
        const docUrl = @json($docUrl);
        const fileType = @json($fileType);
        const officeViewerUrl = @json($officeViewerUrl);
        const previewContainer = document.getElementById('docx-preview-container');

        if (!previewContainer) {
            return;
        }

        function renderOfficeViewer() {
            previewContainer.innerHTML = `
                <iframe
                    src="${officeViewerUrl}"
                    frameborder="0"
                    allowfullscreen
                    class="w-100"
                    style="min-height: 75vh; border: 0; border-radius: 16px; background: #fff;"
                    title="Word document preview"
                ></iframe>
            `;
        }

        async function fetchAndPreviewWord() {
            if (fileType !== 'docx' || typeof docx === 'undefined' || typeof docx.renderAsync !== 'function') {
                renderOfficeViewer();
                return;
            }

            try {
                const response = await fetch(docUrl, { credentials: 'same-origin' });

                if (!response.ok) {
                    throw new Error('Unable to load document');
                }

                const blob = await response.blob();
                await docx.renderAsync(blob, previewContainer);
            } catch (error) {
                renderOfficeViewer();
            }
        }

        fetchAndPreviewWord();
    })();
</script>
