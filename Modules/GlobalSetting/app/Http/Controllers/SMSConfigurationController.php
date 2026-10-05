<?php

namespace Modules\GlobalSetting\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\GlobalSetting\app\Models\Setting;
use Modules\GlobalSetting\app\Models\SmsTemplate;
use Modules\GlobalSetting\database\seeders\SmsTemplateSeeder;

class SMSConfigurationController extends Controller {
    /**
     * Display a listing of the resource.
     */
    public function index(): View {
        checkAdminHasPermissionAndThrowException('setting.view');
        $templates = SmsTemplate::get();
        return view('globalsetting::sms.index', compact('templates'));
    }
    public function update_sms_config(Request $request) {
        checkAdminHasPermissionAndThrowException('setting.update');
        $request->validate([
            'default_sms_driver'              => 'required|in:twilio',
            'sms_order_completed'             => 'sometimes',
            'sms_payment_status'              => 'sometimes',
            'sms_approved_withdraw'           => 'sometimes',
            'sms_rejected_withdraw'           => 'sometimes',
            'sms_instructor_request_approved' => 'sometimes',
            'sms_instructor_request_rejected' => 'sometimes',
            'sms_qna_reply_mail'              => 'sometimes',
            'sms_live_class_mail'             => 'sometimes',
            'sms_gift_course'                 => 'sometimes',
        ], [
            'default_sms_driver.required' => __('Default Driver is required'),
            'default_sms_driver.in'       => __('Selected Default Driver is invalid'),
            'sms_driver_status.required'  => __('Status is required'),
        ]);
        Setting::where('key', 'default_sms_driver')->update(['value' => $request->default_sms_driver]);

        Setting::where('key', 'sms_order_completed')->update(['value' => $request->sms_order_completed]);
        Setting::where('key', 'sms_payment_status')->update(['value' => $request->sms_payment_status]);
        Setting::where('key', 'sms_approved_withdraw')->update(['value' => $request->sms_approved_withdraw]);
        Setting::where('key', 'sms_rejected_withdraw')->update(['value' => $request->sms_rejected_withdraw]);
        Setting::where('key', 'sms_instructor_request_approved')->update(['value' => $request->sms_instructor_request_approved]);
        Setting::where('key', 'sms_instructor_request_rejected')->update(['value' => $request->sms_instructor_request_rejected]);
        Setting::where('key', 'sms_qna_reply_mail')->update(['value' => $request->sms_qna_reply_mail]);
        Setting::where('key', 'sms_live_class_mail')->update(['value' => $request->sms_live_class_mail]);
        Setting::where('key', 'sms_gift_course')->update(['value' => $request->sms_gift_course]);

        cache()->forget('setting');

        $notification = __('Update Successfully');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }
    public function update_twilio_config(Request $request) {
        checkAdminHasPermissionAndThrowException('setting.update');
        $request->validate([
            'twilio_sid'        => 'required',
            'twilio_token'      => 'required',
            'twilio_from'       => 'required',
            'twilio_phone_code' => 'required',
        ], [
            'twilio_sid.required'        => __('Twilio SID is required'),
            'twilio_token.required'      => __('Auth token is required'),
            'twilio_from.required'       => __('The phone number is required.'),
            'twilio_phone_code.required' => __('The phone code is required.'),
        ]);

        Setting::where('key', 'twilio_sid')->update(['value' => $request->twilio_sid]);
        Setting::where('key', 'twilio_token')->update(['value' => $request->twilio_token]);
        Setting::where('key', 'twilio_from')->update(['value' => $request->twilio_from]);
        Setting::where('key', 'twilio_phone_code')->update(['value' => $request->twilio_phone_code]);

        cache()->forget('setting');

        $notification = __('Update Successfully');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }
    public function edit_template($id) {
        checkAdminHasPermissionAndThrowException('setting.view');

        $template = SmsTemplate::where('id', $id)->firstOrFail();

        $templateName = $template->name;

        $variables = [];

        try {
            $templatesArray = collect(SmsTemplateSeeder::smsTemplates());
            $staticTemplate = $templatesArray->firstWhere('name', $templateName);

            if (!$staticTemplate) {
                throw new Exception("Template '{$templateName}' not found in the static template list.");
            }

            preg_match_all('/{{\s*(.*?)\s*}}/', $staticTemplate['message'], $matches);

            $variables = array_unique($matches[1]);

        } catch (Exception $e) {
            info($e->getMessage());
        }

        return view('globalsetting::sms.template', compact('template', 'variables'));
    }
    public function update_sms_template(Request $request, $id) {
        checkAdminHasPermissionAndThrowException('setting.update');
        $rules = [
            'message' => 'required',
        ];
        $customMessages = [
            'message.required' => __('Message is required'),
        ];

        $request->validate($rules, $customMessages);

        $template = SmsTemplate::find($id);
        if ($template) {
            $template->message = $request->message;
            $template->save();
            $notification = __('Updated Successfully');
            $notification = ['messege' => $notification, 'alert-type' => 'success'];

            return redirect()->route('admin.sms-configuration')->with($notification);
        } else {
            $notification = __('Something went wrong');
            $notification = ['messege' => $notification, 'alert-type' => 'error'];

            return redirect()->back()->with($notification);
        }
    }

}
