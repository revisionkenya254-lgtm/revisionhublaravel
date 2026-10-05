<?php

use Illuminate\Support\Facades\Route;
use Modules\BasicPayment\app\Http\Controllers\API\PaymentController as PaymentApiController;
use Modules\BasicPayment\app\Http\Controllers\API\PaypalPaymentController;
use Modules\BasicPayment\app\Http\Controllers\BasicPaymentController;
use Modules\BasicPayment\app\Http\Controllers\FrontPaymentController;
use Modules\BasicPayment\app\Http\Controllers\PaymentController;

Route::group(['as' => 'admin.', 'prefix' => 'admin', 'middleware' => ['auth:admin', 'translation']], function () {

    Route::controller(BasicPaymentController::class)->group(function () {
        Route::get('basicpayment', 'basicpayment')->name('basicpayment');
        Route::put('update-paypal', 'update_paypal')->name('update-paypal');
        Route::put('mpesa-stk-push-update', 'mpesa_stk_push_update')->name('mpesa-stk-push-update');
    });

});
Route::group(['middleware' => ['auth', 'verified']], function () {
    Route::controller(PaymentController::class)->group(function () {
        Route::post('place-order/{method}', 'placeOrder')->where('method', 'paypal|mpesa_stk_push')->name('place.order');
        Route::get('payment', 'index')->name('payment');
        Route::get('payment/mpesa-stk-push/status/{invoice_id}', 'mpesa_stk_push_status')->name('mpesa-stk-push.status');
        Route::get('payment/mpesa-stk-push/success/{invoice_id}', 'mpesa_stk_push_success')->name('mpesa-stk-push.success');
        Route::get('payment/mpesa-stk-push/failed/{invoice_id}', 'mpesa_stk_push_failed')->name('mpesa-stk-push.failed');

        Route::get('payment-success', 'payment_success')->name('payment-success');
        Route::get('payment-failed', 'payment_failed')->name('payment-failed');

        Route::post('pay-via-free-gateway', 'pay_via_free_gateway')->name('pay-via-free-gateway');
        Route::get('pay-via-paypal', 'pay_via_paypal')->name('pay-via-paypal');
        Route::post('pay-via-mpesa-stk-push', 'pay_via_mpesa_stk_push')->name('pay-via-mpesa-stk-push');
    });
    Route::get('paypal-success-payment', [FrontPaymentController::class, 'paypal_success'])->name('paypal-success-payment');
});

Route::group(['as' => 'payment-api.'], function () {
    Route::get('app/payment', [PaymentApiController::class, 'payment'])->name('payment');
    Route::get('app/payment/mpesa-stk-push/status', [PaymentApiController::class, 'mpesa_stk_push_status'])->name('mpesa-stk-push.status');
    Route::get('app/payment/mpesa-stk-push/success', [PaymentApiController::class, 'mpesa_stk_push_success'])->name('mpesa-stk-push.success');
    Route::get('app/payment/mpesa-stk-push/failed', [PaymentApiController::class, 'mpesa_stk_push_failed'])->name('mpesa-stk-push.failed');

    Route::get('webview-success-payment', [PaymentApiController::class, 'payment_success'])->name('webview-success-payment')->middleware('payment.api');
    Route::get('webview-failed-payment', [PaymentApiController::class, 'payment_failed'])->name('webview-failed-payment');

    Route::get('paypal-webview', [PaypalPaymentController::class, 'pay_via_paypal'])->name('paypal-webview')->middleware('payment.api');
    Route::get('paypal-success', [PaypalPaymentController::class, 'paypal_success'])->name('paypal-success')->middleware('payment.api');
    Route::post('mpesa-stk-push-webview', [PaymentApiController::class, 'pay_via_mpesa_stk_push'])->name('mpesa-stk-push-webview')->middleware('payment.api');
});
