@php
    use App\Models\ProductNoteTopic;
@endphp

<aside class="tutorial-note__sidebar">
    <div class="tutorial-note__sidebar-card">
        <div class="tutorial-note__brand">
            <div class="tutorial-note__brand-icon">T</div>
            <div>
                <strong>{{ __('Tutor LMS') }}</strong>
                <small>{{ __('Note Reader') }}</small>
            </div>
        </div>

        <div class="tutorial-note__course">
            <h3>{{ $product->metadata['subject'] ?? $product->title }}</h3>
            <div class="tutorial-note__progress-meta">
                <span>{{ __('Your Progress') }}</span>
                <strong>{{ number_format($progressPercent, 0) }}%</strong>
            </div>
            <div class="tutorial-note__progress-bar">
                <span style="width: {{ min(100, max(0, $progressPercent)) }}%;"></span>
            </div>
        </div>

        <div class="tutorial-note__search">
            <input type="search" id="note-topic-search" class="form-control" placeholder="{{ __('Search in course') }}">
        </div>

        <div class="tutorial-note__curriculum" id="note-topic-list">
            @if ($curriculum->isEmpty())
                <p class="text-muted mb-0">{{ __('No published curriculum items yet.') }}</p>
            @else
                @foreach ($curriculum as $node)
                    @include('frontend.pages.product-note-viewer.partials.curriculum-node', [
                        'node' => $node,
                        'currentNode' => $currentNode,
                        'nodeRoute' => $nodeRoute,
                    ])
                @endforeach
            @endif
        </div>
    </div>
</aside>
