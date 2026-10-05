<?php

namespace Modules\Course\app\Http\Controllers;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Traits\RedirectHelperTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Course\app\Http\Requests\CourseCategoryStoreRequest;
use Modules\Course\app\Http\Requests\CourseSubCategoryRequest;
use Modules\Course\app\Models\CourseCategory;
use Modules\Language\app\Enums\TranslationModels;
use Modules\Language\app\Models\Language;
use Modules\Language\app\Traits\GenerateTranslationTrait;

class CourseSubCategoryController extends Controller
{
    use GenerateTranslationTrait, RedirectHelperTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CourseCategory::query();
        $query->when($request->keyword, function($q) {
            $q->whereHas('translations', function($q) {
                $q->where('name', 'like', '%' . request('keyword') . '%');
            });
        });
        $query->where('parent_id', $request->parent_id);
        $query->when(
            $request->status !== null && $request->status !== '',
            fn($q) => $q->where('status', $request->status)
        );
        $orderBy = $request->order_by == 1 ? 'asc' : 'desc';
        $categories = $request->get('par-page') == 'all' ?
            $query->orderBy('id', $orderBy)->get() :
            $query->orderBy('id', $orderBy)->paginate($request->get('par-page') ?? null)->withQueryString();
        $parentCategory = CourseCategory::find($request->parent_id);
        $isGrandChildFlow = $parentCategory?->parent_id !== null;

        return view('course::course-sub-category.index', compact('categories', 'parentCategory', 'isGrandChildFlow'));
    }

    public function levelIndex(Request $request, string $level)
    {
        abort_unless(in_array($level, ['sub', 'grand']), 404);

        $query = CourseCategory::query()->with([
            'translation',
            'parentCategory.translation',
            'parentCategory.parentCategory.translation',
        ]);

        $query->when($request->keyword, function($q) {
            $q->whereHas('translations', function($q) {
                $q->where('name', 'like', '%' . request('keyword') . '%');
            });
        });

        if ($level === 'sub') {
            $query->whereHas('parentCategory', fn($q) => $q->whereNull('parent_id'));
        } else {
            $query->whereHas('parentCategory.parentCategory', fn($q) => $q->whereNull('parent_id'));
        }

        $query->when(
            $request->status !== null && $request->status !== '',
            fn($q) => $q->where('status', $request->status)
        );

        $orderBy = $request->order_by == 1 ? 'asc' : 'desc';
        $categories = $request->get('par-page') == 'all' ?
            $query->orderBy('id', $orderBy)->get() :
            $query->orderBy('id', $orderBy)->paginate($request->get('par-page') ?? null)->withQueryString();

        $title = $level === 'sub' ? __('Sub Categories') : __('Grand Child Categories');

        return view('course::course-sub-category.level-index', compact('categories', 'level', 'title'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $selectedParentId = $request->parent_id;
        $selectedParent = $selectedParentId ? CourseCategory::find($selectedParentId) : null;
        $categoryLevel = $selectedParent?->parent_id ? 'grand' : 'sub';
        $title = $categoryLevel === 'grand' ? __('Create Grand Child Category') : __('Create Sub Category');
        $storeRouteName = 'admin.course-sub-categories.store';
        $parentCategories = CourseCategory::with('translation')
            ->when($categoryLevel === 'grand', function ($q) {
                $q->whereHas('parentCategory', fn($parent) => $parent->whereNull('parent_id'));
            }, function ($q) {
                $q->whereNull('parent_id');
            })
            ->where('status', 1)
            ->get();
        $existingCategories = CourseCategory::with([
            'translation',
            'parentCategory.translation',
            'parentCategory.parentCategory.translation',
        ])
            ->when($selectedParentId, function ($q) use ($selectedParentId) {
                $q->where('parent_id', $selectedParentId);
            }, function ($q) use ($categoryLevel) {
                if ($categoryLevel === 'grand') {
                    $q->whereHas('parentCategory.parentCategory', fn($parent) => $parent->whereNull('parent_id'));
                } else {
                    $q->whereHas('parentCategory', fn($parent) => $parent->whereNull('parent_id'));
                }
            })
            ->orderByDesc('id')
            ->get();

        return view('course::course-sub-category.create', compact('parentCategories', 'selectedParentId', 'categoryLevel', 'title', 'storeRouteName', 'existingCategories'));
    }

    public function createGrand(Request $request)
    {
        $selectedParentId = $request->parent_id;
        $parentCategories = CourseCategory::with('translation')
            ->whereHas('parentCategory', fn($parent) => $parent->whereNull('parent_id'))
            ->where('status', 1)
            ->get();
        $categoryLevel = 'grand';
        $title = __('Create Grand Child Category');
        $storeRouteName = 'admin.course-grand-categories.store';
        $existingCategories = CourseCategory::with([
            'translation',
            'parentCategory.translation',
            'parentCategory.parentCategory.translation',
        ])
            ->whereHas('parentCategory.parentCategory', fn($parent) => $parent->whereNull('parent_id'))
            ->orderByDesc('id')
            ->get();

        return view('course::course-sub-category.create', compact('parentCategories', 'selectedParentId', 'categoryLevel', 'title', 'storeRouteName', 'existingCategories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CourseSubCategoryRequest $request, string $parentId): RedirectResponse
    {
        $parentId = $request->parent_id ?? $parentId;
        $parent = CourseCategory::with('parentCategory')->findOrFail($parentId);
        if ($parent->parentCategory?->parent_id !== null) {
            return back()->withInput()->withErrors([
                'parent_id' => __('Grand child categories cannot be used as parents.'),
            ]);
        }

        $subCategory = new CourseCategory();
        $subCategory->slug = $request->slug;
        $subCategory->parent_id = $parentId;
        $subCategory->status = $request->status;
        $subCategory->save();

        $languages = Language::get();

        $this->generateTranslations(
            TranslationModels::CourseCategory,
            $subCategory,
            'course_category_id',
            $request,
        );

        return $this->redirectWithMessage(
            RedirectType::CREATE->value,
            'admin.course-sub-category.edit',
            [
                'parent_id' => $parentId,
                'sub_category_id' => $subCategory->id,
                'code' => $languages->first()->code
            ]
        );
    }

    public function storeWithParent(CourseSubCategoryRequest $request): RedirectResponse
    {
        return $this->store($request, $request->parent_id);
    }

    public function storeGrandWithParent(CourseSubCategoryRequest $request): RedirectResponse
    {
        $parent = CourseCategory::findOrFail($request->parent_id);
        if ($parent->parent_id === null || $parent->parentCategory?->parent_id !== null) {
            return back()->withInput()->withErrors([
                'parent_id' => __('Please select a sub category as the parent.'),
            ]);
        }

        return $this->store($request, $request->parent_id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $parentId, string $subCategoryId)
    {
        $code = request('code') ?? getSessionLanguage();
        if (!Language::where('code', $code)->exists()) {
            abort(404);
        }
        $subCategory = CourseCategory::findOrFail($subCategoryId);
        $languages = allLanguages();
        $isGrandCategory = $subCategory->parentCategory?->parent_id !== null;
        $parentCategories = CourseCategory::with('translation')
            ->when($isGrandCategory, function ($q) {
                $q->whereHas('parentCategory', fn($parent) => $parent->whereNull('parent_id'));
            }, function ($q) {
                $q->whereNull('parent_id');
            })
            ->where('id', '!=', $subCategory->id)
            ->where('status', 1)
            ->get();

        return view('course::course-sub-category.edit', compact('subCategory', 'code', 'languages', 'parentCategories', 'isGrandCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CourseSubCategoryRequest $request, $parentId, $subCategoryId): RedirectResponse
    {
        $category = CourseCategory::findOrFail($subCategoryId);
        $newParentId = $request->parent_id ?? $parentId;
        $parent = CourseCategory::with('parentCategory')->findOrFail($newParentId);
        if ($parent->parentCategory?->parent_id !== null) {
            return back()->withInput()->withErrors([
                'parent_id' => __('Grand child categories cannot be used as parents.'),
            ]);
        }

        $category->parent_id = $newParentId;
        $category->status = $request->status;
        $category->save();

        $languages = Language::get();

        $this->updateTranslations(
            $category,
            $request,
            $request->validated(),
        );

        return $this->redirectWithMessage(
            RedirectType::UPDATE->value,
            'admin.course-sub-category.edit',
            [
                'parent_id' => $parentId,
                'sub_category_id' => $subCategoryId,
                'code' => $languages->first()->code
            ]
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $parentId, string $subCategoryId)
    {
        $subCategory = CourseCategory::findOrFail($subCategoryId);
        if(Course::where('category_id', $subCategoryId)->exists()){
            return redirect()->route('admin.course-category.index')->with(['messege' => 'Category can not be deleted because it has courses', 'alert-type' => 'error']);
        }
        $subCategory->translations()->delete();
        $subCategory->delete();

        return $this->redirectWithMessage(RedirectType::DELETE->value, 'admin.course-sub-category.index', ['parent_id' => $parentId]);
    }

    public function statusUpdate($id)
    {
        $subCategory = CourseCategory::find($id);
        $status = $subCategory->status == 1 ? 0 : 1;
        $subCategory->update(['status' => $status]);

        $notification = __('Updated Successfully');

        return response()->json([
            'success' => true,
            'message' => $notification,
        ]);
    }
}
