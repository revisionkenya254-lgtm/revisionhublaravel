@switch($block['type'])
    @case('heading')
        <input type="text" class="form-control block-heading-text" placeholder="{{ __('Heading text') }}" value="{{ $block['content']['text'] ?? '' }}">
        <select class="form-select block-heading-level">
            @foreach (['h2', 'h3', 'h4'] as $level)
                <option value="{{ $level }}" @selected(($block['content']['level'] ?? 'h2') === $level)>{{ strtoupper($level) }}</option>
            @endforeach
        </select>
        @break

    @case('image')
        <input type="text" class="form-control block-image-src" placeholder="{{ __('Image URL or path') }}" value="{{ $block['content']['src'] ?? '' }}">
        <input type="text" class="form-control block-image-alt" placeholder="{{ __('Alt text') }}" value="{{ $block['content']['alt'] ?? '' }}">
        <input type="text" class="form-control block-image-caption" placeholder="{{ __('Caption') }}" value="{{ $block['content']['caption'] ?? '' }}">
        @break

    @case('embed')
        <input type="text" class="form-control block-embed-url" placeholder="{{ __('Embed URL') }}" value="{{ $block['content']['url'] ?? '' }}">
        <input type="text" class="form-control block-embed-title" placeholder="{{ __('Embed title') }}" value="{{ $block['content']['title'] ?? '' }}">
        @break

    @case('code')
        <input type="text" class="form-control block-code-language" placeholder="{{ __('Language') }}" value="{{ $block['content']['language'] ?? 'text' }}">
        <textarea class="form-control block-code-body" rows="6" placeholder="{{ __('Code snippet') }}">{{ $block['content']['code'] ?? '' }}</textarea>
        @break

    @case('table')
        <textarea class="form-control block-table-html" rows="6" placeholder="{{ __('Table HTML') }}">{{ $block['content']['html'] ?? '' }}</textarea>
        @break

    @case('callout')
        <select class="form-select block-callout-variant">
            @foreach (['info', 'success', 'warning', 'danger'] as $variant)
                <option value="{{ $variant }}" @selected(($block['content']['variant'] ?? 'info') === $variant)>{{ ucfirst($variant) }}</option>
            @endforeach
        </select>
        <input type="text" class="form-control block-callout-title" placeholder="{{ __('Callout title') }}" value="{{ $block['content']['title'] ?? '' }}">
        <textarea class="form-control block-callout-body" rows="4" placeholder="{{ __('Callout body') }}">{{ $block['content']['body'] ?? '' }}</textarea>
        @break

    @case('bullet_list')
        <textarea class="form-control block-bullet-items" rows="5" placeholder="{{ __('One bullet per line') }}">{{ collect($block['content']['items'] ?? [])->implode("\n") }}</textarea>
        @break

    @case('quote')
        <textarea class="form-control block-quote-text" rows="4" placeholder="{{ __('Quote') }}">{{ $block['content']['quote'] ?? '' }}</textarea>
        <input type="text" class="form-control block-quote-cite" placeholder="{{ __('Citation') }}" value="{{ $block['content']['cite'] ?? '' }}">
        @break

    @case('divider')
        <p class="mb-0 text-muted">{{ __('A visual divider will be rendered here.') }}</p>
        @break

    @case('rich_text')
    @default
        <textarea class="form-control block-rich-html" rows="6" placeholder="{{ __('HTML or formatted content') }}">{{ $block['content']['html'] ?? '' }}</textarea>
@endswitch
