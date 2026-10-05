<section class="note-block note-block--embed">
    <div class="ratio ratio-16x9">
        <iframe src="{{ $block->content_json['url'] ?? '' }}" title="{{ $block->content_json['title'] ?? __('Embedded content') }}" allowfullscreen></iframe>
    </div>
</section>
