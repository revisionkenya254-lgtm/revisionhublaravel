@php
    $imageSrc = $block->content_json['src'] ?? '';
    $resolvedImageSrc = \Illuminate\Support\Str::startsWith($imageSrc, ['http://', 'https://', '//']) ? $imageSrc : asset($imageSrc);
@endphp
<figure class="note-block note-block--image">
    <img src="{{ $resolvedImageSrc }}" alt="{{ $block->content_json['alt'] ?? '' }}" class="img-fluid rounded-4">
    @if (!empty($block->content_json['caption']))
        <figcaption class="mt-2 text-muted">{{ $block->content_json['caption'] }}</figcaption>
    @endif
</figure>
