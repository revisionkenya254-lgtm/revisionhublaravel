<section class="note-block note-block--callout">
    @if (!empty($block->content_json['title']))
        <h5>{{ $block->content_json['title'] }}</h5>
    @endif
    <p class="mb-0">{{ $block->content_json['body'] ?? '' }}</p>
</section>
