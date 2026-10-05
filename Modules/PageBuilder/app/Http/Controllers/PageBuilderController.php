<?php

namespace Modules\PageBuilder\app\Http\Controllers;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Traits\RedirectHelperTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Modules\Language\app\Enums\TranslationModels;
use Modules\Language\app\Models\Language;
use Modules\Language\app\Traits\GenerateTranslationTrait;
use Modules\PageBuilder\app\Models\CustomPage;

class PageBuilderController extends Controller {
    use GenerateTranslationTrait, RedirectHelperTrait;
    /**
     * Display a listing of the resource.
     */
    public function index() {
        checkAdminHasPermissionAndThrowException('page.management');
        $pages = CustomPage::get();
        return view('pagebuilder::index', compact('pages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {
        return view('pagebuilder::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        checkAdminHasPermissionAndThrowException('page.management');
        $validated = $request->validate([
            'name'            => ['required'],
            'slug'            => ['required', 'string', 'max:255', 'unique:custom_pages,slug'],
            'fixed_header'    => ['required'],
            'status'          => ['required'],
            'seo_title'       => 'nullable|string|max:1000',
            'seo_description' => 'nullable|string|max:2000',
        ], [
            'name.required'          => __('The name field is required.'),
            'slug.required'          => __('The slug field is required.'),
            'seo_title.max'          => __('SEO title must be a string with a maximum length of 1000 characters.'),
            'seo_description.max'    => __('SEO description must be a string with a maximum length of 2000 characters.'),
            'seo_title.string'       => __('SEO title must be a string with a maximum length of 1000 characters.'),
            'seo_description.string' => __('SEO description must be a string with a maximum length of 2000 characters.'),
        ]);

        $pageBuilder = CustomPage::create($validated);

        $languages = allLanguages();

        $this->generateTranslations(
            TranslationModels::CustomPage,
            $pageBuilder,
            'custom_page_id',
            $request,
        );

        return $this->redirectWithMessage(RedirectType::CREATE->value, 'admin.page-builder.edit', ['page_builder' => $pageBuilder->id, 'code' => $languages->first()->code]);

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id) {
        checkAdminHasPermissionAndThrowException('page.management');
        $code = request('code') ?? getSessionLanguage();
        abort_unless(Language::where('code', $code)->exists(), 404);
        $page = CustomPage::with('translation')->findOrFail($id);
        $languages = allLanguages();

        return view('pagebuilder::edit', compact('page', 'code', 'languages'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse {
        checkAdminHasPermissionAndThrowException('page.management');

        $validated = $request->validate([
            'name'            => ['required'],
            'slug'            => ['required', 'string', 'max:255', 'unique:custom_pages,slug, ' . $id],
            'fixed_header'    => ['required'],
            'status'          => ['required'],
            'seo_title'       => 'nullable|string|max:1000',
            'seo_description' => 'nullable|string|max:2000',
        ], [
            'name.required'          => __('The name field is required.'),
            'slug.required'          => __('The slug field is required.'),
            'seo_title.max'          => __('SEO title must be a string with a maximum length of 1000 characters.'),
            'seo_description.max'    => __('SEO description must be a string with a maximum length of 2000 characters.'),
            'seo_title.string'       => __('SEO title must be a string with a maximum length of 1000 characters.'),
            'seo_description.string' => __('SEO description must be a string with a maximum length of 2000 characters.'),

        ]);

        $page = CustomPage::findOrFail($id);
        $page->update($validated);

        $this->updateTranslations(
            $page,
            $request,
            $validated,
        );

        return $this->redirectWithMessage(RedirectType::UPDATE->value, 'admin.page-builder.edit', ['page_builder' => $page->id, 'code' => $request->code]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {
        checkAdminHasPermissionAndThrowException('page.management');

        $page = CustomPage::whereNotIn('slug',['privacy-policy', 'terms-and-conditions'])->findOrFail($id);
        $page->translations()->each(function ($translation) {
            $translation->custom_page()->dissociate();
            $translation->delete();
        });
        $page->delete();

        $directory = base_path("Modules/PageBuilder/resources/views/builder/pages/{$page->slug}");
        if (File::exists($directory) && File::isDirectory($directory)) {
            File::deleteDirectory($directory);
        }
        $vendorDirectory = base_path("Modules/PageBuilder/resources/views/temp/pages/{$page->slug}");
        if (File::exists($vendorDirectory) && File::isDirectory($vendorDirectory)) {
            File::deleteDirectory($vendorDirectory);
        }

        return $this->redirectWithMessage(RedirectType::DELETE->value, 'admin.page-builder.index');

    }

    public function statusUpdate($id) {
        checkAdminHasPermissionAndThrowException('page.management');

        $page = CustomPage::find($id);
        $status = $page->status == 1 ? 0 : 1;
        $page->update(['status' => $status]);

        $notification = __('Updated Successfully');

        return response()->json([
            'success' => true,
            'message' => $notification,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function editor($slug) {
        checkAdminHasPermissionAndThrowException('page.management');
        $code = request('code') ?? getSessionLanguage();
        $language = Language::where('code', $code)->first();
        abort_unless($language, 404);

        $page = CustomPage::whereSlug($slug)->firstOrFail();

        $base = base_path("Modules/PageBuilder/resources/views/temp/pages/{$slug}");
        $path = "{$base}/{$code}.json";

        $content = null;

        if (file_exists($path)) {
            $content = file_get_contents($path);
        } else {
            $default = allLanguages()?->first()?->code;
            $fallbackPath = $default ? "{$base}/{$default}.json" : null;

            if ($fallbackPath && file_exists($fallbackPath)) {
                $content = file_get_contents($fallbackPath);
            }
        }

        // Ensure content is a valid JSON-like string
        $isJsonLike = is_string($content) && preg_match('/^\s*[\{\[]/', $content);
        $page_json = $isJsonLike ? $content : json_encode([]);

        return view('pagebuilder::builder.index', compact('page', 'page_json', 'language'));
    }
    /**
     * Update the specified resource in storage.
     */
    public function updateEditor(Request $request, string $slug): JsonResponse {
        checkAdminHasPermissionAndThrowException('page.management');
        CustomPage::whereSlug($slug)->firstOrFail();

        $code = $request->code ?? getSessionLanguage();

        $request->merge([
            'code' => $code,
        ]);

        $validator = Validator::make($request->all(), [
            'code'    => 'required|exists:languages,code',
            'content' => 'required|string',
            'json'    => 'required|json',
        ], [
            'code.required'    => __('Language is required and must be a string.'),
            'code.exists'      => __('The selected language is invalid.'),
            'content.required' => __('The content field is required.'),
            'content.string'   => __('The content field must be a string.'),
            'json.required'    => __('The JSON structure is required.'),
            'json.json'        => __('The JSON field must be a valid JSON string.'),
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }
        try {
            $directory = base_path("Modules/PageBuilder/resources/views/builder/pages/{$slug}");
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $vendorDirectory = base_path("Modules/PageBuilder/resources/views/temp/pages/{$slug}");
            if (!file_exists($vendorDirectory)) {
                mkdir($vendorDirectory, 0755, true);
            }

            $path = "{$directory}/{$code}.blade.php";
            $json_path = "{$vendorDirectory}/{$code}.json";

            // Save the file
            file_put_contents($path, $request->content);
            file_put_contents($json_path, $request->json);

            return response()->json([
                'success' => true,
                'message' => __('Updated successfully'),
                'path'    => $path,
            ]);
        } catch (\Exception $e) {
            info($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Something went wrong'),
            ], 500);
        }
    }
    /**
     * Upload file
     */
    public function upload(Request $request): JsonResponse {
        checkAdminHasPermissionAndThrowException('page.management');
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimetypes:image/jpeg,image/png,image/gif,image/webp,image/svg+xml|max:2048',
        ], [
            'file.required'  => __('The image is required.'),
            'file.mimetypes' => __('The image must be an image file.'),
            'file.max'       => __('The image may not be greater than 2048 kilobytes.'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $file_name = null;
            if ($request->hasFile('file')) {
                $file_name = file_upload($request->file('file'), 'uploads/custom-images/');
            }

            return response()->json([
                'success'   => true,
                'message'   => __('Uploaded successfully.'),
                'file_name' => asset($file_name),
            ]);
        } catch (\Exception $e) {
            info($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Something went wrong'),
            ], 500);
        }
    }
    /**
     * Delete Upload file
     */
    public function deleteUpload(Request $request) {
        checkAdminHasPermissionAndThrowException('page.management');
        $validator = Validator::make($request->all(), [
            'file' => 'required',
        ], [
            'file.required' => __('The image is required.'),
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }
        try {
            $path = "uploads/custom-images/{$request->file}";
            if (File::exists(public_path($path))) {
                unlink(public_path($path));
            }

            return response()->json([
                'success' => true,
                'message' => __('Deleted successfully'),
            ]);
        } catch (\Exception $e) {
            info($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Something went wrong'),
            ], 500);
        }
    }
    /**
     * Frontend show
     */
    public function show($slug) {
        $page = CustomPage::whereSlug($slug)->active()->firstOrFail();
        $code = request('code') ?? getSessionLanguage();
        $base = "pagebuilder::builder.pages.{$page->slug}";
        $path = "{$base}.{$code}";

        if (!view()->exists($path)) {
            $default = allLanguages()?->first()?->code;
            $fallback = $default ? "{$base}.{$default}" : null;
            if (!$fallback || !view()->exists($fallback)) {
                $path = "pagebuilder::builder.fallback-page";
            } else {
                $path = $fallback;
            }
        }

        return view($path, compact('page'));
    }
}
