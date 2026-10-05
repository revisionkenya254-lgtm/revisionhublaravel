<?php

use App\Http\Controllers\Admin\AddonsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\CatalogResourceController;
use App\Http\Controllers\Admin\ManualEnrollmentController;
use App\Http\Controllers\Admin\ProductQuizController;
use App\Http\Controllers\Admin\ProductReviewController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\AiSettingsController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Global\CloudStorageController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\AdminOtpController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;


Route::group(['as' => 'admin.', 'prefix' => 'admin'], function () {
    /* Start admin auth route */
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('store-login', [AuthenticatedSessionController::class, 'store'])->name('store-login');
    Route::get('otp', [AdminOtpController::class, 'show'])->name('otp.notice');
    Route::post('otp/verify', [AdminOtpController::class, 'verify'])->name('otp.verify');
    Route::post('otp/resend', [AdminOtpController::class, 'resend'])->name('otp.resend');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    /* End admin auth route */

    Route::middleware(['auth:admin'])->group(function () {
        Route::get('/', [DashboardController::class, 'dashboard']);
        Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

        Route::controller(AdminProfileController::class)->group(function () {
            Route::get('edit-profile', 'edit_profile')->name('edit-profile');
            Route::put('profile-update', 'profile_update')->name('profile-update');
            Route::put('update-password', 'update_password')->name('update-password');
        });

        Route::get('role/assign', [RolesController::class, 'assignRoleView'])->name('role.assign');
        Route::post('role/assign/{id}', [RolesController::class, 'getAdminRoles'])->name('role.assign.admin');
        Route::put('role/assign', [RolesController::class, 'assignRoleUpdate'])->name('role.assign.update');
        Route::resource('/role', RolesController::class);
        Route::resource('/role', RolesController::class);

        // Manual Enrollment
        Route::controller(ManualEnrollmentController::class)
            ->prefix('manual-enrollment')
            ->name('manual-enrollment.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::delete('/{id}', 'destroy')->name('destroy');
                Route::get('/student-enrolled-courses', 'studentEnrolledCourses')->name('student-enrolled-courses');
            });

        Route::prefix('past-papers')
            ->name('past-papers.')
            ->controller(CatalogResourceController::class)
            ->group(function () {
                Route::get('/', 'index')->defaults('type', 'past_paper')->name('index');
                Route::get('/create', 'create')->defaults('type', 'past_paper')->name('create');
                Route::post('/', 'store')->defaults('type', 'past_paper')->name('store');
                Route::get('/{resource}/edit', 'edit')->defaults('type', 'past_paper')->name('edit');
                Route::put('/{resource}', 'update')->defaults('type', 'past_paper')->name('update');
                Route::delete('/{resource}', 'destroy')->defaults('type', 'past_paper')->name('destroy');
                Route::put('/{resource}/approval-status', 'statusUpdate')->defaults('type', 'past_paper')->name('status-update');
                Route::get('/{resource}/ai-document', 'aiDocument')->defaults('type', 'past_paper')->name('ai-document');
                Route::post('/{resource}/ai-reprocess', 'reprocessAiDocument')->defaults('type', 'past_paper')->name('ai-reprocess');
            });

        Route::prefix('past-papers/reviews')
            ->name('past-papers.reviews.')
            ->controller(ProductReviewController::class)
            ->group(function () {
                Route::get('/', 'index')->defaults('type', 'past_paper')->name('index');
                Route::get('/{review}', 'show')->defaults('type', 'past_paper')->name('show');
                Route::put('/{review}', 'update')->defaults('type', 'past_paper')->name('update');
                Route::delete('/{review}', 'destroy')->defaults('type', 'past_paper')->name('destroy');
            });

        Route::prefix('predictions')
            ->name('predictions.')
            ->controller(CatalogResourceController::class)
            ->group(function () {
                Route::get('/', 'index')->defaults('type', 'prediction')->name('index');
                Route::get('/create', 'create')->defaults('type', 'prediction')->name('create');
                Route::post('/', 'store')->defaults('type', 'prediction')->name('store');
                Route::get('/{resource}/edit', 'edit')->defaults('type', 'prediction')->name('edit');
                Route::put('/{resource}', 'update')->defaults('type', 'prediction')->name('update');
                Route::delete('/{resource}', 'destroy')->defaults('type', 'prediction')->name('destroy');
                Route::put('/{resource}/approval-status', 'statusUpdate')->defaults('type', 'prediction')->name('status-update');
                Route::post('/{resource}/ai-reprocess', 'reprocessAiDocument')->defaults('type', 'prediction')->name('ai-reprocess');
            });

        Route::prefix('predictions/reviews')
            ->name('predictions.reviews.')
            ->controller(ProductReviewController::class)
            ->group(function () {
                Route::get('/', 'index')->defaults('type', 'prediction')->name('index');
                Route::get('/{review}', 'show')->defaults('type', 'prediction')->name('show');
                Route::put('/{review}', 'update')->defaults('type', 'prediction')->name('update');
                Route::delete('/{review}', 'destroy')->defaults('type', 'prediction')->name('destroy');
            });

        Route::prefix('notes')
            ->name('notes.')
            ->controller(CatalogResourceController::class)
            ->group(function () {
                Route::get('/', 'index')->defaults('type', 'note')->name('index');
                Route::get('/create', 'create')->defaults('type', 'note')->name('create');
                Route::post('/', 'store')->defaults('type', 'note')->name('store');
                Route::get('/{resource}/edit', 'edit')->defaults('type', 'note')->name('edit');
                Route::put('/{resource}', 'update')->defaults('type', 'note')->name('update');
                Route::delete('/{resource}', 'destroy')->defaults('type', 'note')->name('destroy');
                Route::put('/{resource}/approval-status', 'statusUpdate')->defaults('type', 'note')->name('status-update');
                Route::post('/{resource}/ai-reprocess', 'reprocessAiDocument')->defaults('type', 'note')->name('ai-reprocess');
            });

        Route::prefix('notes/reviews')
            ->name('notes.reviews.')
            ->controller(ProductReviewController::class)
            ->group(function () {
                Route::get('/', 'index')->defaults('type', 'note')->name('index');
                Route::get('/{review}', 'show')->defaults('type', 'note')->name('show');
                Route::put('/{review}', 'update')->defaults('type', 'note')->name('update');
                Route::delete('/{review}', 'destroy')->defaults('type', 'note')->name('destroy');
            });

        Route::prefix('quizzes')
            ->name('quizzes.')
            ->controller(ProductQuizController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'selectTier')->name('create');
                Route::get('/create/{tier}', 'create')->name('create-tier');
                Route::post('/', 'store')->name('store');
                Route::get('/{resource}/edit', 'edit')->name('edit');
                Route::put('/{resource}', 'update')->name('update');
                Route::delete('/{resource}', 'destroy')->name('destroy');
                Route::put('/{resource}/approval-status', 'statusUpdate')->name('status-update');
                Route::post('/{resource}/ai-reprocess', 'reprocessAiDocument')->name('ai-reprocess');
            });

        Route::prefix('quizzes/reviews')
            ->name('quizzes.reviews.')
            ->controller(ProductReviewController::class)
            ->group(function () {
                Route::get('/', 'index')->defaults('type', 'quiz')->name('index');
                Route::get('/{review}', 'show')->defaults('type', 'quiz')->name('show');
                Route::put('/{review}', 'update')->defaults('type', 'quiz')->name('update');
                Route::delete('/{review}', 'destroy')->defaults('type', 'quiz')->name('destroy');
            });
    });
    Route::resource('admin', AdminController::class)->except('show');
    Route::put('admin-status/{id}', [AdminController::class, 'changeStatus'])->name('admin.status');
    // Settings routes
        Route::get('settings', [SettingController::class, 'settings'])->name('settings');
        Route::get('ai-settings', [AiSettingsController::class, 'index'])->name('ai-settings.index');
        Route::put('ai-settings', [AiSettingsController::class, 'update'])->name('ai-settings.update');
        Route::get('ai-settings/health-check', [AiSettingsController::class, 'healthCheck'])->name('ai-settings.health-check');
        Route::post('cloud/store', [CloudStorageController::class, 'store'])->name('cloud.store');
        Route::get('sync-modules', [AddonsController::class, 'syncModules'])->name('addons.sync');
    });
