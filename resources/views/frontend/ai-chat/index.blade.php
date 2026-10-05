@extends('frontend.layouts.master')

@section('meta_title', __('AI Chat'))
@section('body_class', 'revision-ai-chat-page')

@php
    $chatRouteName = request()->routeIs('ai-chat') ? 'ai-chat' : 'student.ai-chat.index';
    $chatBaseUrl = route($chatRouteName);
    $isEmbedded = request()->boolean('embedded');
    $returnToUrl = $returnToUrl ?? (request()->query('return_to') ?: null);
    $chatQuery = $returnToUrl ? ['return_to' => $returnToUrl] : [];
    $chatUrlForConversation = function ($conversationId = null) use ($chatRouteName, $chatQuery) {
        $params = $chatQuery;

        if ($conversationId) {
            $params['conversation'] = $conversationId;
        }

        return route($chatRouteName, $params);
    };
    $aiCreditsUrl = route('ai-chat.credits');
    $currentUser = auth()->user();
    $currentUserName = $currentUser?->name ?: __('Student');
    $currentUserInitial = strtoupper(mb_substr($currentUserName, 0, 1));
    $activeConversationTitle = $activeConversation?->title ?: __('New chat');
    $todayConversations = $conversations->filter(function ($conversation) {
        $date = $conversation->last_message_at ?? $conversation->created_at;
        return $date?->isToday();
    });
    $recentConversations = $conversations->filter(function ($conversation) {
        $date = $conversation->last_message_at ?? $conversation->created_at;
        return $date?->isAfter(now()->subDays(30)) && ! $date?->isToday();
    });
    $olderConversations = $conversations->reject(function ($conversation) {
        $date = $conversation->last_message_at ?? $conversation->created_at;
        return $date?->isAfter(now()->subDays(30));
    });
@endphp

@section('contents')
    <section class="ai-chat-page {{ $isEmbedded ? 'ai-chat-page--embedded' : '' }}">
        <div class="ai-chat-page__backdrop" id="ai-chat-dialog-backdrop" aria-hidden="true"></div>
        <div class="container ai-chat-page__dialog" role="dialog" aria-modal="true" aria-labelledby="ai-chat-dialog-title">
            <div class="ai-chat-page__toolbar">
                <button type="button" class="ai-chat-page__close ai-chat-page__close--text" id="ai-chat-close-dialog" aria-label="{{ __('Cancel') }}">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    <span>{{ __('Cancel') }}</span>
                </button>
            </div>
            <h2 id="ai-chat-dialog-title" class="ai-chat-visually-hidden">{{ __('AI Chat') }}</h2>
            <div class="ai-chat-shell ai-chat-shell--chatgpt">
                @include('frontend.ai-chat.partials.sidebar')
                @include('frontend.ai-chat.partials.main')
            </div>
            <div class="ai-chat-sidebar-backdrop" id="ai-chat-sidebar-backdrop" aria-hidden="true"></div>
        </div>
    </section>
@endsection

@push('styles')
    <style>
        body.revision-ai-chat-page {
            background:
                radial-gradient(circle at top left, rgba(110, 79, 255, 0.06), transparent 30%),
                radial-gradient(circle at top right, rgba(84, 43, 214, 0.05), transparent 24%),
                #f6f7fb;
            /* allow page scrolling so UI elements (dropdowns, tooltips) are not clipped */
            overflow: auto;
        }

        body.revision-ai-chat-page .main-area.fix {
            height: var(--ai-chat-viewport-height, calc(100dvh - 140px));
            padding-top: 0;
            background: transparent;
            /* allow inner scrolling where appropriate */
            overflow: auto;
        }

        .ai-chat-page {
            height: 100%;
            padding: 0;
        }

        .ai-chat-page > .container {
            height: 100%;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .ai-chat-main__mobile-bar {
            display: none;
        }

        .ai-chat-mobile-drawer-toggle {
            display: inline-flex;
            align-items: stretch;
            gap: 8px;
            padding: 10px 14px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.96);
            color: #111827;
            font-weight: 700;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.06);
        }

        .ai-chat-sidebar-backdrop {
            display: none;
        }

        .ai-chat-sidebar {
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 24px;
            padding: 0 18px 18px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
            backdrop-filter: blur(10px);
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(107, 78, 252, 0.28) transparent;
        }

        .ai-chat-sidebar::-webkit-scrollbar,
        .ai-chat-thread::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .ai-chat-sidebar::-webkit-scrollbar-track,
        .ai-chat-thread::-webkit-scrollbar-track {
            background: transparent;
        }

        .ai-chat-sidebar::-webkit-scrollbar-thumb,
        .ai-chat-thread::-webkit-scrollbar-thumb {
            background: rgba(107, 78, 252, 0.24);
            border-radius: 999px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        .ai-chat-sidebar::-webkit-scrollbar-thumb:hover,
        .ai-chat-thread::-webkit-scrollbar-thumb:hover {
            background: rgba(107, 78, 252, 0.38);
            background-clip: padding-box;
        }

        .ai-chat-page__intro {
            display: none;
        }

        .ai-chat-page__intro h1 {
            margin: 0 0 8px;
            font-size: clamp(30px, 3vw, 42px);
            font-weight: 800;
            color: #111827;
        }

        .ai-chat-page__intro p {
            margin: 0;
            color: #6b7280;
            max-width: 720px;
        }

        .ai-chat-sidebar__header,
        .ai-chat-sidebar__credits-top,
        .ai-chat-main__header,
        .ai-chat-composer__footer,
        .ai-chat-message__meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .ai-chat-kicker {
            margin: 0 0 4px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #6b4efc;
            font-weight: 700;
        }

        .ai-chat-sidebar__header h6,
        .ai-chat-main__header h4 {
            margin: 0;
            font-weight: 700;
            color: #111827;
        }

        .ai-chat-new-btn,
        .ai-chat-mini-btn,
        .ai-chat-send-btn,
        .ai-chat-icon-btn,
        .ai-chat-model-switch button {
            border: 0;
            border-radius: 14px;
            transition: all .18s ease;
        }

        .ai-chat-new-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 14px;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            color: #111827;
            font-weight: 600;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.04);
        }

        .ai-chat-new-btn:hover,
        .ai-chat-send-btn:hover,
        .ai-chat-mini-btn:hover,
        .ai-chat-model-switch button:hover {
            transform: translateY(-1px);
        }

        .ai-chat-sidebar__search {
            margin: 14px 0 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 12px;
            border-radius: 14px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: rgba(248, 250, 252, 0.92);
            overflow: hidden;
            max-height: 72px;
            transition: max-height 0.22s ease, opacity 0.22s ease, margin 0.22s ease, padding 0.22s ease, transform 0.22s ease;
        }

        .ai-chat-sidebar__search--collapsed {
            max-height: 0;
            margin: 0;
            padding-top: 0;
            padding-bottom: 0;
            border-width: 0;
            opacity: 0;
            transform: translateY(-6px);
            pointer-events: none;
        }

        .ai-chat-sidebar__search.is-open {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .ai-chat-sidebar__search i {
            color: #94a3b8;
        }

        .ai-chat-sidebar__search input,
        .ai-chat-composer textarea {
            width: 100%;
            border: 0;
            background: transparent;
            outline: none;
            color: #111827;
        }

        .ai-chat-sidebar__search input::placeholder,
        .ai-chat-composer textarea::placeholder {
            color: #9ca3af;
        }

        .ai-chat-sidebar__list {
            display: grid;
            gap: 8px;
            min-height: 0;
            max-height: none;
            overflow: visible;
            padding-right: 4px;
        }

        .ai-chat-history-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 12px;
            border-radius: 16px;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.06);
            color: #111827;
        }

        .ai-chat-history-item.is-active {
            background:
                linear-gradient(90deg, rgba(49, 94, 251, 0.18), rgba(49, 94, 251, 0.08)),
                linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(243, 246, 255, 0.98));
            border-color: rgba(49, 94, 251, 0.30);
            box-shadow:
                inset 3px 0 0 rgba(49, 94, 251, 0.92),
                0 10px 18px rgba(49, 94, 251, 0.10);
        }

        .ai-chat-history-item__body {
            display: grid;
            gap: 3px;
            min-width: 0;
        }

        .ai-chat-history-item__body strong,
        .ai-chat-history-item__body span,
        .ai-chat-sidebar__empty p,
        .ai-chat-sidebar__credits small {
            margin: 0;
            color: #6b7280;
        }

        .ai-chat-history-item__body strong {
            display: inline-flex;
            align-items: center;
            min-width: 0;
            gap: 0;
            color: #111827;
            transition: color 0.18s ease;
        }

        .ai-chat-history-item.is-active .ai-chat-history-item__body strong {
            color: #315efb;
        }

        .ai-chat-history-item.is-active .ai-chat-history-item__body strong::before {
            content: '';
            display: inline-block;
            width: 7px;
            height: 7px;
            margin-right: 8px;
            border-radius: 999px;
            background: #315efb;
            box-shadow: 0 0 0 3px rgba(49, 94, 251, 0.12);
            flex: 0 0 auto;
        }

        .ai-chat-history-item__active-pill {
            display: none;
            align-self: center;
            padding: 4px 8px;
            border-radius: 999px;
            background: rgba(49, 94, 251, 0.12);
            color: #315efb;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .ai-chat-history-item.is-active .ai-chat-history-item__active-pill {
            display: inline-flex;
        }

        .ai-chat-history-item.is-active .ai-chat-history-item__action {
            color: #315efb;
            opacity: 0.8;
        }

        .ai-chat-sidebar__empty,
        .ai-chat-empty {
            text-align: center;
            padding: 20px 16px;
        }

        .ai-chat-empty {
            max-width: 520px;
        }

        .ai-chat-empty h5 {
            margin: 10px 0 8px;
            font-size: clamp(22px, 2vw, 30px);
            font-weight: 700;
            color: #111827;
        }

        .ai-chat-empty p {
            margin: 0 auto;
            max-width: 460px;
            color: #6b7280;
        }

        .ai-chat-sidebar__empty-icon,
        .ai-chat-empty__icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 12px;
            display: grid;
            place-items: center;
            border-radius: 20px;
            background: linear-gradient(135deg, rgba(107, 78, 252, 0.18), rgba(168, 85, 247, 0.14));
            color: #6b4efc;
        }

        .ai-chat-sidebar__footer {
            display: grid;
            gap: 12px;
            margin-top: auto;
            padding-top: 14px;
        }

        .ai-chat-sidebar__credits {
            padding: 14px;
            border-radius: 16px;
            border: 1px solid rgba(15, 23, 42, 0.06);
            background: #fff;
        }

        .ai-chat-credit-bar {
            width: 100%;
            height: 6px;
            margin: 10px 0 8px;
            border-radius: 999px;
            background: rgba(107, 78, 252, 0.1);
            overflow: hidden;
        }

        .ai-chat-credit-bar span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #6b4efc, #8b5cf6);
        }

        .ai-chat-shell {
            display: grid;
            grid-template-columns: 292px minmax(0, 1fr);
            gap: 16px;
            align-items: start;
            flex: 1 1 auto;
            min-height: 0;
        }

        .ai-chat-sidebar {
            align-self: start;
        }

        .ai-chat-main {
            border-radius: 28px;
            border: 1px solid rgba(15, 23, 42, 0.06);
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.04);
            backdrop-filter: blur(12px);
            min-height: 0;
            display: flex;
            flex-direction: column;
            padding: 22px;
            overflow: hidden;
        }

        .ai-chat-main__header {
            display: none;
        }

        .ai-chat-main__status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 12px;
            border-radius: 999px;
            background: rgba(16, 185, 129, 0.09);
            color: #047857;
            font-weight: 600;
            white-space: nowrap;
        }

        .ai-chat-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #10b981;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.55);
            animation: aiPulse 1.8s infinite;
        }

        .ai-chat-thread {
            display: grid;
            gap: 14px;
            flex: 1 1 auto;
            min-height: 0;
            max-height: none;
            overflow: auto;
            padding: 6px 2px 12px;
            scrollbar-width: thin;
            scrollbar-color: rgba(107, 78, 252, 0.28) transparent;
        }

        .ai-chat-thread[data-empty="1"] {
            min-height: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ai-chat-message {
            display: flex;
            align-items: flex-end;
            gap: 10px;
        }

        .ai-chat-message.is-user {
            justify-content: flex-end;
        }

        .ai-chat-message__avatar {
            width: 40px;
            height: 40px;
            flex: 0 0 40px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, rgba(107, 78, 252, 0.18), rgba(168, 85, 247, 0.16));
            color: #6b4efc;
        }

        .ai-chat-message__avatar--user {
            background: linear-gradient(135deg, #6b4efc, #8b5cf6);
            color: #fff;
        }

        .ai-chat-message__bubble {
            max-width: min(760px, 86%);
            border-radius: 24px;
            padding: 16px 18px;
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid rgba(148, 163, 184, 0.14);
            box-shadow: 0 14px 30px rgba(17, 24, 39, 0.05);
        }

        .ai-chat-message.is-user .ai-chat-message__bubble {
            background: linear-gradient(135deg, rgba(107, 78, 252, 0.18), rgba(168, 85, 247, 0.14));
            border-color: rgba(107, 78, 252, 0.18);
        }

        .ai-chat-message__content {
            color: #111827;
            line-height: 1.65;
            white-space: pre-wrap;
        }

        .ai-chat-message__content > *:first-child,
        .ai-chat-message__content > .ai-chat-block:first-child,
        .ai-chat-message__content > .ai-chat-equation-block:first-child,
        .ai-chat-message__content > .ai-chat-table-wrap:first-child {
            margin-top: 0;
        }

        .ai-chat-message__content > *:last-child,
        .ai-chat-message__content > .ai-chat-block:last-child,
        .ai-chat-message__content > .ai-chat-equation-block:last-child,
        .ai-chat-message__content > .ai-chat-table-wrap:last-child {
            margin-bottom: 0;
        }

        .ai-chat-message__content p,
        .ai-chat-message__content ul,
        .ai-chat-message__content ol,
        .ai-chat-message__content blockquote,
        .ai-chat-message__content pre,
        .ai-chat-message__content table,
        .ai-chat-message__content .ai-chat-equation-block {
            margin: 0.85rem 0;
        }

        .ai-chat-message__content p {
            white-space: normal;
        }

        .ai-chat-message__content ul,
        .ai-chat-message__content ol {
            padding-left: 1.35rem;
        }

        .ai-chat-message__content li + li {
            margin-top: 0.35rem;
        }

        .ai-chat-message__content blockquote {
            padding: 0.85rem 1rem;
            border-left: 3px solid rgba(107, 78, 252, 0.34);
            background: rgba(107, 78, 252, 0.05);
            border-radius: 14px;
            color: #374151;
        }

        .ai-chat-message__content code {
            padding: 0.15rem 0.38rem;
            border-radius: 8px;
            background: rgba(15, 23, 42, 0.06);
            font-size: 0.95em;
        }

        .ai-chat-message__content pre {
            position: relative;
            overflow: auto;
            padding: 1rem 1rem 0.95rem;
            border-radius: 18px;
            background: #0f172a;
            color: #e2e8f0;
            border: 1px solid rgba(148, 163, 184, 0.18);
            white-space: pre;
        }

        .ai-chat-message__content pre code {
            display: block;
            padding: 0;
            background: transparent;
            color: inherit;
            font-size: 0.92rem;
            line-height: 1.7;
        }

        .ai-chat-table-wrap {
            overflow-x: auto;
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4);
        }

        .ai-chat-message__content table {
            width: 100%;
            border-collapse: collapse;
            min-width: 360px;
            margin: 0;
            background: #fff;
        }

        .ai-chat-message__content th,
        .ai-chat-message__content td {
            padding: 0.8rem 0.9rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.14);
            text-align: left;
            vertical-align: top;
        }

        .ai-chat-message__content thead th {
            background: rgba(107, 78, 252, 0.06);
            font-weight: 700;
        }

        .ai-chat-message__content tbody tr:last-child td {
            border-bottom: 0;
        }

        .ai-chat-equation-block {
            position: relative;
            padding: 0.9rem 0.95rem 0.8rem;
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(248, 250, 252, 0.96), rgba(243, 244, 246, 0.94));
            border: 1px solid rgba(148, 163, 184, 0.16);
        }

        .ai-chat-equation-block .MathJax,
        .ai-chat-equation-block mjx-container {
            display: block;
            margin: 0.2rem 0 0;
            overflow-x: auto;
            overflow-y: hidden;
        }

        .ai-chat-copy-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1;
            min-height: 30px;
            padding: 0 10px;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.24);
            background: rgba(255, 255, 255, 0.9);
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            transition: transform 0.15s ease, background 0.15s ease, color 0.15s ease;
        }

        .ai-chat-copy-btn:hover {
            transform: translateY(-1px);
            background: #fff;
            color: #111827;
        }

        .ai-chat-block,
        .ai-chat-equation-block,
        .ai-chat-table-wrap {
            position: relative;
        }

        .ai-chat-image {
            width: 100%;
            border-radius: 18px;
            display: block;
            object-fit: cover;
            box-shadow: 0 14px 30px rgba(17, 24, 39, 0.1);
        }

        .ai-chat-image__caption {
            margin-top: 10px;
            font-size: 13px;
            color: #6b7280;
        }

        .ai-chat-message__meta {
            margin-top: 10px;
            font-size: 12px;
            color: #9ca3af;
        }

        .ai-chat-message.is-assistant .ai-chat-message__bubble {
            position: relative;
        }

        .ai-chat-message.is-streaming .ai-chat-message__bubble {
            border-color: rgba(107, 78, 252, 0.26);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(245, 243, 255, 0.94));
        }

        .ai-chat-cursor {
            display: inline-block;
            width: 8px;
            height: 18px;
            margin-left: 2px;
            border-radius: 999px;
            background: #6b4efc;
            animation: aiBlink 1s steps(2, start) infinite;
            vertical-align: text-bottom;
        }

        .ai-chat-skeleton {
            height: 14px;
            border-radius: 999px;
            background: linear-gradient(90deg, rgba(229, 231, 235, 0.9), rgba(243, 244, 246, 0.95), rgba(229, 231, 235, 0.9));
            background-size: 200% 100%;
            animation: aiShimmer 1.4s linear infinite;
            margin-bottom: 10px;
        }

        .ai-chat-skeleton:last-child {
            margin-bottom: 0;
            width: 70%;
        }

        .ai-chat-composer {
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid rgba(15, 23, 42, 0.06);
            flex: 0 0 auto;
        }

        .ai-chat-composer__row {
            display: grid;
            grid-template-columns: 46px 46px minmax(0, 1fr) 56px;
            gap: 10px;
            align-items: end;
            padding: 10px;
            border-radius: 24px;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
        }

        .ai-chat-icon-btn,
        .ai-chat-send-btn {
            height: 46px;
            width: 46px;
            display: grid;
            place-items: center;
        }

        .ai-chat-icon-btn {
            background: rgba(107, 78, 252, 0.08);
            color: #6b4efc;
        }

        .ai-chat-send-btn {
            background: linear-gradient(135deg, #6b4efc, #8b5cf6);
            color: #fff;
            box-shadow: 0 14px 30px rgba(107, 78, 252, 0.24);
        }

        .ai-chat-composer textarea {
            min-height: 46px;
            max-height: 180px;
            resize: none;
            padding: 12px 4px 0;
            line-height: 1.6;
        }

        .ai-chat-composer__footer {
            margin-top: 10px;
            flex-wrap: wrap;
            color: #6b7280;
            font-size: 13px;
        }

        .ai-chat-mini-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.14);
            color: #374151;
        }

        .ai-chat-image-tools {
            display: grid;
            gap: 12px;
            margin-top: 14px;
            padding: 14px;
            border-radius: 20px;
            background: linear-gradient(180deg, rgba(107, 78, 252, 0.07), rgba(255, 255, 255, 0.94));
            border: 1px solid rgba(107, 78, 252, 0.12);
        }

        .ai-chat-image-tools__presets {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .ai-chat-preset-btn {
            border: 1px solid rgba(107, 78, 252, 0.12);
            background: rgba(255, 255, 255, 0.92);
            color: #5b21b6;
            padding: 9px 12px;
            border-radius: 999px;
            font-weight: 600;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .ai-chat-preset-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(107, 78, 252, 0.12);
        }

        .ai-chat-image-tools__selectors {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .ai-chat-image-field {
            display: grid;
            gap: 6px;
        }

        .ai-chat-image-field span {
            font-size: 12px;
            color: #6b7280;
            font-weight: 600;
        }

        .ai-chat-image-field select {
            width: 100%;
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: #fff;
            color: #111827;
            border-radius: 14px;
            padding: 10px 12px;
            outline: none;
        }

        .ai-chat-media-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .ai-chat-media-action {
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: rgba(255, 255, 255, 0.96);
            color: #374151;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
        }

        .ai-chat-model-switch {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .ai-chat-model-switch button {
            padding: 11px 10px;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            color: #374151;
            font-weight: 600;
        }

        .ai-chat-model-switch button.is-active {
            background: linear-gradient(135deg, rgba(107, 78, 252, 0.18), rgba(168, 85, 247, 0.14));
            border-color: rgba(107, 78, 252, 0.24);
            color: #5b21b6;
        }

        @keyframes aiBlink {
            0%, 49% { opacity: 1; }
            50%, 100% { opacity: 0; }
        }

        @keyframes aiPulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        @keyframes aiShimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        @media (max-width: 1199.98px) {
            .ai-chat-main__mobile-bar {
                display: flex;
                justify-content: flex-start;
                margin: 0 0 8px;
            }

            .ai-chat-main__mobile-bar--inside {
                margin: 0 0 12px;
            }

            .ai-chat-mobile-drawer-toggle {
                padding: 9px 12px;
                border-radius: 12px;
                font-size: 13px;
            }

            .ai-chat-sidebar-backdrop {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.42);
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.2s ease;
                z-index: 18;
            }

            .ai-chat-sidebar-backdrop.is-visible {
                opacity: 1;
                pointer-events: auto;
            }

            .ai-chat-shell {
                grid-template-columns: 1fr;
                grid-template-rows: auto minmax(0, 1fr);
            }

            .ai-chat-shell--chatgpt {
                gap: 10px;
                padding-bottom: 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-main {
                min-height: 0;
                overflow: visible;
                padding: 10px 10px 8px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar {
                position: fixed;
                top: calc(var(--ai-chat-header-offset, 0px) + 12px);
                left: 12px;
                bottom: 12px;
                width: min(88vw, 360px);
                max-width: 360px;
                transform: translateX(calc(-100% - 18px));
                transition: transform 0.24s ease, box-shadow 0.24s ease;
                z-index: 19;
                border-radius: 24px;
                padding: 12px;
                box-shadow: none;
                overflow: hidden;
            }

            .ai-chat-shell--chatgpt.is-sidebar-drawer-open .ai-chat-sidebar {
                transform: translateX(0);
                box-shadow: 24px 0 60px rgba(15, 23, 42, 0.2);
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header {
                margin-bottom: 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__history {
                flex: 1 1 auto;
                min-height: 0;
                overflow: auto;
                max-height: none;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__footer {
                margin-top: auto;
            }

            .ai-chat-main--empty .ai-chat-composer {
                width: 100%;
            }
        }

        @media (min-width: 1200px) {
            .ai-chat-sidebar {
                position: sticky;
                top: 0;
                max-height: calc(var(--ai-chat-viewport-height, calc(100dvh - 140px)) - 36px);
            }

            .ai-chat-main {
                max-height: calc(var(--ai-chat-viewport-height, calc(100dvh - 140px)) - 36px);
            }

            .ai-chat-composer {
                position: sticky;
                bottom: 0;
                z-index: 2;
                margin-top: 12px;
                padding-bottom: 2px;
                background: linear-gradient(180deg, rgba(255, 255, 255, 0), rgba(255, 255, 255, 0.96) 22%);
            }
        }

        @media (max-width: 767.98px) {
            .ai-chat-main__mobile-bar {
                display: flex;
                justify-content: flex-start;
                margin: 0 0 8px;
            }

            .ai-chat-main__mobile-bar--inside {
                margin: 0 0 12px;
            }

            .ai-chat-mobile-drawer-toggle {
                padding: 9px 12px;
                border-radius: 12px;
                font-size: 13px;
            }

            .ai-chat-sidebar-backdrop {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.42);
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.2s ease;
                z-index: 18;
            }

            .ai-chat-sidebar-backdrop.is-visible {
                opacity: 1;
                pointer-events: auto;
            }

            .ai-chat-sidebar,
            .ai-chat-main {
                border-radius: 20px;
            }

            .ai-chat-main {
                padding: 16px;
            }

            .ai-chat-composer__row {
                grid-template-columns: 42px 42px minmax(0, 1fr) 48px;
                padding: 8px;
            }

            .ai-chat-message__bubble {
                max-width: 100%;
            }

            .ai-chat-image-tools__selectors {
                grid-template-columns: 1fr;
            }

            .ai-chat-sidebar__list {
                max-height: none;
            }

            .ai-chat-shell--chatgpt {
                grid-template-columns: 1fr;
                grid-template-rows: auto minmax(0, 1fr);
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar {
                position: fixed;
                top: calc(var(--ai-chat-header-offset, 0px) + 12px);
                left: 12px;
                bottom: 12px;
                width: min(86vw, 340px);
                max-width: 340px;
                transform: translateX(calc(-100% - 18px));
                transition: transform 0.24s ease, box-shadow 0.24s ease;
                z-index: 19;
                border-radius: 24px;
                box-shadow: none;
            }

            .ai-chat-shell--chatgpt.is-sidebar-drawer-open .ai-chat-sidebar {
                transform: translateX(0);
                box-shadow: 24px 0 60px rgba(15, 23, 42, 0.2);
            }
        }

        .ai-chat-page > .container {
            max-width: none;
            width: 100%;
            padding-left: 0;
            padding-right: 0;
        }

        .ai-chat-shell--chatgpt {
            gap: 0;
            grid-template-columns: clamp(260px, 24vw, 320px) minmax(0, 1fr);
            align-items: stretch;
            border: 0;
            border-radius: 0;
            overflow: hidden;
            background: transparent;
            box-shadow: none;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar {
            --ai-chat-sidebar-width: clamp(260px, 24vw, 320px);
            border-right: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 0;
            background: linear-gradient(180deg, rgba(247, 249, 255, 0.98), rgba(255, 255, 255, 0.96));
            box-shadow: none;
            padding: clamp(8px, 1vw, 12px) 14px 14px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 0;
            max-height: calc(var(--ai-chat-viewport-height, calc(100dvh - 140px)) - 48px);
        }

        .ai-chat-shell--chatgpt .ai-chat-main {
            border-radius: 0;
            border: 0;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: none;
            padding: clamp(16px, 1.6vw, 22px) clamp(18px, 1.8vw, 22px) clamp(14px, 1.4vw, 18px);
            overflow: hidden;
        }

        .ai-chat-shell--chatgpt {
            font-size: 14px;
        }

        .ai-chat-brand__name {
            font-size: 16px;
        }

        .ai-chat-main__heading h4 {
            font-size: clamp(18px, 1.7vw, 26px);
        }

        .ai-chat-sidebar__search {
            margin: 12px 0 10px;
            padding: 9px 10px;
            gap: 8px;
            border-radius: 12px;
        }

        .ai-chat-sidebar__list {
            gap: 6px;
        }

        .ai-chat-history-item {
            padding: 10px 11px;
            border-radius: 14px;
        }

        .ai-chat-sidebar__footer {
            gap: 10px;
            margin-top: 12px;
        }

        .ai-chat-sidebar__credits {
            padding: 12px;
            border-radius: 14px;
        }

        .ai-chat-sidebar__empty,
        .ai-chat-empty {
            padding: 16px 12px;
        }

        .ai-chat-sidebar__empty-icon,
        .ai-chat-empty__icon {
            width: 48px;
            height: 48px;
            margin-bottom: 10px;
            border-radius: 16px;
        }

        .ai-chat-thread {
            gap: 12px;
        }

        .ai-chat-message {
            gap: 8px;
        }

        .ai-chat-message__avatar {
            width: 34px;
            height: 34px;
            flex-basis: 34px;
            border-radius: 12px;
        }

        .ai-chat-message__bubble {
            padding: 12px 14px;
            border-radius: 18px;
            max-width: min(740px, 84%);
        }

        .ai-chat-message__content {
            font-size: 14px;
            line-height: 1.55;
        }

        .ai-chat-image__caption,
        .ai-chat-message__meta,
        .ai-chat-composer__footer {
            font-size: 12px;
        }

        .ai-chat-skeleton {
            height: 12px;
            margin-bottom: 8px;
        }

        .ai-chat-composer {
            margin-top: 12px;
            padding-top: 10px;
        }

        .ai-chat-composer__row {
            grid-template-columns: 40px 40px minmax(0, 1fr) 46px;
            gap: 8px;
            padding: 8px;
            border-radius: 18px;
        }

        .ai-chat-icon-btn,
        .ai-chat-send-btn {
            width: 40px;
            height: 40px;
        }

        .ai-chat-mini-btn,
        .ai-chat-new-btn {
            padding: 8px 10px;
            border-radius: 12px;
            gap: 6px;
        }

        .ai-chat-composer textarea {
            min-height: 40px;
            padding: 10px 4px 0;
            font-size: 14px;
            line-height: 1.45;
        }

        .ai-chat-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #315efb;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .ai-chat-brand__mark {
            width: 28px;
            height: 28px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: rgba(49, 94, 251, 0.12);
            color: #315efb;
        }

        .ai-chat-brand__name {
            font-size: 18px;
        }

        .ai-chat-sidebar__header-actions {
            display: flex;
            align-items: center;
            gap: 4px;
            width: 100%;
            flex-wrap: nowrap;
            overflow-x: auto;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .ai-chat-sidebar__header {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .ai-chat-sidebar__header-actions::-webkit-scrollbar {
            display: none;
        }

        .ai-chat-sidebar__header-actions .ai-chat-new-btn--compact {
            padding: 7px 9px;
            border-radius: 12px;
            font-size: 13px;
            white-space: nowrap;
            box-shadow: none;
        }

        .ai-chat-usage-card {
            display: grid;
            grid-template-columns: 38px minmax(0, 1fr) auto;
            align-items: center;
            gap: 10px;
            width: 100%;
            min-width: 0;
            padding: 12px 14px;
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(91, 55, 255, 0.08), rgba(255, 255, 255, 0.98));
            border: 1px solid rgba(91, 55, 255, 0.12);
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.05);
            text-decoration: none;
            color: inherit;
        }

        .ai-chat-usage-card__avatar {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #315efb, #7c9dff);
            color: #fff;
            font-size: 14px;
            flex: 0 0 auto;
        }

        .ai-chat-usage-card__body {
            min-width: 0;
            display: grid;
            gap: 2px;
        }

        .ai-chat-usage-card__body strong,
        .ai-chat-usage-card__body span {
            margin: 0;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .ai-chat-usage-card__body strong {
            color: #111827;
            font-size: 14px;
            font-weight: 800;
        }

        .ai-chat-usage-card__body span {
            color: #6b7280;
            font-size: 12px;
        }

        .ai-chat-usage-card__meta {
            width: 38px;
            height: 38px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(91, 55, 255, 0.12);
            color: #5b37ff;
            flex: 0 0 auto;
        }

        .ai-chat-usage-card__body small {
            display: block;
            margin-top: 2px;
            color: #6b7280;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .ai-chat-usage-card__progress {
            width: 100%;
            height: 7px;
            margin-top: 8px;
            border-radius: 999px;
            background: rgba(91, 55, 255, 0.12);
            overflow: hidden;
        }

        .ai-chat-usage-card__progress span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #315efb, #8f7bff);
            box-shadow: 0 4px 10px rgba(91, 55, 255, 0.26);
        }

        .ai-chat-user-card--compact {
            flex: 1 1 auto;
            min-width: 0;
            padding: 7px 9px 7px 7px;
            border-radius: 999px;
            gap: 6px;
            grid-template-columns: 34px minmax(0, 1fr) auto;
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.03);
        }

        .ai-chat-sidebar__history {
            display: grid;
            gap: 14px;
            flex: 1 1 auto;
            min-height: 0;
            overflow: visible;
            padding-right: 2px;
        }

        .ai-chat-history-group {
            display: grid;
            gap: 8px;
        }

        .ai-chat-history-group__label {
            margin: 0;
            padding: 0 4px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #9ca3af;
        }

        .ai-chat-history-item {
            padding: 12px 12px 12px 14px;
            border-radius: 18px;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.06);
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.03);
        }

        .ai-chat-history-item.is-active {
            background:
                linear-gradient(90deg, rgba(49, 94, 251, 0.20), rgba(49, 94, 251, 0.08)),
                linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(243, 246, 255, 0.99));
            border-color: rgba(49, 94, 251, 0.34);
            box-shadow:
                inset 3px 0 0 rgba(49, 94, 251, 0.95),
                0 10px 18px rgba(49, 94, 251, 0.12);
        }

        .ai-chat-history-item__action {
            color: #c4c9d4;
            font-size: 18px;
            line-height: 1;
        }

        .ai-chat-sidebar__footer {
            display: grid;
            gap: 12px;
            margin-top: 16px;
            flex: 0 0 auto;
            margin-top: auto;
        }

        .ai-chat-user-card {
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr) auto;
            align-items: center;
            gap: 10px;
            padding: 12px 12px 12px 10px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(15, 23, 42, 0.08);
            text-decoration: none;
            color: inherit;
        }

        .ai-chat-user-card__avatar {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #315efb, #7c9dff);
            color: #fff;
            font-weight: 800;
        }

        .ai-chat-user-card--compact .ai-chat-user-card__avatar {
            width: 34px;
            height: 34px;
            font-size: 13px;
        }

        .ai-chat-user-card__body {
            min-width: 0;
            display: grid;
            gap: 2px;
        }

        .ai-chat-user-card__body strong,
        .ai-chat-user-card__body span {
            margin: 0;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .ai-chat-user-card__body strong {
            color: #111827;
            font-size: 14px;
        }

        .ai-chat-user-card__body span {
            color: #6b7280;
            font-size: 12px;
        }

        .ai-chat-user-card--compact .ai-chat-user-card__body strong,
        .ai-chat-user-card--compact .ai-chat-user-card__body span {
            font-size: 12px;
        }

        .ai-chat-visually-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            margin: -1px;
            padding: 0;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .ai-chat-main__header {
            display: flex;
            margin-bottom: 22px;
            align-items: flex-start;
        }

        .ai-chat-main__heading {
            min-width: 0;
        }

        .ai-chat-main__heading h4 {
            margin: 0;
            font-size: clamp(22px, 2.1vw, 32px);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.03em;
            color: #111827;
        }

        .ai-chat-main__heading p {
            margin: 8px 0 0;
            display: block;
        }

        .ai-chat-main__status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #315efb;
            font-size: 13px;
            font-weight: 700;
        }

        .ai-chat-status-dot {
            background: #315efb;
            box-shadow: 0 0 0 0 rgba(49, 94, 251, 0.35);
        }

        .ai-chat-thread {
            gap: 18px;
            padding: 0 4px 12px;
            overflow: visible;
        }

        .ai-chat-message {
            gap: 12px;
        }

        .ai-chat-message__avatar {
            background: linear-gradient(135deg, rgba(49, 94, 251, 0.16), rgba(49, 94, 251, 0.10));
            color: #315efb;
        }

        .ai-chat-message__avatar--user {
            background: linear-gradient(135deg, #dce6ff, #c9d7ff);
            color: #315efb;
        }

        .ai-chat-message__bubble {
            max-width: min(760px, 82%);
            padding: 14px 16px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.97);
            border: 1px solid rgba(148, 163, 184, 0.16);
            box-shadow: 0 12px 28px rgba(17, 24, 39, 0.05);
        }

        .ai-chat-message.is-assistant .ai-chat-message__bubble {
            background: transparent;
            border: 0;
            box-shadow: none;
            padding: 0;
            max-width: min(840px, 90%);
        }

        .ai-chat-message.is-user .ai-chat-message__bubble {
            background: linear-gradient(135deg, rgba(49, 94, 251, 0.16), rgba(49, 94, 251, 0.10));
            border-color: rgba(49, 94, 251, 0.18);
        }

        .ai-chat-empty {
            max-width: 560px;
        }

        .ai-chat-empty__icon {
            background: linear-gradient(135deg, rgba(49, 94, 251, 0.18), rgba(49, 94, 251, 0.10));
            color: #315efb;
        }

        .ai-chat-empty h5 {
            font-size: clamp(22px, 2vw, 30px);
        }

        .ai-chat-composer {
            margin-top: 18px;
        }

        .ai-chat-composer__surface {
            display: grid;
            gap: 12px;
            padding: 14px;
            border-radius: 28px;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 14px 36px rgba(15, 23, 42, 0.06);
        }

        .ai-chat-composer__chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .ai-chat-chip {
            padding: 9px 14px;
            border-radius: 999px;
            border: 1px solid rgba(49, 94, 251, 0.16);
            background: rgba(49, 94, 251, 0.08);
            color: #315efb;
            font-weight: 700;
        }

        .ai-chat-composer__row {
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            padding: 0;
            border: 0;
            background: transparent;
        }

        .ai-chat-composer__actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .ai-chat-icon-btn--ghost {
            background: rgba(49, 94, 251, 0.08);
            color: #315efb;
        }

        .ai-chat-send-btn {
            background: linear-gradient(135deg, #315efb, #5b86ff);
            box-shadow: 0 14px 30px rgba(49, 94, 251, 0.24);
        }

        .ai-chat-composer textarea {
            min-height: 46px;
            padding: 12px 4px 0;
            font-size: 15px;
        }

        .ai-chat-composer__footer {
            margin-top: 10px;
            justify-content: center;
            color: #6b7280;
            font-size: 13px;
        }

        .ai-chat-image-tools {
            display: grid;
            gap: 12px;
            margin-top: 0;
        }

        .ai-chat-model-switch {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .ai-chat-model-switch button.is-active {
            background: linear-gradient(135deg, rgba(49, 94, 251, 0.18), rgba(49, 94, 251, 0.12));
            border-color: rgba(49, 94, 251, 0.22);
            color: #315efb;
        }

        @media (max-width: 1199.98px) {
            .ai-chat-shell--chatgpt {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto;
                min-height: auto;
                align-items: start;
                gap: 12px;
                padding-bottom: 12px;
            }

            .ai-chat-shell--chatgpt .ai-chat-main {
                order: 1;
                min-height: auto;
                overflow: visible;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar {
                order: 2;
                border-right: 0;
                border-bottom: 1px solid rgba(15, 23, 42, 0.08);
                min-height: auto;
                height: auto;
                overflow: visible;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header {
                margin-bottom: 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__history {
                flex: 0 0 auto;
                max-height: 180px;
                overflow: auto;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__footer {
                margin-top: 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-composer {
                margin-top: 8px;
            }
        }

        @media (min-width: 1200px) {
            .ai-chat-shell--chatgpt .ai-chat-main {
                max-height: calc(var(--ai-chat-viewport-height, calc(100dvh - 140px)) - 2px);
            }

            .ai-chat-composer {
                position: sticky;
                bottom: 0;
                z-index: 2;
                background: linear-gradient(180deg, rgba(255, 255, 255, 0), rgba(255, 255, 255, 0.96) 22%);
                padding-bottom: 2px;
            }
        }

        @media (max-width: 767.98px) {
            .ai-chat-shell--chatgpt {
                min-height: 0;
                gap: 10px;
                padding-bottom: 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar,
            .ai-chat-shell--chatgpt .ai-chat-main {
                min-height: 0;
            }

            .ai-chat-shell--chatgpt .ai-chat-main,
            .ai-chat-shell--chatgpt .ai-chat-sidebar {
                padding: 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar {
                gap: 8px;
                border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__history {
                max-height: 150px;
                overflow: auto;
            }

            .ai-chat-sidebar__empty,
            .ai-chat-empty {
                text-align: left;
                padding: 8px 6px;
            }

            .ai-chat-sidebar__empty-icon,
            .ai-chat-empty__icon {
                width: 40px;
                height: 40px;
                margin-left: 0;
                margin-right: 0;
            }

            .ai-chat-sidebar__empty h6,
            .ai-chat-empty h6 {
                font-size: 14px;
                margin-bottom: 4px;
            }

            .ai-chat-sidebar__empty p,
            .ai-chat-empty p {
                font-size: 12px;
                line-height: 1.4;
                margin-bottom: 0;
            }

            .ai-chat-shell--chatgpt .ai-chat-main {
                justify-content: flex-start;
                padding-top: 12px;
                padding-bottom: 8px;
            }

            .ai-chat-composer__row {
                grid-template-columns: minmax(0, 1fr);
                gap: 6px;
            }

            .ai-chat-composer__actions {
                justify-content: flex-end;
                gap: 6px;
            }

            .ai-chat-message__bubble {
                max-width: 100%;
                padding: 12px 12px;
            }

            .ai-chat-main--empty {
                justify-content: flex-start;
                align-content: flex-start;
                gap: 10px;
                padding: 12px 10px 10px;
            }

            .ai-chat-main--empty .ai-chat-main__hero {
                max-width: 100%;
                gap: 8px;
            }

            .ai-chat-main__hero h2 {
                font-size: clamp(18px, 5.4vw, 24px);
            }

            .ai-chat-main__hero .ai-chat-style-pills {
                width: 100%;
                max-width: 320px;
            }

            .ai-chat-main--empty .ai-chat-composer {
                width: 100%;
                max-width: 100%;
            }

            .ai-chat-shell--chatgpt .ai-chat-composer {
                margin-top: 6px;
            }

            .ai-chat-composer__surface {
                padding: 10px;
                border-radius: 22px;
            }

            .ai-chat-composer textarea {
                min-height: 36px;
                font-size: 14px;
            }

            .ai-chat-send-btn,
            .ai-chat-icon-btn,
            .ai-chat-mini-btn {
                width: 38px;
                height: 38px;
            }
        }

        .ai-chat-shell--chatgpt {
            min-height: calc(var(--ai-chat-viewport-height, calc(100dvh - 140px)) - 18px);
            height: auto;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar,
        .ai-chat-shell--chatgpt .ai-chat-main {
            min-height: calc(var(--ai-chat-viewport-height, calc(100dvh - 140px)) - 18px);
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar {
            overflow: hidden;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar__history {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
            padding-right: 4px;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar__footer {
            margin-top: auto;
            flex: 0 0 auto;
        }

        .ai-chat-shell--chatgpt .ai-chat-main {
            min-height: calc(var(--ai-chat-viewport-height, calc(100dvh - 140px)) - 18px);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            padding: clamp(16px, 1.6vw, 22px) clamp(18px, 1.8vw, 22px) clamp(14px, 1.4vw, 18px);
        }

        .ai-chat-shell--chatgpt .ai-chat-main__header {
            flex: 0 0 auto;
            margin-bottom: 12px;
        }

        .ai-chat-shell--chatgpt .ai-chat-thread {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
            overscroll-behavior: contain;
        }

        .ai-chat-shell--chatgpt .ai-chat-composer {
            flex: 0 0 auto;
            margin-top: 10px;
            padding-top: 0;
            border-top: 0;
            position: sticky;
            bottom: 0;
            z-index: 3;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0), rgba(255, 255, 255, 0.96) 20%);
            padding-bottom: 2px;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar {
            --ai-chat-sidebar-width: 296px;
            width: 100%;
            transition: width 0.22s ease, min-width 0.22s ease, max-width 0.22s ease, padding 0.22s ease;
        }

        .ai-chat-shell--chatgpt.sidebar-collapsed {
            grid-template-columns: 74px minmax(0, 1fr);
        }

        .ai-chat-shell--chatgpt.sidebar-collapsed .ai-chat-sidebar {
            width: 74px;
            min-width: 74px;
            max-width: 74px;
            padding-left: 10px;
            padding-right: 10px;
            overflow: hidden;
        }

        .ai-chat-shell--chatgpt.sidebar-collapsed .ai-chat-sidebar__search,
        .ai-chat-shell--chatgpt.sidebar-collapsed .ai-chat-sidebar__history,
        .ai-chat-shell--chatgpt.sidebar-collapsed .ai-chat-sidebar__footer,
        .ai-chat-shell--chatgpt.sidebar-collapsed .ai-chat-new-btn {
            display: none;
        }

        .ai-chat-shell--chatgpt.sidebar-collapsed .ai-chat-sidebar__header-actions {
            justify-content: center;
            width: 100%;
        }

        .ai-chat-shell--chatgpt.sidebar-collapsed .ai-chat-sidebar__header-actions .ai-chat-new-btn--compact {
            display: none;
        }

        .ai-chat-shell--chatgpt.sidebar-collapsed .ai-chat-sidebar__header-actions .ai-chat-user-card {
            display: none;
        }

        .ai-chat-shell--chatgpt.sidebar-collapsed .ai-chat-usage-card {
            display: none;
        }

        .ai-chat-shell--chatgpt.sidebar-collapsed .ai-chat-sidebar__header-actions button {
            width: 42px;
            height: 42px;
            padding: 0;
            justify-content: center;
        }

        .ai-chat-shell--chatgpt.sidebar-collapsed .ai-chat-sidebar__header-actions button span {
            display: none;
        }

        .ai-chat-shell--chatgpt.sidebar-collapsed .ai-chat-sidebar__header-actions [data-sidebar-toggle-icon] {
            transform: rotate(180deg);
        }

        .ai-chat-shell--chatgpt.sidebar-collapsed .ai-chat-sidebar__header-actions .ai-chat-icon-btn--ghost {
            flex: 0 0 auto;
        }

        @media (max-width: 1199.98px) {
            .ai-chat-shell--chatgpt.sidebar-collapsed {
                grid-template-columns: 1fr;
            }

            .ai-chat-shell--chatgpt.sidebar-collapsed .ai-chat-sidebar {
                width: 100%;
                min-width: 0;
                max-width: none;
                padding-left: 14px;
                padding-right: 14px;
            }
        }

        @media (max-width: 767.98px) {
            .ai-chat-shell--chatgpt .ai-chat-sidebar__header-actions {
                gap: 4px;
                justify-content: flex-start;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header-actions .ai-chat-new-btn--compact {
                padding: 6px 9px;
                font-size: 11px;
                min-height: 34px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header-actions button {
                width: 34px;
                height: 34px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header-actions .ai-chat-user-card--compact {
                padding: 6px 8px 6px 6px;
                gap: 6px;
                grid-template-columns: 28px minmax(0, 1fr) auto;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header-actions .ai-chat-user-card--compact .ai-chat-user-card__avatar {
                width: 28px;
                height: 28px;
                font-size: 11px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header-actions .ai-chat-user-card--compact .ai-chat-user-card__body strong,
            .ai-chat-shell--chatgpt .ai-chat-sidebar__header-actions .ai-chat-user-card--compact .ai-chat-user-card__body span {
                font-size: 11px;
            }
        }

        .ai-chat-main--empty {
            display: grid;
            grid-template-rows: minmax(0, 1fr) auto;
            align-items: stretch;
            justify-content: stretch;
            align-content: stretch;
            justify-items: stretch;
            gap: 14px;
            padding: 0;
            min-height: 100%;
        }

        .ai-chat-main--empty .ai-chat-main__header,
        .ai-chat-main--empty .ai-chat-thread {
            display: none;
        }

        .ai-chat-main--empty .ai-chat-composer {
            width: 100%;
            margin-top: 0;
            align-self: start;
        }

        .ai-chat-main__hero {
            width: 100%;
            max-width: none;
            display: grid;
            justify-items: center;
            gap: 12px;
            text-align: center;
            margin: 0;
            padding: 0;
            border-radius: 0;
            background: transparent;
            border: 0;
            box-shadow: none;
        }

        .ai-chat-main__hero h2 {
            margin: 0;
            font-size: clamp(26px, 2.3vw, 40px);
            line-height: 1.15;
            letter-spacing: -0.03em;
            color: #111827;
            font-weight: 800;
        }

        .ai-chat-main__hero-copy {
            margin: 0;
            max-width: 56ch;
            font-size: clamp(14px, 1.1vw, 16px);
            line-height: 1.6;
            color: #6b7280;
        }

        .ai-chat-main__hero .ai-chat-style-pills {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 999px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.06);
            width: min(100%, 520px);
        }

        .ai-chat-main__hero .ai-chat-style-pills button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 16px;
            border: 0;
            background: transparent;
            color: #111827;
            font-weight: 600;
        }

        .ai-chat-main__hero .ai-chat-style-pills button.is-active {
            background: linear-gradient(135deg, rgba(49, 94, 251, 0.16), rgba(49, 94, 251, 0.10));
            color: #315efb;
        }

        .ai-chat-main__starter-prompts {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            width: min(100%, 640px);
            margin-top: 6px;
        }

        .ai-chat-starter-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid rgba(49, 94, 251, 0.14);
            background: rgba(49, 94, 251, 0.06);
            color: #315efb;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 8px 16px rgba(49, 94, 251, 0.06);
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        .ai-chat-starter-pill:hover {
            transform: translateY(-1px);
            background: rgba(49, 94, 251, 0.1);
            box-shadow: 0 12px 22px rgba(49, 94, 251, 0.1);
        }

        @media (max-width: 1199.98px) {
            .ai-chat-shell--chatgpt {
                gap: 12px;
                padding-bottom: 12px;
                grid-template-rows: auto auto;
                align-items: start;
            }

            .ai-chat-shell--chatgpt .ai-chat-main {
                order: 1;
                min-height: auto;
                overflow: visible;
                padding: 14px 16px 12px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar {
                order: 2;
                min-height: auto;
                height: auto;
                overflow: visible;
                padding: 12px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__history {
                flex: 0 0 auto;
                max-height: 180px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__footer {
                margin-top: 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-composer {
                margin-top: 8px;
            }

            .ai-chat-main--empty .ai-chat-composer {
                width: 100%;
            }
        }

        @media (max-width: 767.98px) {
            .ai-chat-main__mobile-bar {
                display: flex;
                justify-content: flex-start;
                margin: 0 0 8px;
            }

            .ai-chat-main__mobile-bar--inside {
                margin: 0 0 12px;
            }

            .ai-chat-mobile-drawer-toggle {
                padding: 9px 12px;
                border-radius: 12px;
                font-size: 13px;
            }

            .ai-chat-sidebar-backdrop {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.42);
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.2s ease;
                z-index: 18;
            }

            .ai-chat-sidebar-backdrop.is-visible {
                opacity: 1;
                pointer-events: auto;
            }

            .ai-chat-shell--chatgpt {
                grid-template-columns: 1fr;
                grid-template-rows: auto minmax(0, 1fr);
                gap: 8px;
                padding-bottom: 8px;
            }

            .ai-chat-shell--chatgpt .ai-chat-main {
                padding: 10px;
                padding-top: 12px;
                padding-bottom: 8px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar {
                position: fixed;
                top: calc(var(--ai-chat-header-offset, 0px) + 12px);
                left: 12px;
                bottom: 12px;
                width: min(86vw, 340px);
                max-width: 340px;
                transform: translateX(calc(-100% - 18px));
                transition: transform 0.24s ease, box-shadow 0.24s ease;
                z-index: 19;
                border-radius: 24px;
                padding: 12px;
                box-shadow: none;
            }

            .ai-chat-shell--chatgpt.is-sidebar-drawer-open .ai-chat-sidebar {
                transform: translateX(0);
                box-shadow: 24px 0 60px rgba(15, 23, 42, 0.2);
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header {
                margin-bottom: 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__history {
                max-height: 140px;
            }

            .ai-chat-sidebar__empty,
            .ai-chat-empty {
                padding: 8px 6px;
            }

            .ai-chat-sidebar__empty-icon,
            .ai-chat-empty__icon {
                width: 40px;
                height: 40px;
            }

            .ai-chat-sidebar__empty h6,
            .ai-chat-empty h6 {
                font-size: 14px;
                margin-bottom: 4px;
            }

            .ai-chat-sidebar__empty p,
            .ai-chat-empty p {
                font-size: 12px;
                line-height: 1.4;
                margin-bottom: 0;
            }

            .ai-chat-main--empty {
                align-items: flex-start;
                gap: 8px;
                padding: 10px 8px 8px;
            }

            .ai-chat-main--empty .ai-chat-main__hero {
                gap: 8px;
            }

            .ai-chat-main__hero h2 {
                font-size: clamp(18px, 5.2vw, 23px);
            }

            .ai-chat-main__hero .ai-chat-style-pills {
                max-width: 300px;
            }

            .ai-chat-main__hero .ai-chat-style-pills button {
                padding: 10px 12px;
                gap: 6px;
                font-size: 13px;
            }

            .ai-chat-composer {
                margin-top: 6px;
            }

            .ai-chat-composer__surface {
                padding: 10px;
                border-radius: 20px;
            }

            .ai-chat-composer__row {
                gap: 6px;
            }

            .ai-chat-composer__actions {
                gap: 6px;
            }

            .ai-chat-composer textarea {
                min-height: 34px;
                font-size: 14px;
            }

            .ai-chat-icon-btn,
            .ai-chat-send-btn,
            .ai-chat-mini-btn {
                width: 36px;
                height: 36px;
            }

            .ai-chat-message__bubble {
                padding: 11px 12px;
            }
        }

        .ai-chat-main--empty .ai-chat-composer__surface {
            border-radius: 28px;
            padding: 14px;
        }

        @media (max-width: 1199.98px) {
            .ai-chat-shell--chatgpt .ai-chat-sidebar {
                padding: 10px 10px 12px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header {
                margin-bottom: 6px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header-actions {
                gap: 6px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header-actions .ai-chat-new-btn--compact {
                padding: 7px 10px;
                font-size: 12px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header-actions button {
                width: 38px;
                height: 38px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__history {
                max-height: 160px;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-group {
                gap: 6px;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-group__label {
                padding: 0 2px;
                font-size: 11px;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-item {
                padding: 10px 10px 10px 12px;
                border-radius: 14px;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-item__body strong {
                font-size: 13px;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-item__body span {
                font-size: 11px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__footer {
                margin-top: 8px;
                gap: 8px;
            }

            .ai-chat-shell--chatgpt .ai-chat-user-card {
                grid-template-columns: 34px minmax(0, 1fr) auto;
                gap: 8px;
                padding: 10px;
                border-radius: 14px;
            }

            .ai-chat-shell--chatgpt .ai-chat-user-card__avatar {
                width: 34px;
                height: 34px;
                font-size: 12px;
            }

            .ai-chat-shell--chatgpt .ai-chat-user-card__body strong {
                font-size: 13px;
            }

            .ai-chat-shell--chatgpt .ai-chat-user-card__body span {
                font-size: 11px;
            }
        }

        @media (max-width: 767.98px) {
            .ai-chat-shell--chatgpt .ai-chat-sidebar {
                padding: 8px 8px 10px;
                gap: 8px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header-actions #ai-chat-sidebar-toggle.is-disabled {
                pointer-events: none;
                opacity: 0.45;
                cursor: default;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header {
                margin-bottom: 4px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header-actions {
                gap: 4px;
                width: 100%;
                justify-content: flex-start;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header-actions .ai-chat-new-btn--compact {
                padding: 6px 9px;
                font-size: 11px;
                min-height: 34px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header-actions button {
                width: 34px;
                height: 34px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__history {
                max-height: 120px;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-group {
                gap: 5px;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-item {
                padding: 9px 9px 9px 11px;
                border-radius: 12px;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-item__body strong {
                font-size: 12px;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-item__body span {
                font-size: 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__footer {
                margin-top: 6px;
                gap: 6px;
            }

            .ai-chat-shell--chatgpt .ai-chat-user-card {
                grid-template-columns: 32px minmax(0, 1fr) auto;
                gap: 6px;
                padding: 8px 9px;
                border-radius: 12px;
            }

            .ai-chat-shell--chatgpt .ai-chat-user-card__avatar {
                width: 32px;
                height: 32px;
            }

            .ai-chat-shell--chatgpt .ai-chat-user-card__body strong {
                font-size: 12px;
            }

            .ai-chat-shell--chatgpt .ai-chat-user-card__body span {
                font-size: 10px;
            }

            .ai-chat-page > .container {
                max-width: none;
                width: 100%;
                padding-left: clamp(12px, 1.6vw, 24px);
                padding-right: clamp(12px, 1.6vw, 24px);
            }

            .ai-chat-shell--chatgpt {
                gap: clamp(14px, 1.4vw, 20px);
                grid-template-columns: clamp(300px, 24vw, 360px) minmax(0, 1fr);
                align-items: stretch;
                min-height: calc(var(--ai-chat-viewport-height, calc(100dvh - 140px)) - 20px);
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar {
                position: sticky;
                top: 0;
                height: calc(var(--ai-chat-viewport-height, calc(100dvh - 140px)) - 20px);
                max-height: none;
                border-radius: 28px;
                border: 1px solid rgba(84, 73, 255, 0.12);
                background:
                    radial-gradient(circle at top left, rgba(107, 78, 252, 0.08), transparent 24%),
                    linear-gradient(180deg, rgba(248, 250, 255, 0.98), rgba(255, 255, 255, 0.98));
                box-shadow: 0 20px 60px rgba(17, 24, 39, 0.08);
                padding: 18px 16px 16px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header {
                gap: 12px;
                margin-bottom: 12px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header-actions {
                gap: 8px;
                flex-wrap: wrap;
            }

            .ai-chat-shell--chatgpt .ai-chat-usage-card {
                margin-top: 2px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__search {
                margin-top: 16px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__history {
                padding-right: 2px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__footer {
                margin-top: 14px;
            }

            .ai-chat-shell--chatgpt .ai-chat-main {
                min-height: calc(var(--ai-chat-viewport-height, calc(100dvh - 140px)) - 20px);
                border-radius: 28px;
                border: 1px solid rgba(84, 73, 255, 0.08);
                background:
                    radial-gradient(circle at top left, rgba(107, 78, 252, 0.06), transparent 18%),
                    linear-gradient(180deg, rgba(255, 255, 255, 0.99), rgba(247, 249, 255, 0.98));
                box-shadow: 0 24px 60px rgba(17, 24, 39, 0.08);
                padding: clamp(16px, 1.8vw, 24px);
            }

            .ai-chat-shell--chatgpt .ai-chat-main__header {
                margin-bottom: 14px;
            }

            .ai-chat-shell--chatgpt .ai-chat-thread {
                padding: 4px 4px 12px;
            }

            .ai-chat-shell--chatgpt .ai-chat-main--empty {
                display: grid;
                align-content: center;
                justify-items: center;
                gap: 18px;
            }

            .ai-chat-shell--chatgpt .ai-chat-main--empty .ai-chat-main__header,
            .ai-chat-shell--chatgpt .ai-chat-main--empty .ai-chat-thread {
                display: none;
            }

            .ai-chat-shell--chatgpt .ai-chat-main--empty .ai-chat-main__hero {
                width: min(100%, 980px);
                margin: 0 auto;
                padding: clamp(18px, 3vw, 42px) 12px 6px;
                text-align: center;
            }

            .ai-chat-shell--chatgpt .ai-chat-main__hero h2 {
                font-size: clamp(28px, 3.2vw, 54px);
                line-height: 1.02;
                letter-spacing: -0.04em;
                max-width: 760px;
                margin-left: auto;
                margin-right: auto;
            }

            .ai-chat-shell--chatgpt .ai-chat-main__hero .ai-chat-style-pills {
                width: min(100%, 420px);
                margin: 24px auto 0;
                box-shadow: 0 12px 34px rgba(84, 73, 255, 0.08);
            }

            .ai-chat-shell--chatgpt .ai-chat-main__hero .ai-chat-style-pills button {
                min-height: 52px;
            }

            .ai-chat-shell--chatgpt .ai-chat-composer {
                width: min(100%, 860px);
                margin: 12px auto 0;
                padding-top: 0;
            }

            .ai-chat-shell--chatgpt .ai-chat-composer__surface {
                border-radius: 30px;
                border-color: rgba(84, 73, 255, 0.16);
                box-shadow: 0 20px 50px rgba(17, 24, 39, 0.08);
            }

            .ai-chat-shell--chatgpt .ai-chat-composer__row {
                grid-template-columns: minmax(0, 1fr) auto;
            }

            .ai-chat-shell--chatgpt .ai-chat-composer textarea {
                min-height: 60px;
                font-size: 16px;
                padding-top: 12px;
            }

            .ai-chat-shell--chatgpt .ai-chat-message__bubble {
                max-width: min(820px, 84%);
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar-backdrop {
                background: rgba(15, 23, 42, 0.48);
                backdrop-filter: blur(4px);
            }
        }

        .ai-chat-page > .container {
            max-width: none;
            width: 100%;
            padding-left: clamp(12px, 1.6vw, 24px);
            padding-right: clamp(12px, 1.6vw, 24px);
        }

        .ai-chat-shell--chatgpt {
            gap: clamp(14px, 1.4vw, 20px);
            grid-template-columns: clamp(300px, 24vw, 360px) minmax(0, 1fr);
            align-items: stretch;
            min-height: calc(var(--ai-chat-viewport-height, calc(100dvh - 140px)) - 20px);
            overflow: hidden;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar {
            position: sticky;
            top: 0;
            height: calc(var(--ai-chat-viewport-height, calc(100dvh - 140px)) - 20px);
            max-height: none;
            border-radius: 28px;
            border: 1px solid rgba(84, 73, 255, 0.12);
            background:
                radial-gradient(circle at top left, rgba(107, 78, 252, 0.08), transparent 24%),
                linear-gradient(180deg, rgba(248, 250, 255, 0.98), rgba(255, 255, 255, 0.98));
            box-shadow: 0 20px 60px rgba(17, 24, 39, 0.08);
            padding: 18px 16px 16px;
            overflow: hidden;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar__header {
            gap: 12px;
            margin-bottom: 12px;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar__header-actions {
            gap: 8px;
            flex-wrap: wrap;
        }

        .ai-chat-shell--chatgpt .ai-chat-usage-card {
            margin-top: 2px;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar__search {
            margin-top: 16px;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar__history {
            padding-right: 2px;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar__footer {
            margin-top: 14px;
        }

        .ai-chat-shell--chatgpt .ai-chat-main {
            min-height: calc(var(--ai-chat-viewport-height, calc(100dvh - 140px)) - 20px);
            border-radius: 28px;
            border: 1px solid rgba(84, 73, 255, 0.08);
            background:
                radial-gradient(circle at top left, rgba(107, 78, 252, 0.06), transparent 18%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.99), rgba(247, 249, 255, 0.98));
            box-shadow: 0 24px 60px rgba(17, 24, 39, 0.08);
            padding: clamp(16px, 1.8vw, 24px);
            overflow: hidden;
        }

        .ai-chat-shell--chatgpt .ai-chat-main__header {
            margin-bottom: 14px;
        }

        .ai-chat-shell--chatgpt .ai-chat-thread {
            padding: 4px 4px 12px;
        }

        .ai-chat-shell--chatgpt .ai-chat-main--empty {
            display: grid;
            align-content: center;
            justify-items: center;
            gap: 18px;
        }

        .ai-chat-shell--chatgpt .ai-chat-main--empty .ai-chat-main__header,
        .ai-chat-shell--chatgpt .ai-chat-main--empty .ai-chat-thread {
            display: none;
        }

        .ai-chat-shell--chatgpt .ai-chat-main--empty .ai-chat-main__hero {
            width: min(100%, 980px);
            margin: 0;
            padding: clamp(18px, 3vw, 42px) 12px 6px;
            text-align: center;
        }

        .ai-chat-shell--chatgpt .ai-chat-main__hero h2 {
            font-size: clamp(28px, 3.2vw, 54px);
            line-height: 1.02;
            letter-spacing: -0.04em;
            max-width: 760px;
            margin-left: auto;
            margin-right: auto;
        }

        .ai-chat-shell--chatgpt .ai-chat-main__hero .ai-chat-style-pills {
            width: min(100%, 420px);
            margin: 24px auto 0;
            box-shadow: 0 12px 34px rgba(84, 73, 255, 0.08);
        }

        .ai-chat-shell--chatgpt .ai-chat-main__hero .ai-chat-style-pills button {
            min-height: 52px;
        }

        .ai-chat-shell--chatgpt .ai-chat-composer {
            width: min(100%, 860px);
            margin: 12px auto 0;
            padding-top: 0;
        }

        .ai-chat-shell--chatgpt .ai-chat-composer__surface {
            border-radius: 30px;
            border-color: rgba(84, 73, 255, 0.16);
            box-shadow: 0 20px 50px rgba(17, 24, 39, 0.08);
        }

        .ai-chat-shell--chatgpt .ai-chat-composer__row {
            grid-template-columns: minmax(0, 1fr) auto;
        }

        .ai-chat-shell--chatgpt .ai-chat-composer textarea {
            min-height: 60px;
            font-size: 16px;
            padding-top: 12px;
        }

        .ai-chat-shell--chatgpt .ai-chat-message__bubble {
            max-width: min(820px, 84%);
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar-backdrop {
            background: rgba(15, 23, 42, 0.48);
            backdrop-filter: blur(4px);
        }

        @media (min-width: 1200px) {
            .ai-chat-page > .container {
                padding-left: 0;
                padding-right: 0;
            }

            .ai-chat-shell--chatgpt {
                gap: 0;
                border-radius: 0;
                background: #fff;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar,
            .ai-chat-shell--chatgpt .ai-chat-main {
                border-radius: 0;
                box-shadow: none;
                background: #fff;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar {
                border: 0;
                border-right: 1px solid rgba(15, 23, 42, 0.08);
                padding: 18px 16px 16px;
            }

            .ai-chat-shell--chatgpt .ai-chat-main {
                border: 0;
                padding: 18px 20px 16px;
            }

            .ai-chat-shell--chatgpt .ai-chat-main__mobile-bar {
                display: none;
            }

            .ai-chat-shell--chatgpt .ai-chat-main--empty {
                background: #fff;
            }

            .ai-chat-shell--chatgpt .ai-chat-main--empty .ai-chat-main__hero {
                max-width: 720px;
            }

            .ai-chat-shell--chatgpt .ai-chat-main--empty .ai-chat-composer {
                width: min(100%, 860px);
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__intro,
            .ai-chat-shell--chatgpt .ai-chat-usage-card,
            .ai-chat-shell--chatgpt .ai-chat-sidebar__prompts,
            .ai-chat-shell--chatgpt .ai-chat-sidebar__actions {
                border-radius: 18px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header {
                padding-bottom: 10px;
                margin-bottom: 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__search {
                margin-bottom: 10px;
                padding-bottom: 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__history {
                padding-top: 0;
                padding-bottom: 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__footer {
                margin-top: 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__intro {
                padding-bottom: 10px;
                margin-bottom: 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-usage-card {
                gap: 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-usage-card__body strong {
                font-size: 26px;
            }

            .ai-chat-shell--chatgpt .ai-chat-usage-card__body span {
                font-size: 12px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__action-btn {
                min-height: 38px;
                font-size: 14px;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-group {
                gap: 4px;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-group__label {
                font-size: 11px;
                letter-spacing: 0.03em;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-item {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 10px;
                padding: 9px 0;
                border: 0;
                border-bottom: 1px solid rgba(15, 23, 42, 0.06);
                border-radius: 0;
                background: transparent;
                box-shadow: none;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-item:last-child {
                border-bottom: 0;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-item__body {
                gap: 2px;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-item__body strong {
                font-size: 14px;
                font-weight: 600;
                color: #111827;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-item__body span {
                font-size: 11px;
                color: #6b7280;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-item__action {
                font-size: 14px;
                align-self: center;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__empty {
                padding: 6px 0;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__empty-icon {
                width: 44px;
                height: 44px;
                border-radius: 14px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__empty p {
                font-size: 12px;
            }
        }

        .ai-chat-page > .container {
            padding-left: clamp(8px, 1.2vw, 18px);
            padding-right: clamp(8px, 1.2vw, 18px);
        }

        .ai-chat-shell--chatgpt {
            --ai-chat-shell-gap: clamp(10px, 1.2vw, 18px);
            --ai-chat-sidebar-pad-x: clamp(12px, 1.3vw, 18px);
            --ai-chat-sidebar-pad-y: clamp(12px, 1.2vw, 18px);
            --ai-chat-main-pad-x: clamp(14px, 1.7vw, 24px);
            --ai-chat-main-pad-y: clamp(14px, 1.7vw, 24px);
            gap: var(--ai-chat-shell-gap);
            align-items: stretch;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar,
        .ai-chat-shell--chatgpt .ai-chat-main {
            min-height: 0;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar {
            padding: var(--ai-chat-sidebar-pad-y) var(--ai-chat-sidebar-pad-x);
            gap: clamp(10px, 1vw, 14px);
        }

        .ai-chat-shell--chatgpt .ai-chat-main {
            padding: var(--ai-chat-main-pad-y) var(--ai-chat-main-pad-x);
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar__header {
            gap: 8px;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar__search {
            margin-top: 8px;
            margin-bottom: 8px;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar__history {
            flex: 1 1 auto;
            min-height: 0;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar__footer {
            margin-top: auto;
            padding-top: 12px;
        }

        .ai-chat-shell--chatgpt .ai-chat-usage-card {
            width: 100%;
            min-width: 0;
        }

        .ai-chat-shell--chatgpt .ai-chat-usage-card__body strong {
            font-size: clamp(16px, 1.6vw, 26px);
        }

        .ai-chat-shell--chatgpt .ai-chat-usage-card__body span {
            white-space: normal;
        }

        @media (max-width: 1199.98px) {
            .ai-chat-page > .container {
                padding-left: 8px;
                padding-right: 8px;
            }

            .ai-chat-shell--chatgpt .ai-chat-main__mobile-bar {
                display: flex;
                justify-content: flex-start;
                margin: 0 0 8px;
            }

            .ai-chat-shell--chatgpt .ai-chat-main__mobile-bar--inside {
                margin: 0 0 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-main {
                padding: 10px;
                overflow: visible;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar {
                position: fixed;
                top: calc(var(--ai-chat-header-offset, 0px) + 10px);
                left: 10px;
                bottom: 10px;
                width: min(88vw, 360px);
                max-width: 360px;
                transform: translateX(calc(-100% - 18px));
                transition: transform 0.24s ease, box-shadow 0.24s ease;
                z-index: 19;
                border-radius: 24px;
                padding: 12px;
                box-shadow: none;
                overflow-y: auto;
                overflow-x: hidden;
                -webkit-overflow-scrolling: touch;
            }

            .ai-chat-shell--chatgpt.is-sidebar-drawer-open .ai-chat-sidebar {
                transform: translateX(0);
                box-shadow: 24px 0 60px rgba(15, 23, 42, 0.2);
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__history {
                max-height: none;
                overflow: visible;
                margin-top: 0;
                padding-top: 0;
                flex: 0 0 auto;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__footer {
                margin-top: 8px;
                padding-top: 8px;
            }
        }

        @media (max-width: 767.98px) {
            .ai-chat-page > .container {
                padding-left: 6px;
                padding-right: 6px;
            }

            .ai-chat-shell--chatgpt {
                --ai-chat-shell-gap: 8px;
            }

            .ai-chat-shell--chatgpt .ai-chat-main {
                padding: 8px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar {
                top: calc(var(--ai-chat-header-offset, 0px) + 8px);
                left: 8px;
                bottom: 8px;
                width: min(92vw, 340px);
                max-width: 340px;
                padding: 8px 10px 10px;
                border-radius: 22px;
            }

            .ai-chat-mobile-drawer-toggle {
                width: auto;
                max-width: 100%;
                padding: 8px 10px;
                font-size: 12px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__search {
                margin-top: 6px;
                margin-bottom: 6px;
                padding: 9px 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__footer {
                gap: 8px;
            }

            .ai-chat-shell--chatgpt .ai-chat-usage-card {
                padding: 10px 12px;
                border-radius: 16px;
            }

            .ai-chat-shell--chatgpt .ai-chat-usage-card__body strong {
                font-size: 16px;
            }

            .ai-chat-shell--chatgpt .ai-chat-usage-card__body span,
            .ai-chat-shell--chatgpt .ai-chat-usage-card__body small {
                font-size: 11px;
            }
        }

        @media (min-width: 1200px) {
            .ai-chat-page > .container {
                padding-left: 0;
                padding-right: 0;
            }

            .ai-chat-shell--chatgpt {
                gap: 0;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar {
                width: clamp(248px, 22vw, 292px);
                padding: 12px 12px 14px;
                border-right: 1px solid rgba(15, 23, 42, 0.08);
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header {
                gap: 8px;
                margin-bottom: 8px;
                padding-bottom: 8px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header-actions {
                gap: 6px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__search {
                margin: 8px 0 8px;
                padding: 9px 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__history {
                gap: 10px;
                padding-right: 0;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-group {
                gap: 4px;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-group__label {
                padding: 0 2px;
                font-size: 11px;
                letter-spacing: 0.03em;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-item {
                padding: 10px 0;
                border: 0;
                border-bottom: 1px solid rgba(15, 23, 42, 0.06);
                border-radius: 0;
                background: transparent;
                box-shadow: none;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-item:last-child {
                border-bottom: 0;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-item__body {
                gap: 2px;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-item__body strong {
                font-size: 14px;
                font-weight: 600;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-item__body span {
                font-size: 11px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__footer {
                gap: 10px;
                padding-top: 12px;
            }

            .ai-chat-shell--chatgpt .ai-chat-usage-card {
                padding: 12px 12px 12px 12px;
                border-radius: 16px;
            }

            .ai-chat-shell--chatgpt .ai-chat-usage-card__avatar,
            .ai-chat-shell--chatgpt .ai-chat-usage-card__meta {
                width: 36px;
                height: 36px;
            }

            .ai-chat-shell--chatgpt .ai-chat-usage-card__body strong {
                font-size: 16px;
            }

            .ai-chat-shell--chatgpt .ai-chat-usage-card__body span,
            .ai-chat-shell--chatgpt .ai-chat-usage-card__body small {
                font-size: 11px;
            }

            .ai-chat-shell--chatgpt .ai-chat-main {
                padding: 18px 22px 16px;
            }

            .ai-chat-shell--chatgpt .ai-chat-main__header {
                margin-bottom: 14px;
            }

            .ai-chat-shell--chatgpt .ai-chat-thread {
                gap: 14px;
                padding-right: 2px;
            }

            .ai-chat-shell--chatgpt .ai-chat-message {
                gap: 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-message__avatar {
                width: 36px;
                height: 36px;
                flex-basis: 36px;
            }

            .ai-chat-shell--chatgpt .ai-chat-message__bubble {
                max-width: min(780px, 84%);
            }

            .ai-chat-shell--chatgpt .ai-chat-composer {
                margin-top: 14px;
            }
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar {
            align-self: stretch;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar__header,
        .ai-chat-shell--chatgpt .ai-chat-sidebar__search,
        .ai-chat-shell--chatgpt .ai-chat-sidebar__footer {
            width: 100%;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar__history {
            padding-top: 0;
            margin-top: -4px;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar__header-actions {
            width: 100%;
        }

        .ai-chat-shell--chatgpt .ai-chat-history-item {
            width: 100%;
            min-height: 0;
        }

        .ai-chat-shell--chatgpt .ai-chat-history-group__label {
            margin-top: 0;
            margin-bottom: 0;
            padding-top: 0;
        }

        .ai-chat-shell--chatgpt .ai-chat-main {
            display: flex;
            flex-direction: column;
        }

        .ai-chat-shell--chatgpt .ai-chat-main--empty {
            align-items: center;
        }

        .ai-chat-shell--chatgpt .ai-chat-main--empty .ai-chat-main__hero,
        .ai-chat-shell--chatgpt .ai-chat-main--empty .ai-chat-thread,
        .ai-chat-shell--chatgpt .ai-chat-main--empty .ai-chat-composer {
            width: 100%;
            margin-left: 0;
            margin-right: 0;
        }

        .ai-chat-shell--chatgpt .ai-chat-main__hero {
            width: 100%;
            margin-left: 0;
            margin-right: 0;
            text-align: center;
        }

        .ai-chat-shell--chatgpt .ai-chat-main__hero .ai-chat-style-pills {
            margin: 22px auto 0;
            width: min(100%, 420px);
        }

        .ai-chat-shell--chatgpt .ai-chat-main--empty .ai-chat-composer {
            padding-bottom: 2px;
        }

        .ai-chat-shell--chatgpt .ai-chat-history-item {
            align-items: center;
            gap: 6px;
            padding: 6px 0;
        }

        .ai-chat-shell--chatgpt .ai-chat-history-item__body {
            gap: 0;
            min-width: 0;
        }

        .ai-chat-shell--chatgpt .ai-chat-history-item__body strong {
            font-size: 13px;
            line-height: 1.2;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ai-chat-shell--chatgpt .ai-chat-history-item__body span {
            display: none;
        }

        .ai-chat-shell--chatgpt .ai-chat-history-item__action {
            font-size: 12px;
            opacity: 0.55;
            align-self: stretch;
        }

        @media (max-width: 1199.98px) {
            .ai-chat-shell--chatgpt .ai-chat-main {
                padding: 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-main--empty .ai-chat-main__hero,
            .ai-chat-shell--chatgpt .ai-chat-main--empty .ai-chat-thread,
            .ai-chat-shell--chatgpt .ai-chat-main--empty .ai-chat-composer,
            .ai-chat-shell--chatgpt .ai-chat-main__hero {
                width: 100%;
            }

            .ai-chat-shell--chatgpt .ai-chat-main__hero {
                margin-top: 4px;
            }
        }

        @media (max-width: 767.98px) {
            .ai-chat-page > .container {
                padding-left: 4px;
                padding-right: 4px;
            }

            .ai-chat-shell--chatgpt {
                --ai-chat-shell-gap: 6px;
            }

            .ai-chat-shell--chatgpt .ai-chat-main {
                padding: 8px;
            }

            .ai-chat-shell--chatgpt .ai-chat-composer {
                margin-top: 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-composer__surface {
                padding: 10px;
                border-radius: 22px;
            }

            .ai-chat-shell--chatgpt .ai-chat-composer__row {
                gap: 8px;
            }

            .ai-chat-shell--chatgpt .ai-chat-composer textarea {
                min-height: 52px;
                padding-top: 10px;
                font-size: 14px;
                line-height: 1.45;
            }

            .ai-chat-shell--chatgpt .ai-chat-composer__footer {
                margin-top: 8px;
                font-size: 12px;
            }

            .ai-chat-shell--chatgpt .ai-chat-main__hero {
                margin-top: 2px;
            }

            .ai-chat-shell--chatgpt .ai-chat-main__hero h2 {
                font-size: clamp(18px, 5vw, 22px);
            }

            .ai-chat-shell--chatgpt .ai-chat-main__hero .ai-chat-style-pills {
                width: 100%;
                margin-top: 18px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar {
                top: calc(var(--ai-chat-header-offset, 0px) + 6px);
                left: 6px;
                bottom: 6px;
                width: min(92vw, 330px);
                max-width: 330px;
                padding: 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__search {
                margin-top: 4px;
                margin-bottom: 4px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__footer {
                gap: 8px;
                padding-top: 8px;
            }

            .ai-chat-shell--chatgpt .ai-chat-usage-card {
                padding: 10px 11px;
            }
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar {
            background: linear-gradient(180deg, rgba(240, 244, 250, 0.98), rgba(226, 232, 242, 0.98));
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar-backdrop {
            background: rgba(15, 23, 42, 0.50);
        }

        @media (min-width: 1200px) {
            .ai-chat-shell--chatgpt .ai-chat-sidebar {
                background: linear-gradient(180deg, rgba(237, 242, 249, 0.99), rgba(223, 229, 240, 0.99));
            }
        }

        @media (max-width: 1199.98px) {
            .ai-chat-shell--chatgpt .ai-chat-sidebar {
                background: linear-gradient(180deg, rgba(239, 244, 250, 0.99), rgba(225, 232, 242, 0.99));
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar {
                overflow-y: auto;
                overflow-x: hidden;
                -webkit-overflow-scrolling: touch;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__header {
                margin-bottom: 4px;
                padding-bottom: 0;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__search {
                margin: 4px 0 4px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__history {
                margin-top: 0;
                padding-top: 0;
                gap: 8px;
                flex: 0 0 auto;
                overflow: visible;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-group {
                gap: 4px;
            }

            .ai-chat-shell--chatgpt .ai-chat-history-group__label {
                margin: 0;
                padding: 0 2px;
                line-height: 1.1;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__empty {
                padding: 6px 4px 4px;
            }

            .ai-chat-shell--chatgpt .ai-chat-sidebar__footer {
                margin-top: 8px;
                padding-top: 8px;
            }
        }

        .ai-chat-shell--chatgpt {
            overflow: hidden;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .ai-chat-shell--chatgpt {
            height: calc(var(--ai-chat-viewport-height, calc(100dvh - 140px)) - 20px);
            max-height: calc(var(--ai-chat-viewport-height, calc(100dvh - 140px)) - 20px);
            min-height: 0;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar,
        .ai-chat-shell--chatgpt .ai-chat-main {
            height: 100%;
            max-height: 100%;
            min-height: 0;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar__scroll {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            gap: clamp(10px, 1vw, 14px);
            overflow: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(107, 78, 252, 0.28) transparent;
            padding-right: 4px;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar__scroll::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar__scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar__scroll::-webkit-scrollbar-thumb {
            background: rgba(107, 78, 252, 0.24);
            border-radius: 999px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar__scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(107, 78, 252, 0.38);
            background-clip: padding-box;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar__history {
            flex: 1 1 auto;
            min-height: 0;
            overflow: visible;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar__footer {
            flex: 0 0 auto;
            margin-top: 0;
            padding-top: 14px;
            position: sticky;
            bottom: 0;
            z-index: 3;
            background:
                linear-gradient(180deg, rgba(247, 249, 255, 0.72), rgba(247, 249, 255, 0.96));
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-top: 1px solid rgba(84, 73, 255, 0.14);
            box-shadow: 0 -10px 24px rgba(17, 24, 39, 0.05);
            border-top-left-radius: 18px;
            border-top-right-radius: 18px;
            padding-left: 10px;
            padding-right: 10px;
        }

        .ai-chat-shell--chatgpt .ai-chat-main {
            display: flex;
            flex-direction: column;
            align-content: stretch;
            min-height: 0;
            overflow: hidden;
        }

        .ai-chat-shell--chatgpt .ai-chat-main__header,
        .ai-chat-shell--chatgpt .ai-chat-main__hero {
            flex: 0 0 auto;
        }

        .ai-chat-shell--chatgpt .ai-chat-thread {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
            overscroll-behavior: contain;
        }

        .ai-chat-shell--chatgpt .ai-chat-composer {
            flex: 0 0 auto;
            margin-top: 0;
            align-self: stretch;
            position: sticky;
            bottom: 0;
            z-index: 3;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0), rgba(255, 255, 255, 0.96) 20%);
            padding-bottom: 2px;
        }

        .ai-chat-shell--chatgpt .ai-chat-main--empty {
            align-items: center;
            justify-content: center;
            gap: 22px;
            padding-top: clamp(16px, 4vw, 48px);
            padding-bottom: clamp(16px, 4vw, 48px);
        }

        .ai-chat-shell--chatgpt .ai-chat-main--empty .ai-chat-main__hero {
            align-self: center;
        }

        .ai-chat-shell--chatgpt .ai-chat-main--empty .ai-chat-main__hero,
        .ai-chat-shell--chatgpt .ai-chat-main--empty .ai-chat-composer {
            width: min(100%, 920px);
        }

        .ai-chat-shell--chatgpt .ai-chat-main--empty .ai-chat-composer {
            align-self: center;
            margin-top: 0;
        }

        body.revision-ai-chat-page .main-area.fix {
            height: 100dvh;
            padding-top: 0;
            padding-bottom: 0;
            overflow: hidden;
        }

        .ai-chat-page > .container {
            padding-left: 0 !important;
            padding-right: 0 !important;
            max-width: none !important;
            width: 100% !important;
        }

        .ai-chat-shell--chatgpt {
            height: 100%;
            min-height: 100%;
            max-height: none;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar,
        .ai-chat-shell--chatgpt .ai-chat-main {
            height: 100%;
            min-height: 0;
            max-height: 100%;
        }

        .ai-chat-shell--chatgpt .ai-chat-main--empty .ai-chat-thread {
            display: none;
        }

        body.revision-ai-chat-page {
            overflow: hidden;
        }

        body.revision-ai-chat-page .main-area.fix {
            height: 100dvh;
            padding: 0;
            overflow: hidden;
            background: transparent;
        }

        .ai-chat-page {
            position: fixed;
            inset: 0;
            z-index: 1200;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(10px, 2vw, 24px);
            overflow: hidden;
            background:
                radial-gradient(circle at top left, rgba(110, 79, 255, 0.08), transparent 28%),
                radial-gradient(circle at bottom right, rgba(70, 90, 255, 0.08), transparent 24%),
                rgba(15, 23, 42, 0.52);
        }

        .ai-chat-page__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.10);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .ai-chat-page__dialog {
            position: relative;
            z-index: 1;
            width: min(100%, 1540px);
            height: min(100%, 920px);
            max-width: none !important;
            padding: 0;
            border-radius: 32px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 30px 80px rgba(15, 23, 42, 0.28);
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        .ai-chat-page__toolbar {
            display: flex;
            justify-content: flex-end;
            padding: 16px 18px 0;
            background: transparent;
            position: relative;
            z-index: 5;
        }

        .ai-chat-page__close {
            z-index: 20;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 0 16px;
            border: 1px solid rgba(220, 38, 38, 0.18);
            border-radius: 999px;
            background: #fff;
            color: #b91c1c;
            box-shadow: 0 14px 30px rgba(220, 38, 38, 0.14);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            transition: transform 0.18s ease, background 0.18s ease, box-shadow 0.18s ease, color 0.18s ease;
            font-size: 14px;
            font-weight: 700;
        }

        .ai-chat-page__close--text {
            box-shadow: 0 14px 30px rgba(148, 163, 184, 0.12);
            color: #334155;
        }

        .ai-chat-page__close--text i {
            font-size: 14px;
            color: #ef4444;
        }

        .ai-chat-page__close--text span {
            display: inline-block;
        }

        .ai-chat-page__close:hover,
        .ai-chat-page__close--text:hover {
            transform: translateY(-1px);
            background: rgba(248, 250, 252, 0.98);
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.14);
            color: #111827;
        }

        .ai-chat-page__close--text:hover i {
            color: #dc2626;
        }

        .ai-chat-page__close:focus-visible,
        .ai-chat-page__close--text:focus-visible {
            outline: 3px solid rgba(59, 130, 246, 0.28);
            outline-offset: 3px;
        }

        .ai-chat-page__close--text:active {
            transform: translateY(0);
        }

        .ai-chat-page__close--text:active i {
            transform: translateX(-1px);
        }

        .ai-chat-page__dialog > .ai-chat-shell {
            height: 100%;
        }

        .ai-chat-page > .container {
            padding-left: 0 !important;
            padding-right: 0 !important;
            width: min(100%, 1540px) !important;
            height: min(100%, 920px);
        }

        .ai-chat-page--embedded {
            position: static;
            inset: auto;
            display: block;
            min-height: 100%;
            padding: 0;
            overflow: hidden;
            background: transparent;
        }

        .ai-chat-page--embedded .ai-chat-page__backdrop {
            display: none;
        }

        .ai-chat-page--embedded .ai-chat-page__close {
            display: none;
        }

        .ai-chat-page--embedded .ai-chat-page__dialog {
            position: static;
            inset: auto;
            transform: none;
            width: 100%;
            height: 100%;
            max-width: none;
            max-height: none;
            margin: 0;
            padding: 0;
            border-radius: 0;
            box-shadow: none;
            background: transparent;
        }

        .ai-chat-page--embedded .ai-chat-page__dialog {
            display: flex;
            flex-direction: column;
        }

        .ai-chat-page--embedded > .container {
            width: 100% !important;
            height: 100% !important;
            max-width: none !important;
            max-height: none;
            padding: 0 !important;
        }

        .ai-chat-page--embedded .ai-chat-page__dialog > .ai-chat-shell {
            flex: 1 1 auto;
            min-height: 0;
        }

        .ai-chat-shell--chatgpt {
            height: 100%;
            min-height: 100%;
            max-height: none;
        }

        .ai-chat-shell--chatgpt .ai-chat-sidebar,
        .ai-chat-shell--chatgpt .ai-chat-main {
            height: 100%;
            min-height: 0;
            max-height: 100%;
        }
        </style>
@endpush

@push('scripts')
    <script>
        (() => {
            const header = document.querySelector('header');
            const closeDialogBtn = document.getElementById('ai-chat-close-dialog');
            const dialogBackdrop = document.getElementById('ai-chat-dialog-backdrop');
            const thread = document.getElementById('ai-chat-thread');
            const emptyState = document.getElementById('ai-chat-empty-state');
            const form = document.getElementById('ai-chat-form');
            const promptInput = document.getElementById('ai-chat-prompt');
            const sendBtn = document.getElementById('ai-chat-send');
            const imageBtn = document.getElementById('ai-chat-image');
            const imageSizeSelect = document.getElementById('ai-chat-image-size');
            const imageStyleSelect = document.getElementById('ai-chat-image-style');
            const conversationInput = document.getElementById('ai-chat-conversation-id');
            const historyList = document.getElementById('ai-chat-history-list');
            const newChatBtn = document.getElementById('ai-chat-new-button');
            const sidebar = document.getElementById('ai-chat-sidebar');
            const sidebarToggle = document.getElementById('ai-chat-sidebar-toggle');
            const sidebarToggleIcon = sidebarToggle?.querySelector('[data-sidebar-toggle-icon]');
            const mobileDrawerToggle = document.getElementById('ai-chat-mobile-sidebar-toggle');
            const sidebarBackdrop = document.getElementById('ai-chat-sidebar-backdrop');
            const searchToggle = document.getElementById('ai-chat-search-toggle');
            const searchPanel = document.getElementById('ai-chat-search-panel');
            const searchInput = document.getElementById('ai-chat-search');
            const copyLastBtn = document.getElementById('ai-chat-copy-last');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const homeUrl = @json(route('home'));
            const returnToUrl = @json($returnToUrl);
            const isEmbedded = @json($isEmbedded);

            const closeDialog = () => {
                try {
                    if (window.parent !== window && typeof window.parent.revisionHubCloseAiChatModal === 'function') {
                        window.parent.revisionHubCloseAiChatModal();
                        return;
                    }
                } catch (error) {
                    // Ignore cross-window access failures and fall back below.
                }

                if (returnToUrl) {
                    window.location.href = returnToUrl;
                    return;
                }

                if (document.referrer && document.referrer.startsWith(window.location.origin) && ! document.referrer.includes('/ai-chat')) {
                    window.location.href = document.referrer;
                    return;
                }

                window.location.href = homeUrl;
            };

            closeDialogBtn?.addEventListener('click', closeDialog);
            dialogBackdrop?.addEventListener('click', closeDialog);
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeDialog();
                }
            });

            const endpoints = {
                conversations: @json(route('ai-chat.conversations.store')),
                streamBase: @json(route('ai-chat.conversations.store')),
            };
            const pageBaseUrl = @json($chatBaseUrl);
            const conversationUrl = (conversationId) => {
                const url = new URL(`${pageBaseUrl}/${encodeURIComponent(conversationId)}`, window.location.origin);

                if (returnToUrl) {
                    url.searchParams.set('return_to', returnToUrl);
                }

                if (isEmbedded) {
                    url.searchParams.set('embedded', '1');
                }

                return url.href;
            };

            const state = {
                conversationId: conversationInput.value || null,
                lastPrompt: '',
                lastAnswer: '',
                isBusy: false,
            };
            const sidebarStorageKey = 'revision-ai-chat-sidebar-collapsed';
            const canCollapseSidebar = window.matchMedia('(min-width: 1200px)').matches;
            const mobileDrawerQuery = window.matchMedia('(max-width: 1199.98px)');
            const mobileDrawerClass = 'is-sidebar-drawer-open';

            const updateViewportHeight = () => {
                const headerHeight = header ? header.offsetHeight : 0;
                const availableHeight = Math.max(window.innerHeight, 560);
                document.documentElement.style.setProperty('--ai-chat-viewport-height', `${availableHeight}px`);
                document.documentElement.style.setProperty('--ai-chat-header-offset', `${headerHeight}px`);
            };

            const setSidebarCollapsed = (collapsed, persist = true) => {
                const shell = sidebar?.closest('.ai-chat-shell--chatgpt');
                if (!sidebar || !shell) return;

                shell.classList.toggle('sidebar-collapsed', collapsed);
                sidebar.classList.toggle('is-collapsed', collapsed);
                sidebarToggle?.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                sidebarToggle?.setAttribute('aria-label', collapsed ? @json(__('Expand sidebar')) : @json(__('Collapse sidebar')));
                if (sidebarToggleIcon) {
                    sidebarToggleIcon.className = collapsed ? 'fas fa-chevron-right' : 'fas fa-chevron-left';
                }
                if (persist) {
                    localStorage.setItem(sidebarStorageKey, collapsed ? '1' : '0');
                }
            };

            const setMobileDrawerOpen = (open) => {
                const shell = sidebar?.closest('.ai-chat-shell--chatgpt');
                if (!sidebar || !shell) return;

                shell.classList.toggle(mobileDrawerClass, open);
                sidebarBackdrop?.classList.toggle('is-visible', open);
                sidebarBackdrop?.setAttribute('aria-hidden', open ? 'false' : 'true');
                mobileDrawerToggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
                sidebarToggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
                sidebarToggle?.setAttribute('aria-label', open ? @json(__('Close sidebar')) : @json(__('Collapse sidebar')));
                if (sidebarToggleIcon) {
                    sidebarToggleIcon.className = open ? 'fas fa-times' : 'fas fa-chevron-left';
                }
            };

            const syncSidebarState = () => {
                if (mobileDrawerQuery.matches) {
                    setSidebarCollapsed(true, false);
                    setMobileDrawerOpen(false);
                    sidebarToggle?.removeAttribute('aria-disabled');
                    sidebarToggle?.removeAttribute('tabindex');
                    sidebarToggle?.classList.remove('is-disabled');
                    return;
                }

                sidebarToggle?.removeAttribute('aria-disabled');
                sidebarToggle?.removeAttribute('tabindex');
                sidebarToggle?.classList.remove('is-disabled');
                setSidebarCollapsed(canCollapseSidebar && localStorage.getItem(sidebarStorageKey) === '1');
                setMobileDrawerOpen(false);
            };

            updateViewportHeight();
            window.addEventListener('resize', updateViewportHeight);
            window.addEventListener('resize', syncSidebarState);
            mobileDrawerQuery.addEventListener?.('change', syncSidebarState);

            syncSidebarState();

            sidebarToggle?.addEventListener('click', () => {
                if (!canCollapseSidebar || mobileDrawerQuery.matches) return;
                const shell = sidebar?.closest('.ai-chat-shell--chatgpt');
                const collapsed = shell?.classList.contains('sidebar-collapsed');
                setSidebarCollapsed(!collapsed);
            });

            mobileDrawerToggle?.addEventListener('click', () => {
                if (!mobileDrawerQuery.matches) return;
                const shell = sidebar?.closest('.ai-chat-shell--chatgpt');
                const isOpen = shell?.classList.contains(mobileDrawerClass);
                setMobileDrawerOpen(!isOpen);
            });

            sidebarBackdrop?.addEventListener('click', () => {
                if (mobileDrawerQuery.matches) {
                    setMobileDrawerOpen(false);
                }
            });

            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && mobileDrawerQuery.matches) {
                    setMobileDrawerOpen(false);
                }
            });

            const setSearchOpen = (open) => {
                if (!searchPanel || !searchToggle) return;
                searchPanel.classList.toggle('is-open', open);
                searchToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (open) {
                    searchInput?.focus();
                }
            };

            searchToggle?.addEventListener('click', () => {
                const isOpen = searchPanel?.classList.contains('is-open');
                setSearchOpen(!isOpen);
            });

            searchInput?.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    setSearchOpen(false);
                }
            });

            const scrollThreadToBottom = () => {
                thread.scrollTop = thread.scrollHeight;
            };

            const escapeHtml = (value) => {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };

            const parseResponsePayload = async (response) => {
                const contentType = response.headers.get('content-type') || '';
                const raw = await response.text();

                if (!raw) {
                    return {};
                }

                try {
                    return contentType.includes('application/json') || raw.trim().startsWith('{') || raw.trim().startsWith('[')
                        ? JSON.parse(raw)
                        : { message: raw };
                } catch (error) {
                    return raw.trim().startsWith('<') ? {} : { message: raw };
                }
            };

            const ensureEmptyStateRemoved = () => {
                if (emptyState) {
                    emptyState.remove();
                }
                thread.dataset.empty = '0';
            };

            const markdownEscapeHtml = (value) => escapeHtml(value).replace(/`/g, '&#96;');

            const renderMarkdown = (value) => {
                const text = String(value || '').replace(/\r\n?/g, '\n');
                const lines = text.split('\n');
                const blocks = [];
                let paragraph = [];
                let listType = null;
                let listItems = [];
                let codeLines = [];
                let inCodeBlock = false;
                let codeLanguage = '';
                let pendingMath = null;

                const inline = (input) => {
                    const escaped = markdownEscapeHtml(input);
                    return escaped
                        .replace(/`([^`]+)`/g, '<code>$1</code>')
                        .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
                        .replace(/__([^_]+)__/g, '<strong>$1</strong>')
                        .replace(/\*([^*\n]+)\*/g, '<em>$1</em>')
                        .replace(/_([^_\n]+)_/g, '<em>$1</em>')
                        .replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>');
                };

                const escapeMath = (input) => String(input || '').replace(/[&<>]/g, (char) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                }[char]));

                const flushParagraph = () => {
                    if (!paragraph.length) return;
                    const text = paragraph.join(' ').trim();
                    if (text.startsWith('$$') && text.endsWith('$$') && text.length > 4) {
                        blocks.push(`
                            <div class="ai-chat-equation-block" data-copy-text="${escapeHtml(text)}">
                                <button type="button" class="ai-chat-copy-btn" data-copy-text="${escapeHtml(text)}">${@json(__('Copy'))}</button>
                                <div class="ai-chat-equation">${escapeMath(text)}</div>
                            </div>
                        `);
                    } else {
                        blocks.push(`<p>${inline(text)}</p>`);
                    }
                    paragraph = [];
                };

                const flushList = () => {
                    if (!listItems.length || !listType) return;
                    blocks.push(`<${listType}>${listItems.map((item) => `<li>${inline(item)}</li>`).join('')}</${listType}>`);
                    listItems = [];
                    listType = null;
                };

                const flushCode = () => {
                    if (!codeLines.length) return;
                    const code = codeLines.join('\n');
                    const languageClass = codeLanguage ? ` class="language-${escapeHtml(codeLanguage)}"` : '';
                    blocks.push(`
                        <div class="ai-chat-block">
                            <button type="button" class="ai-chat-copy-btn" data-copy-text="${escapeHtml(code)}">${@json(__('Copy'))}</button>
                            <pre><code${languageClass}>${markdownEscapeHtml(code)}</code></pre>
                        </div>
                    `);
                    codeLines = [];
                    codeLanguage = '';
                };

                const isTableSeparator = (line) => /^\s*\|?(?:\s*:?-{3,}:?\s*\|)+\s*:?-{3,}:?\s*\|?\s*$/.test(line);
                const isTableRow = (line) => /^\s*\|.*\|\s*$/.test(line);

                const flushTable = (tableLines) => {
                    if (!tableLines.length) return;
                    const rows = tableLines.filter(Boolean).map((row) => row.trim().replace(/^\|/, '').replace(/\|$/, '').split('|').map((cell) => cell.trim()));
                    if (rows.length < 2) return;

                    const headers = rows[0];
                    const bodyRows = rows.slice(2);
                    const headHtml = headers.map((cell) => `<th>${inline(cell)}</th>`).join('');
                    const bodyHtml = bodyRows.map((row) => `<tr>${row.map((cell) => `<td>${inline(cell)}</td>`).join('')}</tr>`).join('');
                    blocks.push(`
                        <div class="ai-chat-table-wrap">
                            <table>
                                <thead><tr>${headHtml}</tr></thead>
                                <tbody>${bodyHtml}</tbody>
                            </table>
                        </div>
                    `);
                };

                let tableLines = [];

                lines.forEach((line) => {
                    const trimmed = line.trimEnd();

                    if (trimmed.startsWith('```')) {
                        if (inCodeBlock) {
                            inCodeBlock = false;
                            flushCode();
                        } else {
                            flushParagraph();
                            flushList();
                            tableLines = [];
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
                        if (tableLines.length) {
                            flushTable(tableLines);
                            tableLines = [];
                        }
                        return;
                    }

                    const unordered = trimmed.match(/^[-*+]\s+(.*)$/);
                    const ordered = trimmed.match(/^\d+\.\s+(.*)$/);
                    const tableCandidate = isTableRow(trimmed) || isTableSeparator(trimmed);

                    if (unordered || ordered) {
                        flushParagraph();
                        const nextType = unordered ? 'ul' : 'ol';
                        if (listType && listType !== nextType) {
                            flushList();
                        }
                        listType = nextType;
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
                        flushTable(tableLines);
                        tableLines = [];
                    }

                    flushList();
                    paragraph.push(trimmed);
                });

                if (inCodeBlock) {
                    flushCode();
                }

                if (tableLines.length) {
                    flushTable(tableLines);
                }

                flushParagraph();
                flushList();

                return blocks.join('');
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
                element.innerHTML = renderMarkdown(value);

                try {
                    const mathJax = await loadMathJax();
                    if (mathJax?.typesetPromise) {
                        await mathJax.typesetPromise([element]);
                    }
                } catch (error) {
                    // Keep the markdown rendering if math rendering is unavailable.
                }
            };

            const copyToClipboard = async (text) => {
                if (!text) return false;
                try {
                    await navigator.clipboard.writeText(text);
                    return true;
                } catch (error) {
                    return false;
                }
            };

            const createMessageNode = ({ role, content = '', meta = '', streaming = false }) => {
                const wrapper = document.createElement('div');
                wrapper.className = `ai-chat-message ${role === 'user' ? 'is-user' : 'is-assistant'}${streaming ? ' is-streaming' : ''}`;

                const bubble = document.createElement('div');
                bubble.className = 'ai-chat-message__bubble';

                const contentBox = document.createElement('div');
                contentBox.className = 'ai-chat-message__content';
                contentBox.textContent = content;

                const metaBox = document.createElement('div');
                metaBox.className = 'ai-chat-message__meta';
                metaBox.innerHTML = meta || '';

                if (role !== 'user') {
                    const avatar = document.createElement('div');
                    avatar.className = 'ai-chat-message__avatar';
                    avatar.innerHTML = '<i class="fas fa-robot"></i>';
                    wrapper.appendChild(avatar);
                }

                bubble.appendChild(contentBox);
                bubble.appendChild(metaBox);

                if (streaming) {
                    const cursor = document.createElement('span');
                    cursor.className = 'ai-chat-cursor';
                    contentBox.appendChild(cursor);
                }

                wrapper.appendChild(bubble);

                if (role === 'user') {
                    const avatar = document.createElement('div');
                    avatar.className = 'ai-chat-message__avatar ai-chat-message__avatar--user';
                    avatar.innerHTML = '<i class="fas fa-user"></i>';
                    wrapper.appendChild(avatar);
                }

                return {
                    node: wrapper,
                    contentBox,
                    metaBox,
                    bubble,
                };
            };

            const renderImageMessage = (assistantNode, image) => {
                assistantNode.contentBox.textContent = '';

                const imageWrap = document.createElement('div');
                const img = document.createElement('img');
                img.className = 'ai-chat-image';
                img.src = image.image_url;
                img.alt = image.revised_prompt || image.prompt || @json(__('Generated image'));
                imageWrap.appendChild(img);

                if (image.revised_prompt) {
                    const caption = document.createElement('div');
                    caption.className = 'ai-chat-image__caption';
                    caption.textContent = image.revised_prompt;
                    imageWrap.appendChild(caption);
                }

                const actions = document.createElement('div');
                actions.className = 'ai-chat-media-actions';

                const downloadBtn = document.createElement('button');
                downloadBtn.type = 'button';
                downloadBtn.className = 'ai-chat-media-action';
                downloadBtn.dataset.imageAction = 'download';
                downloadBtn.dataset.imageUrl = image.image_url || '';
                downloadBtn.textContent = @json(__('Download'));

                const shareBtn = document.createElement('button');
                shareBtn.type = 'button';
                shareBtn.className = 'ai-chat-media-action';
                shareBtn.dataset.imageAction = 'share';
                shareBtn.dataset.imageUrl = image.image_url || '';
                shareBtn.dataset.imagePrompt = image.revised_prompt || image.prompt || '';
                shareBtn.textContent = @json(__('Share'));

                actions.appendChild(downloadBtn);
                actions.appendChild(shareBtn);
                imageWrap.appendChild(actions);

                assistantNode.contentBox.appendChild(imageWrap);
                assistantNode.bubble.classList.remove('is-streaming');
                assistantNode.metaBox.innerHTML = `
                    <span>${new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}</span>
                    <span>${escapeHtml((image.provider || '').toUpperCase())}</span>
                    ${image.image_size ? `<span>${escapeHtml(image.image_size)}</span>` : ''}
                    ${image.image_style ? `<span>${escapeHtml(image.image_style)}</span>` : ''}
                `;
            };

            thread?.addEventListener('click', async (event) => {
                const copyButton = event.target.closest('[data-copy-text]');
                if (copyButton) {
                    const text = copyButton.dataset.copyText || '';
                    const copied = await copyToClipboard(text);
                    if (window.revisionHubToast) {
                        window.revisionHubToast(copied ? 'success' : 'error', copied ? @json(__('Copied to clipboard.')) : @json(__('Unable to copy.')));
                    }
                    return;
                }

                const button = event.target.closest('[data-image-action]');
                if (!button) return;

                const action = button.dataset.imageAction;
                const imageUrl = button.dataset.imageUrl || '';
                const imagePrompt = button.dataset.imagePrompt || '';

                if (!imageUrl) return;

                if (action === 'download') {
                    const link = document.createElement('a');
                    link.href = imageUrl;
                    link.download = `revisionhub-image-${Date.now()}.png`;
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    return;
                }

                if (action === 'share') {
                    try {
                        if (navigator.share) {
                            await navigator.share({
                                title: @json(__('Revision Hub AI Image')),
                                text: imagePrompt || @json(__('Generated image')),
                                url: new URL(imageUrl, window.location.origin).href,
                            });
                            return;
                        }

                        await navigator.clipboard.writeText(new URL(imageUrl, window.location.origin).href);
                        if (window.revisionHubToast) {
                            window.revisionHubToast('success', @json(__('Image link copied to clipboard.')));
                        }
                    } catch (error) {
                        if (window.revisionHubToast) {
                            window.revisionHubToast('error', @json(__('Unable to share the image.')));
                        }
                    }
                }
            });

            const addMessage = (role, content, meta = '') => {
                ensureEmptyStateRemoved();
                const { node } = createMessageNode({
                    role,
                    content,
                    meta,
                });
                thread.appendChild(node);
                scrollThreadToBottom();
                return node;
            };

            const setActiveConversation = (conversationId, title = null) => {
                state.conversationId = conversationId;
                conversationInput.value = conversationId || '';

                if (title && historyList) {
                    const activeItem = historyList.querySelector('.ai-chat-history-item.is-active');
                    if (activeItem) {
                        const titleNode = activeItem.querySelector('strong');
                        if (titleNode) {
                            titleNode.textContent = title;
                        }
                        activeItem.dataset.title = title;
                    }
                }
            };

            const createConversation = async (title = null) => {
                const payload = new FormData();
                payload.append('_token', csrf);
                if (title) {
                    payload.append('title', title);
                }

                const response = await fetch(endpoints.conversations, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: payload,
                });

                const data = await parseResponsePayload(response);

                if (!response.ok || data.status !== 'success') {
                    throw new Error(data.message || @json(__('Unable to create a new chat.')));
                }

                    const conversation = data.conversation;
                    const conversationKey = conversation.public_id || conversation.id;
                    setActiveConversation(conversationKey, conversation.title);
                    window.history.replaceState({}, '', conversationUrl(conversationKey));

                    if (historyList) {
                        const empty = historyList.querySelector('.ai-chat-sidebar__empty');
                        if (empty) {
                            empty.remove();
                        }

                        const item = document.createElement('a');
                        item.href = conversationUrl(conversationKey);
                        item.className = 'ai-chat-history-item is-active';
                        item.dataset.title = conversation.title;
                        item.innerHTML = `
                            <div class="ai-chat-history-item__body">
                                <strong>${escapeHtml(conversation.title)}</strong>
                                <span>${@json(__('Just now'))}</span>
                            </div>
                            <span class="ai-chat-history-item__active-pill">${@json(__('Current'))}</span>
                            <span class="ai-chat-history-item__action">...</span>
                        `;

                        const historyTarget = historyList.querySelector('.ai-chat-history-group .ai-chat-sidebar__list') || historyList;
                        historyTarget.prepend(item);
                        historyList.querySelectorAll('.ai-chat-history-item').forEach((node) => {
                            node.classList.remove('is-active');
                        });
                        item.classList.add('is-active');
                    }

                return conversationKey;
            };

            const parseStream = async (response, onEvent) => {
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    const parts = buffer.split('\n\n');
                    buffer = parts.pop() || '';

                    for (const chunk of parts) {
                        let eventName = 'message';
                        let dataLine = '';

                        chunk.split('\n').forEach((line) => {
                            if (line.startsWith('event:')) {
                                eventName = line.replace('event:', '').trim();
                            }
                            if (line.startsWith('data:')) {
                                dataLine += line.replace('data:', '').trim();
                            }
                        });

                        if (!dataLine) continue;

                        try {
                            onEvent(eventName, JSON.parse(dataLine));
                        } catch (error) {
                            // Ignore malformed event payloads.
                        }
                    }
                }
            };

            const sendMessage = async (rawPrompt, options = {}) => {
                const prompt = String(rawPrompt || '').trim();
                if (!prompt || state.isBusy) return;

                state.isBusy = true;
                state.lastPrompt = prompt;
                sendBtn.disabled = true;

                ensureEmptyStateRemoved();
                addMessage('user', prompt, `<span>${@json(__('Just now'))}</span>`);

                const assistantNode = createMessageNode({
                    role: 'assistant',
                    content: '',
                    meta: `<span>${@json(__('Assistant is typing'))}</span>`,
                    streaming: true,
                });

                const typingSkeleton = document.createElement('div');
                typingSkeleton.innerHTML = '<div class="ai-chat-skeleton"></div><div class="ai-chat-skeleton" style="width: 88%"></div>';
                assistantNode.contentBox.appendChild(typingSkeleton);

                thread.appendChild(assistantNode.node);
                scrollThreadToBottom();

                try {
                let conversationId = state.conversationId;
                if (!conversationId) {
                        conversationId = await createConversation();
                    }

                    const payload = new FormData();
                    payload.append('_token', csrf);
                    payload.append('message', prompt);
                    const response = await fetch(`${endpoints.streamBase}/${conversationId}/stream`, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/event-stream, application/json',
                        },
                        body: payload,
                    });

                    if (!response.ok) {
                        let message = @json(__('Unable to generate a response.'));
                        const data = await parseResponsePayload(response);
                        message = data.message || message;
                        throw new Error(message);
                    }

                    let answer = '';
                    assistantNode.contentBox.textContent = '';
                    const cursor = document.createElement('span');
                    cursor.className = 'ai-chat-cursor';

                    await parseStream(response, (eventName, data) => {
                        if (eventName === 'token') {
                            answer += data || '';
                            assistantNode.contentBox.textContent = answer;
                            assistantNode.contentBox.appendChild(cursor);
                            scrollThreadToBottom();
                        }

                        if (eventName === 'meta' && data?.conversation_title) {
                            const activeItem = historyList?.querySelector('.ai-chat-history-item.is-active');
                            if (activeItem) {
                                activeItem.dataset.title = data.conversation_title;
                                const titleNode = activeItem.querySelector('strong');
                                if (titleNode) {
                                    titleNode.textContent = data.conversation_title;
                                }
                            }
                            setActiveConversation(conversationId, data.conversation_title);
                        }

                        if (eventName === 'done') {
                            answer = data?.content || answer;
                            await renderAssistantContent(assistantNode.contentBox, answer);
                            assistantNode.bubble.classList.remove('is-streaming');
                            assistantNode.metaBox.innerHTML = `<span>${new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}</span>`;
                            state.lastAnswer = answer;
                            scrollThreadToBottom();
                        }

                        if (eventName === 'error') {
                            throw new Error(prefixNetworkErrorMessage(data?.message || @json(__('Something went wrong.'))));
                        }
                    });

                    if (!state.conversationId) {
                        setActiveConversation(conversationId);
                    }
                } catch (error) {
                    const message = prefixNetworkErrorMessage(error?.message || @json(__('Something went wrong.')));
                    assistantNode.contentBox.textContent = message;
                    assistantNode.bubble.classList.remove('is-streaming');
                    assistantNode.metaBox.innerHTML = `<span>${@json(__('Failed'))}</span>`;
                    if (window.revisionHubToast) {
                        window.revisionHubToast('error', message);
                    }
                } finally {
                    state.isBusy = false;
                    sendBtn.disabled = false;
                    scrollThreadToBottom();
                }
            };

            const generateImage = async (rawPrompt) => {
                const prompt = String(rawPrompt || '').trim();
                if (!prompt || state.isBusy) return;

                state.isBusy = true;
                state.lastPrompt = prompt;
                sendBtn.disabled = true;
                if (imageBtn) {
                    imageBtn.disabled = true;
                }

                ensureEmptyStateRemoved();
                addMessage('user', prompt, `<span>${@json(__('Just now'))}</span>`);

                const assistantNode = createMessageNode({
                    role: 'assistant',
                    content: '',
                    meta: `<span>${@json(__('Generating image'))}</span>`,
                    streaming: true,
                });

                const typingSkeleton = document.createElement('div');
                typingSkeleton.innerHTML = '<div class="ai-chat-skeleton"></div><div class="ai-chat-skeleton" style="width: 88%"></div>';
                assistantNode.contentBox.appendChild(typingSkeleton);
                thread.appendChild(assistantNode.node);
                scrollThreadToBottom();

                try {
                    let conversationId = state.conversationId;
                    if (!conversationId) {
                        conversationId = await createConversation();
                    }

                    const payload = new FormData();
                    payload.append('_token', csrf);
                    payload.append('prompt', prompt);
                    payload.append('size', imageSizeSelect?.value || '1024x1024');
                    payload.append('style', imageStyleSelect?.value || 'auto');
                    payload.append('type', 'image');

                    const response = await fetch(`${endpoints.streamBase}/${conversationId}/stream`, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: payload,
                    });

                    const data = await parseResponsePayload(response);

                    if (!response.ok || data.status !== 'success') {
                        throw new Error(data.message || @json(__('Unable to generate an image.')));
                    }

                    renderImageMessage(assistantNode, data.image || {});

                    if (data?.conversation?.title) {
                        setActiveConversation(conversationId, data.conversation.title);
                    }
                } catch (error) {
                    const message = prefixNetworkErrorMessage(error?.message || @json(__('Something went wrong.')));
                    assistantNode.contentBox.textContent = message;
                    assistantNode.bubble.classList.remove('is-streaming');
                    assistantNode.metaBox.innerHTML = `<span>${@json(__('Failed'))}</span>`;
                    if (window.revisionHubToast) {
                        window.revisionHubToast('error', message);
                    }
                } finally {
                    state.isBusy = false;
                    sendBtn.disabled = false;
                    if (imageBtn) {
                        imageBtn.disabled = false;
                    }
                    scrollThreadToBottom();
                }
            };

            form?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const prompt = promptInput.value.trim();
                promptInput.value = '';
                await sendMessage(prompt);
            });

            imageBtn?.addEventListener('click', async () => {
                const prompt = promptInput.value.trim();
                promptInput.value = '';
                await generateImage(prompt);
            });

            promptInput?.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    form?.requestSubmit?.();
                }
            });

            newChatBtn?.addEventListener('click', async () => {
                window.location.href = pageBaseUrl;
            });

            document.querySelectorAll('[data-prompt]').forEach((button) => {
                button.addEventListener('click', () => {
                    promptInput.value = button.dataset.prompt || '';
                    promptInput.focus();
                });
            });

            copyLastBtn?.addEventListener('click', async () => {
                if (!state.lastAnswer) return;

                try {
                    await navigator.clipboard.writeText(state.lastAnswer);
                    if (window.revisionHubToast) {
                        window.revisionHubToast('success', @json(__('Answer copied to clipboard.')));
                    }
                } catch (error) {
                    if (window.revisionHubToast) {
                        window.revisionHubToast('error', @json(__('Unable to copy the answer.')));
                    }
                }
            });

            searchInput?.addEventListener('input', () => {
                const term = searchInput.value.toLowerCase().trim();
                historyList?.querySelectorAll('.ai-chat-history-item').forEach((item) => {
                    const title = (item.dataset.title || item.textContent || '').toLowerCase();
                    item.style.display = title.includes(term) ? '' : 'none';
                });
            });

            scrollThreadToBottom();
        })();
    </script>
@endpush

