@php
    use App\Models\ProductNoteTopic;

    $children = $node->getRelation('children') ?? collect();
    $isLeaf = in_array($node->node_type, [ProductNoteTopic::TYPE_READING, ProductNoteTopic::TYPE_RESOURCE], true);
    $isActive = $currentNode?->id === $node->id;
@endphp

@if ($isLeaf)
    <a href="{{ ($nodeRoute)($node) }}" class="tutorial-note__lesson {{ $isActive ? 'is-active' : '' }}" data-topic-link>
        <span class="tutorial-note__lesson-dot"></span>
        <span>{{ $node->title }}</span>
    </a>
@else
    <div class="tutorial-note__group">
        <button type="button" class="tutorial-note__group-toggle" data-group-toggle>
            <span>{{ $node->title }}</span>
            <span class="tutorial-note__group-icon">+</span>
        </button>
        <div class="tutorial-note__group-children">
            @foreach ($children as $child)
                @include('frontend.pages.product-note-viewer.partials.curriculum-node', [
                    'node' => $child,
                    'currentNode' => $currentNode,
                    'nodeRoute' => $nodeRoute,
                ])
            @endforeach
        </div>
    </div>
@endif
