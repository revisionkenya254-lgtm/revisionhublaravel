<?php

namespace Modules\Course\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Taxonomy\TaxonomyManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Course\app\Models\CourseCategory;

class TaxonomyManagementController extends Controller
{
    public function __construct(private readonly TaxonomyManagementService $taxonomy)
    {
    }

    public function index(): View
    {
        $levels = CourseCategory::with([
            'translation',
            'subCategories' => fn ($query) => $query->with(['translation', 'subCategories.translation'])
                ->orderBy('order')
                ->orderBy('id'),
        ])
            ->whereNull('parent_id')
            ->where('node_type', CourseCategory::TYPE_EDUCATION_LEVEL)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        $sharedLeaves = $levels->mapWithKeys(function (CourseCategory $level) {
            $classNodes = $level->subCategories->whereIn('node_type', [
                CourseCategory::TYPE_GRADE,
                CourseCategory::TYPE_FORM,
                CourseCategory::TYPE_NATIONAL_EXAM,
            ]);

            $leaves = $classNodes
                ->flatMap->subCategories
                ->whereIn('node_type', [CourseCategory::TYPE_SUBJECT, CourseCategory::TYPE_PATHWAY])
                ->groupBy('slug')
                ->map(function ($copies, string $slug) use ($classNodes) {
                    $first = $copies->sortBy('order')->first();

                    return [
                        'slug' => $slug,
                        'name' => $first?->translation?->name ?? $slug,
                        'node_type' => $first?->node_type,
                        'order' => $first?->order ?? 0,
                        'coverage' => $copies->pluck('parent_id')->unique()->count(),
                        'expected' => $classNodes->count(),
                    ];
                })
                ->sortBy('order')
                ->values();

            return [$level->id => $leaves];
        });

        $archivedNodes = CourseCategory::onlyTrashed()
            ->with([
                'translation',
                'parentCategory' => fn ($query) => $query->withTrashed()->with([
                    'translation',
                    'parentCategory' => fn ($parentQuery) => $parentQuery->withTrashed(),
                ]),
            ])
            ->orderByDesc('deleted_at')
            ->limit(200)
            ->get()
            ->filter(fn (CourseCategory $node) => ! $node->parentCategory || ! $node->parentCategory->trashed())
            ->unique(function (CourseCategory $node) {
                $parent = $node->parentCategory;
                if (in_array($node->node_type, [CourseCategory::TYPE_SUBJECT, CourseCategory::TYPE_PATHWAY], true)
                    && $parent
                    && in_array($parent->node_type, [
                        CourseCategory::TYPE_GRADE,
                        CourseCategory::TYPE_FORM,
                        CourseCategory::TYPE_NATIONAL_EXAM,
                    ], true)) {
                    return 'shared:'.$parent->parent_id.':'.$node->slug;
                }

                return 'node:'.$node->id;
            })
            ->take(50)
            ->values();

        return view('course::taxonomy.index', compact('levels', 'sharedLeaves', 'archivedNodes'));
    }

    public function createNode(Request $request): View|RedirectResponse
    {
        $parent = $request->integer('parent_id')
            ? CourseCategory::findOrFail($request->integer('parent_id'))
            : null;

        if ($parent && in_array($parent->node_type, [
            CourseCategory::TYPE_GRADE,
            CourseCategory::TYPE_FORM,
            CourseCategory::TYPE_NATIONAL_EXAM,
        ], true)) {
            return redirect()->route('admin.taxonomy.shared.create', [
                'education_level' => $parent->parent_id,
            ]);
        }

        $allowedTypes = $this->taxonomy->allowedChildTypes($parent);

        return view('course::taxonomy.node-form', compact('parent', 'allowedTypes'));
    }

    public function storeNode(Request $request): RedirectResponse
    {
        $data = $this->validateNode($request);
        $this->taxonomy->createNode($request->integer('parent_id') ?: null, $data);

        return $this->success(__('Taxonomy node created.'));
    }

    public function editNode(CourseCategory $node): View|RedirectResponse
    {
        $parent = $node->parentCategory;
        if ($parent && in_array($node->node_type, [
            CourseCategory::TYPE_SUBJECT,
            CourseCategory::TYPE_PATHWAY,
        ], true) && in_array($parent->node_type, [
            CourseCategory::TYPE_GRADE,
            CourseCategory::TYPE_FORM,
            CourseCategory::TYPE_NATIONAL_EXAM,
        ], true)) {
            return redirect()->route('admin.taxonomy.shared.edit', [
                'education_level' => $parent->parent_id,
                'slug' => $node->slug,
            ]);
        }

        $allowedTypes = $this->taxonomy->allowedChildTypes($parent);

        return view('course::taxonomy.node-form', compact('node', 'parent', 'allowedTypes'));
    }

    public function updateNode(Request $request, CourseCategory $node): RedirectResponse
    {
        $this->taxonomy->updateNode($node, $this->validateNode($request));

        return $this->success(__('Taxonomy node updated.'));
    }

    public function archiveNode(CourseCategory $node): RedirectResponse
    {
        $this->taxonomy->archiveNode($node);

        return $this->success(__('Taxonomy branch archived.'));
    }

    public function restoreNode(int $node): RedirectResponse
    {
        $this->taxonomy->restoreNode($node);

        return $this->success(__('Taxonomy branch restored.'));
    }

    public function createShared(CourseCategory $educationLevel): View
    {
        $this->ensureEducationLevel($educationLevel);

        return view('course::taxonomy.shared-form', compact('educationLevel'));
    }

    public function storeShared(Request $request, CourseCategory $educationLevel): RedirectResponse
    {
        $this->taxonomy->syncSharedLeaf($educationLevel, null, $this->validateShared($request));

        return $this->success(__('Shared subject or pathway created for every grade and assessment.'));
    }

    public function editShared(CourseCategory $educationLevel, string $slug): View
    {
        $this->ensureEducationLevel($educationLevel);
        $classIds = CourseCategory::where('parent_id', $educationLevel->id)
            ->whereIn('node_type', [
                CourseCategory::TYPE_GRADE,
                CourseCategory::TYPE_FORM,
                CourseCategory::TYPE_NATIONAL_EXAM,
            ])
            ->pluck('id');
        $leaf = CourseCategory::with('translation')
            ->whereIn('parent_id', $classIds)
            ->where('slug', $slug)
            ->firstOrFail();

        return view('course::taxonomy.shared-form', compact('educationLevel', 'leaf', 'slug'));
    }

    public function updateShared(Request $request, CourseCategory $educationLevel, string $slug): RedirectResponse
    {
        $this->taxonomy->syncSharedLeaf($educationLevel, $slug, $this->validateShared($request));

        return $this->success(__('Shared subject or pathway updated everywhere.'));
    }

    public function archiveShared(CourseCategory $educationLevel, string $slug): RedirectResponse
    {
        $this->taxonomy->archiveSharedLeaf($educationLevel, $slug);

        return $this->success(__('Shared subject or pathway archived everywhere.'));
    }

    private function validateNode(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'node_type' => ['required', 'string', 'in:'.implode(',', CourseCategory::NODE_TYPES)],
            'order' => ['required', 'integer', 'min:0', 'max:65535'],
            'parent_id' => ['nullable', 'integer', 'exists:course_categories,id'],
        ]);
    }

    private function validateShared(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'node_type' => ['required', 'in:subject,pathway'],
            'order' => ['required', 'integer', 'min:0', 'max:65535'],
        ]);
    }

    private function ensureEducationLevel(CourseCategory $node): void
    {
        abort_unless(
            $node->parent_id === null && $node->node_type === CourseCategory::TYPE_EDUCATION_LEVEL,
            404
        );
    }

    private function success(string $message): RedirectResponse
    {
        return redirect()->route('admin.taxonomy.index')->with([
            'messege' => $message,
            'alert-type' => 'success',
        ]);
    }
}
