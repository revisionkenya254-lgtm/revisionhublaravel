<?php

use App\Enums\ThemeList;
use App\Exceptions\AccessPermissionDeniedException;
use App\Models\Course;
use App\Models\Product;
use App\Models\User;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\BasicPayment\app\Models\BasicPayment;
use Modules\Blog\app\Models\Blog;
use Modules\Brand\app\Models\Brand;
use Modules\Course\app\Helper\CourseCategoryHelper;
use Modules\Currency\app\Models\MultiCurrency;
use Modules\FooterSetting\app\Models\FooterSetting;
use Modules\Frontend\app\Models\FeaturedCourseSection;
use Modules\Frontend\app\Models\FeaturedInstructor;
use Modules\GlobalSetting\app\Models\CustomCode;
use Modules\GlobalSetting\app\Models\Setting;
use Modules\GlobalSetting\app\Models\SmsTemplate;
use Modules\Language\app\Enums\AllCountriesDetailsEnum;
use Modules\Language\app\Models\Language;
use Modules\Location\app\Models\Country;
use Modules\Order\app\Models\Enrollment;
use Modules\Order\app\Models\Order;
use Modules\Order\app\Models\OrderItem;
use Modules\SiteAppearance\app\Models\SectionSetting;
use Modules\Testimonial\app\Models\Testimonial;
use App\Services\Ai\AiCreditPurchaseService;
use Nwidart\Modules\Facades\Module;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;

function file_upload(UploadedFile $file, string $path = 'uploads/custom-images/', string|null $oldFile = '', bool $optimize = false)
{
    $extention = $file->getClientOriginalExtension();
    $file_name = 'revisionhubkenya-img' . date('-Y-m-d-h-i-s-') . rand(999, 9999) . '.' . $extention;
    $file_name = $path . $file_name;
    $file->move(public_path($path), $file_name);

    try {
        if ($oldFile && !str($oldFile)->contains('uploads/website-images') && File::exists(public_path($oldFile))) {
            File::delete(public_path($oldFile));
        }

        if ($optimize) {
            ImageOptimizer::optimize(public_path($file_name));
        }
    } catch (Exception $e) {
        Log::info($e->getMessage());
    }

    return $file_name;
}
// file upload method
if (!function_exists('allLanguages')) {
    function allLanguages()
    {
        $allLanguages = Cache::rememberForever('allLanguages', function () {
            return Language::select('code', 'name', 'direction', 'status')->get();
        });

        if (!$allLanguages) {
            $allLanguages = Language::select('code', 'name', 'direction', 'status')->get();
        }

        return $allLanguages;
    }
}

if (!function_exists('allCurrencies')) {
    function allCurrencies()
    {
        $allCurrencies = Cache::rememberForever('allCurrencies', function () {
            return MultiCurrency::get();
        });

        if (!$allCurrencies) {
            $allCurrencies = MultiCurrency::get();
        }

        return $allCurrencies;
    }
}
if (!function_exists('brands')) {
    function brands()
    {
        return Cache::rememberForever('brands', function () {
            return Brand::select('name', 'image', 'url')->where('status', 1)->get();
        });
    }
}
if (!function_exists('SectionSetting')) {
    function SectionSetting()
    {
        return Cache::rememberForever('section_settings', function () {
            return SectionSetting::first();
        });
    }
}
if (!function_exists('FooterSetting')) {
    function FooterSetting()
    {
        return Cache::rememberForever('footer_settings', function () {
            return FooterSetting::first();
        });
    }
}

if (!function_exists('getSessionLanguage')) {
    function getSessionLanguage(): string
    {
        if (!session()->has('lang')) {
            session()->put('lang', config('app.locale'));
            session()->forget('text_direction');
            session()->put('text_direction', 'ltr');
        }

        $lang = Session::get('lang');

        return $lang;
    }
}

if (!function_exists('getSessionCurrency')) {
    function getSessionCurrency(): string
    {
        if (!session()->has('currency_code') || !session()->has('currency_rate') || !session()->has('currency_position')) {
            $currency = allCurrencies()->where('is_default', 'yes')->first();
            session()->put('currency_code', $currency->currency_code);
            session()->forget('currency_position');
            session()->put('currency_position', $currency->currency_position);
            session()->forget('currency_icon');
            session()->put('currency_icon', $currency->currency_icon);
            session()->forget('currency_rate');
            session()->put('currency_rate', $currency->currency_rate);
        }

        return Session::get('currency_code');
    }
}

function admin_lang()
{
    return Session::get('admin_lang');
}
if (!function_exists('getSocialLinks')) {
    function getSocialLinks()
    {
        return Cache::rememberForever('getSocialLinks', function () {
            return \Modules\SocialLink\app\Models\SocialLink::select('link', 'icon')->get();
        });
    }
}

// calculate currency
function currency($price)
{
    getSessionCurrency();
    $currency_icon = Session::get('currency_icon');
    $currency_rate = Session::has('currency_rate') ? Session::get('currency_rate') : 1;
    $currency_position = Session::get('currency_position');

    $price = $price * $currency_rate;
    $price = number_format($price, 2, '.', ',');

    if ($currency_position == 'before_price') {
        $price = $currency_icon . $price;
    } elseif ($currency_position == 'before_price_with_space') {
        $price = $currency_icon . ' ' . $price;
    } elseif ($currency_position == 'after_price') {
        $price = $price . $currency_icon;
    } elseif ($currency_position == 'after_price_with_space') {
        $price = $price . ' ' . $currency_icon;
    } else {
        $price = $currency_icon . $price;
    }

    return $price;
}

if (!function_exists('formatCurrencyValue')) {
    function formatCurrencyValue($price, $currency)
    {
        $currency_rate = $currency?->currency_rate ?? 1;
        $currency_icon = $currency?->currency_icon ?? '';
        $currency_position = $currency?->currency_position ?? 'before_price';

        $price = $price * $currency_rate;
        $price = number_format($price, 2, '.', ',');

        if ($currency_position == 'before_price') {
            return $currency_icon . $price;
        } elseif ($currency_position == 'before_price_with_space') {
            return $currency_icon . ' ' . $price;
        } elseif ($currency_position == 'after_price') {
            return $price . $currency_icon;
        } elseif ($currency_position == 'after_price_with_space') {
            return $price . ' ' . $currency_icon;
        }

        return $currency_icon . $price;
    }
}

if (!function_exists('defaultCurrency')) {
    function defaultCurrency($price)
    {
        $currency = allCurrencies()->where('is_default', 'yes')->first() ?? allCurrencies()->first();

        return formatCurrencyValue($price, $currency);
    }
}

if (!function_exists('convert_amount_to_base_currency')) {
    /**
     * Convert amount to base currency using currencies table.
     */
    function convert_amount_to_base_currency(float $amount, float $currency_rate): float
    {

        if ($currency_rate <= 0) {
            return $amount;
        }

        return $amount / $currency_rate;
    }
}

// calculate currency without icon
if (!function_exists('currencyWithoutIcon')) {
    function currencyWithoutIcon($price, $currency_code = null)
    {
        // Get the currency code from parameter or session
        $code = $currency_code ?: getSessionCurrency();

        // Get currency object from the list
        $currency = allCurrencies()->where('currency_code', $code)->first();

        // Fallback to default currency if not found
        if (!$currency) {
            $currency = allCurrencies()->where('is_default', 'yes')->first();
        }

        $convertedPrice = $price * $currency->currency_rate;
        return number_format($convertedPrice, 2, '.', '');
    }
}
if (!function_exists('userAuth')) {
    function userAuth()
    {
        return Auth::guard('web')->user();
    }
}
if (!function_exists('adminAuth')) {
    function adminAuth()
    {
        return Auth::guard('admin')->user();
    }
}

// custom decode and encode input value
function html_decode($text)
{
    $after_decode = htmlspecialchars_decode($text, ENT_QUOTES);

    return $after_decode;
}

if (!function_exists('checkAdminHasPermission')) {
    function checkAdminHasPermission($permission): bool
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin) {
            return false;
        }

        return $admin->can($permission) ? true : false;
    }
}

if (!function_exists('checkAdminHasPermissionAndThrowException')) {
    function checkAdminHasPermissionAndThrowException($permission)
    {
        if (!checkAdminHasPermission($permission)) {
            throw new AccessPermissionDeniedException();
        }
    }
}

if (!function_exists('getSettingStatus')) {
    function getSettingStatus($key)
    {
        if (Cache::has('setting')) {
            $setting = Cache::get('setting');
            if (!is_null($key)) {
                return $setting->$key == 'active' ? true : false;
            }
        } else {
            try {
                return Setting::where('key', $key)->first()?->value == 'active' ? true : false;
            } catch (Exception $e) {
                if (app()->isLocal()) {
                    Log::info($e->getMessage());
                }

                return false;
            }
        }

        return false;
    }
}
if (!function_exists('checkCrentials')) {
    function checkCrentials()
    {
        if (Cache::has('setting') && $settings = Cache::get('setting')) {
            if ($settings->recaptcha_status !== 'inactive' && ($settings->recaptcha_site_key == 'recaptcha_site_key' || $settings->recaptcha_secret_key == 'recaptcha_secret_key' || $settings->recaptcha_site_key == '' || $settings->recaptcha_secret_key == '')) {
                return (object) [
                    'status' => true,
                    'message' => __('Google Recaptcha credentails not found'),
                    'description' => __('This may create a problem while submitting any form submission from website. Please fill up the credential from google account.'),
                    'route' => 'admin.crediential-setting',
                ];
            }

            if ($settings->pixel_status !== 'inactive' && ($settings->pixel_app_id == 'pixel_app_id' || $settings->pixel_app_id == '')) {
                return (object) [
                    'status' => true,
                    'message' => __('Facebook Pixel credentails not found'),
                    'description' => __('This may create a problem to analyze your website. Please fill up the credential to avoid any problem.'),
                    'route' => 'admin.crediential-setting',
                ];
            }

            if ($settings->facebook_login_status !== 'inactive' && ($settings->facebook_app_id == 'facebook_app_id' || $settings->facebook_app_secret == 'facebook_app_secret' || $settings->facebook_redirect_url == 'facebook_redirect_url' || $settings->facebook_app_id == '' || $settings->facebook_app_secret == '' || $settings->facebook_redirect_url == '')) {
                return (object) [
                    'status' => true,
                    'message' => __('Facebook login credentails not found'),
                    'description' => __('This may create a problem while logging in using facebook. Please fill up the credential to avoid any problem.'),
                    'route' => 'admin.crediential-setting',
                ];
            }

            $googleClientId = config('services.google.client_id') ?: ($settings->gmail_client_id ?? null);
            $googleClientSecret = config('services.google.client_secret') ?: ($settings->gmail_secret_id ?? null);

            if ($settings->google_login_status !== 'inactive' && ($googleClientId == 'gmail_client_id' || $googleClientSecret == 'gmail_secret_id' || $googleClientId == '' || $googleClientSecret == '')) {
                return (object) [
                    'status' => true,
                    'message' => __('Google login credentails not found'),
                    'description' => __('This may create a problem while logging in using google. Please fill up the credential to avoid any problem.'),
                    'route' => 'admin.crediential-setting',
                ];
            }

            if ($settings->google_tagmanager_status !== 'inactive' && ($settings->google_tagmanager_id == 'google_tagmanager_id' || $settings->google_tagmanager_id == '')) {
                return (object) [
                    'status' => true,
                    'message' => __('Google tag manager credentials not found'),
                    'description' => __('This may create a problem to analyze your website. Please fill up the credential to avoid any problem.'),
                    'route' => 'admin.crediential-setting',
                ];
            }
            if ($settings->google_analytic_status !== 'inactive' && ($settings->google_analytic_id == 'google_analytic_id' || $settings->google_analytic_id == '')) {
                return (object) [
                    'status' => true,
                    'message' => __('Google analytic credentials not found'),
                    'description' => __('This may create a problem to analyze your website. Please fill up the credential to avoid any problem.'),
                    'route' => 'admin.crediential-setting',
                ];
            }

            if ($settings->tawk_status !== 'inactive' && ($settings->tawk_chat_link == 'tawk_chat_link' || $settings->tawk_chat_link == '')) {
                return (object) [
                    'status' => true,
                    'message' => __('Tawk Chat Link credentails not found'),
                    'description' => __('This may create a problem to analyze your website. Please fill up the credential to avoid any problem.'),
                    'route' => 'admin.crediential-setting',
                ];
            }

            if ($settings->pusher_status !== 'inactive' && ($settings->pusher_app_id == 'pusher_app_id' || $settings->pusher_app_key == 'pusher_app_key' || $settings->pusher_app_secret == 'pusher_app_secret' || $settings->pusher_app_cluster == 'pusher_app_cluster' || $settings->pusher_app_id == '' || $settings->pusher_app_key == '' || $settings->pusher_app_secret == '' || $settings->pusher_app_cluster == '')) {
                return (object) [
                    'status' => true,
                    'message' => __('Pusher credentails not found'),
                    'description' => __('This may create a problem while logging in using google. Please fill up the credential to avoid any problem.'),
                    'route' => 'admin.crediential-setting',
                ];
            }

            if ($settings->mail_host == 'mail_host' || $settings->mail_username == 'mail_username' || $settings->mail_password == 'mail_password' || $settings->mail_host == '' || $settings->mail_port == '' || $settings->mail_username == '' || $settings->mail_password == '') {
                return (object) [
                    'status' => true,
                    'message' => __('Mail credentails not found'),
                    'description' => __('This may create a problem while sending email. Please fill up the credential to avoid any problem.'),
                    'route' => 'admin.email-configuration',
                ];
            }
            if ($settings->wasabi_status !== 'inactive' && ($settings->wasabi_access_id == 'wasabi_access_id' || $settings->wasabi_access_id == '' || $settings->wasabi_secret_key == 'wasabi_secret_key' || $settings->wasabi_secret_key == '' || $settings->wasabi_bucket == 'wasabi_secret_key' || $settings->wasabi_bucket == '' || $settings->wasabi_region == 'wasabi_region' || $settings->wasabi_region == '')) {
                return (object) [
                    'status' => true,
                    'message' => __('Wasabi cloud storage credentials not found'),
                    'description' => __('This may create a problem to analyze your website. Please fill up the credential to avoid any problem.'),
                    'route' => 'admin.crediential-setting',
                ];
            }
            if (($settings->bunny_cdn_status ?? 'inactive') !== 'inactive' && (blank($settings->bunny_core_api_key) || blank($settings->bunny_cdn_pull_zone_name) || blank($settings->bunny_cdn_hostname))) {
                return (object) [
                    'status' => true,
                    'message' => __('Bunny CDN credentials not found'),
                    'description' => __('This may create a problem while managing Bunny CDN resources or serving CDN assets. Please fill up the Bunny credentials to avoid any problem.'),
                    'route' => 'admin.crediential-setting',
                ];
            }
            if (($settings->bunny_storage_status ?? 'inactive') !== 'inactive' && (blank($settings->bunny_core_api_key) || blank($settings->bunny_storage_zone_name) || blank($settings->bunny_storage_access_key) || blank($settings->bunny_storage_api_endpoint) || blank($settings->bunny_storage_cdn_url))) {
                return (object) [
                    'status' => true,
                    'message' => __('Bunny cloud storage credentials not found'),
                    'description' => __('This may create a problem while storing product assets. Please fill up the Bunny credentials to avoid any problem.'),
                    'route' => 'admin.crediential-setting',
                ];
            }
            if (($settings->bunny_stream_status ?? 'inactive') !== 'inactive' && (blank($settings->bunny_core_api_key) || blank($settings->bunny_stream_library_id) || blank($settings->bunny_stream_api_key) || blank($settings->bunny_stream_cdn_hostname) || blank($settings->bunny_stream_pull_zone_name))) {
                return (object) [
                    'status' => true,
                    'message' => __('Bunny Stream credentials not found'),
                    'description' => __('This may create a problem while managing Bunny Stream videos. Please fill up the Bunny credentials to avoid any problem.'),
                    'route' => 'admin.crediential-setting',
                ];
            }
            if ($settings->aws_status !== 'inactive' && ($settings->aws_access_id == 'aws_access_id' || $settings->aws_access_id == '' || $settings->aws_secret_key == 'aws_secret_key' || $settings->aws_secret_key == '' || $settings->aws_bucket == 'aws_secret_key' || $settings->aws_bucket == '' || $settings->aws_region == 'aws_region' || $settings->aws_region == '')) {
                return (object) [
                    'status' => true,
                    'message' => __('AWS cloud storage credentials not found'),
                    'description' => __('This may create a problem to analyze your website. Please fill up the credential to avoid any problem.'),
                    'route' => 'admin.crediential-setting',
                ];
            }
        }

        if (!Cache::has('basic_payment') && Module::isEnabled('BasicPayment')) {
            Cache::rememberForever('basic_payment', function () {
                $payment_info = BasicPayment::get();
                $basic_payment = [];
                foreach ($payment_info as $payment_item) {
                    $basic_payment[$payment_item->key] = $payment_item->value;
                }

                return (object) $basic_payment;
            });
        }

        if (Cache::has('basic_payment') && $basicPayment = Cache::get('basic_payment')) {
            $basicPaymentValue = function (string $key, mixed $default = null) use ($basicPayment) {
                return data_get($basicPayment, $key, $default);
            };

            if ($basicPayment?->paypal_status !== 'inactive' && ($basicPayment?->paypal_client_id == 'paypal_client_id' || $basicPayment?->paypal_secret_key == 'paypal_secret_key' || $basicPayment?->paypal_client_id == '' || $basicPayment?->paypal_secret_key == '')) {
                return (object) [
                    'status' => true,
                    'message' => __('Paypal credentails not found'),
                    'description' => __('This may create a problem while making payment. Please fill up the credential to avoid any problem.'),
                    'route' => 'admin.basicpayment',
                ];
            }
            if (($basicPayment?->mpesa_stk_push_status ?? 'inactive') !== 'inactive') {
                $activeMode = $basicPaymentValue('mpesa_stk_push_account_mode', 'sandbox');
                $sandboxConfig = config('basicpayment.mpesa_stk_push.sandbox', []);
                $productionConfig = config('basicpayment.mpesa_stk_push.production', []);

                $resolveCredential = function (string $settingKey, array $configSource, string $configKey, mixed $default = '') use ($basicPaymentValue) {
                    $configuredValue = data_get($configSource, $configKey, '');

                    if (!blank($configuredValue)) {
                        return $configuredValue;
                    }

                    $storedValue = $basicPaymentValue($settingKey, $default);

                    return blank($storedValue) ? $default : $storedValue;
                };

                $missingSandbox = blank($resolveCredential('mpesa_stk_sandbox_consumer_key', $sandboxConfig, 'consumer_key'))
                    || blank($resolveCredential('mpesa_stk_sandbox_consumer_secret', $sandboxConfig, 'consumer_secret'))
                    || blank($resolveCredential('mpesa_stk_sandbox_shortcode', $sandboxConfig, 'shortcode'))
                    || blank($resolveCredential('mpesa_stk_sandbox_party_b', $sandboxConfig, 'party_b'))
                    || blank($resolveCredential('mpesa_stk_sandbox_passkey', $sandboxConfig, 'passkey'));

                $missingProduction = blank($resolveCredential('mpesa_stk_production_consumer_key', $productionConfig, 'consumer_key'))
                    || blank($resolveCredential('mpesa_stk_production_consumer_secret', $productionConfig, 'consumer_secret'))
                    || blank($resolveCredential('mpesa_stk_production_shortcode', $productionConfig, 'shortcode'))
                    || blank($resolveCredential('mpesa_stk_production_party_b', $productionConfig, 'party_b'))
                    || blank($resolveCredential('mpesa_stk_production_passkey', $productionConfig, 'passkey'));

                if (($activeMode === 'sandbox' && $missingSandbox) || ($activeMode === 'production' && $missingProduction)) {
                    return (object) [
                        'status' => true,
                        'message' => __('Mpesa STK Push credential not found'),
                        'description' => __('This may create a problem while making payment. Please fill up the credential to avoid any problem.'),
                        'route' => 'admin.basicpayment',
                    ];
                }
            }
        }

        return false;
    }
}

if (!function_exists('isRoute')) {
    function isRoute(string|array $route, string|null $returnValue = null)
    {
        if (is_array($route)) {
            foreach ($route as $value) {
                if (Route::is($value)) {
                    return is_null($returnValue) ? true : $returnValue;
                }
            }
            return false;
        }

        if (Route::is($route)) {
            return is_null($returnValue) ? true : $returnValue;
        }

        return false;
    }
}
// get default language
if (!function_exists('getDefaultLanguage')) {
    function getDefaultLanguage(): string
    {
        // cache default language
        $defaultLanguage = Cache::rememberForever('defaultLanguage', function () {
            try {
                return Language::where('is_default', 1)->first()->code;
            } catch (\Exception $e) {
                info($e->getMessage());
                return 'en';
            }
        });

        return $defaultLanguage;
    }
}

/**
 * Set the tab step for the form
 *
 * @param string $name name of the tab session
 * @param string $step current step of the tab
 *
 * @return void
 */
if (!function_exists('setFormTabStep')) {
    function setFormTabStep(string $name, string $step): void
    {
        session()->flash($name, $step);
    }
}

/**
 * Get all countries from cache
 *
 * @return Collection all countries
 */
if (!function_exists('countries')) {
    function countries()
    {
        $countries = Cache::get('countries');

        if ($countries instanceof Collection && $countries->isNotEmpty()) {
            $dbCountryCount = Country::count();

            if ($countries->count() === $dbCountryCount) {
                return $countries;
            }
        }

        $countries = Country::all();
        Cache::forever('countries', $countries);

        return $countries;
    }
}

if (!function_exists('countriesWithDialCodes')) {
    function countriesWithDialCodes()
    {
        $countryDialCodes = AllCountriesDetailsEnum::getAll()
            ->mapWithKeys(function (object $country) {
                return [trim(mb_strtolower($country->name)) => $country->phone];
            });

        return countries()
            ->map(function ($country) use ($countryDialCodes) {
                $dialCode = $countryDialCodes[trim(mb_strtolower($country->name))] ?? null;

                return (object) [
                    'id' => $country->id,
                    'name' => $country->name,
                    'dial_code' => $dialCode ? '+' . $dialCode : null,
                ];
            })
            ->sortBy('name')
            ->values();
    }
}

if (!function_exists('normalizeRegistrationPhone')) {
    function normalizeRegistrationPhone(?string $phone = null, ?string $countryCode = null, ?string $localPhone = null): string
    {
        return preg_replace('/\s+/', '', trim((string) $phone));
    }
}

if (!function_exists('normalizeMpesaPhoneNumber')) {
    function normalizeMpesaPhoneNumber(?string $phone = null): ?string
    {
        $digits = preg_replace('/\D+/', '', trim((string) $phone));

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '2540')) {
            $digits = '254' . substr($digits, 4);
        } elseif (str_starts_with($digits, '0')) {
            $digits = '254' . substr($digits, 1);
        } elseif (str_starts_with($digits, '7') && strlen($digits) === 9) {
            $digits = '254' . $digits;
        }

        return preg_match('/^2547\d{8}$/', $digits) ? $digits : null;
    }
}

if (!function_exists('instructorStatus')) {
    function instructorStatus()
    {
        return auth('web')->user()?->instructorInfo?->status;
    }
}

if (!function_exists('isInstructorAccount')) {
    function isInstructorAccount(): bool
    {
        if (!auth('web')->check()) {
            return false;
        }

        $user = auth('web')->user();

        return $user?->role === 'instructor' || instructorStatus() === \App\Enums\UserStatus::APPROVED->value;
    }
}

if (!function_exists('instructorEntryRouteName')) {
    function instructorEntryRouteName(): string
    {
        return isInstructorAccount() ? 'instructor.dashboard' : 'become-instructor';
    }
}

if (!function_exists('canSeeBecomeInstructorLink')) {
    function canSeeBecomeInstructorLink(): bool
    {
        if (!auth('web')->check()) {
            return false;
        }

        return auth('web')->user()?->role === 'student'
            && !isInstructorAccount()
            && instructorStatus() !== \App\Enums\UserStatus::PENDING->value;
    }
}

if (!function_exists('userAccountBadgeState')) {
    function userAccountBadgeState(): string
    {
        if (!auth('web')->check()) {
            return 'guest';
        }

        if (isInstructorAccount()) {
            return 'instructor';
        }

        if (instructorStatus() === \App\Enums\UserStatus::PENDING->value) {
            return 'pending-instructor';
        }

        return auth('web')->user()?->role === 'student' ? 'student' : 'student';
    }
}

if (!function_exists('userAccountBadgeLabel')) {
    function userAccountBadgeLabel(): string
    {
        return match (userAccountBadgeState()) {
            'instructor' => __('Instructor'),
            'pending-instructor' => __('Pending instructor'),
            'student' => __('Student'),
            default => '',
        };
    }
}

if (!function_exists('userAccountBadgeIcon')) {
    function userAccountBadgeIcon(): string
    {
        return match (userAccountBadgeState()) {
            'instructor' => 'fa-check-circle',
            'pending-instructor' => 'fa-hourglass-half',
            'student' => 'fa-user-graduate',
            default => '',
        };
    }
}

if (!function_exists('userAccountBadgeClass')) {
    function userAccountBadgeClass(): string
    {
        return match (userAccountBadgeState()) {
            'instructor' => 'is-instructor',
            'pending-instructor' => 'is-pending-instructor',
            'student' => 'is-student',
            default => '',
        };
    }
}

if (!function_exists('instructorRequestBadgeState')) {
    function instructorRequestBadgeState(): string
    {
        if (!auth('web')->check()) {
            return 'guest';
        }

        return match (instructorStatus()) {
            \App\Enums\UserStatus::APPROVED->value => 'instructor',
            \App\Enums\UserStatus::PENDING->value => 'pending-instructor',
            \App\Enums\UserStatus::REJECTED->value => 'rejected-instructor',
            default => auth('web')->user()?->role === 'student' ? 'student' : 'student',
        };
    }
}

if (!function_exists('instructorRequestBadgeLabel')) {
    function instructorRequestBadgeLabel(): string
    {
        return match (instructorRequestBadgeState()) {
            'instructor' => __('Instructor'),
            'pending-instructor' => __('Pending instructor'),
            'rejected-instructor' => __('Rejected instructor'),
            'student' => __('Student'),
            default => '',
        };
    }
}

if (!function_exists('instructorRequestBadgeIcon')) {
    function instructorRequestBadgeIcon(): string
    {
        return match (instructorRequestBadgeState()) {
            'instructor' => 'fa-check-circle',
            'pending-instructor' => 'fa-hourglass-half',
            'rejected-instructor' => 'fa-times-circle',
            'student' => 'fa-user-graduate',
            default => '',
        };
    }
}

if (!function_exists('instructorRequestBadgeClass')) {
    function instructorRequestBadgeClass(): string
    {
        return match (instructorRequestBadgeState()) {
            'instructor' => 'is-instructor',
            'pending-instructor' => 'is-pending-instructor',
            'rejected-instructor' => 'is-rejected-instructor',
            'student' => 'is-student',
            default => '',
        };
    }
}
if (!function_exists('customCode')) {
    function customCode()
    {
        return Cache::rememberForever('customCode', function () {
            return CustomCode::select('css', 'header_javascript', 'javascript')->first();
        });
    }
}

/** Truncate string function */
if (!function_exists('truncate')) {
    function truncate($text, $limit = 60)
    {
        $text = $text ?? '';
        if (mb_strlen($text) > $limit) {
            return mb_substr($text, 0, $limit) . '...';
        }
        return $text;
    }
}

/** Format date function */
if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'd M, Y')
    {
        return Carbon::parse($date)->format($format);
    }
}
if (!function_exists('formatTime')) {
    function formatTime($date, $format = 'h:i a')
    {
        return Carbon::parse($date)->format($format);
    }
}
if (!function_exists('formattedDateTime')) {
    function formattedDateTime($datetime)
    {
        return formatDate($datetime) . ' - ' . formatTime($datetime);
    }
}

/** Format minutes to hours */
if (!function_exists('minutesToHours')) {
    function minutesToHours($minutesToHours)
    {
        if ($minutesToHours === 0 || $minutesToHours === null) {
            return '--.--';
        }

        $hours = floor($minutesToHours / 60);
        $minutes = $minutesToHours % 60;
        return $hours . 'h ' . ($minutes ? $minutes . 'm' : '');
    }
}

if (!function_exists('revisionHubSubscriptionPlans')) {
    function revisionHubSubscriptionPlans(): array
    {
        return [
            [
                'id' => 'month-1',
                'name' => __('1 Month Plan'),
                'amount' => 500,
                'price' => 'KES 500',
                'period' => __('month'),
                'billing' => __('Billed every month'),
                'months' => 1,
            ],
            [
                'id' => 'month-3',
                'name' => __('3 Months Plan'),
                'amount' => 1000,
                'price' => 'KES 1,000',
                'period' => __('3 months'),
                'billing' => __('Billed every 3 months'),
                'months' => 3,
            ],
            [
                'id' => 'month-6',
                'name' => __('6 Months Plan'),
                'amount' => 2500,
                'price' => 'KES 2,500',
                'period' => __('6 months'),
                'billing' => __('Billed every 6 months'),
                'months' => 6,
            ],
            [
                'id' => 'year-1',
                'name' => __('1 Year Plan'),
                'amount' => 4000,
                'price' => 'KES 4,000',
                'period' => __('year'),
                'billing' => __('Billed every year'),
                'months' => 12,
            ],
        ];
    }
}

if (!function_exists('revisionHubSubscriptionPlan')) {
    function revisionHubSubscriptionPlan(string $planId): ?array
    {
        return collect(revisionHubSubscriptionPlans())->firstWhere('id', $planId);
    }
}

/**
 * Price an immediate subscription upgrade. Unused time on the active plan is
 * converted to a credit against a longer plan; downgrades are not allowed
 * while a subscription is active.
 */
if (!function_exists('revisionHubSubscriptionUpgradeQuote')) {
    function revisionHubSubscriptionUpgradeQuote(User $user, array $targetPlan): array
    {
        // Controllers may provide presentation-only plan arrays; pricing and
        // duration always come from the canonical server-side plan definition.
        $targetPlan = revisionHubSubscriptionPlan((string) ($targetPlan['id'] ?? '')) ?? $targetPlan;
        $baseAmount = (int) $targetPlan['amount'];

        if (!hasActiveSubscription($user)) {
            return [
                'allowed' => true,
                'kind' => 'new',
                'base_amount' => $baseAmount,
                'credit_amount' => 0,
                'payable_amount' => $baseAmount,
                'current_plan' => null,
            ];
        }

        $currentPlan = revisionHubSubscriptionPlan((string) $user->subscription_plan_id);
        if (!$currentPlan) {
            return [
                'allowed' => true,
                'kind' => 'new',
                'base_amount' => $baseAmount,
                'credit_amount' => 0,
                'payable_amount' => $baseAmount,
                'current_plan' => null,
            ];
        }

        if ((int) $targetPlan['months'] <= (int) $currentPlan['months']) {
            return [
                'allowed' => false,
                'message' => __('Choose a longer plan to upgrade your active subscription.'),
                'current_plan' => $currentPlan,
            ];
        }

        $startedAt = $user->subscription_started_at;
        $expiresAt = $user->subscription_expires_at;
        $totalSeconds = $startedAt && $expiresAt ? max(1, $startedAt->diffInSeconds($expiresAt)) : 1;
        $remainingSeconds = $expiresAt ? max(0, now()->diffInSeconds($expiresAt, false)) : 0;
        $credit = min(
            (int) $currentPlan['amount'],
            (int) round(((int) $currentPlan['amount']) * ($remainingSeconds / $totalSeconds))
        );

        return [
            'allowed' => true,
            'kind' => 'upgrade',
            'base_amount' => $baseAmount,
            'credit_amount' => $credit,
            'payable_amount' => max(0, $baseAmount - $credit),
            'current_plan' => $currentPlan,
        ];
    }
}

if (!function_exists('revisionHubSubscriptionBonusFraction')) {
    function revisionHubSubscriptionBonusFraction(): float
    {
        return max(0, (float) config('ai.purchase.subscription_bonus_fraction', 0.2));
    }
}

if (!function_exists('revisionHubSubscriptionBonusCredits')) {
    function revisionHubSubscriptionBonusCredits(int $amount): int
    {
        $purchaseService = app(AiCreditPurchaseService::class);
        $directCredits = (int) data_get($purchaseService->resolve($amount), 'credits', $purchaseService->creditsForAmount($amount));

        return (int) round($directCredits * revisionHubSubscriptionBonusFraction());
    }
}

if (!function_exists('revisionHubSubscriptionExpiry')) {
    function revisionHubSubscriptionExpiry(string $planId, ?Carbon $startAt = null): Carbon
    {
        $plan = revisionHubSubscriptionPlan($planId);
        $months = (int) ($plan['months'] ?? 0);
        $startAt = $startAt ? $startAt->copy() : Carbon::now();

        return $months > 0
            ? $startAt->copy()->addMonthsNoOverflow($months)
            : $startAt->copy()->addMonthNoOverflow();
    }
}

if (!function_exists('hasActiveSubscription')) {
    function hasActiveSubscription($user = null): bool
    {
        $user = $user instanceof User ? $user : userAuth();

        if (!$user) {
            return false;
        }

        $expiresAt = $user->subscription_expires_at ?? null;

        return filled($user->subscription_plan_id ?? null)
            && $expiresAt instanceof Carbon
            && $expiresAt->isFuture();
    }
}

if (!function_exists('activateSubscriptionPlanForUser')) {
    function activateSubscriptionPlanForUser(User $user, string $planId, ?Order $order = null): void
    {
        $plan = revisionHubSubscriptionPlan($planId);

        if (!$plan) {
            return;
        }

        $startedAt = Carbon::now();

        $user->forceFill([
            'subscription_plan_id' => $plan['id'],
            'subscription_started_at' => $startedAt,
            'subscription_expires_at' => revisionHubSubscriptionExpiry($plan['id'], $startedAt),
        ])->save();
    }
}

/** Set enrollment ids in session */

if (!function_exists('setEnrollmentIdsInSession')) {
    function setEnrollmentIdsInSession()
    {
        if (auth('web')->check()) {
            $enrollmentsIds = Enrollment::where('user_id', userAuth()->id)->pluck('course_id')->toArray();

            if (hasActiveSubscription()) {
                $subscriptionCourseIds = Course::active()->pluck('id')->toArray();
                $enrollmentsIds = array_values(array_unique(array_merge($enrollmentsIds, $subscriptionCourseIds)));
            }

            session()->put('enrollments', $enrollmentsIds);
            return;
        }

        session()->put('enrollments', []);
    }
}
/** Set instructor course ids in session */

if (!function_exists('setInstructorCourseIdsInSession')) {
    function setInstructorCourseIdsInSession()
    {
        if (auth('web')->check() && userAuth()->role == 'instructor') {
            $enrollmentsIds = Course::where('instructor_id', userAuth()->id)->pluck('id')->toArray();
            session()->put('instructor_courses', $enrollmentsIds);
            return;
        }

        session()->put('instructor_courses', []);
    }
}

if (!function_exists('processText')) {
    function processText($text)
    {
        // Replace text within square brackets with a <span> tag
        $patternSquareBrackets = '/\[(.*?)\]/';
        $replacementSquareBrackets = '<span class="highlight">$1</span>';
        $text = preg_replace($patternSquareBrackets, $replacementSquareBrackets, $text);

        // Replace text within curly brackets with a <span> tag
        $patternCurlyBrackets = '/\{(.*?)\}/';
        $replacementCurlyBrackets = '<b>$1</b>';
        $text = preg_replace($patternCurlyBrackets, $replacementCurlyBrackets, $text);

        // Replace backslashes with <br> tags
        $patternBackslash = '/\\\\/';
        $replacementBackslash = '<br>';
        $text = preg_replace($patternBackslash, $replacementBackslash, $text);

        // Return the modified text
        return $text;
    }
}
function calculateReadingTime($content)
{
    // Average reading speed (words per minute)
    $readingSpeed = 200;

    // Strip HTML tags and count the words
    $wordCount = str_word_count(strip_tags($content));

    // Calculate the reading time in minutes
    $readingTime = ceil($wordCount / $readingSpeed);

    return $readingTime;
}

if (!function_exists('getTags')) {
    function getTags($jsonTag = [])
    {
        $tags = $jsonTag;
        $tags_string = '';
        foreach ($tags as $tag) {
            $tags_string .= $tag->value . ',';
        }
        return $tags_string = rtrim($tags_string, ',');
    }
}
if (!function_exists('extractGoogleDriveVideoId')) {
    function extractGoogleDriveVideoId($url)
    {
        $googleDriveRegex = '/(?:https?:\/\/)?(?:www\.)?(?:drive\.google\.com\/(?:uc\?id=|file\/d\/|open\?id=)|youtu\.be\/)([\w-]{25,})[?=&#]*/';
        if (preg_match($googleDriveRegex, $url, $matches)) {
            return $matches[1];
        }
        return null;
    }
}

if (!function_exists('extractAndFilterImageSrc')) {
    function extractAndFilterImageSrc($string)
    {
        preg_match_all('/<img[^>]+src="([^">]+)"/i', $string, $matches);
        foreach (array_filter(array_map(function ($src) {
            $path = preg_replace('/^.*\/(uploads\/.*)$/', '$1', $src);
            return preg_match('/^uploads\/forum-images\/[^\/]+\.[a-zA-Z]{3,4}$/', $path) ? $path : null;
        }, $matches[1])) as $image) {
            $fullPath = public_path($image);
            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }

        }
    }
}
if (!function_exists('replaceImageSources')) {
    function replaceImageSources($html)
    {
        $baseUrl = url('uploads/forum-images/');
        $pattern = '/<img\s+[^>]*src=["\']([^"\']+)["\'][^>]*>/i';

        $replacement = function ($matches) use ($baseUrl) {
            $existingSrc = $matches[1];
            $newSrc = $baseUrl . '/' . basename($existingSrc);
            return str_replace($existingSrc, $newSrc, $matches[0]);
        };
        $newHtml = preg_replace_callback($pattern, $replacement, $html);
        return $newHtml;
    }
}
if (!function_exists('adminSearchRouteList')) {
    function adminSearchRouteList(): object
    {
        $route_list = [
            (object) ['name' => __('Dashboard'), 'route' => route('admin.dashboard'), 'permission' => 'dashboard.view'],
            (object) ['name' => __('Courses'), 'route' => route('admin.courses.index'), 'permission' => 'course.management'],
            (object) ['name' => __('Course Categories'), 'route' => route('admin.course-category.index'), 'permission' => 'course.management'],
            (object) ['name' => __('Course languages'), 'route' => route('admin.course-language.index'), 'permission' => 'course.management'],
            (object) ['name' => __('Course levels'), 'route' => route('admin.course-level.index'), 'permission' => 'course.management'],
            (object) ['name' => __('Course Reviews'), 'route' => route('admin.course-review.index'), 'permission' => 'course.management'],
            (object) ['name' => __('Course Delete Requests'), 'route' => route('admin.course-delete-request.index'), 'permission' => 'course.management'],
            (object) ['name' => __('Certificate Builder'), 'route' => route('admin.certificate-builder.index'), 'permission' => 'course.certificate.management'],
            (object) ['name' => __('Badges'), 'route' => route('admin.badges.index'), 'permission' => 'badge.management'],
            (object) ['name' => __('Blog Categories'), 'route' => route('admin.blog-category.index'), 'permission' => 'blog.category.view'],
            (object) ['name' => __('Blog List'), 'route' => route('admin.blogs.index'), 'permission' => 'blog.view'],
            (object) ['name' => __('Blog Comments'), 'route' => route('admin.blog-comment.index'), 'permission' => 'blog.comment.view'],
            (object) ['name' => __('Order History'), 'route' => route('admin.orders'), 'permission' => 'order.management'],
            (object) ['name' => __('Pending Payment'), 'route' => route('admin.pending-orders'), 'permission' => 'order.management'],
            (object) ['name' => __('Coupon List'), 'route' => route('admin.coupon.index'), 'permission' => 'coupon.management'],
            (object) ['name' => __('Withdraw Method'), 'route' => route('admin.withdraw-method.index'), 'permission' => 'withdraw.management'],
            (object) ['name' => __('Withdraw list'), 'route' => route('admin.withdraw-list'), 'permission' => 'withdraw.management'],
            (object) ['name' => __('Instructor Request List'), 'route' => route('admin.instructor-request.index'), 'permission' => 'instructor.request.list'],
            (object) ['name' => __('Instructor Request Settings'), 'route' => route('admin.instructor-request-setting.index'), 'permission' => 'instructor.request.list'],
            (object) ['name' => __('All Students'), 'route' => route('admin.all-customers'), 'permission' => 'customer.view'],
            (object) ['name' => __('All Instructors'), 'route' => route('admin.all-instructors'), 'permission' => 'customer.view'],
            (object) ['name' => __('Active Users'), 'route' => route('admin.active-customers'), 'permission' => 'customer.view'],
            (object) ['name' => __('Non verified Users'), 'route' => route('admin.non-verified-customers'), 'permission' => 'customer.view'],
            (object) ['name' => __('Banned Users'), 'route' => route('admin.banned-customers'), 'permission' => 'customer.view'],
            (object) ['name' => __('Send bulk mail Users'), 'route' => route('admin.send-bulk-mail'), 'permission' => 'customer.view'],
            (object) ['name' => __('Countries'), 'route' => route('admin.country.index'), 'permission' => 'location.view'],
            (object) ['name' => __('Site Themes'), 'route' => route('admin.site-appearance.index'), 'permission' => 'appearance.management'],
            (object) ['name' => __('Section Setting'), 'route' => route('admin.section-setting.index'), 'permission' => 'appearance.management'],
            (object) ['name' => __('Site Colors'), 'route' => route('admin.site-color-setting.index'), 'permission' => 'appearance.management'],
            (object) ['name' => __('About Section'), 'route' => route('admin.about-section.index', ['code' => 'en']), 'permission' => 'section.management'],
            (object) ['name' => __('Featured Course Section'), 'route' => route('admin.featured-course-section.index'), 'permission' => 'section.management'],
            (object) ['name' => __('Newsletter Section'), 'route' => route('admin.newsletter-section.index'), 'permission' => 'section.management'],
            (object) ['name' => __('Featured Instructor'), 'route' => route('admin.featured-instructor-section.edit', ['featured_instructor_section' => 1, 'code' => 'en']), 'permission' => 'section.management'],
            (object) ['name' => __('Counter Section'), 'route' => route('admin.counter-section.index'), 'permission' => 'section.management'],
            (object) ['name' => __('Faq Section'), 'route' => route('admin.faq-section.index', ['code' => 'en']), 'permission' => 'section.management'],
            (object) ['name' => __('Our Features Section'), 'route' => route('admin.our-features-section.index', ['code' => 'en']), 'permission' => 'section.management'],
            (object) ['name' => __('Banner Section'), 'route' => route('admin.banner-section.index'), 'permission' => 'section.management'],
            (object) ['name' => __('Contact Page Section'), 'route' => route('admin.contact-section.index'), 'permission' => 'section.management'],
            (object) ['name' => __('Brands'), 'route' => route('admin.brand.index'), 'permission' => 'brand.managemen'],
            (object) ['name' => __('Footer Setting'), 'route' => route('admin.footersetting.index'), 'permission' => 'footer.management'],
            (object) ['name' => __('Menu Builder'), 'route' => route('admin.menubuilder.index'), 'permission' => 'menu.view'],
            (object) ['name' => __('Page Builder'), 'route' => route('admin.page-builder.index'), 'permission' => 'page.management'],
            (object) ['name' => __('Social Links'), 'route' => route('admin.social-link.index'), 'permission' => 'social.link.management'],
            (object) ['name' => __('FAQS'), 'route' => route('admin.faq.index'), 'permission' => 'faq.view'],
            (object) ['name' => __('Subscriber List'), 'route' => route('admin.subscriber-list'), 'permission' => 'newsletter.view'],
            (object) ['name' => __('Subscriber Send bulk mail'), 'route' => route('admin.send-mail-to-newsletter'), 'permission' => 'newsletter.view'],
            (object) ['name' => __('Testimonial'), 'route' => route('admin.testimonial.index'), 'permission' => 'testimonial.view'],
            (object) ['name' => __('Contact Messages'), 'route' => route('admin.contact-messages'), 'permission' => 'contect.message.view'],
            (object) ['name' => __('General Settings'), 'route' => route('admin.general-setting'), 'permission' => 'setting.view', 'tab' => 'general_tab'],
            (object) ['name' => __('Logo & Favicon'), 'route' => route('admin.general-setting'), 'permission' => 'setting.view', 'tab' => 'logo_favicon_tab'],
            (object) ['name' => __('Video Watermark'), 'route' => route('admin.general-setting'), 'permission' => 'setting.view', 'tab' => 'watermark_tab'],
            (object) ['name' => __('Cookie Consent'), 'route' => route('admin.general-setting'), 'permission' => 'setting.view', 'tab' => 'cookie_consent_tab'],
            (object) ['name' => __('Breadcrumb image'), 'route' => route('admin.general-setting'), 'permission' => 'setting.view', 'tab' => 'breadcrump_img_tab'],
            (object) ['name' => __('Copyright Text'), 'route' => route('admin.general-setting'), 'permission' => 'setting.view', 'tab' => 'copyright_text_tab'],
            (object) ['name' => __('Maintenance Mode'), 'route' => route('admin.general-setting'), 'permission' => 'setting.view', 'tab' => 'mmaintenance_mode_tab'],
            (object) ['name' => __('Credential Settings'), 'route' => route('admin.crediential-setting'), 'permission' => 'setting.view', 'tab' => 'google_recaptcha_tab'],
            (object) ['name' => __('Google reCaptcha'), 'route' => route('admin.crediential-setting'), 'permission' => 'setting.view', 'tab' => 'google_recaptcha_tab'],
            (object) ['name' => __('Google Tag Manager'), 'route' => route('admin.crediential-setting'), 'permission' => 'setting.view', 'tab' => 'google_tag_tab'],
            (object) ['name' => __('Wasabi Cloud Storage'), 'route' => route('admin.crediential-setting'), 'permission' => 'setting.view', 'tab' => 'wasabi_tab'],
            (object) ['name' => __('Bunny Cloud'), 'route' => route('admin.crediential-setting'), 'permission' => 'setting.view', 'tab' => 'bunny_storage_tab'],
            (object) ['name' => __('AWS Cloud Storage'), 'route' => route('admin.crediential-setting'), 'permission' => 'setting.view', 'tab' => 'aws_tab'],
            (object) ['name' => __('Google Analytic'), 'route' => route('admin.crediential-setting'), 'permission' => 'setting.view', 'tab' => 'google_analytic_tab'],
            (object) ['name' => __('Facebook Pixel'), 'route' => route('admin.crediential-setting'), 'permission' => 'setting.view', 'tab' => 'facebook_pixel_tab'],
            (object) ['name' => __('Social Login'), 'route' => route('admin.crediential-setting'), 'permission' => 'setting.view', 'tab' => 'social_login_tab'],
            (object) ['name' => __('Tawk Chat'), 'route' => route('admin.crediential-setting'), 'permission' => 'setting.view', 'tab' => 'tawk_chat_tab'],
            (object) ['name' => __('Email Configuration'), 'route' => route('admin.email-configuration'), 'permission' => 'setting.view', 'tab' => 'setting_tab'],
            (object) ['name' => __('Email Template'), 'route' => route('admin.email-configuration'), 'permission' => 'setting.view', 'tab' => 'email_template_tab'],
            (object) ['name' => __('SMS Configuration'), 'route' => route('admin.sms-configuration'), 'permission' => 'setting.view', 'tab' => 'setting_tab'],
            (object) ['name' => __('Twilio'), 'route' => route('admin.sms-configuration'), 'permission' => 'setting.view', 'tab' => 'twilio_template_tab'],
            (object) ['name' => __('SMS Template'), 'route' => route('admin.sms-configuration'), 'permission' => 'setting.view', 'tab' => 'sms_template_tab'],
            (object) ['name' => __('SEO Setup'), 'route' => route('admin.seo-setting'), 'permission' => 'setting.view'],
            (object) [
                'name' => __('Custom CSS'),
                'route' => route('admin.custom-code', ['type' => 'css']),
                'permission' => 'setting.view',
            ],
            (object) [
                'name' => __('Custom JS'),
                'route' => route('admin.custom-code', ['type' => 'js']),
                'permission' => 'setting.view',
            ],
            (object) [
                'name' => __('Marketing Settings'),
                'route' => route('admin.marketing-setting'),
                'permission' => 'setting.view',
            ],
            (object) ['name' => __('Clear cache'), 'route' => route('admin.cache-clear'), 'permission' => 'setting.view'],
            (object) ['name' => __('Database Clear'), 'route' => route('admin.database-clear'), 'permission' => 'setting.view'],
            (object) ['name' => __('System Update'), 'route' => route('admin.system-update.index'), 'permission' => 'setting.view'],
            (object) ['name' => __('Manage Addons'), 'route' => route('admin.addons.view'), 'permission' => 'setting.view'],
            (object) ['name' => __('Admin Commission'), 'route' => route('admin.commission-setting'), 'permission' => 'setting.view'],
            (object) ['name' => __('Manage Language'), 'route' => route('admin.languages.index'), 'permission' => 'language.view'],
            (object) ['name' => __('Payment Gateway'), 'route' => route('admin.basicpayment'), 'permission' => 'basic.payment.view'],
            (object) ['name' => __('Multi Currency'), 'route' => route('admin.currency.index'), 'permission' => 'currency.view'],
            (object) ['name' => __('Manage Admin'), 'route' => route('admin.admin.index'), 'permission' => 'admin.view'],
            (object) ['name' => __('Role & Permissions'), 'route' => route('admin.role.index'), 'permission' => 'role.view'],
            (object) ['name' => __('Refund History'), 'route' => route('admin.refund-request'), 'permission' => 'refund'],
            (object) ['name' => __('Pending Refund'), 'route' => route('admin.pending-refund-request'), 'permission' => 'refund'],
            (object) ['name' => __('Rejected Refund'), 'route' => route('admin.rejected-refund-request'), 'permission' => 'refund'],
            (object) ['name' => __('Complete Refund'), 'route' => route('admin.complete-refund-request'), 'permission' => 'refund'],
            (object) ['name' => __('Manual Enrollment'), 'route' => route('admin.manual-enrollment.index'), 'permission' => 'enrollment.manual.view'],
        ];

        if (DEFAULT_HOMEPAGE == ThemeList::BUSINESS->value) {
            $route_list[] = (object) ['name' => __('Slider Section'), 'route' => route('admin.slider-section.index', ['code' => 'en']), 'permission' => 'section.management'];
        } else {
            $route_list[] = (object) ['name' => __('Hero Section'), 'route' => route('admin.hero-section.index', ['code' => 'en']), 'permission' => 'section.management'];
        }
        if (in_array(DEFAULT_HOMEPAGE, [ThemeList::MAIN->value, ThemeList::ONLINE->value, ThemeList::UNIVERSITY->value, ThemeList::LANGUAGE->value])) {
            $route_list[] = (object) ['name' => __('Counter Section'), 'route' => route('admin.counter-section.index'), 'permission' => 'section.management'];
        }
        if (Module::has('LiveChat') && Module::isEnabled('LiveChat')) {
            $route_list[] = (object) ['name' => __('Pusher'), 'route' => route('admin.crediential-setting'), 'permission' => 'setting.view', 'tab' => 'pusher_tab'];
        }

        usort($route_list, function ($a, $b) {
            return strcmp($a->name, $b->name);
        });

        return (object) $route_list;
    }
}
//wasabi config setup
if (!function_exists('set_wasabi_config')) {
    function set_wasabi_config()
    {
        $wasabi_setting = Cache::get('setting');
        config(['filesystems.disks.wasabi.key' => data_get($wasabi_setting, 'wasabi_access_id')]);
        config(['filesystems.disks.wasabi.secret' => data_get($wasabi_setting, 'wasabi_secret_key')]);
        config(['filesystems.disks.wasabi.bucket' => data_get($wasabi_setting, 'wasabi_bucket')]);
        config(['filesystems.disks.wasabi.region' => data_get($wasabi_setting, 'wasabi_region')]);
    }
}
if (!function_exists('set_bunny_config')) {
    function set_bunny_config()
    {
        $bunny_setting = Cache::get('setting');
        config(['bunny.core_api_key' => data_get($bunny_setting, 'bunny_core_api_key')]);
        config(['bunny.cdn_pull_zone_name' => data_get($bunny_setting, 'bunny_cdn_pull_zone_name')]);
        config(['bunny.cdn_hostname' => data_get($bunny_setting, 'bunny_cdn_hostname')]);
        config(['bunny.cdn_status' => data_get($bunny_setting, 'bunny_cdn_status')]);
        config(['bunny.storage_zone_name' => data_get($bunny_setting, 'bunny_storage_zone_name')]);
        config(['bunny.storage_access_key' => data_get($bunny_setting, 'bunny_storage_access_key')]);
        config(['bunny.storage_api_endpoint' => data_get($bunny_setting, 'bunny_storage_api_endpoint')]);
        config(['bunny.storage_cdn_url' => data_get($bunny_setting, 'bunny_storage_cdn_url')]);
        config(['bunny.storage_status' => data_get($bunny_setting, 'bunny_storage_status')]);
        config(['bunny.stream_library_id' => data_get($bunny_setting, 'bunny_stream_library_id')]);
        config(['bunny.stream_api_key' => data_get($bunny_setting, 'bunny_stream_api_key')]);
        config(['bunny.stream_cdn_hostname' => data_get($bunny_setting, 'bunny_stream_cdn_hostname')]);
        config(['bunny.stream_pull_zone_name' => data_get($bunny_setting, 'bunny_stream_pull_zone_name')]);
        config(['bunny.stream_status' => data_get($bunny_setting, 'bunny_stream_status')]);
    }
}
if (!function_exists('set_aws_config')) {
    function set_aws_config()
    {
        $aws_setting = Cache::get('setting');
        $awsBucket = data_get($aws_setting, 'aws_bucket');
        config(['filesystems.disks.aws.key' => data_get($aws_setting, 'aws_access_id')]);
        config(['filesystems.disks.aws.secret' => data_get($aws_setting, 'aws_secret_key')]);
        config(['filesystems.disks.aws.bucket' => $awsBucket]);
        config(['filesystems.disks.aws.region' => data_get($aws_setting, 'aws_region')]);
        config(['filesystems.disks.aws.url' => $awsBucket ? "https://{$awsBucket}.s3.amazonaws.com/" : null]);
    }
}
if (!function_exists('generateUniqueSlug')) {
    /**
     * Generate a unique slug for a model based on an initial base slug.
     *
     * @param string $model The model class to check for existing slugs (e.g., Course::class).
     * @param string $title The title to convert to a base slug.
     * @param int|null $ignoreId Optional model id to exclude when checking for collisions.
     * @return string A unique slug string that can be safely used in the model.
     */
    function generateUniqueSlug($model, $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title, '-');
        $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($model), true);

        $slug = $baseSlug;
        $counter = 1;

        while (
            ($usesSoftDeletes ? $model::withTrashed() : $model::query())
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter++;
        }
        return $slug;
    }
}
if (!function_exists('convertMinutesToHoursAndMinutes')) {
    function convertMinutesToHoursAndMinutes($minutes)
    {
        if ($minutes <= 0) {
            return "0m";
        }
        return $minutes < 60 ? "{$minutes}m" : floor($minutes / 60) . "h" . ($minutes % 60 ? " " . $minutes % 60 . "m" : "");
    }
}
if (!function_exists('generateVideoEmbedUrl')) {
    /**
     * Generates an embed URL for video platforms.
     *
     * @param string $storage The storage platform ('upload','bunny_stream','youtube','vimeo','external_link','google_drive','iframe','wasabi','aws').
     * @param string $file_type The type of file (video','audio','pdf','txt','docx','iframe','image','file','other).
     * @param string $url The video URL.
     *
     * @return string|null The embed URL or null if not found.
     */
    function generateVideoEmbedUrl($url, $storage, $file_type = 'video')
    {
        if ($file_type !== 'video') {
            return asset($url);
        }
        if ($storage == 'google_drive') {
            if (preg_match('/(?:https?:\/\/)?(?:www\.)?(?:drive\.google\.com\/(?:uc\?id=|file\/d\/|open\?id=)|youtu\.be\/)([\w-]{25,})[?=&#]*/', $url, $match)) {
                return "https://drive.google.com/file/d/" . $match[1] . "/preview";
            }
            return null;
        }
        if ($storage == 'youtube') {
            if (preg_match('/(?:youtube(?:-nocookie)?\.com\/(?:.*[?&]v=|embed\/|shorts\/|v\/)|youtu\.be\/)([\w-]{11})/i', $url, $match)) {
                return "https://www.youtube.com/embed/" . $match[1] . "?rel=0";
            }
            return null;
        }
        if ($storage == 'vimeo') {
            if (preg_match('/(?:vimeo\.com\/)(\d{8,})/', $url, $match)) {
                return "https://player.vimeo.com/video/" . $match[1];
            }
            return null;
        }
        if ($storage == 'bunny_stream') {
            $libraryId = trim((string) config('bunny.stream_library_id'));
            $playerHost = trim((string) config('bunny.stream_cdn_hostname', 'player.mediadelivery.net'));

            if ($libraryId === '' || $url === '') {
                return null;
            }

            if ($playerHost === '') {
                $playerHost = 'player.mediadelivery.net';
            }

            if (!Str::startsWith($playerHost, ['http://', 'https://'])) {
                $playerHost = 'https://' . ltrim($playerHost, '/');
            }

            return rtrim($playerHost, '/') . '/embed/' . rawurlencode($libraryId) . '/' . rawurlencode($url);
        }
        if (in_array($storage, ['wasabi', 'aws'])) {
            return Storage::disk($storage)->temporaryUrl($url, now()->addSeconds(30));
        }
        return asset($url);
    }

}
if (!function_exists('apiCurrency')) {
    // calculate currency
    function apiCurrency($price, $currency_code = null)
    {
        $currency = allCurrencies()->where('currency_code', $currency_code)->first();
        if (!$currency) {
            $currency = allCurrencies()->where('is_default', 'yes')->first();
        }

        $currency_icon = $currency->currency_icon;
        $currency_rate = $currency->currency_rate;
        $currency_position = $currency->currency_position;

        $price = $price * $currency_rate;
        $price = number_format($price, 2, '.', ',');

        if ($currency_position == 'before_price') {
            $price = $currency_icon . $price;
        } elseif ($currency_position == 'before_price_with_space') {
            $price = $currency_icon . ' ' . $price;
        } elseif ($currency_position == 'after_price') {
            $price = $price . $currency_icon;
        } elseif ($currency_position == 'after_price_with_space') {
            $price = $price . ' ' . $currency_icon;
        } else {
            $price = $currency_icon . $price;
        }

        return $price;
    }
}

if (!function_exists('sessionCartToDatabase')) {
    /**
     * Transfers items from the session cart to the authenticated user's database cart.
     *
     * @param \App\Models\User $user The authenticated user.
     * @return void
     */
    function sessionCartToDatabase(): void
    {
        if (Cart::content()->count() > 0 && auth()->check()) {
            $user = userAuth();
            $carts = Cart::content();
            foreach ($carts as $item) {
                $itemType = $item->options['item_type'] ?? 'course';
                if ($itemType === 'product') {
                    $product = \App\Models\Product::active()->find($item->options['product_id'] ?? null);
                    if ($product && !hasProductInPurchased($user, $product) && !hasProductInCart($user, $product)) {
                        $user->carts()->create(['item_type' => 'product', 'product_id' => $product->id]);
                    }
                } else {
                    $course = Course::active()->find($item->id);
                    if ($course && !isOwnCourse($user, $course) && !hasCourseInPurchased($user, $course) && !hasCourseInCart($user, $course)) {
                        $user->carts()->create(['item_type' => 'course', 'course_id' => $item->id]);
                    }
                }
            }
            Cart::destroy();
        }
    }
}
if (!function_exists('isOwnCourse')) {
    function isOwnCourse($user, $course)
    {
        return $course->instructor_id == $user->id;
    }
}
if (!function_exists('hasCourseInPurchased')) {
    function hasCourseInPurchased($user, $course)
    {
        if (hasActiveSubscription($user)) {
            return true;
        }

        return $user->enrollments()->where('course_id', $course->id)->exists();
    }
}
if (!function_exists('hasCourseInCart')) {
    function hasCourseInCart($user, $course)
    {
        return $user->carts()->where('course_id', $course->id)->exists();
    }
}
if (!function_exists('hasProductInPurchased')) {
    function hasProductInPurchased($user, $product)
    {
        if (hasActiveSubscription($user)) {
            return true;
        }

        return OrderItem::where('item_type', 'product')
            ->where('product_id', $product->id)
            ->whereHas('order', fn($q) => $q->where('buyer_id', $user->id)->where('payment_status', 'paid'))
            ->exists();
    }
}
if (!function_exists('hasProductInCart')) {
    function hasProductInCart($user, $product)
    {
        return $user->carts()->where('product_id', $product->id)->exists();
    }
}
if (!function_exists('SMSTemplate')) {
    function SMSTemplate($templateName, $str_replace = [])
    {
        // Fetch the template by name
        $template = SmsTemplate::where('name', $templateName)->first();
        $message = $template->message;

        // Check if the $str_replace array exists and is not empty
        if (!empty($str_replace)) {
            // Replace placeholders with actual values
            foreach ($str_replace as $key => $value) {
                $message = str_replace(["{{" . $key . "}}", "{{ " . $key . " }}"], $value, $message);
            }
        }

        return $message;
    }
}
if (!function_exists('sendSMS')) {
    function sendSMS(?string $to, ?string $message): bool
    {
        if (empty($to) || empty($message)) {
            return false;
        }

        $setting = cache()->get('setting');
        $driver = $setting?->default_sms_driver;

        // Normalize phone number: remove space, dash, underscore
        $normalizedPhone = preg_replace('/[\s_-]+/', '', $to);

        $phoneCodeProperty = "{$driver}_phone_code";
        $countryCode = $setting?->$phoneCodeProperty ?? null;

        // Validate number if country code is set
        if ($countryCode) {
            $escapedCode = preg_quote($countryCode, '/');
            $regex = "/^{$escapedCode}\d{9,15}$/";

            if (!preg_match($regex, $normalizedPhone)) {
                info('Invalid phone number format: ' . $to);
                return false;
            }
        }

        try {
            $smsService = App\Factories\SMSFactory::make($driver);
            return $smsService->send($normalizedPhone, $message);
        } catch (Exception $e) {
            info("SMS sending failed: {$e->getMessage()}");
            return false;
        }
    }
}
if (!function_exists('getCategories')) {
    function getCategories(): array|Collection
    {
        return CourseCategoryHelper::getTrendingCategoriesWithCounts();
    }
}
if (!function_exists('getFeaturedCourse')) {
    function getFeaturedCourse(): object|null
    {
        return FeaturedCourseSection::first();
    }
}
if (!function_exists('getFeaturedBlogs')) {
    function getFeaturedBlogs(int $total = 4): object|null
    {
        return Blog::with(['translation', 'author'])
            ->whereHas('category', function ($q) {
                $q->where('status', 1);
            })
            ->where(['show_homepage' => 1, 'status' => 1])->orderBy('created_at', 'desc')->limit($total)->get();
    }
}
if (!function_exists('getSelectedInstructors')) {
    function getSelectedInstructors(): object|null
    {
        $featuredInstructorSection = FeaturedInstructor::first();
        $instructorIds = json_decode($featuredInstructorSection->instructor_ids ?? '[]');

        $selectedInstructors = User::whereIn('id', $instructorIds)
            ->with([
                'courses' => function ($query) {
                    $query->withCount([
                        'reviews as avg_rating' => function ($query) {
                            $query->select(DB::raw('coalesce(avg(rating),0)'));
                        }
                    ]);
                }
            ])
            ->get();

        return $selectedInstructors;
    }
}
if (!function_exists('getTestimonials')) {
    function getTestimonials(int $total = 4): object|null
    {
        return Testimonial::active()->orderBy('created_at', 'desc')->limit($total)->get();
    }
}
if (!function_exists('getBrowserName')) {
    function getBrowserName($userAgent)
    {
        $userAgent = strtolower($userAgent ?? '');

        // ✅ API Clients
        if (strpos($userAgent, 'postman') !== false)
            return 'Postman';
        if (strpos($userAgent, 'thunder client') !== false)
            return 'Thunder Client';
        if (strpos($userAgent, 'insomnia') !== false)
            return 'Insomnia';
        if (strpos($userAgent, 'curl') !== false)
            return 'cURL';
        if (strpos($userAgent, 'httpclient') !== false)
            return 'HTTP Client';
        if (strpos($userAgent, 'okhttp') !== false)
            return 'OkHttp (Android)';
        if (strpos($userAgent, 'axios') !== false)
            return 'Axios';
        if (strpos($userAgent, 'python-requests') !== false)
            return 'Python Requests';
        if (strpos($userAgent, 'java') !== false)
            return 'Java Client';

        // ✅ Browsers
        if (strpos($userAgent, 'edg') !== false)
            return 'Edge';
        if (strpos($userAgent, 'opr') !== false || strpos($userAgent, 'opera') !== false)
            return 'Opera';
        if (strpos($userAgent, 'chrome') !== false && strpos($userAgent, 'chromium') === false)
            return 'Chrome';
        if (strpos($userAgent, 'firefox') !== false)
            return 'Firefox';
        if (strpos($userAgent, 'safari') !== false && strpos($userAgent, 'chrome') === false)
            return 'Safari';
        if (strpos($userAgent, 'msie') !== false || strpos($userAgent, 'trident') !== false)
            return 'Internet Explorer';
        if (strpos($userAgent, 'chromium') !== false)
            return 'Chromium';

        // ✅ Mobile Apps or Others
        if (strpos($userAgent, 'android') !== false)
            return 'Android WebView';
        if (strpos($userAgent, 'iphone') !== false)
            return 'iPhone Safari';
        if (strpos($userAgent, 'ipad') !== false)
            return 'iPad Safari';
        if (strpos($userAgent, 'flutter') !== false)
            return 'Flutter App';
        if (strpos($userAgent, 'reactnative') !== false)
            return 'React Native App';

        return 'Unknown';
    }
}
if (!function_exists('getDeviceType')) {
    function getDeviceType($userAgent)
    {
        $userAgent = strtolower($userAgent ?? '');

        if (preg_match('/mobile|iphone|android|blackberry|phone/', $userAgent)) {
            return 'Mobile';
        }

        if (preg_match('/postman|thunder|curl|insomnia|axios|okhttp/', $userAgent)) {
            return 'API Client';
        }

        return 'Desktop';
    }
}

