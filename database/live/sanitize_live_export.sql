-- Applied only to a temporary export database.
-- The source database is never modified by this script.

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM `jobs`;
DELETE FROM `failed_jobs`;
DELETE FROM `personal_access_tokens`;
DELETE FROM `password_reset_tokens`;
DELETE FROM `device_sessions`;
DELETE FROM `refresh_tokens`;
DELETE FROM `user_devices`;
DELETE FROM `carts`;

-- OAuth and conferencing credentials must be configured on production.
DELETE FROM `socialite_credentials`;
DELETE FROM `jitsi_settings`;
DELETE FROM `zoom_credentials`;

SET FOREIGN_KEY_CHECKS = 1;

UPDATE `admins`
SET `otp_code` = NULL,
    `otp_purpose` = NULL,
    `otp_expires_at` = NULL,
    `forget_password_token` = NULL;

UPDATE `users`
SET `otp_code` = NULL,
    `otp_purpose` = NULL,
    `otp_expires_at` = NULL,
    `forget_password_token` = NULL,
    `remember_token` = NULL,
    `google_access_token` = NULL,
    `google_refresh_token` = NULL;

UPDATE `course_live_classes`
SET `password` = NULL;

-- Remove plaintext service secrets while retaining non-secret settings.
UPDATE `settings`
SET `value` = ''
WHERE `key` IN (
    'recaptcha_secret_key',
    'facebook_app_secret',
    'gmail_client_id',
    'gmail_secret_id',
    'mail_password',
    'pusher_app_id',
    'pusher_app_key',
    'pusher_app_secret',
    'wasabi_access_id',
    'wasabi_secret_key',
    'aws_access_id',
    'aws_secret_key',
    'twilio_sid',
    'twilio_token',
    'bunny_core_api_key',
    'bunny_storage_access_key',
    'bunny_stream_api_key'
);

UPDATE `settings`
SET `value` = 'inactive'
WHERE `key` IN (
    'recaptcha_status',
    'facebook_login_status',
    'google_login_status',
    'pusher_status',
    'wasabi_status',
    'aws_status',
    'twilio_status',
    'bunny_cdn_status',
    'bunny_storage_status',
    'bunny_stream_status'
);

UPDATE `basic_payments`
SET `value` = ''
WHERE `key` IN ('paypal_app_id', 'paypal_client_id', 'paypal_secret_key');

UPDATE `basic_payments`
SET `value` = 'inactive'
WHERE `key` IN ('paypal_status', 'mpesa_stk_push_status');

UPDATE `payment_gateways`
SET `value` = ''
WHERE `key` IN ('paystack_public_key', 'paystack_secret_key');

UPDATE `payment_gateways`
SET `value` = 'inactive'
WHERE `key` = 'paystack_status';
