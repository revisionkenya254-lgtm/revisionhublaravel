<?php

use Illuminate\Support\Facades\Route;
use Modules\PageBuilder\app\Http\Controllers\PageBuilderController;

Route::group(['middleware' => ['auth:admin', 'translation'], 'prefix' => 'admin', 'as' => 'admin.'], function () {
    Route::resource('page-builder', PageBuilderController::class)->names('page-builder')->except('show');

    Route::controller(PageBuilderController::class)->group(function () {
        Route::put('/page-builder/status-update/{id}', 'statusUpdate')->name('status-update');

        Route::get('page-builder/editor/{slug}', 'editor')->name('page-builder.editor');
        Route::post('page-builder/editor/{slug}', 'updateEditor')->name('page-builder.editor.update');

        Route::post('page-builder/upload', 'upload')->name('page-builder.upload');
        Route::post('page-builder/delete-upload', 'deleteUpload')->name('page-builder.delete.upload');
    });
});

Route::group(['middleware' => 'maintenance.mode'], function () {
    Route::get('page/{slug}', [PageBuilderController::class, 'show']);
});
