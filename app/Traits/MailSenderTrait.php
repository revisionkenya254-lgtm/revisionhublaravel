<?php

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\GlobalSetting\app\Models\Setting;

trait MailSenderTrait
{
    private static function isQueable(): bool
    {
        return getSettingStatus('is_queable');
    }

    private static function setMailConfig(): bool
    {
        try {
            if (Cache::has('setting')) {
                $email_setting = Cache::get('setting');
            } else {
                $setting_info = Setting::get();
                $setting = [];
                foreach ($setting_info as $setting_item) {
                    $setting[$setting_item->key] = $setting_item->value;
                }
                $email_setting = (object) $setting;
            }

            $smtpConfig = config('mail.mailers.smtp', []);
            $fromConfig = config('mail.from', []);

            $mailConfig = [
                'transport' => 'smtp',
                'url' => $smtpConfig['url'] ?? null,
                'host' => self::mailSettingValue($email_setting, 'mail_host', $smtpConfig['host'] ?? null),
                'port' => self::mailSettingValue($email_setting, 'mail_port', $smtpConfig['port'] ?? null),
                'encryption' => self::normalizeNullableMailValue(
                    self::mailSettingValue($email_setting, 'mail_encryption', $smtpConfig['encryption'] ?? null)
                ),
                'username' => self::mailSettingValue($email_setting, 'mail_username', $smtpConfig['username'] ?? null),
                'password' => self::mailSettingValue($email_setting, 'mail_password', $smtpConfig['password'] ?? null),
                'timeout' => $smtpConfig['timeout'] ?? null,
                'local_domain' => $smtpConfig['local_domain'] ?? null,
            ];

            config(['mail.mailers.smtp' => $mailConfig]);
            config(['mail.from.address' => self::mailSettingValue($email_setting, 'mail_sender_email', $fromConfig['address'] ?? null)]);
            config(['mail.from.name' => self::mailSettingValue($email_setting, 'mail_sender_name', $fromConfig['name'] ?? null)]);

            return true;
        } catch (Exception $e) {
            if (app()->isLocal()) {
                Log::error($e->getMessage());
            }

            return false;
        }
    }

    private static function mailSettingValue(object $settings, string $key, mixed $fallback = null): mixed
    {
        $value = data_get($settings, $key);

        if ($value === null) {
            return $fallback;
        }

        if (is_string($value) && trim($value) === '') {
            return $fallback;
        }

        return $value;
    }

    private static function normalizeNullableMailValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return strtolower(trim($value)) === 'null' ? null : $value;
    }
}
