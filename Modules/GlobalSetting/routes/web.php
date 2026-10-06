<?php

use Illuminate\Support\Facades\Route;
use Modules\GlobalSetting\app\Http\Controllers\EmailSettingController;
use Modules\GlobalSetting\app\Http\Controllers\GlobalSettingController;
use Modules\GlobalSetting\app\Http\Controllers\ManageAddonController;
use Modules\GlobalSetting\app\Http\Controllers\SMSConfigurationController;

Route::group(['as' => 'admin.', 'prefix' => 'admin', 'middleware' => ['auth:admin', 'translation']], function () {

    Route::controller(GlobalSettingController::class)->group(function () {

        Route::get('general-setting', 'general_setting')->name('general-setting');
        Route::get('commission-setting', 'commission_setting')->name('commission-setting');
        Route::put('commission-setting', 'update_commission')->name('update-commission-setting');
        Route::put('update-general-setting', 'update_general_setting')->name('update-general-setting');

        Route::put('update-logo-favicon', 'update_logo_favicon')->name('update-logo-favicon');
        Route::put('update-video-watermark', 'update_video_watermark')->name('update-video-watermark');
        Route::put('update-cookie-consent', 'update_cookie_consent')->name('update-cookie-consent');
        Route::put('update-custom-pagination', 'update_custom_pagination')->name('update-custom-pagination');
        Route::put('update-default-avatar', 'update_default_avatar')->name('update-default-avatar');
        Route::put('update-breadcrumb', 'update_breadcrumb')->name('update-breadcrumb');
        Route::put('update-copyright-text', 'update_copyright_text')->name('update-copyright-text');
        Route::put('update-maintenance-mode-status', 'update_maintenance_mode_status')->name('update-maintenance-mode-status');
        Route::put('update-maintenance-mode', 'update_maintenance_mode')->name('update-maintenance-mode');

        Route::get('seo-setting', 'seo_setting')->name('seo-setting');
        Route::put('update-seo-setting/{id}', 'update_seo_setting')->name('update-seo-setting');

        Route::get('crediential-setting', 'crediential_setting')->name('crediential-setting');
        Route::get('bunny-dashboard', 'bunny_usage_dashboard')->name('bunny-dashboard');
        Route::get('bunny-invoice/{id}', 'bunny_invoice_download')->name('bunny-invoice.download');
        Route::post('bunny-connection-test', 'bunny_connection_test')->name('bunny-connection-test');
        Route::post('bunny-connection-test/clear', 'bunny_connection_test_clear')->name('bunny-connection-test.clear');
        Route::put('update-google-captcha', 'update_google_captcha')->name('update-google-captcha');
        Route::put('update-tawk-chat', 'update_tawk_chat')->name('update-tawk-chat');

        Route::put('update-google-tag', 'update_google_tagmanager')->name('update-google-tagmaneger');
        Route::put('update-google-analytic', 'update_google_analytic')->name('update-google-analytic');

        Route::put('update-facebook-pixel', 'update_facebook_pixel')->name('update-facebook-pixel');
        Route::put('update-social-login', 'update_social_login')->name('update-social-login');
        Route::put('update-pusher', 'update_pusher')->name('update-pusher');
        Route::put('update-bunny-cloud', 'update_bunny_cloud')->name('update-bunny-cloud');
        Route::put('update-wasabi-cloud', 'update_wasabi_cloud')->name('update-wasabi-cloud');
        Route::put('update-aws-cloud', 'update_aws_cloud')->name('update-aws-cloud');

        Route::get('cache-clear', 'cache_clear')->name('cache-clear');
        Route::post('cache-clear', 'cache_clear_confirm')->name('cache-clear-confirm');
        Route::get('database-clear', 'database_clear')->name('database-clear');
        Route::delete('database-clear-success', 'database_clear_success')->name('database-clear-success');
        Route::get('custom-code/{type}', 'customCode')->name('custom-code');
        Route::post('update-custom-code', 'customCodeUpdate')->name('update-custom-code');
        Route::post('update-custom-code', 'customCodeUpdate')->name('update-custom-code');

        Route::get('system-update', 'systemUpdate')->name('system-update.index');
        Route::post('system-update/store', 'systemUpdateStore')->name('system-update.store');
        Route::post('system-update/redirect', 'systemUpdateRedirect')->name('system-update.redirect');
        Route::delete('system-update/delete', 'systemUpdateDelete')->name('system-update.delete');

        Route::get('marketing-setting', 'marketing_setting')->name('marketing-setting');
        Route::put('update-google-data-layer', 'update_google_data_layer')->name('update-data-layer');
    });

    Route::controller(EmailSettingController::class)->group(function () {

        Route::get('email-configuration', 'email_config')->name('email-configuration');
        Route::put('update-email-configuration', 'update_email_config')->name('update-email-configuration');

        Route::get('edit-email-template/{id}', 'edit_email_template')->name('edit-email-template');
        Route::put('update-email-template/{id}', 'update_email_template')->name('update-email-template');

        Route::post('test/mail/credentials', 'test_mail_credentials')->name('test-mail-credentials');
    });
    Route::controller(SMSConfigurationController::class)->group(function () {
        Route::get('sms-configuration', 'index')->name('sms-configuration');
        Route::put('update-sms-configuration', 'update_sms_config')->name('update-sms-configuration');
        Route::put('update-twilio-sms-configuration', 'update_twilio_config')->name('update-twilio-sms-configuration');
        Route::get('edit-sms-template/{id}', 'edit_template')->name('edit-sms-template');
        Route::put('update-sms-template/{id}', 'update_sms_template')->name('update-sms-template');
    });
    Route::controller(ManageAddonController::class)->prefix('settings')->group(function () {
        Route::get('addons', 'index')->name('addons.view');
        Route::get('addons/install', 'installAddon')->name('addons.install');
        Route::get('addons/update/{slug}', 'updateStatus')->name('addons.update.status');
        Route::post('addons/store', 'installStore')->name('addons.store');
        Route::post('addons/install', 'installProcessStart')->name('addons.install.start');
        Route::delete('addons/delete', 'deleteAddon')->name('addons.delete');
        Route::delete('addons/uninstall/{slug}', 'uninstallAddon')->name('addons.uninstall');
    });

});
