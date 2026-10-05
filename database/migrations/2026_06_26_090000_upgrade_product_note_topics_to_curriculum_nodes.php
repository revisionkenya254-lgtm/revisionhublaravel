<?php

use App\Models\ProductNoteBlock;
use App\Models\ProductNoteTopic;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_note_topics', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('product_note_id')->constrained('product_note_topics')->nullOnDelete();
            $table->string('node_type', 30)->default(ProductNoteTopic::TYPE_TOPIC)->after('parent_id');
            $table->json('content_json')->nullable()->after('is_published');
        });

        DB::table('product_note_topics')->update([
            'parent_id' => DB::raw('parent_topic_id'),
            'node_type' => ProductNoteTopic::TYPE_TOPIC,
        ]);

        $topicsByNote = DB::table('product_note_topics')
            ->orderBy('product_note_id')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('product_note_id');

        foreach ($topicsByNote as $noteId => $topics) {
            foreach ($topics as $topic) {
                $blocks = DB::table('product_note_blocks')
                    ->where('topic_id', $topic->id)
                    ->orderBy('sort_order')
                    ->get()
                    ->map(function ($block) {
                        $content = json_decode($block->content_json ?? '[]', true);

                        return [
                            'type' => $block->block_type,
                            'content' => is_array($content) ? $content : [],
                        ];
                    })
                    ->values()
                    ->all();

                if (! empty($blocks)) {
                    DB::table('product_note_topics')->insert([
                        'product_note_id' => $topic->product_note_id,
                        'parent_topic_id' => null,
                        'parent_id' => $topic->id,
                        'node_type' => ProductNoteTopic::TYPE_READING,
                        'title' => __('Reading Text'),
                        'slug' => $this->uniqueSlug($topic->product_note_id, Str::slug($topic->title . '-reading') ?: 'reading-text'),
                        'summary' => null,
                        'sort_order' => 1,
                        'estimated_read_minutes' => $topic->estimated_read_minutes,
                        'is_published' => (bool) $topic->is_published,
                        'content_json' => json_encode(['blocks' => $blocks]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $resources = DB::table('product_note_resources')
                    ->where('topic_id', $topic->id)
                    ->orderBy('sort_order')
                    ->get();

                foreach ($resources as $resource) {
                    $meta = json_decode($resource->meta_json ?? '[]', true);

                    DB::table('product_note_topics')->insert([
                        'product_note_id' => $topic->product_note_id,
                        'parent_topic_id' => null,
                        'parent_id' => $topic->id,
                        'node_type' => ProductNoteTopic::TYPE_RESOURCE,
                        'title' => $resource->title,
                        'slug' => $this->uniqueSlug($topic->product_note_id, Str::slug($resource->title) ?: 'resource'),
                        'summary' => null,
                        'sort_order' => (int) $resource->sort_order + ($blocks ? 1 : 0),
                        'estimated_read_minutes' => null,
                        'is_published' => true,
                        'content_json' => json_encode([
                            'resource_type' => $resource->resource_type,
                            'url_or_path' => $resource->url_or_path,
                            'description' => is_array($meta) ? ($meta['description'] ?? null) : null,
                        ]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('product_note_topics')
            ->whereIn('node_type', [ProductNoteTopic::TYPE_READING, ProductNoteTopic::TYPE_RESOURCE])
            ->delete();

        Schema::table('product_note_topics', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn(['node_type', 'content_json']);
        });
    }

    private function uniqueSlug(int $noteId, string $base): string
    {
        $slug = $base;
        $suffix = 2;

        while (DB::table('product_note_topics')
            ->where('product_note_id', $noteId)
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }
};
