@php
    $mobileSearchTypes = [
        '' => __('Categories'),
        \App\Models\Product::TYPE_COURSE => __('Videos'),
        \App\Models\Product::TYPE_NOTE => __('Notes'),
        \App\Models\Product::TYPE_PAST_PAPER => __('Papers'),
        \App\Models\Product::TYPE_PREDICTION => __('Predictions'),
        \App\Models\Product::TYPE_QUIZ => __('Quizzes'),
    ];
@endphp

<div class="tgmobile__search">
    <form action="{{ route('catalog') }}" method="GET">
        <div class="tgmobile__search-category">
            <i class="fas fa-th-large" aria-hidden="true"></i>
            <select name="type" aria-label="{{ __('Search category') }}">
                @foreach ($mobileSearchTypes as $typeValue => $typeLabel)
                    <option value="{{ $typeValue }}" @selected((string) request('type') === (string) $typeValue)>{{ $typeLabel }}</option>
                @endforeach
            </select>
            <i class="fas fa-chevron-down tgmobile__search-caret" aria-hidden="true"></i>
        </div>
        <div class="tgmobile__search-field">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="search" placeholder="{{ __('Search For Course . . .') }}" name="search" value="{{ request('search') }}">
        </div>
        <button type="submit" aria-label="{{ __('Search') }}"><i class="fas fa-search" aria-hidden="true"></i></button>
    </form>
</div>
