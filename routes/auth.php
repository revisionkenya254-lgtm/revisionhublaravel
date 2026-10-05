<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\AuthOtpController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SocialiteController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:web')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::get('user-login', fn () => redirect()->route('login'));

    Route::post('user-login/check', [AuthenticatedSessionController::class, 'checkAccountType'])
        ->name('user-login.check');

    Route::post('user-login', [AuthenticatedSessionController::class, 'store'])->name('user-login');
    Route::get('otp', [AuthOtpController::class, 'show'])->name('auth.otp.notice');
    Route::post('otp/verify', [AuthOtpController::class, 'verify'])->name('auth.otp.verify');
    Route::post('otp/resend', [AuthOtpController::class, 'resend'])->name('auth.otp.resend');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('/forget-password', [PasswordResetLinkController::class, 'custom_forget_password'])->name('forget-password');

    Route::get('/reset-password-page/{token}', [NewPasswordController::class, 'custom_reset_password_page'])->name('reset-password-page');

    Route::post('/reset-password-store/{token}', [NewPasswordController::class, 'custom_reset_password_store'])->name('reset-password-store');

    Route::controller(SocialiteController::class)->group(function () {
        Route::get('auth/{driver}', 'redirectToDriver')->name('auth.social');
        Route::get('auth/{driver}/callback', 'handleDriverCallback')->name('auth.social.callback');
    });

});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
