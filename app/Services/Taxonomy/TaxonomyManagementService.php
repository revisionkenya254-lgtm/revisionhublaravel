<?php

namespace App\Services\Taxonomy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Course\app\Models\CourseCategory;
use Modules\Course\app\Service\CourseCategoryCacheClear;

class TaxonomyManagementService
{
    private const CLASS_TYPES = [
        CourseCategory::TYPE_GRADE,
        CourseCategory::TYPE_FORM,
        CourseCategory::TYPE_NATIONAL_EXAM,
    ];

    private const LEAF_TYPES = [
        CourseCategory::TYPE_SUBJECT,
        CourseCategory::TYPE_PATHWAY,
    ];

    public function __construct(private readonly TaxonomyAuditService $audit)
    {
    }

    public function allowedChildTypes(?CourseCategory $parent): array
    {
        if (! $parent) {
            return [CourseCategory::TYPE_EDUCATION_LEVEL];
        }

        return match ($parent->node_type) {
            CourseCategory::TYPE_EDUCATION_LEVEL => [
                CourseCategory::TYPE_GRADE,
                CourseCategory::TYPE_FORM,
                CourseCategory::TYPE_NATIONAL_EXAM,
                CourseCategory::TYPE_SCHOOL,
                CourseCategory::TYPE_COURSE,
                CourseCategory::TYPE_RESOURCE,
            ],
            CourseCategory::TYPE_GRADE,
            CourseCategory::TYPE_FORM,
            CourseCategory::TYPE_NATIONAL_EXAM => self::LEAF_TYPES,
            CourseCategory::TYPE_SCHOOL => [CourseCategory::TYPE_COURSE],
            default => [],
        };
    }

    public function createNode(?int $parentId, array $data): CourseCategory
    {
        return DB::transaction(function () use ($parentId, $data) {
            $parent = $parentId ? CourseCategory::lockForUpdate()->findOrFail($parentId) : null;
            $this->assertAllowedType($parent, $data['node_type']);
            $this->assertSiblingSlugAvailable($parentId, $data['slug']);

            $node = $this->insertNode($parentId, $data);

            if ($parent?->node_type === CourseCategory::TYPE_EDUCATION_LEVEL
                && in_array($node->node_type, self::CLASS_TYPES, true)) {
                $this->cloneSharedLeavesIntoClass($parent, $node);
            }

            $this->audit->record('node_created', $node->id, null, $this->nodeSnapshot($node), [
                'parent_id' => $parentId,
            ]);
            CourseCategoryCacheClear::clear();

            return $node;
        });
    }

    public function updateNode(CourseCategory $node, array $data): CourseCategory
    {
        return DB::transaction(function () use ($node, $data) {
            $node = CourseCategory::lockForUpdate()->findOrFail($node->id);
            $before = $this->nodeSnapshot($node);
            $parent = $node->parent_id ? CourseCategory::findOrFail($node->parent_id) : null;
            $this->assertAllowedType($parent, $data['node_type']);
            $this->assertSiblingSlugAvailable($node->parent_id, $data['slug'], $node->id);

            $node->forceFill([
                'slug' => $data['slug'],
                'node_type' => $data['node_type'],
                'order' => (int) $data['order'],
                'status' => true,
            ])->saveQuietly();
            $this->upsertTranslation($node->id, $data['name']);
            $node->setAttribute('translation_name', $data['name']);
            $this->audit->record('node_updated', $node->id, $before, $this->nodeSnapshot($node));
            CourseCategoryCacheClear::clear();

            return $node->refresh();
        });
    }

    public function syncSharedLeaf(CourseCategory $educationLevel, ?string $originalSlug, array $data): array
    {
        return DB::transaction(function () use ($educationLevel, $originalSlug, $data) {
            $level = CourseCategory::lockForUpdate()->findOrFail($educationLevel->id);
            $this->assertEducationLevel($level);

            if (! in_array($data['node_type'], self::LEAF_TYPES, true)) {
                throw ValidationException::withMessages([
                    'node_type' => __('Shared school nodes must be subjects or pathways.'),
                ]);
            }

            $classNodes = CourseCategory::where('parent_id', $level->id)
                ->whereIn('node_type', self::CLASS_TYPES)
                ->lockForUpdate()
                ->get();

            if ($classNodes->isEmpty()) {
                throw ValidationException::withMessages([
                    'education_level_id' => __('Add at least one grade, form, or national assessment first.'),
                ]);
            }

            $sourceSlug = $originalSlug ?: $data['slug'];
            $existing = CourseCategory::whereIn('parent_id', $classNodes->pluck('id'))
                ->where('slug', $sourceSlug)
                ->lockForUpdate()
                ->get()
                ->keyBy('parent_id');
            $before = $existing->map(fn (CourseCategory $node) => $this->nodeSnapshot($node))->values()->all();

            if ($originalSlug && $existing->isEmpty()) {
                throw ValidationException::withMessages(['slug' => __('The shared subject no longer exists.')]);
            }

            $conflict = CourseCategory::whereIn('parent_id', $classNodes->pluck('id'))
                ->where('slug', $data['slug'])
                ->when($originalSlug, fn ($query) => $query->where('slug', '!=', $sourceSlug))
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'slug' => __('That slug is already used under one or more grades.'),
                ]);
            }

            $ids = [];
            foreach ($classNodes as $classNode) {
                $leaf = $existing->get($classNode->id);

                if ($leaf) {
                    $leaf->forceFill([
                        'slug' => $data['slug'],
                        'node_type' => $data['node_type'],
                        'order' => (int) $data['order'],
                        'status' => true,
                    ])->saveQuietly();
                } else {
                    $leaf = $this->insertNode($classNode->id, $data);
                }

                $this->upsertTranslation($leaf->id, $data['name']);
                $ids[] = $leaf->id;
            }

            $after = CourseCategory::whereIn('id', $ids)
                ->get()
                ->map(fn (CourseCategory $node) => $this->nodeSnapshot($node))
                ->all();
            $this->audit->record(
                $originalSlug ? 'shared_leaf_updated' : 'shared_leaf_created',
                $ids[0] ?? null,
                $before,
                $after,
                ['education_level_id' => $level->id, 'copies' => count($ids)]
            );
            CourseCategoryCacheClear::clear();

            return $ids;
        });
    }

    public function archiveSharedLeaf(CourseCategory $educationLevel, string $slug): int
    {
        return DB::transaction(function () use ($educationLevel, $slug) {
            $level = CourseCategory::lockForUpdate()->findOrFail($educationLevel->id);
            $this->assertEducationLevel($level);
            $classIds = CourseCategory::where('parent_id', $level->id)
                ->whereIn('node_type', self::CLASS_TYPES)
                ->pluck('id');
            $ids = CourseCategory::whereIn('parent_id', $classIds)
                ->where('slug', $slug)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($ids === []) {
                throw ValidationException::withMessages(['slug' => __('The shared subject was not found.')]);
            }

            $before = CourseCategory::whereIn('id', $ids)->get()
                ->map(fn (CourseCategory $node) => $this->nodeSnapshot($node))->all();
            $this->assertNoExternalReferences($ids);
            $archived = $this->archiveIds($ids);
            $this->audit->record('shared_leaf_archived', $ids[0], $before, null, [
                'education_level_id' => $level->id,
                'copies' => count($ids),
            ]);
            CourseCategoryCacheClear::clear();

            return $archived;
        });
    }

    public function archiveNode(CourseCategory $node): int
    {
        return DB::transaction(function () use ($node) {
            $node = CourseCategory::lockForUpdate()->findOrFail($node->id);
            $ids = $this->subtreeIds($node->id);
            $before = CourseCategory::whereIn('id', $ids)->get()
                ->map(fn (CourseCategory $row) => $this->nodeSnapshot($row))->all();
            $this->assertNoExternalReferences($ids);
            $archived = $this->archiveIds(array_reverse($ids));
            $this->audit->record('branch_archived', $node->id, $before, null, [
                'nodes' => count($ids),
            ]);
            CourseCategoryCacheClear::clear();

            return $archived;
        });
    }

    public function restoreNode(int $nodeId): int
    {
        return DB::transaction(function () use ($nodeId) {
            $node = CourseCategory::withTrashed()->lockForUpdate()->findOrFail($nodeId);
            $before = $this->nodeSnapshot($node);

            if ($node->parent_id) {
                $parent = CourseCategory::find($node->parent_id);
                if (! $parent) {
                    throw ValidationException::withMessages([
                        'node' => __('Restore the parent taxonomy node first.'),
                    ]);
                }
            }

            $ids = $this->sharedArchivedLeafIds($node);
            if ($ids === []) {
                $ids = $this->subtreeIds($node->id, true);
            }
            $rows = CourseCategory::withTrashed()->whereIn('id', $ids)->orderBy('id')->get();

            foreach ($rows as $row) {
                $conflict = CourseCategory::where('parent_id', $row->parent_id)
                    ->where('slug', $row->slug)
                    ->where('id', '!=', $row->id)
                    ->exists();
                if ($conflict) {
                    throw ValidationException::withMessages([
                        'node' => __('Cannot restore :slug because an active sibling already uses that slug.', [
                            'slug' => $row->slug,
                        ]),
                    ]);
                }
            }

            $restored = DB::table('course_categories')->whereIn('id', $ids)->update([
                'deleted_at' => null,
                'status' => 1,
                'updated_at' => now(),
            ]);
            $this->audit->record('branch_restored', $node->id, $before, [
                'ids' => $ids,
                'restored' => $restored,
            ]);
            CourseCategoryCacheClear::clear();

            return $restored;
        });
    }

    private function sharedArchivedLeafIds(CourseCategory $node): array
    {
        if (! in_array($node->node_type, self::LEAF_TYPES, true) || ! $node->parent_id) {
            return [];
        }

        $classNode = CourseCategory::withTrashed()->find($node->parent_id);
        if (! $classNode || ! in_array($classNode->node_type, self::CLASS_TYPES, true)) {
            return [];
        }

        $level = CourseCategory::withTrashed()->find($classNode->parent_id);
        if (! $level || $level->node_type !== CourseCategory::TYPE_EDUCATION_LEVEL) {
            return [];
        }

        $classIds = CourseCategory::withTrashed()
            ->where('parent_id', $level->id)
            ->whereIn('node_type', self::CLASS_TYPES)
            ->pluck('id');

        return CourseCategory::withTrashed()
            ->whereIn('parent_id', $classIds)
            ->where('slug', $node->slug)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function insertNode(?int $parentId, array $data): CourseCategory
    {
        $node = new CourseCategory();
        $node->forceFill([
            'parent_id' => $parentId,
            'slug' => $data['slug'],
            'node_type' => $data['node_type'],
            'order' => (int) $data['order'],
            'status' => true,
        ])->saveQuietly();
        $this->upsertTranslation($node->id, $data['name']);

        return $node;
    }

    private function cloneSharedLeavesIntoClass(CourseCategory $level, CourseCategory $newClass): void
    {
        $templateClass = CourseCategory::where('parent_id', $level->id)
            ->whereIn('node_type', self::CLASS_TYPES)
            ->where('id', '!=', $newClass->id)
            ->orderBy('order')
            ->orderBy('id')
            ->first();

        if (! $templateClass) {
            return;
        }

        $templateLeaves = CourseCategory::where('parent_id', $templateClass->id)
            ->whereIn('node_type', self::LEAF_TYPES)
            ->with(['translations'])
            ->orderBy('order')
            ->get();

        foreach ($templateLeaves as $template) {
            $leaf = $this->insertNode($newClass->id, [
                'slug' => $template->slug,
                'name' => $template->translations->firstWhere('lang_code', $this->languageCode())?->name
                    ?? $template->translations->first()?->name
                    ?? $template->slug,
                'node_type' => $template->node_type,
                'order' => $template->order,
            ]);

            foreach ($template->translations as $translation) {
                $this->upsertTranslation($leaf->id, $translation->name, $translation->lang_code);
            }
        }
    }

    private function assertAllowedType(?CourseCategory $parent, string $nodeType): void
    {
        if (! in_array($nodeType, $this->allowedChildTypes($parent), true)) {
            throw ValidationException::withMessages([
                'node_type' => __('That node type is not valid beneath the selected parent.'),
            ]);
        }
    }

    private function assertSiblingSlugAvailable(?int $parentId, string $slug, ?int $ignoreId = null): void
    {
        $exists = CourseCategory::where('parent_id', $parentId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'slug' => __('That slug is already used by an active sibling.'),
            ]);
        }
    }

    private function assertEducationLevel(CourseCategory $node): void
    {
        if ($node->parent_id !== null || $node->node_type !== CourseCategory::TYPE_EDUCATION_LEVEL) {
            throw ValidationException::withMessages([
                'education_level_id' => __('The selected node is not an education level.'),
            ]);
        }
    }

    private function assertNoExternalReferences(array $ids): void
    {
        $references = [
            ['products', 'category_id'],
            ['courses', 'category_id'],
            ['user_skill_topics', 'category_id'],
            ['course_filters', 'course_category_id'],
        ];

        foreach ($references as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $count = DB::table($table)->whereIn($column, $ids)->count();
            if ($count > 0) {
                throw ValidationException::withMessages([
                    'node' => __('This taxonomy branch cannot be archived because :count records in :table use it.', [
                        'count' => $count,
                        'table' => $table,
                    ]),
                ]);
            }
        }
    }

    private function subtreeIds(int $rootId, bool $withTrashed = false): array
    {
        $query = $withTrashed ? CourseCategory::withTrashed() : CourseCategory::query();
        $rows = $query->get(['id', 'parent_id']);
        $byParent = $rows->groupBy(fn ($row) => (string) $row->parent_id);
        $ids = [];
        $walk = function (int $id) use (&$walk, &$ids, $byParent): void {
            $ids[] = $id;
            foreach ($byParent[(string) $id] ?? [] as $child) {
                $walk((int) $child->id);
            }
        };
        $walk($rootId);

        return $ids;
    }

    private function archiveIds(array $ids): int
    {
        return DB::table('course_categories')
            ->whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->update([
                'status' => 0,
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function upsertTranslation(int $categoryId, string $name, ?string $languageCode = null): void
    {
        $languageCode ??= $this->languageCode();
        $query = DB::table('course_category_translations')->where([
            'course_category_id' => $categoryId,
            'lang_code' => $languageCode,
        ]);

        if ($query->exists()) {
            $query->update(['name' => $name, 'updated_at' => now()]);
        } else {
            DB::table('course_category_translations')->insert([
                'course_category_id' => $categoryId,
                'lang_code' => $languageCode,
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function languageCode(): string
    {
        return function_exists('getSessionLanguage')
            ? getSessionLanguage()
            : config('app.locale', 'en');
    }

    private function nodeSnapshot(CourseCategory $node): array
    {
        return [
            'id' => (int) $node->id,
            'parent_id' => $node->parent_id ? (int) $node->parent_id : null,
            'slug' => (string) $node->slug,
            'name' => (string) ($node->getAttribute('translation_name') ?: $node->name),
            'node_type' => (string) $node->node_type,
            'order' => (int) $node->order,
            'status' => (bool) $node->status,
        ];
    }
}
