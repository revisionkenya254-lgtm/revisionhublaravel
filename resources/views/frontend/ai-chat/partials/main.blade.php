<div class="ai-chat-main {{ $messages->isEmpty() ? 'ai-chat-main--empty' : '' }}">
    <div class="ai-chat-main__mobile-bar ai-chat-main__mobile-bar--inside">
        <button type="button" class="ai-chat-mobile-drawer-toggle" id="ai-chat-mobile-sidebar-toggle" aria-controls="ai-chat-sidebar" aria-expanded="false">
            <i class="fas fa-comments"></i>
            <span>{{ __('Chats') }}</span>
        </button>
    </div>
    @if ($messages->isNotEmpty())
        <div class="ai-chat-main__header">
            <div class="ai-chat-main__heading">
                <h4>{{ $activeConversationTitle }}</h4>
                <p>
                    <span class="ai-chat-main__status-badge">
                        <span class="ai-chat-status-dot"></span>
                        {{ __('Instant') }}
                    </span>
                </p>
            </div>
            <button type="button" class="ai-chat-icon-btn ai-chat-icon-btn--ghost ai-chat-share-btn" aria-label="{{ __('Share') }}">
                <i class="fas fa-share"></i>
            </button>
        </div>
    @else
        <div class="ai-chat-main__hero">
            <h2>{{ __('Start chatting with AI') }}</h2>
            <p class="ai-chat-main__hero-copy">
                {{ __('Ask a question, break down a topic, or use one of the quick prompts below to get started faster.') }}
            </p>
            <div class="ai-chat-main__starter-prompts">
                <button type="button" class="ai-chat-starter-pill" data-prompt="{{ __('Explain this topic in simple terms with examples.') }}">{{ __('Explain simply') }}</button>
                <button type="button" class="ai-chat-starter-pill" data-prompt="{{ __('Summarize this topic into concise revision notes.') }}">{{ __('Summarize') }}</button>
                <button type="button" class="ai-chat-starter-pill" data-prompt="{{ __('Give me five practice questions on this topic.') }}">{{ __('Practice questions') }}</button>
            </div>
        </div>
    @endif

    <div class="ai-chat-thread" id="ai-chat-thread" data-empty="{{ $messages->isEmpty() ? '1' : '0' }}">
        @forelse ($messages as $message)
            @php($messageMetadata = $message->metadata ?? [])
            <div class="ai-chat-message {{ $message->role === 'user' ? 'is-user' : 'is-assistant' }}">
                @if ($message->role !== 'user')
                    <div class="ai-chat-message__avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                @endif
                <div class="ai-chat-message__bubble">
                    @if (data_get($messageMetadata, 'type') === 'image' && data_get($messageMetadata, 'image_url'))
                        <div class="ai-chat-message__content">
                            <img class="ai-chat-image"
                                src="{{ data_get($messageMetadata, 'image_url') }}"
                                alt="{{ data_get($messageMetadata, 'revised_prompt') ?: $message->content }}">
                        </div>
                        @if (data_get($messageMetadata, 'revised_prompt'))
                            <div class="ai-chat-image__caption">
                                {{ data_get($messageMetadata, 'revised_prompt') }}
                            </div>
                        @endif
                        <div class="ai-chat-media-actions">
                            <button type="button" class="ai-chat-media-action" data-image-action="download" data-image-url="{{ data_get($messageMetadata, 'image_url') }}">{{ __('Download') }}</button>
                            <button type="button" class="ai-chat-media-action" data-image-action="share" data-image-url="{{ data_get($messageMetadata, 'image_url') }}" data-image-prompt="{{ data_get($messageMetadata, 'revised_prompt') ?: $message->content }}">{{ __('Share') }}</button>
                        </div>
                    @else
                        <div class="ai-chat-message__content">{!! nl2br(e($message->content)) !!}</div>
                    @endif
                    <div class="ai-chat-message__meta">
                        <span>{{ $message->created_at?->format('g:i A') }}</span>
                        @if ($message->provider)
                            <span>{{ strtoupper($message->provider) }}</span>
                        @endif
                    </div>
                </div>
                @if ($message->role === 'user')
                    <div class="ai-chat-message__avatar ai-chat-message__avatar--user">
                        <i class="fas fa-user"></i>
                    </div>
                @endif
            </div>
        @empty
        @endforelse
    </div>

    <form id="ai-chat-form" class="ai-chat-composer">
        @csrf
        <input type="hidden" id="ai-chat-conversation-id" value="{{ $activeConversation?->public_id ?? $activeConversation?->id }}">
        <div class="ai-chat-composer__surface">
            <div class="ai-chat-composer__row">
                <textarea id="ai-chat-prompt" rows="1" placeholder="{{ __('Message ChatGPT') }}"></textarea>
                <div class="ai-chat-composer__actions">
                    <button type="button" class="ai-chat-icon-btn ai-chat-icon-btn--ghost" id="ai-chat-attach" aria-label="{{ __('Attach') }}">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    <button type="button" class="ai-chat-icon-btn ai-chat-icon-btn--ghost" id="ai-chat-image" aria-label="{{ __('Generate image') }}">
                        <i class="fas fa-microphone"></i>
                    </button>
                    <button type="submit" class="ai-chat-send-btn" id="ai-chat-send" aria-label="{{ __('Send') }}">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
