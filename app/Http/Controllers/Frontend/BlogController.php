<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Rules\CustomRecaptcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Blog\app\Helper\BlogHelper;
use Modules\Blog\app\Models\Blog;
use Modules\Blog\app\Models\BlogComment;

class BlogController extends Controller
{
    function index() {
        $blogs = BlogHelper::index(request('category'), request('search'))->paginate(9);

        $categories = BlogHelper::categories();
        $popularBlogs = BlogHelper::popularBlogs();
        return view('frontend.pages.blog', compact('blogs', 'categories', 'popularBlogs'));
    }

    function show(string $slug) {
       $blog = BlogHelper::show($slug);
       $latestBlogs = BlogHelper::latestBlogs($blog->id);
       $categories = BlogHelper::categories();
       $comments = BlogComment::where(['blog_id' => $blog->id])->where('status', 1)->orderBy('created_at', 'desc')->get();

       return view('frontend.pages.blog-details', compact('blog', 'latestBlogs', 'categories', 'comments'));
    }

    function submitComment(Request $request) {
       $request->validate([
        'comment' => ['required', 'max:1000'], 
        'g-recaptcha-response' => Cache::get('setting')->recaptcha_status == 'active' ? ['required', new CustomRecaptcha()] : 'nullable',
       ], [
        'comment.required' => __('The comment field is required'),
        'comment.max' => __('The comment must not be greater than 1000 characters'),
        'g-recaptcha-response.required' => __('The reCAPTCHA verification is required'),
        'g-recaptcha-response.recaptcha' => __('The reCAPTCHA verification failed'),
       ]);
       $comment = new BlogComment();

       $comment->blog_id = $request->blog_id;
       $comment->user_id = userAuth()->id;
       $comment->comment = $request->comment;
       $comment->save();
       return redirect()->back()->withFragment('comments')->with(['messege' => __('Comment added successfully. waiting for approval'), 'alert-type' => 'success']);
    }
}
