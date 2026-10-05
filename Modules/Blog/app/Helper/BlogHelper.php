<?php

namespace Modules\Blog\app\Helper;

use Illuminate\Support\Facades\Cache;
use Modules\Blog\app\Models\Blog;
use Modules\Blog\app\Models\BlogCategory;

class BlogHelper {
    public static function featuredBlogs() {
        return Blog::select('blogs.*')
            ->selectRaw('t.title as translation_title')
            ->selectRaw('ct.title as category_translation_name')
            ->leftJoin('blog_translations as t', function ($q) {
                $q->on('t.blog_id', '=', 'blogs.id')
                    ->where('t.lang_code', getSessionLanguage());
            })
            ->leftJoin('blog_categories as bc', 'blogs.blog_category_id', '=', 'bc.id')
            ->leftJoin('blog_category_translations as ct', function ($q) {
                $q->on('ct.blog_category_id', '=', 'bc.id')
                    ->where('ct.lang_code', getSessionLanguage());
            })
            ->with('author:id,name')
            ->with('category:id,slug')
            ->where('bc.status', 1)
            ->where(['blogs.show_homepage' => 1, 'blogs.status' => 1])
            ->orderBy('blogs.created_at', 'desc')
            ->limit(4)
            ->get();
    }

    public static function index($category = null, $search = null) {
        $query = Blog::query()
            ->select('blogs.*')
            ->selectRaw('bt.title as translation_title')
            ->selectRaw('bt.description as translation_description')
            ->selectRaw('bc.slug as category_slug')
            ->selectRaw('bct.title as category_title')
            ->selectRaw('admin.name as author_name')
            ->leftJoin('blog_translations as bt', function ($q) {
                $q->on('bt.blog_id', '=', 'blogs.id')->where('bt.lang_code', getSessionLanguage());
            })
            ->leftJoin('blog_categories as bc', 'blogs.blog_category_id', '=', 'bc.id')
            ->leftJoin('blog_category_translations as bct', function ($q) {
                $q->on('bct.blog_category_id', '=', 'bc.id')->where('bct.lang_code', getSessionLanguage());
            })
            ->leftJoin('admins as admin', 'blogs.admin_id', '=', 'admin.id')
            ->when($search, function($query) use ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('bt.title', 'like', '%' . $search . '%')
                        ->orWhere('bt.description', 'like', '%' . $search . '%');
                });
            })
            ->when($category, function($query) use ($category) {
                $query->where('bc.slug', $category);
            })
            ->where('bc.status', 1)
            ->where('blogs.status', 1)
            ->orderBy('blogs.created_at', 'desc');

        return $query;
    }

    public static function popularBlogs() {
        return Blog::query()
            ->select('blogs.*')
            ->selectRaw('bt.title as translation_title')
            ->leftJoin('blog_translations as bt', function ($q) {
                $q->on('bt.blog_id', '=', 'blogs.id')->where('bt.lang_code', getSessionLanguage());
            })
            ->leftJoin('blog_categories as bc', 'blogs.blog_category_id', '=', 'bc.id')
            ->where('bc.status', 1)
            ->where(['blogs.status' => 1, 'blogs.is_popular' => 1])
            ->orderBy('blogs.created_at', 'desc')
            ->limit(8)
            ->get();
    }

    public static function latestBlogs($excludeId = null) {
        return Blog::query()
            ->select('blogs.*')
            ->selectRaw('bt.title as translation_title')
            ->leftJoin('blog_translations as bt', function ($q) {
                $q->on('bt.blog_id', '=', 'blogs.id')->where('bt.lang_code', getSessionLanguage());
            })
            ->leftJoin('blog_categories as bc', 'blogs.blog_category_id', '=', 'bc.id')
            ->where('bc.status', 1)
            ->where('blogs.status', 1)
            ->when($excludeId, function($query) use ($excludeId) {
                $query->where('blogs.id', '!=', $excludeId);
            })
            ->orderBy('blogs.created_at', 'desc')
            ->limit(8)
            ->get();
    }

    public static function categories() {
        $lang = getSessionLanguage();

        return Cache::rememberForever("blog_categories_active_{$lang}", function () use ($lang) {
            return BlogCategory::query()
                ->select('blog_categories.*')
                ->selectRaw('bct.title as translation_title')
                ->leftJoin('blog_category_translations as bct', function ($q) use ($lang) {
                    $q->on('bct.blog_category_id', '=', 'blog_categories.id')
                        ->where('bct.lang_code', $lang);
                })
                ->where('blog_categories.status', 1)
                ->get();
        });
    }

    public static function all() {
        $lang = getSessionLanguage();

        return Cache::rememberForever("blog_categories_all_{$lang}", function () use ($lang) {
            return BlogCategory::query()
                ->select('blog_categories.*')
                ->selectRaw('bct.title as translation_title')
                ->leftJoin('blog_category_translations as bct', function ($q) use ($lang) {
                    $q->on('bct.blog_category_id', '=', 'blog_categories.id')
                        ->where('bct.lang_code', $lang);
                })
                ->get();
        });
    }

    public static function show($slug) {
        return Blog::query()
            ->select('blogs.*')
            ->selectRaw('bt.title as translation_title')
            ->selectRaw('bt.description as translation_description')
            ->selectRaw('bt.seo_title as translation_seo_title')
            ->selectRaw('bt.seo_description as translation_seo_description')
            ->selectRaw('admin.name as author_name')
            ->selectRaw('admin.image as author_image')
            ->selectRaw('admin.bio as author_bio')
            ->leftJoin('blog_translations as bt', function ($q) {
                $q->on('bt.blog_id', '=', 'blogs.id')->where('bt.lang_code', getSessionLanguage());
            })
            ->leftJoin('blog_categories as bc', 'blogs.blog_category_id', '=', 'bc.id')
            ->leftJoin('admins as admin', 'blogs.admin_id', '=', 'admin.id')
            ->where('blogs.slug', $slug)
            ->where('bc.status', 1)
            ->where('blogs.status', 1)
            ->firstOrFail();
    }
}
