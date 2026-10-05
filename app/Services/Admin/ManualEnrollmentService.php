<?php

namespace App\Services\Admin;

use App\Http\Requests\Admin\ManualEnrollmentRequest;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Order\app\Models\Enrollment;
use Modules\Order\app\Models\Order;
use Modules\Order\app\Models\OrderItem;
use Modules\Refund\app\Models\InstructorEarningsHold;

class ManualEnrollmentService
{

    /**
     * Get paginated list of manual enrollments.
     */
    public function getEnrollments(): LengthAwarePaginator
    {
        return Enrollment::with([
            'user:id,name,email,image',
            'course:id,title,slug,thumbnail',
            'order:id,payment_method,paid_amount,payable_currency,payable_amount',
            'enrolledBy:id,name',
        ])
            ->whereNotNull('enrolled_by')
            ->latest()
            ->paginate(15);
    }
    /**
     * Get students list for manual enrollment
     */
    public function getStudents(): Collection
    {
        return User::select('id', 'name', 'email', 'phone')->student()->verified()->active()->unbanned()->get();
    }
    /**
     * Get courses list for manual enrollment
     */
    public function getCourses(): Collection
    {
        return Course::select('id', 'title', 'slug', 'price', 'discount')->active()->get();
    }

    /**
     * Get course IDs a student is already enrolled in.
     */
    public function getStudentEnrolledCourseIds(string $userId): array
    {
        return Enrollment::where('user_id', $userId)
            ->pluck('course_id')
            ->toArray();
    }

    /**
     * Manually enroll a student into a course.
     */
    public function store(ManualEnrollmentRequest $request): Enrollment
    {
        // Check already enrolled
        $existing = Enrollment::where('user_id', $request->user_id)
            ->where('course_id', $request->course_id)
            ->first();

        if ($existing) {
            throw new \RuntimeException(__('This student is already enrolled in the selected course.'));
        }

        return DB::transaction(function () use ($request) {
            $isFree = $request->type === 'free';
            $paymentMethod = $isFree ? 'Manual Free' : $request->payment_method;
            $amount = $isFree ? 0 : (float) $request->amount;
            $baseAmount = $amount;
            $adminId = Auth::guard('admin')->id();
            $commissionRate = $isFree ? 0 : (Cache::get('setting')?->commission_rate ?? 0);

            $course = Course::findOrFail($request->course_id);

            $currency = allCurrencies()->where('id', $request->currency_id)->first();

            if ($currency?->is_default == 'no') {
                $baseAmount = convert_amount_to_base_currency($amount, $currency->currency_rate);
            }

            $order = Order::create([
                'invoice_id' => Str::random(10),
                'buyer_id' => $request->user_id,
                'payment_method' => $paymentMethod,
                'status' => 'completed',
                'payment_status' => 'paid',
                'payable_amount' => $baseAmount,
                'gateway_charge' => 0,
                'payable_with_charge' => $amount,
                'paid_amount' => $amount,
                'payable_currency' => $currency?->currency_code,
                'conversion_rate' => $currency?->currency_rate,
                'transaction_id' => Str::random(10),
                'commission_rate' => $commissionRate,
                'order_type' => 'course',
                'order_details' => $request->note ?? json_encode(['note' => $request->note]) ?? null,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'price' => $baseAmount,
                'course_id' => $course->id,
                'commission_rate' => $commissionRate,
            ]);

            $enrollment = Enrollment::create([
                'order_id' => $order->id,
                'user_id' => $request->user_id,
                'course_id' => $request->course_id,
                'has_access' => 1,
                'enrolled_by' => $adminId,
                'enrollment_note' => $request->note,
            ]);

            // Create instructor earnings hold (same as payment success flow)
            if (!$isFree && $baseAmount > 0) {
                $instructor = $course->instructor;
                if ($instructor) {
                    $commissionAmount = $baseAmount * ($commissionRate / 100);
                    $amountAfterCommission = $baseAmount - $commissionAmount;
                    InstructorEarningsHold::create([
                        'order_id' => $order->id,
                        'instructor_id' => $instructor->id,
                        'amount' => $amountAfterCommission,
                    ]);
                }
            }

            return $enrollment;
        });
    }

    /**
     * Delete a manual enrollment.
     * Blocks deletion if instructor earnings have already been released.
     * Otherwise, deletes enrollment, order, order items, and earnings hold together.
     */
    public function delete(int|string $id): void
    {
        $enrollment = Enrollment::with(['order'])
            ->whereNotNull('enrolled_by')
            ->findOrFail($id);

        $order = $enrollment->order;

        if ($order) {
            // Check if instructor earnings hold has been released (paid out)
            $hold = InstructorEarningsHold::where('order_id', $order->id)->first();

            if ($hold && $hold->status === 'released') {
                throw new \RuntimeException(
                    __("Cannot remove this enrollment. The instructor's earnings have already been paid out.")
                );
            }
        }

        DB::transaction(function () use ($enrollment, $order) {
            // Delete instructor earnings hold
            if ($order) {
                InstructorEarningsHold::where('order_id', $order->id)->delete();
                OrderItem::where('order_id', $order->id)->delete();
            }

            // Delete enrollment
            $enrollment->delete();

            // Delete order last (FK dependency)
            if ($order) {
                $order->delete();
            }
        });
    }
}
