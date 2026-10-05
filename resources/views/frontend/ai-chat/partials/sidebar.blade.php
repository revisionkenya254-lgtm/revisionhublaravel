<div class="ai-chat-sidebar ai-chat-sidebar--chatgpt" id="ai-chat-sidebar">
    <div class="ai-chat-sidebar__scroll">
        <div class="ai-chat-sidebar__header">
            <div class="ai-chat-sidebar__header-actions">
                <button type="button" class="ai-chat-icon-btn ai-chat-icon-btn--ghost" id="ai-chat-sidebar-toggle" aria-label="{{ __('Collapse sidebar') }}" aria-expanded="true" aria-controls="ai-chat-sidebar">
                    <i class="fas fa-chevron-left" data-sidebar-toggle-icon></i>
                </button>
                <button type="button" class="ai-chat-icon-btn ai-chat-icon-btn--ghost" id="ai-chat-search-toggle" aria-label="{{ __('Search') }}" aria-expanded="false">
                    <i class="fas fa-search"></i>
                </button>
                <button type="button" class="ai-chat-new-btn ai-chat-new-btn--compact" id="ai-chat-new-button">
                    <i class="fas fa-plus-circle"></i>
                    <span>{{ __('New chat') }}</span>
                </button>
            </div>
        </div>

        <div class="ai-chat-sidebar__search ai-chat-sidebar__search--collapsed" id="ai-chat-search-panel">
            <i class="fas fa-search"></i>
            <input type="search" id="ai-chat-search" placeholder="{{ __('Search chats') }}">
        </div>

        <div class="ai-chat-sidebar__history" id="ai-chat-history-list">
            @if ($todayConversations->isNotEmpty() || $conversations->isEmpty())
                <div class="ai-chat-history-group">
                    <p class="ai-chat-history-group__label">{{ __('Today') }}</p>
                    <div class="ai-chat-sidebar__list">
                        @if ($todayConversations->isNotEmpty())
                            @foreach ($todayConversations as $conversation)
                                <a href="{{ $chatUrlForConversation($conversation->public_id ?? $conversation->id) }}"
                                    class="ai-chat-history-item {{ $activeConversation?->id === $conversation->id ? 'is-active' : '' }}"
                                    data-title="{{ $conversation->title ?: __('New chat') }}">
                                    <div class="ai-chat-history-item__body">
                                        <strong>{{ $conversation->title ?: __('New chat') }}</strong>
                                        <span>{{ optional($conversation->last_message_at)->diffForHumans() ?: __('Just now') }}</span>
                                    </div>
                                    <span class="ai-chat-history-item__active-pill">{{ __('Current') }}</span>
                                    <span class="ai-chat-history-item__action">...</span>
                                </a>
                            @endforeach
                        @else
                            <div class="ai-chat-sidebar__empty">
                                <div class="ai-chat-sidebar__empty-icon">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <h6>{{ __('No chats yet') }}</h6>
                                <p>{{ __('Start a conversation and your chat history will appear here.') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if ($recentConversations->isNotEmpty())
                <div class="ai-chat-history-group">
                    <p class="ai-chat-history-group__label">{{ __('30 Days') }}</p>
                    <div class="ai-chat-sidebar__list">
                        @foreach ($recentConversations as $conversation)
                            <a href="{{ $chatUrlForConversation($conversation->public_id ?? $conversation->id) }}"
                                class="ai-chat-history-item {{ $activeConversation?->id === $conversation->id ? 'is-active' : '' }}"
                                data-title="{{ $conversation->title ?: __('New chat') }}">
                                <div class="ai-chat-history-item__body">
                                    <strong>{{ $conversation->title ?: __('New chat') }}</strong>
                                    <span>{{ optional($conversation->last_message_at)->diffForHumans() ?: __('Just now') }}</span>
                                </div>
                                <span class="ai-chat-history-item__active-pill">{{ __('Current') }}</span>
                                <span class="ai-chat-history-item__action">...</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($olderConversations->isNotEmpty())
                <div class="ai-chat-history-group">
                    <p class="ai-chat-history-group__label">{{ __('Earlier') }}</p>
                    <div class="ai-chat-sidebar__list">
                        @foreach ($olderConversations as $conversation)
                            <a href="{{ $chatUrlForConversation($conversation->public_id ?? $conversation->id) }}"
                                class="ai-chat-history-item {{ $activeConversation?->id === $conversation->id ? 'is-active' : '' }}"
                                data-title="{{ $conversation->title ?: __('New chat') }}">
                                <div class="ai-chat-history-item__body">
                                    <strong>{{ $conversation->title ?: __('New chat') }}</strong>
                                    <span>{{ optional($conversation->last_message_at)->diffForHumans() ?: __('Just now') }}</span>
                                </div>
                                <span class="ai-chat-history-item__active-pill">{{ __('Current') }}</span>
                                <span class="ai-chat-history-item__action">...</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="ai-chat-sidebar__footer">
        <div class="ai-chat-accessory-controls ai-chat-visually-hidden" aria-hidden="true">
            <button type="button" class="ai-chat-mini-btn" id="ai-chat-copy-last">
                <i class="fas fa-copy"></i>
                <span>{{ __('Copy last answer') }}</span>
            </button>
            <div class="ai-chat-image-tools" aria-label="{{ __('Image generator controls') }}">
                <div class="ai-chat-image-tools__presets">
                    <button type="button" class="ai-chat-preset-btn" data-prompt="{{ __('Create a clean educational diagram for this topic.') }}">{{ __('Study diagram') }}</button>
                    <button type="button" class="ai-chat-preset-btn" data-prompt="{{ __('Create a flashcard style revision image for this topic.') }}">{{ __('Flashcard') }}</button>
                    <button type="button" class="ai-chat-preset-btn" data-prompt="{{ __('Create a polished poster with the key points on this topic.') }}">{{ __('Poster') }}</button>
                </div>
                <div class="ai-chat-image-tools__selectors">
                    <label class="ai-chat-image-field">
                        <span>{{ __('Size') }}</span>
                        <select id="ai-chat-image-size">
                            <option value="1024x1024">{{ __('Square') }} 1024x1024</option>
                            <option value="1536x1024">{{ __('Landscape') }} 1536x1024</option>
                            <option value="1024x1536">{{ __('Portrait') }} 1024x1536</option>
                        </select>
                    </label>
                    <label class="ai-chat-image-field">
                        <span>{{ __('Style') }}</span>
                        <select id="ai-chat-image-style">
                            <option value="auto">{{ __('Auto') }}</option>
                            <option value="natural">{{ __('Natural') }}</option>
                            <option value="vivid">{{ __('Vivid') }}</option>
                        </select>
                    </label>
                </div>
            </div>
        </div>
        <a href="{{ $aiCreditsUrl }}" class="ai-chat-usage-card">
            <div class="ai-chat-usage-card__avatar">
                <i class="fas fa-wallet" aria-hidden="true"></i>
            </div>
            <div class="ai-chat-usage-card__body">
                <strong>{{ __('AI Usage') }}</strong>
                <span>{{ __(':value credits remaining', ['value' => number_format($currentBalance)]) }}</span>
                <div class="ai-chat-usage-card__progress" aria-hidden="true">
                    <span style="width: {{ $usagePercent }}%"></span>
                </div>
                <small>{{ __(':value% used this month', ['value' => $usagePercent]) }}</small>
            </div>
            <span class="ai-chat-usage-card__meta" aria-hidden="true">
                <i class="fas fa-chart-line"></i>
            </span>
        </a>
    </div>
</div>
