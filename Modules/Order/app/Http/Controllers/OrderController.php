<?php

namespace Modules\Order\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\BasicPayment\app\Services\PaymentMethodService;
use Modules\Order\app\Models\Enrollment;
use Modules\Order\app\Models\Order;
use Modules\Order\app\Traits\GiftOrderTraits;
use Modules\Refund\app\Models\InstructorEarningsHold;

class OrderController extends Controller {
    use GiftOrderTraits;
    public function index(Request $request) {
        checkAdminHasPermissionAndThrowException('order.management');

        $query = Order::query();
        $query->when($request->keyword, fn($q) => $q->where('invoice_id', 'like', "%{$request->keyword}%"));
        $query->when($request->order_status, fn($q) => $q->where('status', $request->order_status));
        $query->when($request->payment_status, fn($q) => $q->where('payment_status', $request->payment_status));
        $orderBy = $request->order_by == 1 ? 'asc' : 'desc';
        $orders = $request->get('par-page') == 'all' ?
        $query->orderBy('id', $orderBy)->get() :
        $query->orderBy('id', $orderBy)->paginate($request->get('par-page') ?? null)->withQueryString();

        $title = __('Order History');

        return view('order::index', ['orders' => $orders, 'title' => $title]);
    }

    public function pending_order() {

        checkAdminHasPermissionAndThrowException('order.management');

        $orders = Order::with('user')->where('payment_status', 'pending')->latest()->paginate();
        $title = __('Pending Order');

        return view('order::pending-orders', ['orders' => $orders, 'title' => $title]);
    }

    public function show(string $id) {
        checkAdminHasPermissionAndThrowException('order.management');

        $order = Order::where('id', $id)->firstOrFail();

        return view('order::show', ['order' => $order]);
    }

    public function updateOrder(Request $request, $id) {
        checkAdminHasPermissionAndThrowException('order.management');

        $order = Order::with('orderItems.course.instructor')->findOrFail($id);

        $order->status = $request->order_status;
        if (!in_array($order->payment_status, ['paid', 'refunded'])) {
            $order->payment_status = $request->payment_status;
        }
        $order->save();

        if ($request->payment_status == 'paid') {
            // send sms
            if (cache()->get('setting')?->sms_payment_status) {
                $message = SMSTemplate('payment_status', [
                    'order_id'       => $order->invoice_id,
                    'payment_status' => $order->payment_status,
                ]);
                sendSMS($order?->user->phone, $message);
            }

            if ($order->isGiftOrder()) {
                $this->giftOrderDetailsUpdate($order);
            } else {
                foreach ($order->orderItems as $item) {
                    if ($item->item_type === 'product' || !$item->course_id) {
                        continue;
                    }

                    Enrollment::create([
                        'order_id'   => $order->id,
                        'user_id'    => $order->buyer_id,
                        'course_id'  => $item->course_id,
                        'has_access' => 1,
                    ]);

                    // insert instructor commission to his wallet
                    $commissionAmount = $item->price * ($order->commission_rate / 100);
                    $amountAfterCommission = $item->price - $commissionAmount;
                    $instructor = Course::find($item->course_id)->instructor;
                    InstructorEarningsHold::create(['order_id' => $order->id, 'instructor_id' => $instructor->id, 'amount' => $amountAfterCommission]);
                }
            }
        } else {
            if ($order->payment_status != 'paid') {
                foreach ($order->orderItems as $item) {
                    if ($item->item_type === 'product' || !$item->course_id) {
                        continue;
                    }
                    // delete enrollment
                    $enrollment = Enrollment::where('user_id', $order->buyer_id)->where('course_id', $item->course_id)->first();
                    if ($enrollment) {
                        $enrollment->delete();
                    }
                }
                foreach ($order?->instructorEarningsHolds as $earningHold) {
                    if ($earningHold?->status == 'released') {
                        $earningHold->instructor?->decrement('wallet_balance', $earningHold?->amount);
                    }
                }
            }
        }

        $notification = __('order status updated successfully');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    function printInvoice(Request $request, $id) {
        $order = Order::where('id', $id)->firstOrFail();
        return view('order::invoice', ['order' => $order]);
    }

    public function downloadInvoice($id)
    {
        $order = Order::where('id', $id)
            ->where('buyer_id', auth()->id())
            ->firstOrFail();

        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('order::invoice', ['order' => $order])->render());
        $pdf->setPaper('A4');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice_' . $order->invoice_id . '.pdf"',
        ]);
    }

    public function destroy($id) {
        checkAdminHasPermissionAndThrowException('order.management');

        // delete order and order items order enrollments and instructor commission
        $order = Order::findOrFail($id);
        if ($order?->payment_status == 'paid') {
            if ($order->isGiftOrder()) {
                $details = $order?->order_details;
                $enrollment = Enrollment::where('user_id', $details?->user_id)->where('course_id', $details?->course_id)->first();
                if ($enrollment) {
                    $enrollment->delete();
                }
            }
            foreach ($order?->orderItems as $item) {
                if ($item?->item_type === 'product' || !$item?->course_id) {
                    continue;
                }
                // delete enrollment
                $enrollment = Enrollment::where('user_id', $order?->buyer_id)->where('course_id', $item?->course_id)->first();
                if ($enrollment) {
                    $enrollment->delete();
                }
            }
            foreach ($order?->instructorEarningsHolds as $earningHold) {
                if ($earningHold?->status == 'released') {
                    $earningHold->instructor?->decrement('wallet_balance', $earningHold?->amount);
                }
            }

            $order->instructorEarningsHolds()->delete();
        }
        $order->refundRequest()->delete();
        $order->orderItems()->delete();
        if ($order?->payment_method == PaymentMethodService::OFFLINE_PAYMENT && File::exists(public_path($order?->payment_details))) {
            unlink(public_path($order?->payment_details));
        }
        $order->delete();

        $notification = __('Order deleted successfully');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->route('admin.orders')->with($notification);
    }

    public function revokeAccess(Request $request, $id)
    {
        checkAdminHasPermissionAndThrowException('order.management');

        $order = Order::with(['orderItems', 'user', 'instructorEarningsHolds', 'refundRequest'])->findOrFail($id);

        if ($order->isAiCreditsOrder()) {
            return redirect()->back()->with([
                'messege' => __('AI credit orders cannot be revoked from the admin panel yet.'),
                'alert-type' => 'error',
            ]);
        }

        DB::transaction(function () use ($order) {
            $this->removeOrderAccess($order);

            $order->instructorEarningsHolds()->update(['status' => 'refunded']);
            $order->refundRequest()->delete();

            $details = json_decode((string) $order->payment_details, true) ?: [];
            $details['admin_revoked_access'] = true;
            $details['admin_revoked_at'] = now()->toIso8601String();
            $details['admin_revoked_by'] = auth('admin')->id();

            $order->update([
                'status' => 'declined',
                'payment_status' => 'cancelled',
                'payment_details' => json_encode($details),
            ]);
        });

        $notification = __('Order access revoked successfully');

        return redirect()->back()->with([
            'messege' => $notification,
            'alert-type' => 'success',
        ]);
    }
    public function download_payment_receipt($id) {
        checkAdminHasPermissionAndThrowException('order.management');

        $order = Order::findOrFail($id);
        $path = public_path($order?->payment_details);

        if ($order?->payment_method == PaymentMethodService::OFFLINE_PAYMENT && File::exists($path)) {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $newFileName = 'payment_receipt_' . $order->invoice_id . '.' . $extension;
            return response()->download($path, $newFileName);
        }
        abort(404);
    }
    public function giftVerification($invoice_id, $verification_token) {
        $order = Order::whereInvoiceId($invoice_id)->firstOrFail();
        $details = $order?->order_details;

        abort_if(empty($details->verification_token) || $details->verification_token !== $verification_token, 404);
        try {
            DB::beginTransaction();
            Enrollment::firstOrCreate([
                'user_id'   => userAuth()->id,
                'course_id' => $details->course_id,
            ], [
                'order_id'   => $order->id,
                'has_access' => 1,
            ]);

            $order_details = (array) $details;
            unset($order_details['verification_token']);
            $order->order_details = (object) $order_details;
            $order->save();

            DB::commit();

            $notification = __('Congratulations! You have successfully enrolled in the course.') . '🎉';
            $notification = ['messege' => $notification, 'alert-type' => 'success'];
            return redirect()->route('student.enrolled-courses')->with($notification);
        } catch (Exception $e) {
            DB::rollback();
            info($e->getMessage());
            $notification = __('Invalid token, please try again');
            $notification = ['messege' => $notification, 'alert-type' => 'error'];
            return redirect()->route('home')->with($notification);
        }

    }
    public function reSendGiftClaimMail($invoice_id) {
        checkAdminHasPermissionAndThrowException('order.management');
        $order = Order::whereInvoiceId($invoice_id)->firstOrFail();

        abort_if(empty($order?->order_details?->verification_token), 404);
        try {
            $order->order_details = (object) array_merge((array) $order->order_details, [
                "verification_token" => Str::random(100),
            ]);
            $order->save();

            try {
                $course = Course::find($order?->order_details?->course_id);
                $this->sendingGiftCourseMail([
                    'email'        => $order?->order_details?->recipient_email,
                    'name'         => $order?->order_details?->recipient_name,
                    'sender_name'  => $order?->user?->name,
                    'sender_email' => $order?->user?->email,
                    'course_link'  => route('course.show', $course->slug),
                    'course_name'  => $course->title,
                    'message'      => $order?->order_details?->message,
                    'link'         => route('gift-course-verification', ['invoice_id' => $order?->invoice_id, 'verification_token' => $order?->order_details?->verification_token]),
                ]);
            } catch (Exception $e) {
                info($e->getMessage());
            }
            $notification = __('Mail Send Successfully.');
            $notification = ['messege' => $notification, 'alert-type' => 'success'];
            return redirect()->back()->with($notification);
        } catch (Exception $e) {
            info($e->getMessage());
            $notification = __('Something went wrong');
            $notification = ['messege' => $notification, 'alert-type' => 'error'];
            return redirect()->route('home')->with($notification);
        }

    }

    private function removeOrderAccess(Order $order): void
    {
        if ($order->isSubscriptionOrder()) {
            $order->user?->forceFill([
                'subscription_plan_id' => null,
                'subscription_started_at' => null,
                'subscription_expires_at' => null,
            ])->save();
            return;
        }

        $order->orderItems->each(function ($item) use ($order) {
            if ($item->item_type === 'product' || !$item->course_id) {
                return;
            }

            if ($order->isGiftOrder()) {
                $details = $order->order_details;
                $userId = data_get($details, 'user_id', $order->buyer_id);
                Enrollment::where('user_id', $userId)
                    ->where('course_id', $item->course_id)
                    ->delete();
                return;
            }

            Enrollment::where('user_id', $order->buyer_id)
                ->where('course_id', $item->course_id)
                ->delete();
        });

        foreach ($order->instructorEarningsHolds as $earningHold) {
            if ($earningHold->status === 'released') {
                $earningHold->instructor?->decrement('wallet_balance', $earningHold->amount);
            }
        }
    }
}
