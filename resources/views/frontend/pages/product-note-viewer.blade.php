@extends('frontend.layouts.master')
@section('meta_title', $product->title . ' || ' . $setting->app_name)

@push('styles')
    @vite('resources/css/note-reader.css')
@endpush

@push('scripts')
    @vite('resources/js/note-reader.js')
@endpush

@php
    use App\Models\ProductNoteTopic;
    use Illuminate\Support\Str;

    $currentNode = $currentNode ?? null;
    $curriculum = $curriculum ?? collect();
    $bookmarks = $bookmarks ?? collect();
    $progressPercent = $progress?->completion_percent ?? 0;
    $currentContent = $currentNode?->content_json ?? [];
    $nodeRoute = fn($node) => route('product.read-note', ['slug' => $product->slug, 'topicSlug' => $node->slug]);
    $resourceRoute = fn($path) => Str::startsWith($path, ['http://', 'https://', '//']) ? $path : asset($path);
    $attachments = collect($note?->resources ?? []);

    $pageSections = collect($currentContent['blocks'] ?? [])
        ->filter(fn($block) => ($block['type'] ?? null) === 'heading' && filled($block['content']['text'] ?? null))
        ->map(function ($block, $index) {
            return [
                'id' => 'section-' . ($index + 1),
                'title' => $block['content']['text'],
                'level' => $block['content']['level'] ?? 'h2',
            ];
        })->values();

    if ($pageSections->isEmpty() && $currentNode) {
        $pageSections = collect([[
            'id' => 'section-intro',
            'title' => $currentNode->title,
            'level' => 'h2',
        ]]);
    }
@endphp

@section('contents')
    <x-frontend.breadcrumb :title="$product->title" :links="[
        ['url' => route('home'), 'text' => __('Home')],
        ['url' => route('catalog'), 'text' => __('Catalog')],
        ['url' => route('product.show', $product->slug), 'text' => $product->title],
        ['url' => '', 'text' => $previewMode ? __('Preview') : __('Read')],
    ]" />

    <section class="tutorial-note section-py-120">
        <div class="container-fluid px-lg-4">
            <div class="tutorial-note__layout">
                @include('frontend.pages.product-note-viewer.partials.sidebar', [
                    'product' => $product,
                    'curriculum' => $curriculum,
                    'currentNode' => $currentNode,
                    'progressPercent' => $progressPercent,
                    'nodeRoute' => $nodeRoute,
                ])

                <main class="tutorial-note__content">
                    <div class="tutorial-note__breadcrumbs">
                        <span>{{ $product->metadata['subject'] ?? __('Study Notes') }}</span>
                        <span>{{ __('>') }}</span>
                        <span>{{ $currentNode?->title ?? $product->title }}</span>
                    </div>

                    <div class="tutorial-note__header">
                        <div>
                            <h1>{{ $product->title }}</h1>
                            @if ($note?->excerpt)
                                <p>{{ $note->excerpt }}</p>
                            @endif
                        </div>
                        <div class="tutorial-note__nav-buttons">
                            @if ($previousNode)
                                <a href="{{ $nodeRoute($previousNode) }}" class="btn btn-border">{{ __('Previous') }}</a>
                            @endif
                            @if ($nextNode)
                                <a href="{{ $nodeRoute($nextNode) }}" class="btn btn-border">{{ __('Next') }}</a>
                            @endif
                        </div>
                    </div>

                    <article
                        class="tutorial-note__article"
                        id="current-note-topic"
                        data-node-id="{{ $currentNode?->id }}"
                        data-node-type="{{ $currentNode?->node_type }}"
                        data-preview-mode="{{ $previewMode ? '1' : '0' }}"
                        data-progress-url="{{ route('product.note-progress', $product->slug) }}"
                        data-bookmark-url="{{ route('product.note-bookmark', $product->slug) }}"
                    >
                        @if ($currentNode)
                            <div class="tutorial-note__article-body">
                                <section id="section-intro">
                                    <h2>{{ $currentNode->title }}</h2>
                                    @if ($currentNode->summary)
                                        <p>{{ $currentNode->summary }}</p>
                                    @endif
                                </section>

                                @if (in_array($currentNode->node_type, [ProductNoteTopic::TYPE_READING, ProductNoteTopic::TYPE_CHAPTER], true) && ! empty($currentContent['blocks'] ?? []))
                                    @php($headingIndex = 0)
                                    @foreach (($currentContent['blocks'] ?? []) as $block)
                                        @php($type = $block['type'] ?? 'rich_text')
                                        @php($content = $block['content'] ?? [])

                                        @switch($type)
                                            @case('heading')
                                                @php($headingIndex++)
                                                @php($sectionId = 'section-' . $headingIndex)
                                                @if (($content['level'] ?? 'h2') === 'h3')
                                                    <h3 id="{{ $sectionId }}">{{ $content['text'] ?? '' }}</h3>
                                                @else
                                                    <h2 id="{{ $sectionId }}">{{ $content['text'] ?? '' }}</h2>
                                                @endif
                                                @break

                                            @case('bullet_list')
                                                <ul>
                                                    @foreach (($content['items'] ?? []) as $item)
                                                        <li>{{ $item }}</li>
                                                    @endforeach
                                                </ul>
                                                @break

                                            @case('code')
                                                <pre class="tutorial-note__code"><code>{{ $content['code'] ?? '' }}</code></pre>
                                                @break

                                            @case('quote')
                                                <blockquote class="tutorial-note__quote">{{ $content['quote'] ?? '' }}</blockquote>
                                                @break

                                            @case('callout')
                                                <div class="tutorial-note__callout">
                                                    @if (!empty($content['title']))
                                                        <strong>{{ $content['title'] }}</strong>
                                                    @endif
                                                    <p class="mb-0">{{ $content['body'] ?? '' }}</p>
                                                </div>
                                                @break

                                            @case('rich_text')
                                            @default
                                                {!! clean($content['html'] ?? '') !!}
                                        @endswitch
                                    @endforeach
                                @elseif ($currentNode->node_type === ProductNoteTopic::TYPE_RESOURCE)
                                    <div class="tutorial-note__attachment-card">
                                        <strong>{{ $currentNode->title }}</strong>
                                        <p>{{ $currentContent['description'] ?? __('Open the attached resource to continue.') }}</p>
                                        <a href="{{ $resourceRoute($currentContent['url_or_path'] ?? '') }}" class="btn btn-primary" target="_blank">{{ __('Open Attachment') }}</a>
                                    </div>
                                @else
                                    <p>{{ __('This section is empty right now.') }}</p>
                                @endif
                            </div>
                        @else
                            <div class="tutorial-note__empty">
                                <h3>{{ __('This note has no readable content yet.') }}</h3>
                            </div>
                        @endif
                    </article>
                </main>

                <aside class="tutorial-note__utility">
                    <div class="tutorial-note__utility-card">
                        <h4>{{ __('On this page') }}</h4>
                        <div class="tutorial-note__page-links">
                            @foreach ($pageSections as $section)
                                <a href="#{{ $section['id'] }}">{{ $section['title'] }}</a>
                            @endforeach
                        </div>
                    </div>

                    @if ($attachments->isNotEmpty())
                        <div class="tutorial-note__utility-card">
                            <h4>{{ __('Attachments') }}</h4>
                            <div class="tutorial-note__attachments">
                                @foreach ($attachments as $attachment)
                                    <a href="{{ $resourceRoute($attachment->url_or_path ?? '') }}" target="_blank" class="tutorial-note__attachment-link">
                                        <strong>{{ $attachment->title }}</strong>
                                        <small>{{ strtoupper(pathinfo($attachment->url_or_path ?? '', PATHINFO_EXTENSION) ?: ($attachment->resource_type ?? 'FILE')) }}</small>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($currentNode && !$previewMode)
                        <div class="tutorial-note__utility-card">
                            <h4>{{ __('Saved items') }}</h4>
                            <button type="button" class="btn btn-primary w-100 mb-3" id="bookmark-current-topic" data-node-id="{{ $currentNode->id }}">
                                {{ __('Bookmark This Note') }}
                            </button>
                            <div class="tutorial-note__bookmarks" id="bookmark-list">
                                @forelse ($bookmarks as $bookmark)
                                    <a href="{{ $bookmark->topic ? $nodeRoute($bookmark->topic) : '#' }}" class="tutorial-note__attachment-link">
                                        <strong>{{ $bookmark->label ?: $bookmark->topic?->title }}</strong>
                                        <small>{{ __('Saved item') }}</small>
                                    </a>
                                @empty
                                    <p class="text-muted mb-0">{{ __('No bookmarks yet.') }}</p>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </section>
@endsection
