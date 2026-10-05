@php
    $catalogToolbar = $catalogToolbar ?? [];
    $selectedType = (string) data_get($catalogToolbar, 'selected_type', '');
    $topicItems = collect(data_get($catalogToolbar, 'items', []));
    $topicLabel = (string) data_get($catalogToolbar, 'label', __('Topics'));
    $selectedMainSlug = (string) data_get($catalogToolbar, 'main_category', '');
@endphp

@unless (in_array($selectedType, ['past_paper', 'prediction'], true) || $topicItems->isEmpty())
    <div class="catalog-toolbar">
        <div class="catalog-toolbar__left">
            <span>{{ $topicLabel }}</span>
            @foreach ($topicItems as $topic)
                <a href="{{ $topic['href'] }}"
                    class="catalog-topic-pill {{ $topic['is_active'] ? 'is-active' : '' }}"
                    data-catalog-main-category="{{ $selectedMainSlug }}"
                    @if ($topic['category'] !== '') data-catalog-category="{{ $topic['category'] }}" @endif
                    @if ($topic['subject'] !== '') data-catalog-subject="{{ $topic['subject'] }}" @endif>
                    {{ $topic['name'] }}
                </a>
            @endforeach
        </div>
    </div>
@endunless
