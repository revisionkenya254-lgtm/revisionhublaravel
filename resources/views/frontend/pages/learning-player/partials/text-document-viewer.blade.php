@php
    $pages = collect($readerPages ?? []);
    $summary = $readerSummary ?? [];
    $fullText = trim((string) ($readerText ?? ''));
    $pageCount = (int) ($summary['page_count'] ?? $pages->count());
    $characterCount = (int) ($summary['character_count'] ?? mb_strlen($fullText));
    $excerpt = trim((string) ($summary['excerpt'] ?? ''));
    $extractionMethod = trim((string) ($summary['extraction_method'] ?? ''));
@endphp

<div class="text-document-reader">
    <style>
        .text-document-reader {
            display: grid;
            gap: 18px;
            padding: 8px;
        }

        .text-document-reader__header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            padding: 18px 20px;
            border: 1px solid rgba(148, 163, 184, 0.24);
            border-radius: 18px;
            background:
                radial-gradient(circle at top left, rgba(191, 219, 254, 0.34), transparent 32%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.98));
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.06);
        }

        .text-document-reader__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: #2563eb;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .text-document-reader__header h3 {
            margin: 0;
            font-size: 24px;
            color: #0f172a;
        }

        .text-document-reader__header p {
            margin: 8px 0 0;
            color: #475569;
            max-width: 56rem;
        }

        .text-document-reader__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }

        .text-document-reader__stats {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .text-document-reader__stat {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(148, 163, 184, 0.24);
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }

        .text-document-reader__excerpt {
            padding: 16px 18px;
            border-radius: 16px;
            border: 1px solid rgba(59, 130, 246, 0.14);
            background: rgba(239, 246, 255, 0.72);
            color: #0f172a;
            line-height: 1.8;
        }

        .text-document-reader__pages {
            display: grid;
            gap: 16px;
        }

        .text-document-reader__page {
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 18px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.05);
        }

        .text-document-reader__page-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
        }

        .text-document-reader__page-title {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
        }

        .text-document-reader__page-meta {
            color: #64748b;
            font-size: 13px;
        }

        .text-document-reader__page-body {
            padding: 18px 18px 20px;
        }

        .text-document-reader__page-content {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-word;
            font-size: 15px;
            line-height: 1.9;
            color: #0f172a;
        }

        .text-document-reader__empty {
            padding: 28px;
            border-radius: 18px;
            border: 1px dashed rgba(148, 163, 184, 0.4);
            background: rgba(248, 250, 252, 0.9);
            color: #64748b;
            text-align: center;
        }

        @media (max-width: 767px) {
            .text-document-reader__header {
                flex-direction: column;
            }

            .text-document-reader__actions {
                justify-content: flex-start;
            }
        }
    </style>

    <div class="text-document-reader__stats">
        <span class="text-document-reader__stat">
            <i class="fas fa-file-lines"></i>
            {{ __('Pages') }}: {{ $pageCount }}
        </span>
        <span class="text-document-reader__stat">
            <i class="fas fa-font"></i>
            {{ __('Characters') }}: {{ number_format($characterCount) }}
        </span>
        <span class="text-document-reader__stat">
            <i class="fas fa-wand-magic-sparkles"></i>
            {{ __('Method') }}: {{ $extractionMethod !== '' ? $extractionMethod : strtoupper($product->file_type) }}
        </span>
    </div>

    @if ($excerpt !== '')
        <div class="text-document-reader__excerpt">
            <strong class="d-block mb-2">{{ __('Preview') }}</strong>
            {{ $excerpt }}
        </div>
    @endif

    @if ($pages->isNotEmpty())
        <div class="text-document-reader__pages">
            @foreach ($pages as $page)
                <article class="text-document-reader__page">
                    <div class="text-document-reader__page-head">
                        <div>
                            <h4 class="text-document-reader__page-title">
                                {{ $page['heading'] ?: __('Page :number', ['number' => $page['page_number']]) }}
                            </h4>
                            <div class="text-document-reader__page-meta">
                                {{ __('Page') }} {{ $page['page_number'] }}
                                @if (! empty($page['chunk_count']))
                                    · {{ __('Chunks') }}: {{ $page['chunk_count'] }}
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="text-document-reader__page-body">
                        <pre class="text-document-reader__page-content">{{ $page['content'] }}</pre>
                    </div>
                </article>
            @endforeach
        </div>
    @elseif ($fullText !== '')
        <div class="text-document-reader__empty">
            {{ __('We could not split this document into pages, but the extracted text is still available.') }}
        </div>

        <article class="text-document-reader__page">
            <div class="text-document-reader__page-head">
                <div>
                    <h4 class="text-document-reader__page-title">{{ __('Full text') }}</h4>
                    <div class="text-document-reader__page-meta">{{ __('Single-page text fallback') }}</div>
                </div>
            </div>
            <div class="text-document-reader__page-body">
                <pre class="text-document-reader__page-content">{{ $fullText }}</pre>
            </div>
        </article>
    @else
        <div class="text-document-reader__empty">
            {{ __('No extracted text is available for this document yet.') }}
        </div>
    @endif

    <div class="text-document-reader__header">
        <div>
            <div class="text-document-reader__eyebrow">
                {{ strtoupper($product->file_type) }} {{ __('Text Reader') }}
            </div>
            <h3>{{ __('Readable document content') }}</h3>
            <p>{{ __('This version is generated from the extracted document text so it works reliably on shared hosting.') }}</p>
        </div>

        <div class="text-document-reader__actions">
            <button id="text-document-reader-copy" type="button" class="btn btn-outline-primary rounded-pill">
                <i class="fas fa-copy me-1"></i>
                {{ __('Copy text') }}
            </button>
        </div>
    </div>

    <script>
        (function () {
            const copyButton = document.getElementById('text-document-reader-copy');
            if (!copyButton) {
                return;
            }

            const fullText = @json($fullText);

            copyButton.addEventListener('click', async function () {
                if (!fullText) {
                    return;
                }

                try {
                    await navigator.clipboard.writeText(fullText);
                    const original = copyButton.innerHTML;
                    copyButton.innerHTML = '<i class="fas fa-check me-1"></i>{{ __('Copied') }}';
                    setTimeout(() => {
                        copyButton.innerHTML = original;
                    }, 1400);
                } catch (error) {
                    // No-op: clipboard access may be blocked on some browsers.
                }
            });
        })();
    </script>
</div>
