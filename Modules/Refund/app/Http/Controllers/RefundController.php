<?php

namespace Modules\Refund\app\Http\Controllers;

use Exception;
use App\Mail\DefaultMail;
use App\Jobs\DefaultMailJob;
use Illuminate\Http\Request;
use App\Models\CourseProgress;
use App\Traits\MailSenderTrait;
use App\Models\CourseChapterItem;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Modules\Refund\app\Models\RefundRequest;
use Modules\GlobalSetting\app\Models\EmailTemplate;
use Modules\PaymentWithdraw\app\Models\WithdrawMethod;

class RefundController extends Controller {
    use MailSenderTrait;
    public function index() {
        $auth_user = userAuth();
        $refund_requests = RefundRequest::where('user_id', $auth_user->id)->latest()->get();

        return view('refund::index', ['refund_requests' => $refund_requests]);
    }
    public function create($invoice_id) {
        if (cache()->get('setting')?->is_refundable != 'active') {
            return back()->with(['messege' => __('You cannot send a refund request for this order.'), 'alert-type' => 'error']);
        }
        $order = userAuth()->orders()
            ->where(['invoice_id' => $invoice_id, 'payment_status' => 'paid'])
            ->whereHas('instructorEarningsHolds', function ($q) {
                $q->where('status', 'pending');
            })->firstOrFail();

        $averagePercent = $this->checkOrderCourseAveragePercentage($order->orderItems);
        if ($averagePercent >= 30) {
            return back()->with(['messege' => __('Order cannot be refunded because the average course completion exceeds') . ' '."30%", 'alert-type' => 'error']);
        }

        $withdrawMethods = WithdrawMethod::where('status', 'active')->get();

        return view('refund::create', ['order' => $order,'withdrawMethods' => $withdrawMethods]);
    }

    public function store(Request $request, $invoice_id) {
        $request->validate([
            'reasone'             => 'required',
            'payment_method'             => 'required',
            'account_information' => 'required',
        ], [
            'reasone.required'             => __('Reasone is required'),
            'payment_method.required'             => __('Method is required'),
            'account_information.required' => __('Account info is required'),
        ]);

        $auth_user = userAuth();
        $order = $auth_user->orders()
            ->where(['invoice_id' => $invoice_id, 'payment_status' => 'paid'])
            ->whereHas('instructorEarningsHolds', function ($q) {
                $q->where('status', 'pending');
            })->firstOrFail();

        // Check if the order payment status allows a refund request
        if ($order->payment_status !== 'paid' && cache()->get('setting')?->is_refundable != 'active') {
            return back()->with(['messege' => __('You cannot send a refund request for this order.'), 'alert-type' => 'error']);
        }
        $averagePercent = $this->checkOrderCourseAveragePercentage($order->orderItems);
        if ($averagePercent >= 30) {
            return back()->with(['messege' => __('Order cannot be refunded because the average course completion exceeds') . ' '."30%", 'alert-type' => 'error']);
        }
        if (RefundRequest::where(['user_id' => $auth_user->id, 'invoice_id' => $order->invoice_id])->exists()) {
            return back()->with(['messege' => __('A refund request has already been sent for this order.'), 'alert-type' => 'error']);
        }

        // Create a new refund request
        RefundRequest::create([
            'user_id'             => $auth_user->id,
            'invoice_id'          => $order->invoice_id,
            'reasone'             => $request->reasone,
            'method'             => $request->payment_method,
            'account_information' => $request->account_information,
        ]);

        $this->handleMailSending($auth_user->name);

        return to_route('refund-request.index')->with(['messege' => __('Refund request send successully'), 'alert-type' => 'success']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id) {
        $request = RefundRequest::where(['id' => $id, 'user_id' => userAuth()->id, 'status' => 'pending'])->first();

        if ($request) {
            $request->delete();
            return response(['status' => 'success', 'message' => __('Deleted Successfully')]);
        }
        return response(['status' => 'success', 'message' => __('Request not found')]);
    }

    private function handleMailSending($name): void {
        try {
            $email = cache()->get('setting')?->contact_message_receiver_mail;
            self::setMailConfig();

            // Get email template
            $template = EmailTemplate::where('name', 'new_refund')->firstOrFail();
            $mailData['subject'] = $template->subject;

            // Prepare email content
            $message = str_replace('{{user_name}}', $name, $template->message);

            if (self::isQueable()) {
                DefaultMailJob::dispatch($email, $mailData, $message);
            } else {
                Mail::to($email)->send(new DefaultMail($mailData, $message));
            }
        } catch (Exception $e) {
            info($e->getMessage());
        }
    }

    private function checkOrderCourseAveragePercentage($orderItems):float|int {
        $courseCompletionPercentages = [];

        foreach ($orderItems as $item) {
            $courseLectureCount = CourseChapterItem::whereHas('chapter', function ($q) use ($item) {
                $q->where('course_id', $item->course_id);
            })->count();

            $courseLectureCompletedByUser = CourseProgress::where('user_id', userAuth()->id)
                ->where('course_id', $item->course_id)->count();

            $courseCompletedPercent = $courseLectureCount > 0
            ? ($courseLectureCompletedByUser / $courseLectureCount) * 100
            : 0;

            $courseCompletionPercentages[$item->course_id] = round($courseCompletedPercent, 2);
        }

        $averagePercent = count($courseCompletionPercentages) > 0
        ? array_sum($courseCompletionPercentages) / count($courseCompletionPercentages)
        : 0;

        return $averagePercent;
    }
}
