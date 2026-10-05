<?php

namespace Modules\Refund\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DefaultMailJob;
use App\Mail\DefaultMail;
use App\Models\User;
use App\Traits\MailSenderTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\GlobalSetting\app\Models\EmailTemplate;
use Modules\Order\app\Models\Enrollment;
use Modules\Refund\app\Models\RefundRequest;

class RefundController extends Controller {
    use MailSenderTrait;
    public function index() {
        checkAdminHasPermissionAndThrowException('refund');
        $refund_requests = RefundRequest::with('user', 'order')->latest()->get();

        $title = __('Refund History');

        return view('refund::admin.index', ['refund_requests' => $refund_requests, 'title' => $title]);
    }

    public function pending_refund_request() {
        checkAdminHasPermissionAndThrowException('refund');
        $refund_requests = RefundRequest::with('user', 'order')->where('status', 'pending')->latest()->get();

        $title = __('Pending Refund');

        return view('refund::admin.index', ['refund_requests' => $refund_requests, 'title' => $title]);
    }

    public function rejected_refund_request() {
        checkAdminHasPermissionAndThrowException('refund');
        checkAdminHasPermissionAndThrowException('refund');
        $refund_requests = RefundRequest::with('user', 'order')->where('status', 'rejected')->latest()->get();

        $title = __('Rejected Refund');

        return view('refund::admin.index', ['refund_requests' => $refund_requests, 'title' => $title]);
    }

    public function complete_refund_request() {
        $refund_requests = RefundRequest::with('user', 'order')->where('status', 'success')->latest()->get();

        $title = __('Complete Refund');

        return view('refund::admin.index', ['refund_requests' => $refund_requests, 'title' => $title]);
    }

    public function show($id) {
        checkAdminHasPermissionAndThrowException('refund');
        $refund_request = RefundRequest::with('user', 'order')->findOrFail($id);

        return view('refund::admin.show', ['refund_request' => $refund_request]);
    }

    public function destroy($id) {
        checkAdminHasPermissionAndThrowException('refund');
        $refund_request = RefundRequest::findOrFail($id);
        $refund_request->delete();

        $notification = __('Payment approved successfully');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->route('admin.refund-request')->with($notification);
    }
    public function approved_refund_request(Request $request, $id) {
        checkAdminHasPermissionAndThrowException('refund');

        $request->validate([
            'refund_amount' => 'required|numeric',
        ], [
            'refund_amount.required' => __('Amount is required'),
            'refund_amount.numeric'  => __('Amount should be numeric'),
        ]);

        DB::beginTransaction();

        try {
            $refund_request = RefundRequest::findOrFail($id);
            $refund_request->refund_amount = $request->refund_amount;
            $refund_request->status = 'success';
            $refund_request->save();

            foreach ($refund_request?->order?->orderItems as $item) {
                // delete enrollment
                $enrollment = Enrollment::where('user_id', $refund_request?->order?->buyer_id)
                    ->where('course_id', $item->course_id)
                    ->first();

                if ($enrollment) {
                    $enrollment->delete();
                }
            }

            // Update order status
            $refund_request?->order->update([
                'status'         => 'declined',
                'payment_status' => 'refunded',
            ]);

            // Update all instructor earnings holds
            $refund_request?->order?->instructorEarningsHolds()->update(['status' => 'refunded']);

            DB::commit(); // ✅ only commit when everything is fine

            // Notify user
            $user = User::findOrFail($refund_request->user_id);

            $template = EmailTemplate::where('name', 'approved_refund')->firstOrFail();
            $mailData['subject'] = $template->subject;

            $message = str_replace('{{user_name}}', $user->name, $template->message);
            $message = str_replace('{{refund_amount}}', $refund_request->refund_amount, $message);

            $this->handleMailSending($user->email, $mailData, $message);

            $notification = ['messege' => __('Refund approved successfully'), 'alert-type' => 'success'];

            return redirect()->back()->with($notification);

        } catch (Exception $e) {
            DB::rollBack();
            info($e->getMessage());
            return redirect()->back()->with(['messege' => __('Something went wrong'), 'alert-type' => 'error']);
        }
    }

    public function reject_refund_request(Request $request, $id) {
        $refund_request = RefundRequest::findOrFail($id);
        $refund_request->status = 'rejected';
        $refund_request->save();

        $user = User::findOrFail($refund_request->user_id);
        // Get email template
        $template = EmailTemplate::where('name', 'rejected_refund')->firstOrFail();
        $mailData['subject'] = $template->subject;
        // Prepare email content
        $message = str_replace('{{user_name}}', $user->name, $template->message);
        $message = str_replace('{{invoice_id}}', $refund_request->invoice_id, $message);
        $this->handleMailSending($user->email, $mailData, $message);

        $notification = __('Refund rejected successfully');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);

    }
    private function handleMailSending($email, $mailData, $message): void {
        try {
            self::setMailConfig();
            if (self::isQueable()) {
                DefaultMailJob::dispatch($email, $mailData, $message);
            } else {
                Mail::to($email)->send(new DefaultMail($mailData, $message));
            }
        } catch (Exception $e) {
            info($e->getMessage());
        }
    }
}
