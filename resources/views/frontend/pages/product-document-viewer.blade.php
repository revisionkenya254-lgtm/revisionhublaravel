@extends('frontend.layouts.master')
@section('meta_title', $product->title . ' || ' . $setting->app_name)
@section('body_class', 'document-reader-page')

@push('styles')
    <style>
        .document-reader-hero {
            padding: 48px 0 24px;
            background:
                radial-gradient(circle at top left, rgba(29, 78, 216, 0.14), transparent 34%),
                radial-gradient(circle at top right, rgba(99, 102, 241, 0.10), transparent 30%),
                linear-gradient(180deg, #f6f8fc 0%, #ffffff 100%);
        }

        .document-reader-shell {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 28px;
            background: #fff;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .document-reader-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 20px 24px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            background: linear-gradient(180deg, #f8fafc, #ffffff);
        }

        .document-reader-toolbar__meta h2 {
            margin: 0;
            font-size: 28px;
            line-height: 1.2;
            letter-spacing: -0.03em;
        }

        .document-reader-toolbar__meta p {
            margin: 6px 0 0;
            color: #64748b;
        }

        .document-reader-toolbar__badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: #eef2ff;
            color: #0f172a;
            font-weight: 700;
            white-space: nowrap;
        }

        .document-reader-stage {
            padding: 24px;
        }

        .document-reader-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) clamp(320px, 30vw, 420px);
            gap: 24px;
            align-items: start;
        }

        .document-reader-main {
            min-width: 0;
            display: grid;
            gap: 16px;
            order: 1;
        }

        .document-reader-frame {
            min-height: 75vh;
            border-radius: 24px;
            background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
            border: 1px solid rgba(15, 23, 42, 0.08);
            padding: 24px;
            position: relative;
        }

        .document-reader-frame .document-preview-area {
            min-height: 68vh;
            background: #fff;
            border-radius: 18px;
            box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.06);
        }

        .reader-note {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .document-reader-sidebar {
            order: 2;
            position: sticky;
            top: calc(var(--revision-header-topbar-height, 0px) + 112px);
            align-self: start;
        }

        .document-assistant {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 26px;
            background:
                radial-gradient(circle at top left, rgba(99, 102, 241, 0.12), transparent 30%),
                linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: 0 24px 50px rgba(15, 23, 42, 0.08);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: min(78vh, 920px);
        }

        .document-assistant__header {
            padding: 18px 18px 12px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        }

        .document-assistant__eyebrow {
            margin: 0 0 4px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: #5d3fff;
        }

        .document-assistant__eyebrow i {
            font-size: 15px;
        }

        .document-assistant__title {
            margin: 0;
            font-size: 18px;
            line-height: 1.2;
            letter-spacing: -0.03em;
        }

        .document-assistant__subtitle {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.45;
        }

        .document-assistant__tabs {
            display: flex;
            gap: 8px;
            overflow: auto;
            padding: 12px 16px 0;
            scrollbar-width: none;
        }

        .document-assistant__tabs::-webkit-scrollbar {
            display: none;
        }

        .document-assistant__tab {
            flex: 0 0 auto;
            min-height: 38px;
            padding: 0 14px;
            border-radius: 999px;
            border: 1px solid rgba(93, 63, 255, 0.16);
            background: #fff;
            color: #334155;
            font-size: 13px;
            font-weight: 700;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
        }

        .document-assistant__tab:hover {
            transform: translateY(-1px);
            border-color: rgba(93, 63, 255, 0.28);
        }

        .document-assistant__tab.is-active {
            background: linear-gradient(135deg, #5d3fff, #7c4dff);
            border-color: transparent;
            color: #fff;
            box-shadow: 0 12px 24px rgba(93, 63, 255, 0.22);
        }

        .document-assistant__context {
            margin: 10px 16px 0;
            padding: 12px 14px;
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.14);
            background: rgba(255, 255, 255, 0.8);
        }

        .document-assistant__context-label {
            margin: 0 0 4px;
            color: #5d3fff;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        .document-assistant__context-title {
            margin: 0;
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
        }

        .document-assistant__context-meta {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.45;
        }

        .document-assistant__chat {
            flex: 1 1 auto;
            display: grid;
            gap: 12px;
            padding: 16px;
            max-height: none;
            overflow: auto;
        }

        .document-assistant__message {
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .document-assistant__message.is-user {
            justify-content: flex-end;
        }

        .document-assistant__avatar {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #5d3fff, #7c4dff);
            color: #fff;
            font-size: 14px;
            box-shadow: 0 10px 18px rgba(93, 63, 255, 0.2);
        }

        .document-assistant__message.is-user .document-assistant__avatar {
            background: #e2e8f0;
            color: #0f172a;
            box-shadow: none;
        }

        .document-assistant__bubble {
            max-width: calc(100% - 48px);
            padding: 12px 14px;
            border-radius: 18px;
            background: #f8fafc;
            border: 1px solid rgba(15, 23, 42, 0.06);
            color: #0f172a;
            line-height: 1.6;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.04);
            white-space: pre-wrap;
            word-break: break-word;
        }

        .document-assistant__bubble p,
        .document-assistant__bubble ul,
        .document-assistant__bubble ol,
        .document-assistant__bubble blockquote,
        .document-assistant__bubble pre,
        .document-assistant__bubble table,
        .document-assistant__bubble .document-assistant__equation,
        .document-assistant__bubble .document-assistant__table-wrap {
            margin: 0.75rem 0;
        }

        .document-assistant__bubble p {
            white-space: normal;
        }

        .document-assistant__bubble ul,
        .document-assistant__bubble ol {
            padding-left: 1.25rem;
        }

        .document-assistant__bubble blockquote {
            padding: 0.75rem 0.95rem;
            border-left: 3px solid rgba(93, 63, 255, 0.34);
            background: rgba(93, 63, 255, 0.05);
            border-radius: 14px;
            color: #334155;
        }

        .document-assistant__bubble code {
            padding: 0.15rem 0.35rem;
            border-radius: 8px;
            background: rgba(15, 23, 42, 0.06);
            font-size: 0.95em;
        }

        .document-assistant__bubble pre {
            position: relative;
            overflow: auto;
            padding: 0.95rem 1rem 0.9rem;
            border-radius: 16px;
            background: #0f172a;
            color: #e2e8f0;
            border: 1px solid rgba(148, 163, 184, 0.18);
            white-space: pre;
        }

        .document-assistant__bubble pre code {
            display: block;
            padding: 0;
            background: transparent;
            color: inherit;
            line-height: 1.7;
        }

        .document-assistant__table-wrap {
            overflow-x: auto;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.18);
        }

        .document-assistant__bubble table {
            width: 100%;
            min-width: 320px;
            margin: 0;
            border-collapse: collapse;
            background: #fff;
        }

        .document-assistant__bubble th,
        .document-assistant__bubble td {
            padding: 0.75rem 0.85rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.14);
            text-align: left;
            vertical-align: top;
        }

        .document-assistant__bubble thead th {
            background: rgba(93, 63, 255, 0.06);
            font-weight: 700;
        }

        .document-assistant__bubble tbody tr:last-child td {
            border-bottom: 0;
        }

        .document-assistant__equation {
            position: relative;
            padding: 0.9rem 0.95rem 0.8rem;
            border-radius: 16px;
            background: linear-gradient(180deg, rgba(248, 250, 252, 0.98), rgba(241, 245, 249, 0.96));
            border: 1px solid rgba(148, 163, 184, 0.16);
        }

        .document-assistant__equation mjx-container,
        .document-assistant__equation .MathJax {
            display: block;
            margin: 0.15rem 0 0;
            overflow-x: auto;
            overflow-y: hidden;
        }

        .document-assistant__copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            z-index: 1;
            min-height: 28px;
            padding: 0 9px;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.24);
            background: rgba(255, 255, 255, 0.9);
            color: #475569;
            font-size: 11px;
            font-weight: 700;
        }

        .document-assistant__message.is-user .document-assistant__bubble {
            background: linear-gradient(135deg, #eff0ff, #ffffff);
            border-color: rgba(93, 63, 255, 0.12);
        }

        .document-assistant__bubble.is-loading {
            position: relative;
            overflow: hidden;
            color: #64748b;
        }

        .document-assistant__bubble.is-loading::after {
            content: '';
            position: absolute;
            inset: 0;
            background:
                linear-gradient(110deg, rgba(255, 255, 255, 0) 20%, rgba(255, 255, 255, 0.36) 35%, rgba(255, 255, 255, 0) 50%);
            background-size: 220% 100%;
            animation: assistantShimmer 1.2s linear infinite;
            pointer-events: none;
        }

        .document-assistant__suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 0 16px 14px;
        }

        .document-assistant__suggestion {
            flex: 0 0 auto;
            min-height: 36px;
            padding: 0 12px;
            border-radius: 999px;
            border: 1px solid rgba(93, 63, 255, 0.18);
            background: #fff;
            color: #4c1d95;
            font-size: 12px;
            font-weight: 700;
            transition: transform 0.2s ease, background-color 0.2s ease;
        }

        .document-assistant__suggestion:hover {
            transform: translateY(-1px);
            background: #f5f3ff;
        }

        .document-assistant__composer {
            display: grid;
            gap: 10px;
            padding: 0 16px 16px;
            margin-top: auto;
        }

        .document-assistant__composer textarea {
            min-height: 108px;
            resize: vertical;
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.3);
            background: #fff;
            padding: 14px 16px;
            color: #0f172a;
        }

        .document-assistant__composer textarea:focus {
            border-color: rgba(93, 63, 255, 0.42);
            box-shadow: 0 0 0 4px rgba(93, 63, 255, 0.08);
        }

        .document-assistant__composer-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .document-assistant__send {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 14px;
            background: linear-gradient(135deg, #5d3fff, #7c4dff);
            color: #fff;
            font-weight: 800;
            box-shadow: 0 14px 28px rgba(93, 63, 255, 0.22);
        }

        .document-assistant__send:hover {
            color: #fff;
            transform: translateY(-1px);
        }

        .document-assistant__note {
            margin: 0;
            padding: 0 16px 16px;
            color: #94a3b8;
            font-size: 12px;
            line-height: 1.5;
        }

        .document-assistant__context.is-empty {
            opacity: 0.92;
        }

        @keyframes assistantShimmer {
            from { background-position: 0% 0; }
            to { background-position: 220% 0; }
        }

        @media (max-width: 1199.98px) {
            .document-reader-layout {
                grid-template-columns: 1fr;
            }

            .document-reader-main,
            .document-reader-sidebar {
                order: initial;
            }

            .document-reader-sidebar {
                position: static;
                top: auto;
            }
        }

        @media (max-width: 767.98px) {
            .document-reader-toolbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .document-reader-toolbar__meta h2 {
                font-size: 22px;
            }

            .document-reader-stage,
            .document-reader-frame {
                padding: 16px;
            }

            .document-reader-frame {
                min-height: auto;
            }

            .document-assistant__header {
                flex-direction: column;
            }

            .document-assistant__chat {
                max-height: none;
            }

            .document-assistant__composer-row {
                flex-direction: column;
                align-items: stretch;
            }

            .document-assistant__send {
                width: 100%;
            }
        }
    </style>
@endpush

@section('contents')
    <x-frontend.breadcrumb :title="$product->title" :links="[['url' => route('home'), 'text' => __('Home')], ['url' => route('catalog'), 'text' => __('Catalog')], ['url' => route('product.show', $product->slug), 'text' => $product->title], ['url' => '', 'text' => __('Reader')]]" />

    <section class="document-reader-hero">
        <div class="container">
            <div class="document-reader-shell">
                <div class="document-reader-toolbar">
                    <div class="document-reader-toolbar__meta">
                        <h2>{{ $product->title }}</h2>
                        <p>{{ $readerMode === 'pdf' ? __('View-only reader for your purchased study material.') : __('Browser-friendly text reader for extracted document content.') }}</p>
                    </div>
                    <div class="document-reader-toolbar__badge">
                        <i class="fas fa-pen-to-square"></i>
                        {{ strtoupper($readerMode === 'pdf' ? $product->file_type : 'text') }} {{ __('Reader') }}
                    </div>
                </div>

                <div class="document-reader-stage">
                    <div class="document-reader-layout">
                        <div class="document-reader-main">
                            <div class="document-reader-frame">
                                @if ($readerMode === 'pdf')
                                    @include('frontend.pages.learning-player.partials.pdf-viewer', ['file_path' => $documentUrl, 'use_raw_url' => true, 'questionRegions' => $questionRegions ?? collect()])
                                @else
                                    @include('frontend.pages.learning-player.partials.text-document-viewer', [
                                        'product' => $product,
                                        'readerPages' => $readerPages ?? [],
                                        'readerText' => $readerText ?? '',
                                        'readerSummary' => $readerSummary ?? [],
                                    ])
                                @endif
                            </div>

                            <p class="reader-note">
                                {{ __('This material opens inside the app in view-only mode. Download and edit actions are not provided in the student reader.') }}
                            </p>
                        </div>

                        <aside class="document-reader-sidebar" aria-label="{{ __('AI assistant') }}">
                            <div class="document-assistant" id="document-assistant-shell">
                                <div class="document-assistant__header">
                                    <div>
                                        <p class="document-assistant__eyebrow">
                                            <i class="fas fa-sparkles" aria-hidden="true"></i>
                                            {{ __('AI Assistant') }}
                                        </p>
                                        <h3 class="document-assistant__title">{{ __('Ask about this document') }}</h3>
                                        <p class="document-assistant__subtitle">
                                            {{ __('Explain a concept, get a hint, or ask for a clearer version of the current page content.') }}
                                        </p>
                                    </div>
                                    <span class="document-assistant__badge">
                                        <i class="fas fa-brain" aria-hidden="true"></i>
                                        {{ __('Context aware') }}
                                    </span>
                                </div>

                                <div class="document-assistant__chat" id="document-assistant-chat" aria-live="polite">
                                    <div class="document-assistant__message is-assistant">
                                        <div class="document-assistant__avatar" aria-hidden="true">
                                            <i class="fas fa-sparkles"></i>
                                        </div>
                                        <div class="document-assistant__bubble">
                                            {{ __('Hello! Ask me anything about this document. I can explain, solve, hint, or summarize what you are reading.') }}
                                        </div>
                                    </div>
                                </div>

                                <form class="document-assistant__composer" id="document-assistant-form">
                                    @csrf
                                    <input type="hidden" id="document-assistant-mode" value="ask">
                                    <input type="hidden" id="document-assistant-selected-region" value="">
                                    <textarea id="document-assistant-input" placeholder="{{ __('Type your question here...') }}"></textarea>
                                    <div class="document-assistant__composer-row">
                                        <button type="submit" class="document-assistant__send" id="document-assistant-send">
                                            <i class="fas fa-paper-plane" aria-hidden="true"></i>
                                            {{ __('Ask AI') }}
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" id="document-assistant-clear">
                                            {{ __('Clear') }}
                                        </button>
                                    </div>
                                </form>

                                <p class="document-assistant__note">
                                    {{ __('AI can make mistakes. Verify important information before you submit or revise your work.') }}
                                </p>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    @if ($readerMode === 'pdf')
        <script src="{{ asset('frontend/js/pdf.min.js') }}"></script>
    @endif

    <script>
        document.addEventListener('contextmenu', function (event) {
            const readerFrame = event.target.closest('.document-reader-frame');
            if (readerFrame) {
                event.preventDefault();
            }
        });

        document.addEventListener('keydown', function (event) {
            const key = event.key.toLowerCase();
            if ((event.ctrlKey || event.metaKey) && (key === 's' || key === 'p')) {
                event.preventDefault();
            }
        });

        (() => {
            const assistantShell = document.getElementById('document-assistant-shell');
            const assistantChat = document.getElementById('document-assistant-chat');
            const assistantForm = document.getElementById('document-assistant-form');
            const assistantInput = document.getElementById('document-assistant-input');
            const assistantSend = document.getElementById('document-assistant-send');
            const assistantClear = document.getElementById('document-assistant-clear');
            const assistantModeInput = document.getElementById('document-assistant-mode');
            const assistantContextTitle = document.getElementById('document-assistant-context-title');
            const assistantContextMeta = document.getElementById('document-assistant-context-meta');
            const assistantSelectedRegionInput = document.getElementById('document-assistant-selected-region');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const endpoint = @json(route('product.document-assistant-stream', $product->slug));
            const isPdfReader = @json($readerMode === 'pdf');
            const questionRegions = @json(collect($questionRegions ?? [])->values());
            const state = {
                mode: 'ask',
                history: [],
                selectedRegion: null,
                busy: false,
            };

            const modeLabels = {
                explain: @json(__('Explain')),
                solve: @json(__('Solve')),
                hint: @json(__('Hint')),
                summary: @json(__('Summary')),
                related: @json(__('Related Notes')),
                ask: @json(__('Ask AI')),
            };

            const escapeHtml = (value) => String(value || '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
            }[char]));

            const copyToClipboard = async (text) => {
                if (!text) return false;
                try {
                    await navigator.clipboard.writeText(text);
                    return true;
                } catch (error) {
                    return false;
                }
            };

            const renderAssistantMarkdown = (value) => {
                const source = String(value || '').replace(/\r\n?/g, '\n');
                const escapeCode = (text) => escapeHtml(text);
                const formatInline = (text) => {
                    const escaped = escapeHtml(text);
                    return escaped
                        .replace(/`([^`]+)`/g, '<code>$1</code>')
                        .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
                        .replace(/__([^_]+)__/g, '<strong>$1</strong>')
                        .replace(/\*([^*\n]+)\*/g, '<em>$1</em>')
                        .replace(/_([^_\n]+)_/g, '<em>$1</em>')
                        .replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>');
                };

                const lines = source.split('\n');
                const output = [];
                let paragraph = [];
                let listType = null;
                let listItems = [];
                let codeLines = [];
                let inCodeBlock = false;
                let codeLanguage = '';
                let tableLines = [];

                const flushParagraph = () => {
                    if (!paragraph.length) return;
                    const text = paragraph.join(' ').trim();
                    if (text.startsWith('$$') && text.endsWith('$$') && text.length > 4) {
                        output.push(`
                            <div class="document-assistant__equation" data-copy-text="${escapeHtml(text)}">
                                <button type="button" class="document-assistant__copy-btn" data-copy-text="${escapeHtml(text)}">${@json(__('Copy'))}</button>
                                <div class="document-assistant__equation-content">${escapeHtml(text)}</div>
                            </div>
                        `);
                    } else {
                        output.push(`<p>${formatInline(text)}</p>`);
                    }
                    paragraph = [];
                };

                const flushList = () => {
                    if (!listItems.length || !listType) return;
                    output.push(`<${listType}>${listItems.map((item) => `<li>${formatInline(item)}</li>`).join('')}</${listType}>`);
                    listItems = [];
                    listType = null;
                };

                const flushCode = () => {
                    if (!codeLines.length) return;
                    const code = codeLines.join('\n');
                    const languageClass = codeLanguage ? ` class="language-${escapeHtml(codeLanguage)}"` : '';
                    output.push(`
                        <div class="document-assistant__code-block">
                            <button type="button" class="document-assistant__copy-btn" data-copy-text="${escapeHtml(code)}">${@json(__('Copy'))}</button>
                            <pre><code${languageClass}>${escapeCode(code)}</code></pre>
                        </div>
                    `);
                    codeLines = [];
                    codeLanguage = '';
                };

                const isTableSeparator = (line) => /^\s*\|?(?:\s*:?-{3,}:?\s*\|)+\s*:?-{3,}:?\s*\|?\s*$/.test(line);
                const isTableRow = (line) => /^\s*\|.*\|\s*$/.test(line);

                const flushTable = () => {
                    if (tableLines.length < 2) {
                        tableLines = [];
                        return;
                    }

                    const rows = tableLines.map((row) => row.trim().replace(/^\|/, '').replace(/\|$/, '').split('|').map((cell) => cell.trim()));
                    const headers = rows[0];
                    const bodyRows = rows.slice(2);
                    output.push(`
                        <div class="document-assistant__table-wrap">
                            <table>
                                <thead><tr>${headers.map((cell) => `<th>${formatInline(cell)}</th>`).join('')}</tr></thead>
                                <tbody>
                                    ${bodyRows.map((row) => `<tr>${row.map((cell) => `<td>${formatInline(cell)}</td>`).join('')}</tr>`).join('')}
                                </tbody>
                            </table>
                        </div>
                    `);
                    tableLines = [];
                };

                lines.forEach((line) => {
                    const trimmed = line.trimEnd();

                    if (trimmed.startsWith('```')) {
                        if (inCodeBlock) {
                            inCodeBlock = false;
                            flushCode();
                        } else {
                            flushParagraph();
                            flushList();
                            flushTable();
                            inCodeBlock = true;
                            codeLanguage = trimmed.slice(3).trim();
                        }
                        return;
                    }

                    if (inCodeBlock) {
                        codeLines.push(line);
                        return;
                    }

                    if (trimmed === '') {
                        flushParagraph();
                        flushList();
                        flushTable();
                        return;
                    }

                    const unordered = trimmed.match(/^[-*+]\s+(.*)$/);
                    const ordered = trimmed.match(/^\d+\.\s+(.*)$/);
                    const tableCandidate = isTableRow(trimmed) || isTableSeparator(trimmed);

                    if (unordered || ordered) {
                        flushParagraph();
                        flushTable();
                        const type = unordered ? 'ul' : 'ol';
                        if (listType && listType !== type) {
                            flushList();
                        }
                        listType = type;
                        listItems.push((unordered || ordered)[1]);
                        return;
                    }

                    if (tableCandidate) {
                        flushParagraph();
                        flushList();
                        tableLines.push(trimmed);
                        return;
                    }

                    if (tableLines.length) {
                        flushTable();
                    }

                    flushList();
                    paragraph.push(trimmed);
                });

                if (inCodeBlock) {
                    flushCode();
                }

                flushTable();
                flushParagraph();
                flushList();

                return output.join('').replace(/(?:\r?\n){2,}/g, '\n');
            };

            const loadMathJax = (() => {
                let promise = null;

                return () => {
                    if (window.MathJax?.typesetPromise) {
                        return Promise.resolve(window.MathJax);
                    }

                    if (!promise) {
                        window.MathJax = window.MathJax || {
                            tex: {
                                inlineMath: [['\\(', '\\)'], ['$', '$']],
                                displayMath: [['\\[', '\\]'], ['$$', '$$']],
                                processEscapes: true,
                            },
                            options: {
                                skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code'],
                            },
                        };

                        promise = new Promise((resolve, reject) => {
                            const script = document.createElement('script');
                            script.src = 'https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js';
                            script.async = true;
                            script.onload = () => resolve(window.MathJax);
                            script.onerror = () => reject(new Error('Math rendering failed to load.'));
                            document.head.appendChild(script);
                        });
                    }

                    return promise;
                };
            })();

            const renderAssistantContent = async (element, value) => {
                if (!element) return;
                element.innerHTML = renderAssistantMarkdown(value);

                try {
                    const mathJax = await loadMathJax();
                    if (mathJax?.typesetPromise) {
                        await mathJax.typesetPromise([element]);
                    }
                } catch (error) {
                    // If MathJax fails to load, keep the markdown rendering.
                }
            };

            assistantChat?.addEventListener('click', async (event) => {
                const button = event.target.closest('[data-copy-text]');
                if (!button) return;

                const copied = await copyToClipboard(button.dataset.copyText || '');
                if (window.revisionHubToast) {
                    window.revisionHubToast(copied ? 'success' : 'error', copied ? @json(__('Copied to clipboard.')) : @json(__('Unable to copy.')));
                }
            });

            const buildRegionLabel = (region) => {
                if (!region) {
                    return '';
                }

                const parts = [];
                if (region.question_label) parts.push(region.question_label);
                if (region.question_number) parts.push(`Question ${region.question_number}`);
                if (region.page_number) parts.push(`Page ${region.page_number}`);
                return parts.join(' | ');
            };

            const extractQuestionNumber = (prompt) => {
                const match = String(prompt || '').match(/\b(?:question|q|solve)\s*#?\s*(\d{1,3})\b/i);
                if (match) {
                    return Number(match[1]);
                }

                const bareMatch = String(prompt || '').match(/\b(\d{1,3})\b/);
                if (bareMatch && /question|q|solve/i.test(String(prompt || ''))) {
                    return Number(bareMatch[1]);
                }

                return null;
            };

            const findQuestionRegionForPrompt = (prompt) => {
                if (!Array.isArray(questionRegions) || !questionRegions.length) {
                    return null;
                }

                const questionNumber = extractQuestionNumber(prompt);
                if (questionNumber === null) {
                    return null;
                }

                return questionRegions.find((region) => Number(region.question_number) === questionNumber) || null;
            };

            const setContext = (title, meta, isEmpty = false) => {
                if (assistantContextTitle) assistantContextTitle.textContent = title || '';
                if (assistantContextMeta) assistantContextMeta.textContent = meta || '';
                assistantShell?.querySelector('.document-assistant__context')?.classList.toggle('is-empty', Boolean(isEmpty));
            };

            const setMode = (mode) => {
                state.mode = mode;
                assistantModeInput.value = mode;

                document.querySelectorAll('[data-assistant-mode]').forEach((tab) => {
                    tab.classList.toggle('is-active', tab.getAttribute('data-assistant-mode') === mode);
                });

                const modeLabel = modeLabels[mode] || modeLabels.ask;
                if (assistantInput && !assistantInput.value.trim()) {
                    assistantInput.placeholder = mode === 'hint'
                        ? @json(__('Ask for a hint about the current document...'))
                        : mode === 'solve'
                            ? @json(__('Ask the assistant to solve or break down the problem...'))
                            : mode === 'summary'
                                ? @json(__('Ask for a concise summary...'))
                                : @json(__('Type your question here...'));
                }

                if (assistantContextTitle && !state.selectedRegion) {
                    assistantContextTitle.textContent = modeLabel;
                }
            };

            const appendMessage = (role, content, options = {}) => {
                if (!assistantChat) return null;

                const message = document.createElement('div');
                message.className = `document-assistant__message ${role === 'user' ? 'is-user' : 'is-assistant'}`;
                if (options.loading) message.dataset.loading = '1';

                const avatar = document.createElement('div');
                avatar.className = 'document-assistant__avatar';
                avatar.innerHTML = role === 'user'
                    ? '<i class="fas fa-user"></i>'
                    : '<i class="fas fa-sparkles"></i>';

                const bubble = document.createElement('div');
                bubble.className = `document-assistant__bubble ${options.loading ? 'is-loading' : ''}`;
                bubble.innerHTML = escapeHtml(content || '');

                message.appendChild(avatar);
                message.appendChild(bubble);
                assistantChat.appendChild(message);
                assistantChat.scrollTop = assistantChat.scrollHeight;
                return { message, bubble };
            };

            const parseSseBlock = (block) => {
                const event = { type: 'message', data: '' };
                block.split('\n').forEach((line) => {
                    if (line.startsWith('event:')) {
                        event.type = line.slice(6).trim();
                        return;
                    }
                    if (line.startsWith('data:')) {
                        event.data += (event.data ? '\n' : '') + line.slice(5).trimStart();
                    }
                });
                return event;
            };

            const readStream = async (response, assistantBubble) => {
                if (!response.body || !window.TextDecoder) {
                    const text = await response.text();
                    await renderAssistantContent(assistantBubble, text || @json(__('No answer returned.')));
                    return;
                }

                const decoder = new TextDecoder();
                const reader = response.body.getReader();
                let buffer = '';
                let answer = '';

                while (true) {
                    const { value, done } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });

                    let boundaryIndex = buffer.indexOf('\n\n');
                    while (boundaryIndex !== -1) {
                        const block = buffer.slice(0, boundaryIndex).trim();
                        buffer = buffer.slice(boundaryIndex + 2);

                        if (block !== '') {
                            const event = parseSseBlock(block);
                            let payload = event.data;

                            try {
                                payload = payload ? JSON.parse(payload) : {};
                            } catch (error) {
                                payload = { raw: payload };
                            }

                            if (event.type === 'meta') {
                                if (payload?.provider && assistantContextMeta) {
                                    assistantContextMeta.textContent = `${payload.provider}${payload.model ? ` - ${payload.model}` : ''}`;
                                }
                            } else if (event.type === 'token') {
                                const token = typeof payload === 'string' ? payload : (payload.raw || '');
                                answer += token;
                                assistantBubble.textContent = answer;
                                assistantChat.scrollTop = assistantChat.scrollHeight;
                            } else if (event.type === 'done') {
                                const finalContent = payload.content || answer || '';
                                await renderAssistantContent(assistantBubble, finalContent || @json(__('No answer returned.')));
                                answer = finalContent;
                            } else if (event.type === 'error') {
                                throw new Error(prefixNetworkErrorMessage(payload.message || @json(__('Unable to generate an answer.'))));
                            }
                        }

                        boundaryIndex = buffer.indexOf('\n\n');
                    }
                }

                const trailing = buffer.trim();
                if (trailing !== '') {
                    const event = parseSseBlock(trailing);
                    if (event.type === 'done') {
                        let payload = event.data;
                        try {
                            payload = payload ? JSON.parse(payload) : {};
                        } catch (error) {
                            payload = { raw: payload };
                        }
                        await renderAssistantContent(assistantBubble, payload.content || answer || @json(__('No answer returned.')));
                    }
                }
            };

            const sendPrompt = async (prompt) => {
                const value = String(prompt || '').trim();
                if (!value || state.busy) {
                    return;
                }

                if (!state.selectedRegion) {
                    const autoRegion = findQuestionRegionForPrompt(value);
                    if (autoRegion) {
                        state.selectedRegion = autoRegion;
                        if (assistantSelectedRegionInput) {
                            assistantSelectedRegionInput.value = JSON.stringify(state.selectedRegion || {});
                        }

                        const label = buildRegionLabel(state.selectedRegion);
                        setContext(
                            label || @json(__('Selected question')),
                            state.selectedRegion?.content
                                ? state.selectedRegion.content.substring(0, 220)
                                : @json(__('The assistant will use the selected question region as extra context.')),
                            !label
                        );
                    }
                }

                state.busy = true;
                assistantSend.disabled = true;
                assistantInput.disabled = true;

                appendMessage('user', value);
                const assistantEntry = appendMessage('assistant', @json(__('Thinking...')), { loading: true });

                const history = state.history.slice(-8);
                const body = {
                    prompt: value,
                    mode: state.mode,
                    history,
                    selected_region: state.selectedRegion,
                };

                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Accept': 'text/event-stream',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(body),
                    });

                    if (!response.ok) {
                        let message = @json(__('Unable to generate an answer.'));
                        try {
                            const data = await response.json();
                            message = prefixNetworkErrorMessage(data.message || message);
                        } catch (error) {
                            message = prefixNetworkErrorMessage(message);
                        }
                        throw new Error(message);
                    }

                    assistantEntry.bubble.classList.remove('is-loading');
                    await readStream(response, assistantEntry.bubble);

                    state.history.push(
                        { role: 'user', content: value },
                        { role: 'assistant', content: assistantEntry.bubble.textContent || '' }
                    );
                } catch (error) {
                    assistantEntry.bubble.classList.remove('is-loading');
                    assistantEntry.bubble.textContent = prefixNetworkErrorMessage(error?.message || @json(__('Something went wrong.')));
                } finally {
                    state.busy = false;
                    assistantSend.disabled = false;
                    assistantInput.disabled = false;
                    assistantInput.value = '';
                }
            };

            assistantForm?.addEventListener('submit', function (event) {
                event.preventDefault();
                sendPrompt(assistantInput?.value || '');
            });

            assistantClear?.addEventListener('click', function () {
                if (assistantInput) {
                    assistantInput.value = '';
                    assistantInput.focus();
                }
            });

            document.querySelectorAll('[data-assistant-mode]').forEach((tab) => {
                tab.addEventListener('click', function () {
                    setMode(tab.getAttribute('data-assistant-mode') || 'ask');
                });
            });

            document.querySelectorAll('[data-assistant-prompt]').forEach((button) => {
                button.addEventListener('click', function () {
                    const prompt = button.getAttribute('data-assistant-prompt') || '';
                    if (assistantInput) {
                        assistantInput.value = prompt;
                        assistantInput.focus();
                    }
                });
            });

            if (isPdfReader) {
                window.addEventListener('pdf-question-selected', function (event) {
                    state.selectedRegion = event.detail || null;
                    if (assistantSelectedRegionInput) {
                        assistantSelectedRegionInput.value = JSON.stringify(state.selectedRegion || {});
                    }

                    const label = buildRegionLabel(state.selectedRegion);
                    setContext(
                        label || @json(__('Selected question')),
                        state.selectedRegion?.content
                            ? state.selectedRegion.content.substring(0, 220)
                            : @json(__('The assistant will use the selected question region as extra context.')),
                        !label
                    );
                });
            }

            setMode('ask');
            if (assistantSelectedRegionInput) {
                assistantSelectedRegionInput.value = '';
            }
            setContext(
                isPdfReader ? @json(__('Tap a question box in the PDF')) : @json(__('Current document')),
                isPdfReader
                    ? @json(__('When you select a question region, the assistant will use it as extra context for the conversation.'))
                    : @json(Str::limit($readerSummary['excerpt'] ?? $readerText ?? '', 170) ?: __('The assistant will answer using the document content currently loaded in the reader.')),
                false
            );
        })();
    </script>
@endpush
