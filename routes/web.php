<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\QnaController;
use App\Http\Controllers\Frontend\BlogController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\LessonBuilderController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Frontend\DeviceController;
use App\Http\Controllers\Frontend\ContactController;
use App\Http\Controllers\Frontend\CheckOutController;
use App\Http\Controllers\Frontend\FavoriteController;
use App\Http\Controllers\Frontend\HomePageController;
use App\Http\Controllers\Frontend\LearningController;
use App\Http\Controllers\Frontend\AboutPageController;
use App\Http\Controllers\Frontend\CoursePageController;
use App\Http\Controllers\Frontend\CatalogController;
use App\Http\Controllers\Frontend\ProductPageController;
use App\Http\Controllers\Global\CloudStorageController;
use App\Http\Controllers\Frontend\StudentOrderController;
use App\Http\Controllers\Frontend\AiChatController;
use App\Http\Controllers\Frontend\StudentAiChatController;
use App\Http\Controllers\Frontend\CourseContentController;
use App\Http\Controllers\Frontend\StudentReviewController;
use App\Http\Controllers\Frontend\BecomeInstructorController;
use App\Http\Controllers\Frontend\InstructorCourseController;
use App\Http\Controllers\Frontend\InstructorPayoutController;
use App\Http\Controllers\Frontend\QuizImportExportController;
use App\Http\Controllers\Frontend\StudentDashboardController;
use App\Http\Controllers\Frontend\TinymceImageUploadController;
use App\Http\Controllers\Frontend\InstructorDashboardController;
use App\Http\Controllers\Frontend\InstructorLessonQnaController;
use App\Http\Controllers\Frontend\StudentProfileSettingController;
use App\Http\Controllers\Frontend\InstructorAnnouncementController;
use App\Http\Controllers\Frontend\InstructorLiveCredentialController;
use App\Http\Controllers\Frontend\InstructorProductController;
use App\Http\Controllers\Frontend\InstructorAiDocumentController;
use App\Http\Controllers\Frontend\InstructorNotificationController;
use App\Http\Controllers\Frontend\HeaderNotificationController;
use App\Http\Controllers\Frontend\InstructorProductQuizController;
use App\Http\Controllers\Frontend\InstructorProfileSettingController;
use App\Http\Controllers\Frontend\ProductQuizController;
use App\Http\Controllers\AccountDeletionController;
use App\Models\AiChatConversation;
use App\Models\StandaloneAiChatConversation;

Route::group(['middleware' => 'maintenance.mode'], function () {

    /**
     * ============================================================================
     * Global Routes
     * ============================================================================
     */

    Route::get('set-language', [DashboardController::class, 'setLanguage'])->name('set-language');
    Route::get('set-currency', [HomePageController::class, 'setCurrency'])->name('set-currency');

    Route::get('/', [HomePageController::class, 'index'])->name('home');
    Route::view('subscriptions', 'frontend.pages.subscriptions')->name('subscriptions');
    Route::get('ai-chat/c/credits', [AiChatController::class, 'credits'])
        ->middleware(['auth', 'verified'])
        ->name('ai-chat.credits');
    Route::get('ai-chat/c/{conversation?}', [AiChatController::class, 'index'])
        ->middleware(['auth', 'verified'])
        ->whereUuid('conversation')
        ->name('ai-chat');
    Route::post('ai-chat/c/conversations', [AiChatController::class, 'storeConversation'])
        ->middleware(['auth', 'verified'])
        ->name('ai-chat.conversations.store');
    Route::get('ai-chat/c/conversations/{conversation}/messages', [AiChatController::class, 'conversationMessages'])
        ->middleware(['auth', 'verified'])
        ->name('ai-chat.conversations.messages');
    Route::post('ai-chat/c/conversations/{conversation}/image', [AiChatController::class, 'generateImage'])
        ->middleware(['auth', 'verified'])
        ->name('ai-chat.conversations.image');
    Route::post('ai-chat/c/conversations/{conversation}/stream', [AiChatController::class, 'stream'])
        ->middleware(['auth', 'verified'])
        ->name('ai-chat.conversations.stream');
    Route::delete('ai-chat/c/conversations/{conversation}', [AiChatController::class, 'destroy'])
        ->middleware(['auth', 'verified'])
        ->name('ai-chat.conversations.destroy');
    Route::get('ai-chat/{conversation}', function (int $conversation) {
        $aiConversation = StandaloneAiChatConversation::query()->findOrFail($conversation);

        return redirect()->route('ai-chat', ['conversation' => $aiConversation->public_id]);
    })->middleware(['auth', 'verified'])
        ->whereNumber('conversation');

    Route::get('countries', [HomePageController::class, 'countries'])->name('countries');
    Route::get('states/{country_id}', [HomePageController::class, 'states'])->name('states');
    Route::get('cities/{state_id}', [HomePageController::class, 'cities'])->name('cities');

    /** become a instructor */
    Route::get('become-instructor', [BecomeInstructorController::class, 'index'])->name('become-instructor')->middleware('auth');
    Route::post('become-instructor', [BecomeInstructorController::class, 'store'])->name('become-instructor.create')->middleware('auth');
    Route::get('become-instructor/review', [BecomeInstructorController::class, 'review'])->name('become-instructor.review')->middleware('auth');
    Route::put('become-instructor/review', [BecomeInstructorController::class, 'update'])->name('become-instructor.review.update')->middleware('auth');

    Route::get('catalog', [CatalogController::class, 'index'])->name('catalog');
    Route::get('fetch-catalog', [CatalogController::class, 'fetch'])->name('fetch-catalog');
    Route::get('product/{slug}', [ProductPageController::class, 'show'])->name('product.show');
    Route::get('product/{slug}/preview', [ProductPageController::class, 'previewDocument'])->name('product.preview');
    Route::post('product/{slug}/review', [ProductPageController::class, 'storeReview'])->name('product.review.store')->middleware('auth');
    Route::get('product/{slug}/read/{topicSlug?}', [ProductPageController::class, 'readNote'])->name('product.read-note')->middleware('auth');
    Route::get('product/{slug}/reader', [ProductPageController::class, 'readDocument'])->name('product.read-document')->middleware('auth');
    Route::get('product/{slug}/reader/source', [ProductPageController::class, 'documentSource'])->name('product.document-source')->middleware('auth');
    Route::post('product/{slug}/reader/assistant/stream', [ProductPageController::class, 'documentAssistantStream'])->name('product.document-assistant-stream')->middleware('auth');
    Route::post('product/{slug}/reader/question-regions/{regionId}/answer', [ProductPageController::class, 'documentQuestionRegion'])->name('product.document-question-region')->middleware('auth');
    Route::post('product/{slug}/reader/question-regions/{regionId}/stream', [ProductPageController::class, 'documentQuestionRegionStream'])->name('product.document-question-region-stream')->middleware('auth');
    Route::get('product/{product_type}/{product_id}/download', [ProductPageController::class, 'downloadFile'])
        ->whereIn('product_type', ['course', 'past_paper', 'prediction', 'note', 'quiz'])
        ->whereNumber('product_id')
        ->name('product.download');
    Route::get('product/{slug}/start-quiz', [ProductQuizController::class, 'launch'])->name('product.start-quiz')->middleware('auth');
    Route::get('product/{slug}/take-quiz', [ProductQuizController::class, 'attempt'])->name('product.quiz.attempt')->middleware('auth');
    Route::post('product/{slug}/submit-quiz', [ProductQuizController::class, 'submit'])->name('product.quiz.submit')->middleware('auth');
    Route::get('product/{slug}/quiz-results/{attempt}', [ProductQuizController::class, 'result'])->name('product.quiz.result')->middleware('auth');
    Route::post('product/{slug}/note-progress', [ProductPageController::class, 'updateNoteProgress'])->name('product.note-progress')->middleware('auth');
    Route::post('product/{slug}/note-bookmark', [ProductPageController::class, 'toggleNoteBookmark'])->name('product.note-bookmark')->middleware('auth');
    Route::get('courses', [CoursePageController::class, 'index'])->name('courses');
    Route::get('fetch-courses', [CoursePageController::class, 'fetchCourses'])->name('fetch-courses');
    Route::get('course/{slug}', [CoursePageController::class, 'show'])->name('course.show');

    /** cart routes */
    Route::get('cart', [CartController::class, 'index'])->name('cart');
    Route::post('add-to-cart/{id}', [CartController::class, 'addToCart'])->name('add-to-cart');
    Route::get('remove-cart-item/{rowId}', [CartController::class, 'removeCartItem'])->name('remove-cart-item');
    Route::post('apply-coupon', [CartController::class, 'applyCoupon'])->name('apply-coupon');
    Route::get('remove-coupon', [CartController::class, 'removeCoupon'])->name('remove-coupon');

    /** Blog Routes */
    Route::get('blog', [BlogController::class, 'index'])->name('blogs');
    Route::get('blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
    Route::post('blog/submit-comment', [BlogController::class, 'submitComment'])->name('blog.submit-comment');
    Route::get('all-instructors', [HomePageController::class, 'allInstructors'])->name('all-instructors');
    Route::get('instructor-details/{id}/{slug?}', [HomePageController::class, 'instructorDetails'])->name('instructor-details');
    Route::post('quick-connect/{id}', [HomePageController::class, 'quickConnect'])->name('quick-connect');

    /** About page routes */
    Route::get('about-us', [AboutPageController::class, 'index'])->name('about-us');
    /** Contact page routes */
    Route::get('contact', [ContactController::class, 'index'])->name('contact.index');
    Route::post('contact/send-mail', [ContactController::class, 'sendMail'])->name('contact.send-mail');
    Route::post('notifications/{notification}/read', [HeaderNotificationController::class, 'markRead'])
        ->middleware('auth')
        ->name('notifications.read');

    /** other routes */
    Route::group(['prefix' => 'laravel-filemanager', 'middleware' => ['auth:admin'], 'as' => 'admin.'], function () {
        \UniSharp\LaravelFilemanager\Lfm::routes();
    });
    Route::group(['prefix' => 'frontend-filemanager', 'middleware' => ['web'], 'as' => 'frontend.'], function () {
        \UniSharp\LaravelFilemanager\Lfm::routes();
    });

    Route::get('change-theme/{name}', [HomePageController::class, 'changeTheme'])->name('change-theme');

    /**
     * ============================================================================
     * Student Dashboard Routes
     * ============================================================================
     */

    Route::group(['middleware' => ['auth', 'verified', 'role:student'], 'prefix' => 'student', 'as' => 'student.'], function () {
        Route::get('dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
        // Profile setting routes
        Route::get('setting', [StudentProfileSettingController::class, 'index'])->name('setting.index');
        Route::put('setting/profile', [StudentProfileSettingController::class, 'updateProfile'])->name('setting.profile.update');
        Route::put('setting/bio', [StudentProfileSettingController::class, 'updateBio'])->name('setting.bio.update');
        Route::put('setting/password', [StudentProfileSettingController::class, 'updatePassword'])->name('setting.password.update');
        Route::get('setting/experience-modal', [StudentProfileSettingController::class, 'showExperienceModal'])->name('setting.experience-modal');
        Route::get('setting/edit-experience-modal/{id}', [StudentProfileSettingController::class, 'editExperienceModal'])->name('setting.edit-experience-modal');

        Route::post('setting/experience', [StudentProfileSettingController::class, 'storeExperience'])->name('setting.experience.store');
        Route::put('setting/experience/{id}', [StudentProfileSettingController::class, 'updateExperience'])->name('setting.experience.update');
        Route::delete('setting/experience/{id}', [StudentProfileSettingController::class, 'destroyExperience'])->name('setting.experience.destroy');

        Route::get('setting/add-education-modal', [StudentProfileSettingController::class, 'addEducationModal'])->name('setting.add-education-modal');
        Route::post('setting/education', [StudentProfileSettingController::class, 'storeEducation'])->name('setting.education.store');
        Route::get('setting/edit-education-modal/{id}', [StudentProfileSettingController::class, 'editEducationModal'])->name('setting.edit-education-modal');
        Route::put('setting/education/{id}', [StudentProfileSettingController::class, 'updateEducation'])->name('setting.education.update');
        Route::delete('setting/education/{id}', [StudentProfileSettingController::class, 'destroyEducation'])->name('setting.education.destroy');

        Route::put('setting/address', [StudentProfileSettingController::class, 'updateAddress'])->name('setting.address.update');
        Route::put('setting/socials', [StudentProfileSettingController::class, 'updateSocials'])->name('setting.socials.update');

        Route::controller(DeviceController::class)->group(function () {
            Route::get('devices', 'index')->name('devices.index');
            Route::delete('devices/{sessionId}', 'destroy')->name('devices.destroy');
            Route::delete('devices', 'destroyAll')->name('devices.destroyAll');
        });

        /** Order Routes */
        Route::get('orders', [StudentOrderController::class, 'index'])->name('orders.index');
        Route::get('order-details/{id}', [StudentOrderController::class, 'show'])->name('order.show');
        Route::get('order/invoice/{id}', [StudentOrderController::class, 'printInvoice'])->name('order.print-invoice');

        Route::get('reviews', [StudentReviewController::class, 'index'])->name('reviews.index');
        Route::get('reviews/{id}', [StudentReviewController::class, 'show'])->name('reviews.show');
        Route::delete('reviews/{id}', [StudentReviewController::class, 'destroy'])->name('reviews.destroy');
        Route::get('enrolled-courses', [StudentDashboardController::class, 'enrolledCourses'])->name('enrolled-courses');
        Route::get('library', [StudentDashboardController::class, 'library'])->name('library');
        Route::get('quiz-attempts', [StudentDashboardController::class, 'quizAttempts'])->name('quiz-attempts');
        Route::get('ai-chat/c/{conversation?}', [StudentAiChatController::class, 'index'])
            ->whereUuid('conversation')
            ->name('ai-chat.index');
        Route::get('ai-chat/{conversation}', function (int $conversation) {
            $aiConversation = AiChatConversation::query()->findOrFail($conversation);

            return redirect()->route('student.ai-chat.index', ['conversation' => $aiConversation->public_id]);
        })->whereNumber('conversation');
        Route::get('ai-chat/conversations', function () {
            return redirect()->route('student.ai-chat.index');
        });
        Route::post('ai-chat/conversations', [StudentAiChatController::class, 'storeConversation'])->name('ai-chat.conversations.store');
        Route::get('ai-chat/conversations/{conversation}/messages', [StudentAiChatController::class, 'conversationMessages'])->name('ai-chat.conversations.messages');
        Route::post('ai-chat/conversations/{conversation}/image', [StudentAiChatController::class, 'generateImage'])->name('ai-chat.conversations.image');
        Route::post('ai-chat/conversations/{conversation}/stream', [StudentAiChatController::class, 'stream'])->name('ai-chat.conversations.stream');
        Route::delete('ai-chat/conversations/{conversation}', [StudentAiChatController::class, 'destroy'])->name('ai-chat.conversations.destroy');
        Route::view('wishlist', 'frontend.wishlist.index')->name('wishlist');

        /** learning routes */
        Route::get('learning/{slug}', [LearningController::class, 'index'])->name('learning.index');
        Route::post('learning/get-file-info', [LearningController::class, 'getFileInfo'])->name('get-file-info');
        Route::post('learning/make-lesson-complete', [LearningController::class, 'makeLessonComplete'])->name('make-lesson-complete');
        Route::get('learning/resource-download/{id}', [LearningController::class, 'downloadResource'])->name('download-resource');

        Route::get('learning/quiz/{id}', [LearningController::class, 'quizIndex'])->name('quiz.index');
        Route::post('learning/quiz/{id}', [LearningController::class, 'quizStore'])->name('quiz.store');
        Route::get('learning/quiz-result/{id}/{result_id}', [LearningController::class, 'quizResult'])->name('quiz.result');
        Route::get('learning/{slug}/{lesson_id}', [LearningController::class, 'liveSession'])->name('learning.live');

        /** qna routes */
        Route::post('create-question', [QnaController::class, 'create'])->name('qna.create');
        Route::get('fetch-lesson-questions', [QnaController::class, 'fetchLessonQuestions'])->name('fetch-lesson-questions');
        Route::post('create-reply', [QnaController::class, 'createReply'])->name('create-reply');
        Route::get('fetch-replies', [QnaController::class, 'fetchReply'])->name('fetch-replies');

        Route::delete('delete-question/{id}', [QnaController::class, 'destroyQuestion'])->name('destroy-question');
        Route::delete('delete-reply/{id}', [QnaController::class, 'destroyReply'])->name('destroy-reply');

        /** course review Routes */
        Route::post('add-review', [LearningController::class, 'addReview'])->name('add-review');
        Route::get('fetch-reviews/{course_id}', [LearningController::class, 'fetchReviews'])->name('fetch-reviews');

        /** download certificate route */
        Route::get('download-certificate/{id}', [StudentDashboardController::class, 'downloadCertificate'])->name('download-certificate');

    });

    /**
     * ============================================================================
     * Instructor Dashboard Routes
     * ============================================================================
     */

    Route::group(['middleware' => ['auth', 'verified', 'approved.instructor', 'role:instructor'], 'prefix' => 'instructor', 'as' => 'instructor.'], function () {
        Route::get('dashboard', [InstructorDashboardController::class, 'index'])->name('dashboard');
        // Profile setting routes
        Route::get('zoom-setting', [InstructorLiveCredentialController::class, 'index'])->name('zoom-setting.index');
        Route::put('zoom-setting', [InstructorLiveCredentialController::class, 'update'])->name('zoom-setting.update');
        Route::get('jitsi-setting', [InstructorLiveCredentialController::class, 'jitsi_index'])->name('jitsi-setting.index');
        Route::put('jitsi-setting', [InstructorLiveCredentialController::class, 'jitsi_update'])->name('jitsi-setting.update');

        // Google Calendar routes
        Route::get('google-calendar', [GoogleCalendarController::class, 'index'])->name('google-calendar.index');
        Route::get('google-calendar/connect', [GoogleCalendarController::class, 'redirect'])->name('google-calendar.connect');
        Route::get('google-calendar/callback', [GoogleCalendarController::class, 'callback'])->name('google-calendar.callback');
        Route::post('google-calendar/disconnect', [GoogleCalendarController::class, 'disconnect'])->name('google-calendar.disconnect');

        Route::get('setting', [InstructorProfileSettingController::class, 'index'])->name('setting.index');
        Route::put('setting/profile', [InstructorProfileSettingController::class, 'updateProfile'])->name('setting.profile.update');
        Route::put('setting/bio', [InstructorProfileSettingController::class, 'updateBio'])->name('setting.bio.update');
        Route::put('setting/password', [InstructorProfileSettingController::class, 'updatePassword'])->name('setting.password.update');
        Route::get('setting/experience-modal', [InstructorProfileSettingController::class, 'showExperienceModal'])->name('setting.experience-modal');
        Route::get('setting/edit-experience-modal/{id}', [InstructorProfileSettingController::class, 'editExperienceModal'])->name('setting.edit-experience-modal');

        Route::post('setting/experience', [InstructorProfileSettingController::class, 'storeExperience'])->name('setting.experience.store');
        Route::put('setting/experience/{id}', [InstructorProfileSettingController::class, 'updateExperience'])->name('setting.experience.update');
        Route::delete('setting/experience/{id}', [InstructorProfileSettingController::class, 'destroyExperience'])->name('setting.experience.destroy');

        Route::get('setting/add-education-modal', [InstructorProfileSettingController::class, 'addEducationModal'])->name('setting.add-education-modal');
        Route::post('setting/education', [InstructorProfileSettingController::class, 'storeEducation'])->name('setting.education.store');
        Route::get('setting/edit-education-modal/{id}', [InstructorProfileSettingController::class, 'editEducationModal'])->name('setting.edit-education-modal');
        Route::put('setting/education/{id}', [InstructorProfileSettingController::class, 'updateEducation'])->name('setting.education.update');
        Route::delete('setting/education/{id}', [InstructorProfileSettingController::class, 'destroyEducation'])->name('setting.education.destroy');

        Route::put('setting/payout', [InstructorProfileSettingController::class, 'updatePayout'])->name('setting.payout.update');

        Route::put('setting/address', [InstructorProfileSettingController::class, 'updateAddress'])->name('setting.address.update');
        Route::put('setting/socials', [InstructorProfileSettingController::class, 'updateSocials'])->name('setting.socials.update');

        /** Product Routes */
        Route::get('products', [InstructorProductController::class, 'index'])->name('products.index');
        Route::get('products/create', [InstructorProductController::class, 'create'])->name('products.create');
        Route::post('products', [InstructorProductController::class, 'store'])->name('products.store');
        Route::post('products/note-attachments', [InstructorProductController::class, 'uploadNoteAttachments'])->name('products.note-attachments.upload');
        Route::delete('products/note-attachments', [InstructorProductController::class, 'deleteNoteAttachment'])->name('products.note-attachments.destroy');
        Route::get('products/{id}/edit', [InstructorProductController::class, 'edit'])->name('products.edit');
        Route::put('products/{id}', [InstructorProductController::class, 'update'])->name('products.update');
        Route::delete('products/{id}', [InstructorProductController::class, 'destroy'])->name('products.destroy');
        Route::get('ai-documents', [InstructorAiDocumentController::class, 'index'])->name('ai-documents.index');
        Route::get('ai-documents/search', [InstructorAiDocumentController::class, 'search'])->name('ai-documents.search');
        Route::get('ai-documents/{id}', [InstructorAiDocumentController::class, 'show'])->name('ai-documents.show');
        Route::post('ai-documents/upload', [InstructorAiDocumentController::class, 'upload'])->name('ai-documents.upload');
        Route::post('ai-documents/{id}/reprocess', [InstructorAiDocumentController::class, 'reprocess'])->name('ai-documents.reprocess');
        Route::delete('ai-documents/{id}', [InstructorAiDocumentController::class, 'destroy'])->name('ai-documents.destroy');
        Route::get('notifications/ai-documents', [InstructorNotificationController::class, 'index'])->name('notifications.ai-documents.index');
        Route::post('notifications/ai-documents/{notification}/read', [InstructorNotificationController::class, 'markRead'])->name('notifications.ai-documents.read');
        Route::get('quizzes/create', [InstructorProductQuizController::class, 'selectTier'])->name('quizzes.create');
        Route::get('quizzes/create/{tier}', [InstructorProductQuizController::class, 'create'])->name('quizzes.create-tier');
        Route::post('quizzes', [InstructorProductQuizController::class, 'store'])->name('quizzes.store');
        Route::get('quizzes/{id}/edit', [InstructorProductQuizController::class, 'edit'])->name('quizzes.edit');
        Route::put('quizzes/{id}', [InstructorProductQuizController::class, 'update'])->name('quizzes.update');
        /** Course Routes */
        Route::get('courses', [InstructorCourseController::class, 'index'])->name('courses.index');
        Route::get('courses/create', [InstructorCourseController::class, 'create'])->name('courses.create');
        Route::get('courses/create/{id}/step/{step?}', [InstructorCourseController::class, 'edit'])->name('courses.edit');
        Route::get('courses/{id}/edit', [InstructorCourseController::class, 'editView'])->name('courses.edit-view');

        Route::get('courses/get-filters/{category_id}', [InstructorCourseController::class, 'getFiltersByCategory'])->name('courses.get-filters');
        Route::get('courses/get-instructors', [InstructorCourseController::class, 'getInstructors'])->name('courses.get-instructors');

        Route::post('courses/create', [InstructorCourseController::class, 'store'])->name('courses.store');
        Route::post('courses/update', [InstructorCourseController::class, 'update'])->name('courses.update');

        Route::get('courses/{course}/lessons/create', [LessonBuilderController::class, 'create'])
            ->whereNumber('course')
            ->name('lessons.create');
        Route::post('courses/{course}/lessons', [LessonBuilderController::class, 'store'])
            ->whereNumber('course')
            ->name('lessons.store');

        /** Course content routes */
        Route::post('course-chapter/{course_id?}/store', [CourseContentController::class, 'chapterStore'])->name('course-chapter.store');
        Route::get('course-chapter/sorting/{course_id}', [CourseContentController::class, 'chapterSorting'])->name('course-chapter.sorting.index');
        Route::get('course-chapter/edit/{chapter_id}', [CourseContentController::class, 'chapterEdit'])->name('course-chapter.edit');
        Route::put('course-chapter/update/{chapter_id}', [CourseContentController::class, 'chapterUpdate'])->name('course-chapter.update');
        Route::delete('course-chapter/delete/{chapter_id}', [CourseContentController::class, 'chapterDestroy'])->name('course-chapter.destroy');

        Route::post('course-chapter/sorting/{course_id}', [CourseContentController::class, 'chapterSortingStore'])->name('course-chapter.sorting.store');
        Route::get('course-chapter/lesson/create', [CourseContentController::class, 'lessonCreate'])->name('course-chapter.lesson.create');
        Route::post('course-chapter/lesson/create', [CourseContentController::class, 'lessonStore'])->name('course-chapter.lesson.store');
        Route::get('course-chapter/lesson/edit', [CourseContentController::class, 'lessonEdit'])->name('course-chapter.lesson.edit');

        Route::post('course-chapter/lesson/update', [CourseContentController::class, 'lessonUpdate'])->name('course-chapter.lesson.update');
        Route::delete('course-chapter/lesson/{chapter_item_id}/destroy', [CourseContentController::class, 'chapterLessonDestroy'])->name('course-chapter.lesson.destroy');
        Route::post('course-chapter/lesson/sorting/{chapter_id}', [CourseContentController::class, 'sortLessons'])->name('course-chapter.lesson.sorting');

        Route::get('course-chapter/quiz-question/create/{quiz_id}', [CourseContentController::class, 'createQuizQuestion'])->name('course-chapter.quiz-question.create');
        Route::post('course-chapter/quiz-question/create/{quiz_id}', [CourseContentController::class, 'storeQuizQuestion'])->name('course-chapter.quiz-question.store');
        Route::get('course-chapter/quiz-question/edit/{question_id}', [CourseContentController::class, 'editQuizQuestion'])->name('course-chapter quiz-question.edit');
        Route::put('course-chapter/quiz-question/update/{question_id}', [CourseContentController::class, 'updateQuizQuestion'])->name('course-chapter.quiz-question.update');
        Route::delete('course-chapter/quiz-question/delete/{question_id}', [CourseContentController::class, 'destroyQuizQuestion'])->name('course-chapter.quiz-question.destroy');

        Route::get('/course-chapter/quiz-question/export-csv', [QuizImportExportController::class, 'export'])->name('quiz.export-csv');
        Route::get('/course-chapter/quiz-question/import-csv/{quiz_id}', [QuizImportExportController::class, 'importModal'])->name('quiz.import-csv.modal');
        Route::post('/course-chapter/quiz-question/import-csv/{quiz}', [QuizImportExportController::class, 'import'])->name('quiz.import-csv');

        Route::get('course-delete-request/{course_id}', [InstructorCourseController::class, 'showDeleteRequest'])->name('course.delete-request.show');
        Route::post('course-delete-request', [InstructorCourseController::class, 'sendDeleteRequest'])->name('course.send-delete-request');

        /** payout routes */
        Route::get('payout', [InstructorPayoutController::class, 'index'])->name('payout.index');
        Route::get('payout/create', [InstructorPayoutController::class, 'create'])->name('payout.create');
        Route::post('payout/create', [InstructorPayoutController::class, 'store'])->name('payout.store');
        Route::delete('payout/delete/{id}', [InstructorPayoutController::class, 'destroy'])->name('payout.destroy');

        /** announcement routes */
        Route::resource('announcements', InstructorAnnouncementController::class);

        /** my sales routes */
        Route::get('my-sells', [InstructorDashboardController::class, 'mySells'])->name('my-sells.index');
        /** lessons qna routes */
        Route::get('lesson-question', [InstructorLessonQnaController::class, 'index'])->name('lesson-questions.index');
        Route::post('lesson-question/{id}', [InstructorLessonQnaController::class, 'createReply'])->name('lesson-question.reply');
        Route::delete('lesson-question/destroy/{id}', [InstructorLessonQnaController::class, 'destroyQuestion'])->name('lesson-question.destroy');
        Route::delete('lesson-question/reply/destroy/{id}', [InstructorLessonQnaController::class, 'destroyReply'])->name('lesson-reply.destroy');
        Route::put('lesson-question/seen-update/{id}', [InstructorLessonQnaController::class, 'markAsReadUnread'])->name('lesson-question.seen-update');

        Route::post('cloud/store', [CloudStorageController::class, 'store'])->name('cloud.store');
    });
    /** wishlist routes */
    Route::group(['middleware' => ['auth', 'verified', 'role:student']], function () {
        Route::controller(FavoriteController::class)->group(function () {
            Route::get('wishlist/{course:slug}', 'update')->name('wishlist.update');
            Route::delete('wishlist/{course:slug}', 'destroy')->name('wishlist.remove');
        });
    });

    /** secure-video route */
    Route::group(['middleware' => ['auth', 'verified']], function () {
        Route::get('secure-video/{hash}', App\Http\Controllers\SecureLinkPreviewController::class)->name('secure.video')->middleware('signed');
    });

    Route::group(['middleware' => ['auth', 'verified']], function () {
        Route::get('checkout', [CheckOutController::class, 'index'])->name('checkout.index');
        Route::post('tinymce-upload-image', [TinymceImageUploadController::class, 'upload']);
        Route::delete('tinymce-delete-image', [TinymceImageUploadController::class, 'destroy']);
    });
});

Route::get('delete-account', [AccountDeletionController::class, 'showRequestForm'])
    ->name('account-deletion.form');
Route::post('delete-account', [AccountDeletionController::class, 'requestByEmail'])
    ->middleware('throttle:5,1')
    ->name('account-deletion.request');
Route::get('delete-account/confirm/{user}/{email_hash}', [AccountDeletionController::class, 'showConfirmation'])
    ->middleware('signed')
    ->name('account-deletion.confirm.show');
Route::post('delete-account/confirm/{user}/{email_hash}', [AccountDeletionController::class, 'confirm'])
    ->middleware('signed')
    ->name('account-deletion.confirm.destroy');
Route::post('account-deletion/request', [AccountDeletionController::class, 'requestAuthenticated'])
    ->middleware(['auth', 'throttle:5,1'])
    ->name('account-deletion.request.authenticated');

//maintenance mode route
Route::get('/maintenance-mode', function () {
    $setting = Illuminate\Support\Facades\Cache::get('setting', null);
    if (!$setting?->maintenance_mode) {
        return redirect()->route('home');
    }

    return view('global.maintenance');
})->name('maintenance.mode');

require __DIR__ . '/auth.php';

require __DIR__ . '/admin.php';
