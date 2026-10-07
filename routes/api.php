<?php

use App\Http\Controllers\API\AiChatController;
use App\Http\Controllers\API\AuthenticatedController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\FrontendController;
use App\Http\Controllers\API\LessonResourceDownloadController;
use App\Http\Controllers\API\LibraryController;
use App\Http\Controllers\API\ProductDownloadController;
use App\Http\Controllers\API\SubscriptionController;
use App\Http\Controllers\API\WishlistController;
use Illuminate\Support\Facades\Route;
use Modules\BasicPayment\app\Http\Controllers\API\MpesaStkPushCallbackController;
use Modules\BasicPayment\app\Http\Controllers\API\PaymentController as BasicPaymentController;

Route::prefix('auth')->group(function () {
    Route::middleware('guest:sanctum')->group(function () {
        Route::post('register', [AuthenticatedController::class, 'register'])->name('api.register')->middleware('throttle:auth');
        Route::post('login', [AuthenticatedController::class, 'login'])->name('api.login')->middleware('throttle:auth');
        Route::post('verify-otp', [AuthenticatedController::class, 'verifyOtp'])->name('api.verify-otp')->middleware('throttle:auth');
        Route::post('resend-otp', [AuthenticatedController::class, 'resendOtp'])->name('api.resend-otp')->middleware('throttle:auth');
        Route::post('forgot-password', [AuthenticatedController::class, 'forgetPassword'])->name('api.forget-password')->middleware('throttle:password-reset');
        Route::post('reset-password', [AuthenticatedController::class, 'resetPassword'])->name('api.reset-password')->middleware('throttle:password-reset');
        Route::post('refresh', [AuthenticatedController::class, 'refresh'])->name('api.refresh')->middleware('throttle:auth');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthenticatedController::class, 'logout'])->name('api.logout');
        Route::post('logout-all', [AuthenticatedController::class, 'logoutAllApp'])->name('api.logoutAllApp');
        Route::get('me', [AuthenticatedController::class, 'me']);
        Route::get('check-token', [AuthenticatedController::class, 'checkAccessToken']);
        Route::get('devices', [AuthenticatedController::class, 'devices']);
        Route::delete('devices/{sessionId}', [AuthenticatedController::class, 'destroyDevice']);
        Route::delete('devices', [AuthenticatedController::class, 'destroyAllDevices']);
    });
});

Route::prefix('bootstrap')->controller(FrontendController::class)->group(function () {
    Route::get('settings', 'settings');
    Route::get('categories', 'mainCategories');
    Route::get('languages', 'allLanguages');
    Route::get('currencies', 'allCurrency');
    Route::get('countries', 'country_list');
    Route::get('social-links', 'socialLinks');
    Route::get('menu', 'menu');
    Route::get('mobile-menu', 'mobileMenu');
    Route::get('onboarding', 'on_boarding_screen');
    Route::get('faqs', 'faqs');
    Route::get('privacy-policy', 'privacy_policy');
    Route::get('terms-and-conditions', 'terms_and_conditions');
    Route::get('pages/{slug}', 'page');
});

Route::prefix('catalog')->controller(FrontendController::class)->group(function () {
    Route::get('categories/{slug}/subcategories', 'sub_categories');
    Route::get('course-languages', 'course_languages');
    Route::get('course-levels', 'course_levels');
    Route::get('popular', 'popular_courses');
    Route::get('fresh', 'fresh_courses');
    Route::get('products/{type}/{slug}', 'product')
        ->where('type', 'course|past_paper|prediction|note|quiz')
        ->where('slug', '[a-zA-Z0-9-_]+');
    Route::get('products/{type?}', 'products')
        ->where('type', 'course|past_paper|prediction|note|quiz');
    Route::get('search', 'search_courses');
    Route::get('courses', 'search_courses');
    Route::get('courses/{slug}', 'course_details');
    Route::get('courses/{slug}/reviews', 'course_reviews');
    Route::get('lessons/{lesson_id}/free-preview', 'get_lesson_info')
        ->name('api.free-lesson')
        ->whereNumber('lesson_id');
});

Route::get('subscriptions/plans', [SubscriptionController::class, 'plans']);

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('subscriptions')->controller(SubscriptionController::class)->group(function () {
        Route::get('me', 'current');
        Route::post('checkout', 'checkout');
        Route::get('orders/{invoiceId}', 'order')->where('invoiceId', '[A-Za-z0-9-]+');
    });
    Route::post('subscriptions/orders/{invoiceId}/payment', [BasicPaymentController::class, 'clientMpesaPayment'])
        ->where('invoiceId', '[A-Za-z0-9-]+');
    Route::get('subscriptions/orders/{invoiceId}/payment', [BasicPaymentController::class, 'clientMpesaStatus'])
        ->where('invoiceId', '[A-Za-z0-9-]+');

    Route::get('library', [LibraryController::class, 'index']);

    Route::prefix('learning')->controller(DashboardController::class)->group(function () {
        Route::get('enrolled', 'enrolled_courses');
        Route::get('{slug}', 'course_learning')->name('api.learning')->where('slug', '[a-zA-Z0-9-_]+');
        Route::get('{slug}/progress', 'learning_progress')->where('slug', '[a-zA-Z0-9-_]+');
        Route::get('{slug}/items/{type}/{lesson_id}', 'get_lesson_info')
            ->name('api.get-file-info')
            ->where('slug', '[a-zA-Z0-9-_]+')
            ->where('type', 'lesson|document|live|quiz')
            ->whereNumber('lesson_id');
        Route::post('lessons/{lesson_id}/complete', 'make_lesson_complete')->whereNumber('lesson_id');
        Route::get('{slug}/quiz/{id}', 'quiz_index')->name('api.quiz-index')->where('slug', '[a-zA-Z0-9-_]+')->whereNumber('id');
        Route::post('{slug}/quiz/{id}', 'quiz_store')->where('slug', '[a-zA-Z0-9-_]+')->whereNumber('id');
        Route::get('{slug}/quiz-results/{id}', 'quiz_results')->where('slug', '[a-zA-Z0-9-_]+')->whereNumber('id');
        Route::get('{slug}/announcements', 'course_announcements')->where('slug', '[a-zA-Z0-9-_]+');
    });

    Route::prefix('cart')->controller(CartController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('items/{slug}', 'add_to_cart')->where('slug', '[a-zA-Z0-9-_]+');
        Route::delete('items/{slug}', 'remove_from_cart')->where('slug', '[a-zA-Z0-9-_]+');
    });

    Route::prefix('orders')->controller(DashboardController::class)->group(function () {
        Route::get('/', 'orders');
        Route::get('{invoice_id}', 'show_order')->where('invoice_id', '[a-zA-Z0-9-_]+');
        Route::get('{invoice_id}/download', 'downloadInvoice')->where('invoice_id', '[a-zA-Z0-9-_]+');
    });

    Route::get('downloads/products/{product_type}/{product_id}', [ProductDownloadController::class, 'create'])
        ->where('product_type', 'course|past_paper|prediction|note|quiz')
        // Older Android builds serialize integer IDs as doubles (for example, "27.0").
        // Accept only a zero decimal suffix; the controller normalizes it to an integer.
        ->where('product_id', '[0-9]+(?:\\.0+)?');

    Route::prefix('certificates')->controller(DashboardController::class)->group(function () {
        Route::get('{course_slug}/download', 'downloadCertificate')->where('course_slug', '[a-zA-Z0-9-_]+');
    });

    Route::prefix('profile')->controller(DashboardController::class)->group(function () {
        Route::get('/', 'profile');
        Route::put('/', 'update_profile');
        Route::post('avatar', 'update_profile_picture')->withoutMiddleware('json.only');
        Route::put('bio', 'update_bio');
        Route::put('password', 'update_password');
        Route::put('address', 'update_address');
        Route::put('socials', 'update_socials');
    });

    Route::prefix('reviews')->controller(DashboardController::class)->group(function () {
        Route::get('/', 'reviews');
        Route::get('{id}', 'show_review')->whereNumber('id');
        Route::delete('{id}', 'destroy_review')->whereNumber('id');
    });

    Route::prefix('courses')->controller(DashboardController::class)->group(function () {
        Route::post('{slug}/reviews', 'store_review')->where('slug', '[a-zA-Z0-9-_]+');
    });

    Route::prefix('wishlist')->group(function () {
        Route::get('/', [WishlistController::class, 'index']);
        Route::post('/', [WishlistController::class, 'store']);
        Route::delete('{itemType}/{itemId}', [WishlistController::class, 'destroy'])
            ->where('itemType', 'course|note|quiz|prediction|past_paper|past-paper|pastpaper|paspaper')
            ->whereNumber('itemId');
    });

    Route::prefix('quizzes')->controller(DashboardController::class)->group(function () {
        Route::get('attempts', 'quiz_attempts');
        Route::get('attempts/{id}', 'show_quiz_attempt')->whereNumber('id');
    });

    Route::prefix('qna')->controller(DashboardController::class)->group(function () {
        Route::get('lessons/{course_slug}/{lesson_id}/questions', 'fetch_lesson_questions')
            ->where('course_slug', '[a-zA-Z0-9-_]+')
            ->whereNumber('lesson_id');
        Route::post('lessons/{course_slug}/{lesson_id}/questions', 'create_lesson_questions')
            ->name('api.questions-create')
            ->where('course_slug', '[a-zA-Z0-9-_]+')
            ->whereNumber('lesson_id');
        Route::delete('questions/{question_id}', 'destroyQuestion')->whereNumber('question_id');
        Route::post('questions/replies/{lesson_id}/{question_id}', 'create_replay_questions')
            ->whereNumber('lesson_id')
            ->whereNumber('question_id');
        Route::delete('replies/{reply_id}', 'destroyReply')->whereNumber('reply_id');
    });

    Route::prefix('ai')->controller(AiChatController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('credits', 'credits');
        Route::get('chat/conversations', 'conversations');
        Route::post('chat/conversations', 'storeConversation');
        Route::get('chat/conversations/{conversation}/messages', 'conversationMessages');
        Route::post('chat/conversations/{conversation}/stream', 'stream');
        Route::post('chat/conversations/{conversation}/image', 'generateImage');
        Route::delete('chat/conversations/{conversation}', 'destroy');
    });

    Route::prefix('payments')->controller(BasicPaymentController::class)->group(function () {
        Route::get('methods', 'all_payment');
        Route::get('free-order', 'pay_via_free_gateway');
        Route::post('mpesa/orders', 'createUnifiedClientMpesaOrder');
        Route::post('mpesa/orders/{invoiceId}/payment', 'clientMpesaPayment')
            ->where('invoiceId', '[A-Za-z0-9-]+');
        Route::get('mpesa/orders/{invoiceId}', 'clientMpesaStatus')
            ->where('invoiceId', '[A-Za-z0-9-]+');
        Route::get('paypal', 'placeOrder')->defaults('paymentMethod', 'paypal');
    });
});

Route::post('payments/mpesa/callback', MpesaStkPushCallbackController::class)
    ->name('mpesa.stkpush.callback');

// These links are authorized by short-lived signatures issued after access checks.
Route::get('orders/{invoice_id}/file', [DashboardController::class, 'streamInvoice'])
    ->name('api.orders.invoice.file')
    ->where('invoice_id', '[a-zA-Z0-9-_]+')
    ->middleware('signed')
    ->withoutMiddleware('json.only');

Route::get('downloads/products/{product_type}/{product_id}/file', [ProductDownloadController::class, 'file'])
    ->name('api.products.download.file')
    ->where('product_type', 'course|past_paper|prediction|note|quiz')
    ->whereNumber('product_id')
    ->middleware('signed')
    ->withoutMiddleware('json.only');

Route::get('lesson-resources/{resource}/download', LessonResourceDownloadController::class)
    ->name('api.lesson-resources.download')
    ->whereNumber('resource')
    ->middleware('signed')
    ->withoutMiddleware('json.only');

Route::fallback(function () {
    return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
});
