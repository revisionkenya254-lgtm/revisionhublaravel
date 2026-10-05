@extends('frontend.student-dashboard.layouts.master')

@section('meta_title', __('AI Chat'))

@php
    $currentUser = auth()->user();
    $currentUserName = $currentUser?->name ?: __('Student');
    $currentUserInitial = strtoupper(mb_substr($currentUserName, 0, 1));
    $aiCreditsUrl = route('ai-chat.credits');
    $chatBaseUrl = route('student.ai-chat.index');
    $isEmbedded = request()->boolean('embedded');
    $returnToUrl = $returnToUrl ?? (request()->query('return_to') ?: null);
    $chatQuery = $returnToUrl ? ['return_to' => $returnToUrl] : [];
    $chatUrlForConversation = function ($conversationId = null) use ($chatQuery) {
        $params = $chatQuery;

        if ($conversationId) {
            $params['conversation'] = $conversationId;
        }

        return route('student.ai-chat.index', $params);
    };
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

@section('dashboard-sidebar')
    @include('frontend.ai-chat.partials.sidebar')
@endsection

@section('dashboard-contents')
    <div class="ai-chat-shell ai-chat-shell--chatgpt">
        @include('frontend.ai-chat.partials.main')
    </div>
    <div class="ai-chat-sidebar-backdrop" id="ai-chat-sidebar-backdrop" aria-hidden="true"></div>
@endsection

@push('styles')
    <style>
        .ai-chat-sidebar {
            background: linear-gradient(180deg, rgba(84, 73, 255, 0.08), rgba(255, 255, 255, 0.92));
            border: 1px solid rgba(79, 70, 229, 0.12);
            border-radius: 28px;
            padding: 22px;
            box-shadow: 0 20px 60px rgba(17, 24, 39, 0.08);
            backdrop-filter: blur(16px);
        }

        .ai-chat-sidebar__header,
        .ai-chat-sidebar__credits-top,
        .ai-chat-main__header,
        .ai-chat-panel__head,
        .ai-chat-composer__footer,
        .ai-chat-message__meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
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
        .ai-chat-panel__head h5,
        .ai-chat-main__header h4 {
            margin: 0;
            font-weight: 700;
            color: #111827;
        }

        .ai-chat-new-btn,
        .ai-chat-mini-btn,
        .ai-chat-panel__retry,
        .ai-chat-chip,
        .ai-chat-style-pills button,
        .ai-chat-send-btn,
        .ai-chat-icon-btn {
            border: 0;
            border-radius: 16px;
            transition: all .18s ease;
        }

        .ai-chat-new-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: linear-gradient(135deg, #6b4efc, #8b5cf6);
            color: #fff;
            font-weight: 700;
            box-shadow: 0 14px 30px rgba(107, 78, 252, 0.25);
        }

        .ai-chat-new-btn:hover,
        .ai-chat-send-btn:hover,
        .ai-chat-panel__retry:hover,
        .ai-chat-mini-btn:hover,
        .ai-chat-chip:hover,
        .ai-chat-style-pills button:hover {
            transform: translateY(-1px);
        }

        .ai-chat-sidebar__search {
            margin: 18px 0 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 16px;
            border: 1px solid rgba(99, 102, 241, 0.12);
            background: rgba(255, 255, 255, 0.78);
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

        .ai-chat-sidebar__search input,
        .ai-chat-field select,
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
            gap: 10px;
            max-height: 52vh;
            overflow: auto;
            padding-right: 4px;
        }

        .ai-chat-history-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 14px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(148, 163, 184, 0.14);
            color: #111827;
        }

        .ai-chat-history-item.is-active {
            background: linear-gradient(135deg, rgba(107, 78, 252, 0.18), rgba(168, 85, 247, 0.12));
            border-color: rgba(107, 78, 252, 0.22);
        }

        .ai-chat-history-item__body {
            display: grid;
            gap: 3px;
            min-width: 0;
        }

        .ai-chat-history-item__body strong,
        .ai-chat-history-item__body span,
        .ai-chat-sidebar__empty p,
        .ai-chat-side-note p,
        .ai-chat-main__header p,
        .ai-chat-main__hero p,
        .ai-chat-sidebar__credits small {
            margin: 0;
            color: #6b7280;
        }

        .ai-chat-sidebar__empty,
        .ai-chat-empty {
            text-align: center;
            padding: 24px 16px;
        }

        .ai-chat-sidebar__empty-icon,
        .ai-chat-empty__icon {
            width: 54px;
            height: 54px;
            margin: 0 auto 10px;
            display: grid;
            place-items: center;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(107, 78, 252, 0.18), rgba(168, 85, 247, 0.14));
            color: #6b4efc;
        }

        .ai-chat-sidebar__credits {
            margin-top: 18px;
            padding: 18px;
            border-radius: 20px;
            border: 1px solid rgba(99, 102, 241, 0.12);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.94), rgba(243, 244, 255, 0.92));
        }

        .ai-chat-credit-bar {
            width: 100%;
            height: 8px;
            margin: 12px 0 8px;
            border-radius: 999px;
            background: rgba(107, 78, 252, 0.12);
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
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 20px;
            align-items: start;
        }

        .ai-chat-main,
        .ai-chat-panel {
            border-radius: 28px;
            border: 1px solid rgba(148, 163, 184, 0.12);
            background: rgba(255, 255, 255, 0.88);
            box-shadow: 0 20px 60px rgba(17, 24, 39, 0.06);
            backdrop-filter: blur(16px);
        }

        .ai-chat-main {
            padding: 22px;
        }

        .ai-chat-shell--chatgpt .ai-chat-main {
            overflow: auto;
        }

        .ai-chat-main__header {
            padding-bottom: 18px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.12);
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

        .ai-chat-main__hero {
            padding: 24px 0 18px;
        }

        .ai-chat-main__hero h2 {
            margin: 0 0 6px;
            font-size: clamp(24px, 2.5vw, 34px);
            color: #111827;
            font-weight: 800;
        }

        .ai-chat-quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .ai-chat-chip {
            padding: 11px 14px;
            border: 1px solid rgba(99, 102, 241, 0.14);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.95), rgba(244, 243, 255, 0.94));
            color: #3730a3;
            font-weight: 600;
        }

        .ai-chat-thread {
            display: grid;
            gap: 14px;
            min-height: 420px;
            max-height: 62vh;
            overflow: auto;
            padding: 6px 2px 12px;
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
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid rgba(148, 163, 184, 0.12);
        }

        .ai-chat-composer__row {
            display: grid;
            grid-template-columns: 46px 46px minmax(0, 1fr) 56px;
            gap: 10px;
            align-items: end;
            padding: 10px;
            border-radius: 22px;
            background: rgba(248, 250, 252, 0.92);
            border: 1px solid rgba(148, 163, 184, 0.14);
        }

        .ai-chat-icon-btn,
        .ai-chat-send-btn {
            height: 46px;
            width: 46px;
            display: grid;
            place-items: center;
        }

        .ai-chat-icon-btn {
            background: rgba(107, 78, 252, 0.09);
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
            margin-top: 12px;
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

        .ai-chat-settings {
            position: sticky;
            top: 24px;
        }

        .ai-chat-panel {
            padding: 18px;
        }

        .ai-chat-panel__head p,
        .ai-chat-style-group span,
        .ai-chat-field span {
            margin: 0 0 4px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #6b7280;
        }

        .ai-chat-field,
        .ai-chat-style-group {
            display: grid;
            gap: 8px;
            margin-top: 16px;
        }

        .ai-chat-field select {
            padding: 13px 14px;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: #fff;
        }

        .ai-chat-style-pills {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .ai-chat-style-pills button {
            padding: 12px 10px;
            background: #fff;
            border: 1px solid rgba(148, 163, 184, 0.16);
            color: #374151;
            font-weight: 600;
        }

        .ai-chat-style-pills button.is-active {
            background: linear-gradient(135deg, rgba(107, 78, 252, 0.18), rgba(168, 85, 247, 0.14));
            border-color: rgba(107, 78, 252, 0.24);
            color: #5b21b6;
        }

        .ai-chat-side-note {
            margin-top: 16px;
            padding: 16px;
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(107, 78, 252, 0.08), rgba(255, 255, 255, 0.92));
            border: 1px solid rgba(107, 78, 252, 0.12);
        }

        .ai-chat-side-note p {
            color: #4b5563;
        }

        .ai-chat-panel__footer {
            margin-top: 18px;
        }

        .ai-chat-panel__retry {
            width: 100%;
            padding: 12px 14px;
            background: rgba(17, 24, 39, 0.92);
            color: #fff;
            font-weight: 700;
            opacity: .95;
        }

        .ai-chat-panel__retry:disabled {
            opacity: .5;
            cursor: not-allowed;
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
            .ai-chat-shell {
                grid-template-columns: 1fr;
            }

            .ai-chat-settings {
                position: static;
            }
        }

        @media (max-width: 767.98px) {
            .ai-chat-sidebar,
            .ai-chat-main,
            .ai-chat-panel {
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
                max-height: 320px;
            }
        }

        .ai-chat-sidebar--chatgpt,
        .ai-chat-shell--chatgpt {
            background: transparent;
        }

        .ai-chat-sidebar--chatgpt {
            padding: 16px 14px 14px;
            border-radius: 0;
            border-right: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: none;
            background: linear-gradient(180deg, rgba(247, 249, 255, 0.98), rgba(255, 255, 255, 0.96));
            overflow: auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
            max-height: calc(var(--ai-chat-viewport-height, calc(100dvh - 140px)) - 48px);
        }

        .ai-chat-shell--chatgpt {
            display: grid;
            grid-template-columns: minmax(0, 1fr) clamp(260px, 24vw, 320px);
            gap: 0;
            align-items: stretch;
            border: 0;
            border-radius: 0;
            overflow: visible;
            background: transparent;
            box-shadow: none;
        }

        .ai-chat-main,
        .ai-chat-sidebar--chatgpt {
            backdrop-filter: none;
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
            background: linear-gradient(135deg, rgba(49, 94, 251, 0.18), rgba(49, 94, 251, 0.10));
            border-color: rgba(49, 94, 251, 0.22);
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

        .ai-chat-user-card--compact {
            flex: 1 1 auto;
            min-width: 0;
            padding: 7px 9px 7px 7px;
            border-radius: 999px;
            gap: 6px;
            grid-template-columns: 34px minmax(0, 1fr) auto;
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.03);
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
            margin-bottom: 18px;
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
            min-height: 420px;
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
            padding-top: 0;
            border-top: 0;
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
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
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

        .ai-chat-sidebar__list {
            max-height: none;
        }

        .ai-chat-sidebar--chatgpt,
        .ai-chat-shell--chatgpt {
            font-size: 14px;
        }

        .ai-chat-brand__name {
            font-size: 16px;
        }

        .ai-chat-main__heading h4 {
            font-size: clamp(18px, 1.7vw, 26px);
        }

        .ai-chat-main__heading p {
            margin-top: 6px;
        }

        .ai-chat-main__status-badge {
            font-size: 12px;
        }

        .ai-chat-main__hero {
            padding: 18px 0 14px;
        }

        .ai-chat-main__hero h2 {
            font-size: clamp(20px, 2vw, 28px);
        }

        .ai-chat-quick-actions {
            gap: 8px;
            margin-top: 14px;
        }

        .ai-chat-chip {
            padding: 8px 12px;
            font-size: 13px;
        }

        .ai-chat-sidebar__search {
            margin: 14px 0 10px;
            padding: 10px 12px;
            gap: 8px;
            border-radius: 14px;
        }

        .ai-chat-sidebar__list {
            gap: 8px;
        }

        .ai-chat-history-item {
            padding: 11px 12px;
            border-radius: 16px;
        }

        .ai-chat-sidebar__footer {
            gap: 10px;
            margin-top: 14px;
        }

        .ai-chat-user-card {
            grid-template-columns: 38px minmax(0, 1fr) auto;
            gap: 8px;
            padding: 10px;
            border-radius: 16px;
        }

        .ai-chat-user-card__avatar {
            width: 38px;
            height: 38px;
        }

        .ai-chat-user-card__body strong {
            font-size: 13px;
        }

        .ai-chat-user-card__body span {
            font-size: 11px;
        }

        .ai-chat-thread {
            gap: 12px;
            padding: 0 2px 10px;
            min-height: 0;
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

        .ai-chat-empty {
            max-width: 520px;
        }

        .ai-chat-empty h5 {
            font-size: clamp(20px, 1.8vw, 26px);
        }

        .ai-chat-composer {
            margin-top: 14px;
        }

        .ai-chat-composer__surface {
            gap: 10px;
            padding: 12px;
            border-radius: 22px;
        }

        .ai-chat-composer__chips {
            gap: 6px;
        }

        .ai-chat-composer__row {
            gap: 8px;
        }

        .ai-chat-icon-btn,
        .ai-chat-send-btn {
            width: 40px;
            height: 40px;
        }

        .ai-chat-mini-btn {
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

            .ai-chat-sidebar--chatgpt {
                order: 2;
                border-right: 0;
                border-bottom: 1px solid rgba(15, 23, 42, 0.08);
                min-height: auto;
                height: auto;
                overflow: visible;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__header {
                margin-bottom: 10px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__history {
                flex: 0 0 auto;
                max-height: 180px;
                overflow: auto;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__footer {
                margin-top: 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-composer {
                margin-top: 8px;
            }

            .dashboard__area.ai-chat-dashboard-shell .row > .col-lg-3,
            .dashboard__area.ai-chat-dashboard-shell .row > .col-lg-9,
            .dashboard__area.ai-chat-dashboard-shell.ai-chat-dashboard-sidebar-collapsed .row > .col-lg-3,
            .dashboard__area.ai-chat-dashboard-shell.ai-chat-dashboard-sidebar-collapsed .row > .col-lg-9 {
                flex: 0 0 100%;
                max-width: 100%;
            }

            .dashboard__area.ai-chat-dashboard-shell .row > .col-lg-3 {
                order: 2;
            }

            .dashboard__area.ai-chat-dashboard-shell .row > .col-lg-9 {
                order: 1;
            }
        }

        @media (min-width: 1200px) {
            .ai-chat-shell--chatgpt .ai-chat-main {
                max-height: calc(var(--ai-chat-viewport-height, calc(100dvh - 140px)) - 48px);
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
            .ai-chat-sidebar--chatgpt,
            .ai-chat-shell--chatgpt {
                min-height: 0;
                gap: 10px;
                padding-bottom: 10px;
            }

            .ai-chat-sidebar--chatgpt,
            .ai-chat-shell--chatgpt .ai-chat-main {
                padding: 10px;
            }

            .ai-chat-sidebar--chatgpt {
                gap: 8px;
                border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__history {
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

            .ai-chat-image-tools__selectors {
                grid-template-columns: 1fr;
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

        .ai-chat-sidebar--chatgpt {
            min-height: calc(var(--ai-chat-viewport-height, calc(100dvh - 140px)) - 18px);
            height: auto;
            overflow: hidden;
            padding: clamp(8px, 1vw, 12px) 14px 14px;
        }

        .ai-chat-sidebar--chatgpt .ai-chat-sidebar__history {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
            padding-right: 4px;
        }

        .ai-chat-sidebar--chatgpt .ai-chat-sidebar__footer {
            margin-top: auto;
            flex: 0 0 auto;
        }

        .ai-chat-shell--chatgpt {
            min-height: calc(var(--ai-chat-viewport-height, calc(100dvh - 140px)) - 18px);
            height: auto;
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
        }

        .ai-chat-shell--chatgpt .ai-chat-composer {
            flex: 0 0 auto;
            margin-top: 10px;
            padding-top: 0;
            border-top: 0;
        }

        .dashboard__area.ai-chat-dashboard-shell .row {
            align-items: stretch;
        }

        .dashboard__area.ai-chat-dashboard-shell .row > .col-lg-3,
        .dashboard__area.ai-chat-dashboard-shell .row > .col-lg-9 {
            min-width: 0;
            transition: max-width 0.22s ease, flex-basis 0.22s ease, flex-grow 0.22s ease;
        }

        .dashboard__area.ai-chat-dashboard-shell .row > .col-lg-3 {
            flex: 0 0 296px;
            max-width: 296px;
        }

        .dashboard__area.ai-chat-dashboard-shell .row > .col-lg-9 {
            flex: 1 1 0;
            max-width: calc(100% - 296px);
        }

        .dashboard__area.ai-chat-dashboard-shell.ai-chat-dashboard-sidebar-collapsed .row > .col-lg-3 {
            flex-basis: 74px;
            max-width: 74px;
        }

        .dashboard__area.ai-chat-dashboard-shell.ai-chat-dashboard-sidebar-collapsed .row > .col-lg-9 {
            max-width: calc(100% - 74px);
        }

        .ai-chat-sidebar--chatgpt {
            width: 100%;
            transition: width 0.22s ease, min-width 0.22s ease, max-width 0.22s ease, padding 0.22s ease;
        }

        .ai-chat-sidebar--chatgpt.is-collapsed {
            width: 74px;
            min-width: 74px;
            max-width: 74px;
            padding-left: 10px;
            padding-right: 10px;
            overflow: hidden;
        }

        .ai-chat-sidebar--chatgpt.is-collapsed .ai-chat-sidebar__search,
        .ai-chat-sidebar--chatgpt.is-collapsed .ai-chat-sidebar__history,
        .ai-chat-sidebar--chatgpt.is-collapsed .ai-chat-sidebar__footer,
        .ai-chat-sidebar--chatgpt.is-collapsed .ai-chat-new-btn {
            display: none;
        }

        .ai-chat-sidebar--chatgpt.is-collapsed .ai-chat-sidebar__header-actions {
            justify-content: center;
            width: 100%;
        }

        .ai-chat-sidebar--chatgpt.is-collapsed .ai-chat-sidebar__header-actions .ai-chat-new-btn--compact {
            display: none;
        }

        .ai-chat-sidebar--chatgpt.is-collapsed .ai-chat-sidebar__header-actions .ai-chat-user-card {
            display: none;
        }

        .ai-chat-sidebar--chatgpt.is-collapsed .ai-chat-usage-card {
            display: none;
        }

        .ai-chat-sidebar--chatgpt.is-collapsed .ai-chat-sidebar__header-actions button {
            width: 42px;
            height: 42px;
            padding: 0;
            justify-content: center;
        }

        .ai-chat-sidebar--chatgpt.is-collapsed .ai-chat-sidebar__header-actions button span {
            display: none;
        }

        .ai-chat-sidebar--chatgpt.is-collapsed .ai-chat-sidebar__header-actions [data-sidebar-toggle-icon] {
            transform: rotate(180deg);
        }

        @media (max-width: 991.98px) {
            .dashboard__area.ai-chat-dashboard-shell .row > .col-lg-3,
            .dashboard__area.ai-chat-dashboard-shell .row > .col-lg-9,
            .dashboard__area.ai-chat-dashboard-shell.ai-chat-dashboard-sidebar-collapsed .row > .col-lg-3,
            .dashboard__area.ai-chat-dashboard-shell.ai-chat-dashboard-sidebar-collapsed .row > .col-lg-9 {
                flex: 0 0 100%;
                max-width: 100%;
            }

            .ai-chat-sidebar--chatgpt,
            .ai-chat-sidebar--chatgpt.is-collapsed {
                width: 100%;
                min-width: 0;
                max-width: none;
            }
        }

        .ai-chat-main--empty {
            display: grid;
            grid-template-rows: auto auto;
            align-items: start;
            justify-content: stretch;
            align-content: start;
            justify-items: stretch;
            gap: 16px;
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
            gap: 14px;
            text-align: center;
            margin: 0;
        }

        .ai-chat-main__hero h2 {
            margin: 0;
            font-size: clamp(24px, 2vw, 34px);
            line-height: 1.15;
            letter-spacing: -0.03em;
            color: #111827;
            font-weight: 800;
        }

        .ai-chat-main__hero .ai-chat-style-pills {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 999px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.04);
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

        @media (max-width: 1199.98px) {
            .dashboard__area.ai-chat-dashboard-shell .row {
                row-gap: 12px;
            }

            .dashboard__area.ai-chat-dashboard-shell .row > .col-lg-3,
            .dashboard__area.ai-chat-dashboard-shell .row > .col-lg-9,
            .dashboard__area.ai-chat-dashboard-shell.ai-chat-dashboard-sidebar-collapsed .row > .col-lg-3,
            .dashboard__area.ai-chat-dashboard-shell.ai-chat-dashboard-sidebar-collapsed .row > .col-lg-9 {
                flex: 0 0 100%;
                max-width: 100%;
            }

            .dashboard__area.ai-chat-dashboard-shell .row > .col-lg-3 {
                order: 2;
            }

            .dashboard__area.ai-chat-dashboard-shell .row > .col-lg-9 {
                order: 1;
            }

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

            .ai-chat-sidebar--chatgpt {
                order: 2;
                min-height: auto;
                height: auto;
                overflow: visible;
                padding: 12px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__history {
                flex: 0 0 auto;
                max-height: 180px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__footer {
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
            .ai-chat-sidebar--chatgpt,
            .ai-chat-shell--chatgpt {
                gap: 8px;
                padding-bottom: 8px;
            }

            .ai-chat-sidebar--chatgpt,
            .ai-chat-shell--chatgpt .ai-chat-main {
                padding: 10px;
            }

            .ai-chat-shell--chatgpt .ai-chat-main {
                padding-top: 12px;
                padding-bottom: 8px;
            }

            .ai-chat-sidebar--chatgpt {
                gap: 8px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__history {
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
            .ai-chat-sidebar--chatgpt {
                padding: 10px 10px 12px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__header {
                margin-bottom: 6px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__header-actions {
                gap: 6px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__header-actions .ai-chat-new-btn--compact {
                padding: 7px 10px;
                font-size: 12px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__header-actions button {
                width: 38px;
                height: 38px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__history {
                max-height: 160px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-history-group {
                gap: 6px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-history-group__label {
                padding: 0 2px;
                font-size: 11px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-history-item {
                padding: 10px 10px 10px 12px;
                border-radius: 14px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-history-item__body strong {
                font-size: 13px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-history-item__body span {
                font-size: 11px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__footer {
                margin-top: 8px;
                gap: 8px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-user-card {
                grid-template-columns: 34px minmax(0, 1fr) auto;
                gap: 8px;
                padding: 10px;
                border-radius: 14px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-user-card__avatar {
                width: 34px;
                height: 34px;
                font-size: 12px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-user-card__body strong {
                font-size: 13px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-user-card__body span {
                font-size: 11px;
            }
        }

        @media (max-width: 767.98px) {
            .ai-chat-sidebar--chatgpt {
                padding: 8px 8px 10px;
                gap: 8px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__header-actions #ai-chat-sidebar-toggle.is-disabled {
                pointer-events: none;
                opacity: 0.45;
                cursor: default;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__header {
                margin-bottom: 4px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__header-actions {
                gap: 4px;
                width: 100%;
                justify-content: flex-start;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__header-actions .ai-chat-new-btn--compact {
                padding: 6px 9px;
                font-size: 11px;
                min-height: 34px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__header-actions button {
                width: 34px;
                height: 34px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__header-actions .ai-chat-user-card--compact {
                padding: 6px 8px 6px 6px;
                gap: 6px;
                grid-template-columns: 28px minmax(0, 1fr) auto;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__header-actions .ai-chat-user-card--compact .ai-chat-user-card__avatar {
                width: 28px;
                height: 28px;
                font-size: 11px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__header-actions .ai-chat-user-card--compact .ai-chat-user-card__body strong,
            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__header-actions .ai-chat-user-card--compact .ai-chat-user-card__body span {
                font-size: 11px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__history {
                max-height: 120px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-history-group {
                gap: 5px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-history-item {
                padding: 9px 9px 9px 11px;
                border-radius: 12px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-history-item__body strong {
                font-size: 12px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-history-item__body span {
                font-size: 10px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-sidebar__footer {
                margin-top: 6px;
                gap: 6px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-user-card {
                grid-template-columns: 32px minmax(0, 1fr) auto;
                gap: 6px;
                padding: 8px 9px;
                border-radius: 12px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-user-card__avatar {
                width: 32px;
                height: 32px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-user-card__body strong {
                font-size: 12px;
            }

            .ai-chat-sidebar--chatgpt .ai-chat-user-card__body span {
                font-size: 10px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (() => {
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
            const searchToggle = document.getElementById('ai-chat-search-toggle');
            const searchPanel = document.getElementById('ai-chat-search-panel');
            const searchInput = document.getElementById('ai-chat-search');
            const copyLastBtn = document.getElementById('ai-chat-copy-last');
            const retryBtn = document.getElementById('ai-chat-retry');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const dashboardArea = document.querySelector('.dashboard__area');
            const returnToUrl = @json($returnToUrl);
            const isEmbedded = @json($isEmbedded);

            const endpoints = {
                conversations: new URL(@json(route('student.ai-chat.conversations.store')), window.location.origin).href,
                streamBase: new URL(@json(route('student.ai-chat.conversations.store')), window.location.origin).href,
            };
            const chatBaseUrl = @json($chatBaseUrl);
            const conversationUrl = (conversationId) => {
                const url = new URL(`${@json($chatBaseUrl)}/${encodeURIComponent(conversationId)}`, window.location.origin);

                if (returnToUrl) {
                    url.searchParams.set('return_to', returnToUrl);
                }

                if (isEmbedded) {
                    url.searchParams.set('embedded', '1');
                }

                return url.href;
            };
            const conversationStreamUrl = (conversationId) => `${endpoints.streamBase}/${encodeURIComponent(conversationId)}/stream`;
            const setRetryButtonDisabled = (disabled) => {
                if (retryBtn) {
                    retryBtn.disabled = disabled;
                }
            };

            const state = {
                conversationId: conversationInput.value || null,
                lastPrompt: '',
                lastAnswer: '',
                isBusy: false,
                retryPayload: null,
            };
            const sidebarStorageKey = 'revision-ai-chat-sidebar-collapsed';
            const canCollapseSidebar = window.matchMedia('(min-width: 1200px)').matches;
            const mobileSidebarLocked = window.matchMedia('(max-width: 767.98px)');

            const scrollThreadToBottom = () => {
                thread.scrollTop = thread.scrollHeight;
            };

            const setSidebarCollapsed = (collapsed, persist = true) => {
                const shell = sidebar?.closest('.ai-chat-shell--chatgpt');
                if (!sidebar || !shell || !dashboardArea) return;

                shell.classList.toggle('sidebar-collapsed', collapsed);
                sidebar.classList.toggle('is-collapsed', collapsed);
                dashboardArea.classList.toggle('ai-chat-dashboard-shell', true);
                dashboardArea.classList.toggle('ai-chat-dashboard-sidebar-collapsed', collapsed);
                sidebarToggle?.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                sidebarToggle?.setAttribute('aria-label', collapsed ? @json(__('Expand sidebar')) : @json(__('Collapse sidebar')));
                if (sidebarToggleIcon) {
                    sidebarToggleIcon.className = collapsed ? 'fas fa-chevron-right' : 'fas fa-chevron-left';
                }
                if (persist) {
                    localStorage.setItem(sidebarStorageKey, collapsed ? '1' : '0');
                }
            };

            const syncSidebarState = () => {
                if (mobileSidebarLocked.matches) {
                    setSidebarCollapsed(true, false);
                    sidebarToggle?.setAttribute('aria-disabled', 'true');
                    sidebarToggle?.setAttribute('tabindex', '-1');
                    sidebarToggle?.classList.add('is-disabled');
                    return;
                }

                sidebarToggle?.removeAttribute('aria-disabled');
                sidebarToggle?.removeAttribute('tabindex');
                sidebarToggle?.classList.remove('is-disabled');
                setSidebarCollapsed(canCollapseSidebar && localStorage.getItem(sidebarStorageKey) === '1');
            };

            if (dashboardArea) {
                dashboardArea.classList.add('ai-chat-dashboard-shell');
            }

            syncSidebarState();
            window.addEventListener('resize', syncSidebarState);
            mobileSidebarLocked.addEventListener?.('change', syncSidebarState);

            sidebarToggle?.addEventListener('click', () => {
                if (!canCollapseSidebar || mobileSidebarLocked.matches) return;
                const shell = sidebar?.closest('.ai-chat-shell--chatgpt');
                const collapsed = shell?.classList.contains('sidebar-collapsed');
                setSidebarCollapsed(!collapsed);
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
                setActiveConversation(conversation.public_id || conversation.id, conversation.title);
                window.history.replaceState({}, '', data.redirect || conversationUrl(conversation.public_id || conversation.id));

                if (historyList) {
                    const empty = historyList.querySelector('.ai-chat-sidebar__empty');
                    if (empty) {
                        empty.remove();
                    }

                    const item = document.createElement('a');
                    item.href = data.redirect;
                    item.className = 'ai-chat-history-item is-active';
                    item.dataset.title = conversation.title;
                    item.innerHTML = `
                        <div class="ai-chat-history-item__body">
                            <strong>${escapeHtml(conversation.title)}</strong>
                            <span>${@json(__('Just now'))}</span>
                        </div>
                        <span class="ai-chat-history-item__action">...</span>
                    `;

                    const historyTarget = historyList.querySelector('.ai-chat-history-group .ai-chat-sidebar__list') || historyList;
                    historyTarget.prepend(item);
                    historyList.querySelectorAll('.ai-chat-history-item').forEach((node) => {
                        node.classList.remove('is-active');
                    });
                    item.classList.add('is-active');
                }

                return conversation.public_id || conversation.id;
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

            const updateLastAnswerCache = (content) => {
                state.lastAnswer = content;
                setRetryButtonDisabled(!content);
            };

            const sendMessage = async (rawPrompt, options = {}) => {
                const prompt = String(rawPrompt || '').trim();
                if (!prompt || state.isBusy) return;

                state.isBusy = true;
                state.lastPrompt = prompt;
                setRetryButtonDisabled(true);
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
                    payload.append('style', options.style || 'default');

                    const response = await fetch(conversationStreamUrl(conversationId), {
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
                            assistantNode.contentBox.textContent = answer;
                            assistantNode.bubble.classList.remove('is-streaming');
                            assistantNode.metaBox.innerHTML = `<span>${new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}</span>`;
                            updateLastAnswerCache(answer);
                            scrollThreadToBottom();
                        }

                        if (eventName === 'error') {
                            throw new Error(data?.message || @json(__('Something went wrong.')));
                        }
                    });

                    if (!state.conversationId) {
                        setActiveConversation(conversationId);
                    }
                } catch (error) {
                    assistantNode.contentBox.textContent = error.message || @json(__('Something went wrong.'));
                    assistantNode.bubble.classList.remove('is-streaming');
                    assistantNode.metaBox.innerHTML = `<span>${@json(__('Failed'))}</span>`;
                    if (window.revisionHubToast) {
                        window.revisionHubToast('error', error.message || @json(__('Something went wrong.')));
                    }
                } finally {
                    state.isBusy = false;
                    sendBtn.disabled = false;
                    setRetryButtonDisabled(!state.lastAnswer);
                    scrollThreadToBottom();
                }
            };

            const generateImage = async (rawPrompt) => {
                const prompt = String(rawPrompt || '').trim();
                if (!prompt || state.isBusy) return;

                state.isBusy = true;
                state.lastPrompt = prompt;
                setRetryButtonDisabled(true);
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

                    const response = await fetch(conversationStreamUrl(conversationId), {
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
                    assistantNode.contentBox.textContent = error.message || @json(__('Something went wrong.'));
                    assistantNode.bubble.classList.remove('is-streaming');
                    assistantNode.metaBox.innerHTML = `<span>${@json(__('Failed'))}</span>`;
                    if (window.revisionHubToast) {
                        window.revisionHubToast('error', error.message || @json(__('Something went wrong.')));
                    }
                } finally {
                    state.isBusy = false;
                    sendBtn.disabled = false;
                    setRetryButtonDisabled(!state.lastAnswer);
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
                window.location.href = returnToUrl
                    ? `${chatBaseUrl}?return_to=${encodeURIComponent(returnToUrl)}`
                    : chatBaseUrl;
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

            retryBtn?.addEventListener('click', async () => {
                if (!state.lastPrompt || state.isBusy) return;
                await sendMessage(state.lastPrompt, {
                    style: 'default',
                });
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


