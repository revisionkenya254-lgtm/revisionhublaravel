<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductNote;
use App\Models\ProductNoteBlock;
use App\Models\ProductNoteBookmark;
use App\Models\ProductNoteProgress;
use App\Models\ProductNoteResource;
use App\Models\ProductNoteTopic;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductNoteService
{
    public function emptyBuilderPayload(): array
    {
        return [
            'note' => [
                'excerpt' => null,
                'intro_html' => null,
                'estimated_read_minutes' => null,
                'difficulty' => 'intermediate',
                'show_resources' => true,
                'show_discussion' => true,
                'prerequisites' => [],
            ],
            'curriculum' => [],
            'resources' => [],
        ];
    }

    public function builderPayload(Product $product): array
    {
        $note = $product->note;

        if (! $note) {
            return $this->emptyBuilderPayload();
        }

        $curriculum = $this->buildBuilderTree($note->topics);

        return [
            'note' => [
                'excerpt' => $note->excerpt,
                'intro_html' => $note->intro_html,
                'estimated_read_minutes' => $note->estimated_read_minutes,
                'difficulty' => $note->difficulty,
                'show_resources' => $note->show_resources,
                'show_discussion' => $note->show_discussion,
                'prerequisites' => $note->prerequisites ?? [],
            ],
            'curriculum' => $curriculum,
            'resources' => $note->resources->map(fn($resource) => [
                'title' => $resource->title,
                'resource_type' => $resource->resource_type,
                'url_or_path' => $resource->url_or_path,
                'description' => $resource->meta_json['description'] ?? null,
            ])->values()->all(),
        ];
    }

    public function parseBuilderPayload(?string $payload): array
    {
        if (! $payload) {
            return $this->emptyBuilderPayload();
        }

        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'note_payload' => __('The note builder payload is invalid.'),
            ]);
        }

        $normalized = [
            'note' => $decoded['note'] ?? [],
            'curriculum' => array_values($decoded['curriculum'] ?? []),
            'resources' => array_values($decoded['resources'] ?? []),
        ];

        if (! empty($decoded['topics']) && empty($normalized['curriculum'])) {
            $normalized['curriculum'] = $this->legacyTopicsToCurriculum($decoded['topics']);
        }

        return $normalized;
    }

    public function validatePayloadForStatus(array $payload, string $status): void
    {
        if ($status === 'is_draft') {
            return;
        }

        $publishedChapters = collect($payload['curriculum'] ?? [])
            ->filter(fn($node) => ($node['type'] ?? null) === ProductNoteTopic::TYPE_CHAPTER && ($node['is_published'] ?? true));

        if ($publishedChapters->isEmpty()) {
            throw ValidationException::withMessages([
                'note_payload' => __('Published notes must contain at least one published chapter.'),
            ]);
        }

        $hasPublishedTopicWithChildren = $publishedChapters->contains(function ($chapter) {
            if ($this->payloadNodeHasReadableContent($chapter)) {
                return true;
            }

            return collect($chapter['children'] ?? [])->contains(function ($topic) {
                return ($topic['type'] ?? null) === ProductNoteTopic::TYPE_TOPIC
                    && ($topic['is_published'] ?? true)
                    && collect($topic['children'] ?? [])->contains(fn($child) => ($child['is_published'] ?? true));
            });
        });

        if (! $hasPublishedTopicWithChildren) {
            throw ValidationException::withMessages([
                'note_payload' => __('Published chapters must contain either main content or at least one published topic with content items.'),
            ]);
        }

        $hasPublishedReading = collect($this->flattenCurriculum($payload['curriculum'] ?? []))
            ->contains(function ($node) {
                if (($node['is_published'] ?? true) !== true) {
                    return false;
                }

                if (($node['type'] ?? null) === ProductNoteTopic::TYPE_READING) {
                    return true;
                }

                return ($node['type'] ?? null) === ProductNoteTopic::TYPE_CHAPTER
                    && $this->payloadNodeHasReadableContent($node);
            });

        if (! $hasPublishedReading) {
            throw ValidationException::withMessages([
                'note_payload' => __('Published notes must contain at least one published reading item or chapter content.'),
            ]);
        }
    }

    public function sync(Product $product, array $payload): ProductNote
    {
        return DB::transaction(function () use ($product, $payload) {
            $noteData = $payload['note'] ?? [];

            $note = ProductNote::updateOrCreate(
                ['product_id' => $product->id],
                [
                    'excerpt' => $noteData['excerpt'] ?? null,
                    'intro_html' => $noteData['intro_html'] ?? null,
                    'estimated_read_minutes' => $this->nullableInt($noteData['estimated_read_minutes'] ?? null),
                    'difficulty' => $noteData['difficulty'] ?? null,
                    'show_resources' => (bool) ($noteData['show_resources'] ?? true),
                    'show_discussion' => (bool) ($noteData['show_discussion'] ?? true),
                    'prerequisites' => $this->normalizeStringList($noteData['prerequisites'] ?? []),
                ]
            );

            $note->topics()->delete();
            $note->resources()->delete();

            foreach (array_values($payload['curriculum'] ?? []) as $index => $nodeData) {
                $this->createCurriculumNode($note, null, $nodeData, $index, null);
            }

            foreach (array_values($payload['resources'] ?? []) as $resourceIndex => $resourceData) {
                if (! filled($resourceData['title'] ?? null) || ! filled($resourceData['url_or_path'] ?? null)) {
                    continue;
                }

                $note->resources()->create([
                    'title' => $resourceData['title'],
                    'resource_type' => $resourceData['resource_type'] ?? 'link',
                    'url_or_path' => $resourceData['url_or_path'],
                    'meta_json' => [
                        'description' => $resourceData['description'] ?? null,
                    ],
                    'sort_order' => $resourceIndex + 1,
                ]);
            }

            return $note->fresh(['topics', 'resources']);
        });
    }

    public function readerData(Product $product, ?string $currentSlug = null, ?User $user = null, bool $previewMode = false): array
    {
        $product->loadMissing([
            'note.topics',
            'note.resources',
        ]);

        $nodes = ($product->note?->topics ?? collect())
            ->sortBy([
                ['parent_id', 'asc'],
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        $curriculum = $this->buildReaderTree($nodes, null, $previewMode);
        $leafNodes = $this->publishedLeafNodes($nodes, $previewMode);
        $readingNodes = $leafNodes->where('node_type', ProductNoteTopic::TYPE_READING)->values();

        $progress = $user
            ? ProductNoteProgress::where('user_id', $user->id)->where('product_id', $product->id)->first()
            : null;

        $currentNode = $this->resolveCurrentNode($nodes, $leafNodes, $currentSlug, $progress?->topic_id, $previewMode);
        $currentIndex = $currentNode ? $leafNodes->search(fn(ProductNoteTopic $node) => $node->id === $currentNode->id) : false;

        return [
            'note' => $product->note,
            'curriculum' => $curriculum,
            'leafNodes' => $leafNodes,
            'readingNodes' => $readingNodes,
            'currentNode' => $currentNode,
            'currentTopic' => $currentNode,
            'previousNode' => $currentIndex !== false && $currentIndex > 0 ? $leafNodes[$currentIndex - 1] : null,
            'nextNode' => $currentIndex !== false && $currentIndex < ($leafNodes->count() - 1) ? $leafNodes[$currentIndex + 1] : null,
            'progress' => $progress,
            'bookmarks' => $user
                ? ProductNoteBookmark::with(['topic'])
                    ->where('user_id', $user->id)
                    ->where('product_id', $product->id)
                    ->latest()
                    ->get()
                : collect(),
        ];
    }

    public function updateProgress(User $user, Product $product, ProductNoteTopic $topic, ?int $lastBlockId = null): ProductNoteProgress
    {
        $allNodes = $product->note?->topics()->get() ?? collect();
        $readableNodes = $this->publishedLeafNodes($allNodes, false)->values();

        $matchedReading = $readableNodes->first(fn(ProductNoteTopic $node) => $node->id === $topic->id)
            ?? ($topic->node_type === ProductNoteTopic::TYPE_READING
            ? $topic
            : $readableNodes->first());

        $position = $matchedReading ? ($readableNodes->search(fn(ProductNoteTopic $node) => $node->id === $matchedReading->id) + 1) : 0;
        $completion = $readableNodes->count() > 0 && $position > 0
            ? round(($position / $readableNodes->count()) * 100, 2)
            : 0;

        return ProductNoteProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'product_id' => $product->id,
            ],
            [
                'topic_id' => $matchedReading?->id,
                'last_block_id' => null,
                'completion_percent' => $completion,
                'last_read_at' => now(),
            ]
        );
    }

    public function toggleBookmark(User $user, Product $product, ProductNoteTopic $topic, ?int $blockId = null, ?string $label = null): array
    {
        $existing = ProductNoteBookmark::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('topic_id', $topic->id)
            ->whereNull('block_id')
            ->first();

        if ($existing) {
            $existing->delete();

            return ['bookmarked' => false];
        }

        ProductNoteBookmark::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'topic_id' => $topic->id,
            'block_id' => null,
            'label' => $label ?: $topic->title,
        ]);

        return ['bookmarked' => true];
    }

    private function createCurriculumNode(ProductNote $note, ?ProductNoteTopic $parent, array $nodeData, int $index, ?string $parentType): ProductNoteTopic
    {
        $type = $nodeData['type'] ?? null;

        if (! in_array($type, ProductNoteTopic::allowedNodeTypes(), true)) {
            throw ValidationException::withMessages([
                'note_payload' => __('Invalid curriculum node type encountered.'),
            ]);
        }

        $this->assertValidParentChildCombination($parentType, $type);

        $title = trim((string) ($nodeData['title'] ?? ''));
        $fallbackTitle = match ($type) {
            ProductNoteTopic::TYPE_CHAPTER => __('Untitled Chapter'),
            ProductNoteTopic::TYPE_TOPIC => __('Untitled Topic'),
            ProductNoteTopic::TYPE_READING => __('Untitled Reading'),
            ProductNoteTopic::TYPE_RESOURCE => __('Untitled Resource'),
            default => __('Untitled Item'),
        };

        $node = $note->topics()->create([
            'parent_topic_id' => null,
            'parent_id' => $parent?->id,
            'node_type' => $type,
            'title' => $title ?: $fallbackTitle,
            'slug' => $this->uniqueNodeSlug($note, $title ?: $fallbackTitle, $type, $index),
            'summary' => $nodeData['summary'] ?? null,
            'sort_order' => $index + 1,
            'estimated_read_minutes' => $this->nullableInt($nodeData['estimated_read_minutes'] ?? null),
            'is_published' => (bool) ($nodeData['is_published'] ?? true),
            'content_json' => $this->normalizeNodeContent($type, $nodeData),
        ]);

        foreach (array_values($nodeData['children'] ?? []) as $childIndex => $childData) {
            $this->createCurriculumNode($note, $node, $childData, $childIndex, $type);
        }

        return $node;
    }

    private function normalizeNodeContent(string $type, array $nodeData): ?array
    {
        return match ($type) {
            ProductNoteTopic::TYPE_CHAPTER => filled(trim((string) ($nodeData['content_html'] ?? '')))
                ? [
                    'blocks' => [
                        [
                            'type' => ProductNoteBlock::TYPE_RICH_TEXT,
                            'content' => [
                                'html' => $nodeData['content_html'] ?? '',
                            ],
                        ],
                    ],
                ]
                : null,
            ProductNoteTopic::TYPE_READING => [
                'blocks' => collect($nodeData['blocks'] ?? [])
                    ->map(function ($blockData) {
                        $blockType = $blockData['type'] ?? ProductNoteBlock::TYPE_RICH_TEXT;

                        if (! in_array($blockType, ProductNoteBlock::allowedTypes(), true)) {
                            return null;
                        }

                        return [
                            'type' => $blockType,
                            'content' => $this->normalizeBlockContent($blockType, $blockData['content'] ?? []),
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all(),
            ],
            ProductNoteTopic::TYPE_RESOURCE => [
                'resource_type' => $nodeData['resource_type'] ?? 'link',
                'url_or_path' => $nodeData['url_or_path'] ?? '',
                'description' => $nodeData['description'] ?? null,
            ],
            default => null,
        };
    }

    private function normalizeBlockContent(string $type, array $content): array
    {
        return match ($type) {
            ProductNoteBlock::TYPE_RICH_TEXT => [
                'html' => $content['html'] ?? '',
            ],
            ProductNoteBlock::TYPE_HEADING => [
                'text' => $content['text'] ?? '',
                'level' => $content['level'] ?? 'h2',
            ],
            ProductNoteBlock::TYPE_IMAGE => [
                'src' => $content['src'] ?? '',
                'caption' => $content['caption'] ?? null,
                'alt' => $content['alt'] ?? '',
            ],
            ProductNoteBlock::TYPE_EMBED => [
                'url' => $content['url'] ?? '',
                'title' => $content['title'] ?? null,
            ],
            ProductNoteBlock::TYPE_CODE => [
                'language' => $content['language'] ?? 'text',
                'code' => $content['code'] ?? '',
            ],
            ProductNoteBlock::TYPE_TABLE => [
                'html' => $content['html'] ?? '',
            ],
            ProductNoteBlock::TYPE_CALLOUT => [
                'variant' => $content['variant'] ?? 'info',
                'title' => $content['title'] ?? '',
                'body' => $content['body'] ?? '',
            ],
            ProductNoteBlock::TYPE_BULLET_LIST => [
                'items' => $this->normalizeStringList($content['items'] ?? []),
            ],
            ProductNoteBlock::TYPE_QUOTE => [
                'quote' => $content['quote'] ?? '',
                'cite' => $content['cite'] ?? null,
            ],
            default => [],
        };
    }

    private function buildBuilderTree(Collection $nodes, ?int $parentId = null): array
    {
        return $nodes
            ->where('parent_id', $parentId)
            ->sortBy('sort_order')
            ->values()
            ->map(function (ProductNoteTopic $node) use ($nodes) {
                $payload = [
                    'id' => (string) $node->id,
                    'type' => $node->node_type,
                    'title' => $node->title,
                    'summary' => $node->summary,
                    'estimated_read_minutes' => $node->estimated_read_minutes,
                    'is_published' => $node->is_published,
                ];

                if ($node->isReading()) {
                    $payload['blocks'] = $node->content_json['blocks'] ?? [];
                } elseif ($node->isChapter()) {
                    $payload['content_html'] = $this->nodeContentHtml($node);
                } elseif ($node->isResource()) {
                    $payload['resource_type'] = $node->content_json['resource_type'] ?? 'link';
                    $payload['url_or_path'] = $node->content_json['url_or_path'] ?? '';
                    $payload['description'] = $node->content_json['description'] ?? null;
                } else {
                    $payload['children'] = $this->buildBuilderTree($nodes, $node->id);
                }

                if (! isset($payload['children']) && ! $node->isLeaf()) {
                    $payload['children'] = [];
                }

                return $payload;
            })
            ->all();
    }

    private function buildReaderTree(Collection $nodes, ?int $parentId, bool $previewMode): Collection
    {
        return $nodes
            ->where('parent_id', $parentId)
            ->sortBy('sort_order')
            ->values()
            ->filter(fn(ProductNoteTopic $node) => $previewMode || $node->is_published)
            ->map(function (ProductNoteTopic $node) use ($nodes, $previewMode) {
                $node->setRelation('children', $this->buildReaderTree($nodes, $node->id, $previewMode));
                return $node;
            })
            ->values();
    }

    private function publishedLeafNodes(Collection $nodes, bool $previewMode): Collection
    {
        return $nodes
            ->filter(fn(ProductNoteTopic $node) => ($node->isLeaf() || ($node->isChapter() && $this->nodeHasReadableContent($node))) && $this->isVisibleNode($nodes, $node, $previewMode))
            ->sortBy(fn(ProductNoteTopic $node) => $this->nodePath($nodes, $node))
            ->values();
    }

    private function resolveCurrentNode(Collection $nodes, Collection $leafNodes, ?string $currentSlug, ?int $progressTopicId, bool $previewMode): ?ProductNoteTopic
    {
        if ($leafNodes->isEmpty()) {
            return null;
        }

        if ($currentSlug) {
            $exactLeaf = $leafNodes->firstWhere('slug', $currentSlug);
            if ($exactLeaf) {
                return $exactLeaf;
            }

            $containerMatch = $nodes->firstWhere('slug', $currentSlug);
            if ($containerMatch) {
                $descendantLeaf = $leafNodes->first(fn(ProductNoteTopic $leaf) => in_array($containerMatch->id, $this->ancestorIds($nodes, $leaf), true));
                if ($descendantLeaf) {
                    return $descendantLeaf;
                }
            }
        }

        if ($progressTopicId) {
            $matchedByProgress = $leafNodes->firstWhere('id', $progressTopicId);

            if ($matchedByProgress) {
                return $matchedByProgress;
            }
        }

        return $leafNodes->first();
    }

    private function nodeHasReadableContent(ProductNoteTopic $node): bool
    {
        if (! $node->isChapter()) {
            return false;
        }

        $blocks = $node->content_json['blocks'] ?? [];

        return collect($blocks)->contains(function ($block) {
            return ($block['type'] ?? null) === ProductNoteBlock::TYPE_RICH_TEXT
                && filled(trim((string) ($block['content']['html'] ?? '')));
        });
    }

    private function nodeContentHtml(ProductNoteTopic $node): string
    {
        $blocks = $node->content_json['blocks'] ?? [];
        $richText = collect($blocks)->first(function ($block) {
            return ($block['type'] ?? null) === ProductNoteBlock::TYPE_RICH_TEXT;
        });

        return (string) ($richText['content']['html'] ?? '');
    }

    private function payloadNodeHasReadableContent(array $node): bool
    {
        return filled(trim((string) ($node['content_html'] ?? '')));
    }

    private function flattenCurriculum(array $nodes): array
    {
        $flat = [];

        foreach ($nodes as $node) {
            $flat[] = $node;
            foreach ($this->flattenCurriculum($node['children'] ?? []) as $child) {
                $flat[] = $child;
            }
        }

        return $flat;
    }

    private function legacyTopicsToCurriculum(array $topics): array
    {
        return array_values(array_map(function ($topic, $index) {
            return [
                'id' => 'legacy-topic-' . $index,
                'type' => ProductNoteTopic::TYPE_CHAPTER,
                'title' => $topic['title'] ?? __('Chapter ') . ($index + 1),
                'summary' => null,
                'estimated_read_minutes' => null,
                'is_published' => $topic['is_published'] ?? true,
                'children' => [[
                    'id' => 'legacy-topic-item-' . $index,
                    'type' => ProductNoteTopic::TYPE_TOPIC,
                    'title' => $topic['title'] ?? __('Untitled Topic'),
                    'summary' => $topic['summary'] ?? null,
                    'estimated_read_minutes' => $topic['estimated_read_minutes'] ?? null,
                    'is_published' => $topic['is_published'] ?? true,
                    'children' => array_values(array_merge(
                        [[
                            'id' => 'legacy-reading-' . $index,
                            'type' => ProductNoteTopic::TYPE_READING,
                            'title' => __('Reading Text'),
                            'estimated_read_minutes' => $topic['estimated_read_minutes'] ?? null,
                            'is_published' => $topic['is_published'] ?? true,
                            'blocks' => $topic['blocks'] ?? [],
                        ]],
                        array_map(fn($resource, $resourceIndex) => [
                            'id' => 'legacy-resource-' . $index . '-' . $resourceIndex,
                            'type' => ProductNoteTopic::TYPE_RESOURCE,
                            'title' => $resource['title'] ?? __('Untitled Resource'),
                            'resource_type' => $resource['resource_type'] ?? 'link',
                            'url_or_path' => $resource['url_or_path'] ?? '',
                            'description' => $resource['description'] ?? null,
                            'is_published' => true,
                        ], $topic['resources'] ?? [], array_keys($topic['resources'] ?? []))
                    )),
                ]],
            ];
        }, $topics, array_keys($topics)));
    }

    private function assertValidParentChildCombination(?string $parentType, string $childType): void
    {
        $valid = match ($parentType) {
            null => $childType === ProductNoteTopic::TYPE_CHAPTER,
            ProductNoteTopic::TYPE_CHAPTER => $childType === ProductNoteTopic::TYPE_TOPIC,
            ProductNoteTopic::TYPE_TOPIC => in_array($childType, [ProductNoteTopic::TYPE_READING, ProductNoteTopic::TYPE_RESOURCE], true),
            default => false,
        };

        if (! $valid) {
            throw ValidationException::withMessages([
                'note_payload' => __('Invalid curriculum nesting detected.'),
            ]);
        }
    }

    private function uniqueNodeSlug(ProductNote $note, string $title, string $type, int $index): string
    {
        $base = Str::slug($title) ?: $type . '-' . ($index + 1);
        $slug = $base;
        $suffix = 2;

        while ($note->topics()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    private function nodePath(Collection $nodes, ProductNoteTopic $node): string
    {
        $segments = [];
        $cursor = $node;

        while ($cursor) {
            $segments[] = str_pad((string) $cursor->sort_order, 5, '0', STR_PAD_LEFT);
            $cursor = $cursor->parent_id ? $nodes->firstWhere('id', $cursor->parent_id) : null;
        }

        return implode('.', array_reverse($segments));
    }

    private function ancestorIds(Collection $nodes, ProductNoteTopic $node): array
    {
        $ids = [];
        $cursor = $node;

        while ($cursor?->parent_id) {
            $ids[] = $cursor->parent_id;
            $cursor = $nodes->firstWhere('id', $cursor->parent_id);
        }

        return $ids;
    }

    private function isVisibleNode(Collection $nodes, ProductNoteTopic $node, bool $previewMode): bool
    {
        if ($previewMode) {
            return true;
        }

        if (! $node->is_published) {
            return false;
        }

        $cursor = $node;

        while ($cursor?->parent_id) {
            $cursor = $nodes->firstWhere('id', $cursor->parent_id);

            if ($cursor && ! $cursor->is_published) {
                return false;
            }
        }

        return true;
    }

    private function normalizeStringList(array|string|null $value): array
    {
        $items = is_array($value) ? $value : preg_split('/\r\n|\r|\n/', (string) $value);

        return collect($items)
            ->map(fn($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    private function nullableInt(mixed $value): ?int
    {
        return filled($value) ? (int) $value : null;
    }
}
