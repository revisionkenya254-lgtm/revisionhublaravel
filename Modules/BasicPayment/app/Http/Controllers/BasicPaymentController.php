<?php

namespace Modules\BasicPayment\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\BasicPayment\app\Models\BasicPayment;
use Modules\Currency\app\Models\MultiCurrency;

class BasicPaymentController extends Controller
{
    public function basicpayment()
    {
        checkAdminHasPermissionAndThrowException('basic.payment.view');

        $basic_payment = (object) BasicPayment::pluck('value', 'key')->toArray();
        $currencies = MultiCurrency::get();

        return view('basicpayment::index', compact('basic_payment', 'currencies'));
    }

    public function update_paypal(Request $request)
    {
        checkAdminHasPermissionAndThrowException('basic.payment.update');

        $request->validate([
            'paypal_client_id' => 'required',
            'paypal_secret_key' => 'required',
            'paypal_charge' => 'required|numeric',
            'paypal_image' => ['nullable', 'image', 'max:2000', 'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/svg+xml'],
        ]);

        BasicPayment::where('key', 'paypal_client_id')->update(['value' => $request->paypal_client_id]);
        BasicPayment::where('key', 'paypal_secret_key')->update(['value' => $request->paypal_secret_key]);
        BasicPayment::where('key', 'paypal_charge')->update(['value' => $request->paypal_charge]);
        BasicPayment::where('key', 'paypal_status')->update(['value' => $request->paypal_status]);
        BasicPayment::where('key', 'paypal_account_mode')->update(['value' => $request->paypal_account_mode]);

        if ($request->file('paypal_image')) {
            $paypal_setting = BasicPayment::where('key', 'paypal_image')->first();
            $file_name = file_upload($request->paypal_image, 'uploads/custom-images/', $paypal_setting?->value);
            $paypal_setting?->update(['value' => $file_name]);
        }

        $this->put_basic_payment_cache();

        return redirect()->back()->with(['messege' => __('Update Successfully'), 'alert-type' => 'success']);
    }

    public function mpesa_stk_push_update(Request $request)
    {
        checkAdminHasPermissionAndThrowException('basic.payment.update');

        $request->validate([
            'mpesa_stk_push_account_mode' => 'required|in:sandbox,production',
            'mpesa_stk_push_status' => 'required|in:active,inactive',
            'mpesa_stk_push_charge' => 'nullable|numeric',
            'mpesa_stk_push_callback_url' => 'nullable|url|max:2048',
            'mpesa_stk_push_image' => ['nullable', 'image', 'max:2000', 'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/svg+xml'],
        ]);

        $settings = [
            'mpesa_stk_push_account_mode' => $request->mpesa_stk_push_account_mode,
            'mpesa_stk_push_status' => $request->mpesa_stk_push_status,
            'mpesa_stk_push_charge' => $request->input('mpesa_stk_push_charge', 0),
            'mpesa_stk_push_callback_url' => $request->input('mpesa_stk_push_callback_url', ''),
        ];

        foreach ($settings as $key => $value) {
            $paymentSetting = BasicPayment::firstOrCreate(['key' => $key], ['value' => '']);
            $paymentSetting->update(['value' => $value]);
        }

        if ($request->file('mpesa_stk_push_image')) {
            $image_setting = BasicPayment::firstOrCreate(['key' => 'mpesa_stk_push_image'], ['value' => 'uploads/website-images/mpesa.webp']);
            $file_name = file_upload($request->mpesa_stk_push_image, 'uploads/custom-images/', $image_setting->value);
            $image_setting->update(['value' => $file_name]);
        }

        $this->put_basic_payment_cache();

        return redirect()->back()->with(['messege' => __('Update Successfully'), 'alert-type' => 'success']);
    }

    private function put_basic_payment_cache(): void
    {
        Cache::put('basic_payment', (object) BasicPayment::pluck('value', 'key')->toArray());
    }
}
