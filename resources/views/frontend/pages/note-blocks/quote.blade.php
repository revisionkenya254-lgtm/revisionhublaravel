<blockquote class="note-block note-block--quote">
    <p>{{ $block->content_json['quote'] ?? '' }}</p>
    @if (!empty($block->content_json['cite']))
        <footer>{{ $block->content_json['cite'] }}</footer>
    @endif
</blockquote>
