<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Order\app\Models\Order;
use Modules\BasicPayment\app\Services\PaymentMethodService;

class StudentOrderController extends Controller
{
    function index() {
        $orders = Order::where('buyer_id', userAuth()->id)->orderBy('id', 'desc')->paginate(30);
        return view('frontend.student-dashboard.order.index', compact('orders'));
    }

    function show(string $id) {
        $order = Order::where('id', $id)->where('buyer_id', userAuth()->id)->firstOrFail();
        $paymentService = app(PaymentMethodService::class);
        $canRetryPayment = $this->canRetryPayment($order, $paymentService);

        return view('frontend.student-dashboard.order.show', compact('order', 'canRetryPayment'));
    }

    function printInvoice( Request $request, $id) {
        $order = Order::where('id', $id)->where('buyer_id', userAuth()->id)->firstOrFail();
       return view('frontend.student-dashboard.order.invoice', compact('order'));
    }

    private function canRetryPayment(Order $order, PaymentMethodService $paymentService): bool
    {
        if ($order->payment_method === 'Free') {
            return false;
        }

        if (in_array($order->payment_status, ['paid', 'refunded'], true)) {
            return false;
        }

        return $paymentService->isActive($order->payment_method);
    }
}
