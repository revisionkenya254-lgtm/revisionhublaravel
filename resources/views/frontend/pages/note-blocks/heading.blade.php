@php($level = in_array($block->content_json['level'] ?? 'h2', ['h2', 'h3', 'h4']) ? $block->content_json['level'] : 'h2')
<section class="note-block note-block--heading">
    @if ($level === 'h3')
        <h3>{{ $block->content_json['text'] ?? '' }}</h3>
    @elseif ($level === 'h4')
        <h4>{{ $block->content_json['text'] ?? '' }}</h4>
    @else
        <h2>{{ $block->content_json['text'] ?? '' }}</h2>
    @endif
</section>
