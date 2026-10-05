<?php

use Illuminate\Support\Facades\Route;
use Modules\Order\app\Http\Controllers\OrderController;

Route::group(['as' => 'admin.', 'prefix' => 'admin', 'middleware' => ['auth:admin', 'translation']], function () {

    Route::controller(OrderController::class)->group(function () {
        Route::get('/orders', 'index')->name('orders');
        Route::get('/pending-orders', 'pending_order')->name('pending-orders');
        Route::get('/order/{id}', 'show')->name('order');
        Route::post('/update-order/{id}', 'updateOrder')->name('order.update');
        Route::post('/revoke-order-access/{id}', 'revokeAccess')->name('order.revoke-access');
        Route::delete('/order-delete/{id}', 'destroy')->name('order.destroy');

        Route::get('/order/invoice/{id}', 'printInvoice')->name('print-invoice');
        Route::get('/order/payment-receipt/download/{id}', 'download_payment_receipt')->name('download.payment-receipt');
        Route::get('resend/gift-claim-mail/{invoice_id}', 'reSendGiftClaimMail')->name('resend.gift-claim-mail');
    });
});

Route::middleware(['auth', 'verified','translation'])->group(function () {
    Route::get('gift-course-verification/{invoice_id}/{verification_token}', [OrderController::class, 'giftVerification'])->name('gift-course-verification');
});
