<section class="note-block note-block--bullet-list">
    <ul>
        @foreach (($block->content_json['items'] ?? []) as $item)
            <li>{{ $item }}</li>
        @endforeach
    </ul>
</section>
