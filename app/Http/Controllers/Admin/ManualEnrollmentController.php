<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ManualEnrollmentRequest;
use App\Services\Admin\ManualEnrollmentService;
use App\Traits\RedirectHelperTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManualEnrollmentController extends Controller
{
    use RedirectHelperTrait;

    public function __construct(protected ManualEnrollmentService $service)
    {
    }

    public function index()
    {
        checkAdminHasPermissionAndThrowException('enrollment.manual.view');
        $enrollments = $this->service->getEnrollments();
        $paymentService = app(\Modules\BasicPayment\app\Services\PaymentMethodService::class);
        $activeGateways = $paymentService->getActiveGatewaysWithDetails();

        $students = $this->service->getStudents();
        $courses = $this->service->getCourses();
        return view('admin.manual-enrollment.index', compact('enrollments', 'activeGateways', 'students', 'courses'));
    }

    public function store(ManualEnrollmentRequest $request)
    {
        checkAdminHasPermissionAndThrowException('enrollment.manual.store');
        try {
            $this->service->store($request);
            return redirect()->route('admin.manual-enrollment.index')
                ->with(['messege' => __('Student enrolled successfully.'), 'alert-type' => 'success']);
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()
                ->with(['messege' => $e->getMessage(), 'alert-type' => 'error']);
        }
    }

    public function destroy(string $id)
    {
        checkAdminHasPermissionAndThrowException('enrollment.manual.delete');
        try {
            $this->service->delete($id);
            return redirect()->route('admin.manual-enrollment.index')
                ->with(['messege' => __('Enrollment removed successfully.'), 'alert-type' => 'success']);
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.manual-enrollment.index')
                ->with(['messege' => $e->getMessage(), 'alert-type' => 'error']);
        }
    }

    /**
     * Return course IDs a student is already enrolled in (JSON).
     */
    public function studentEnrolledCourses(Request $request): JsonResponse
    {
        checkAdminHasPermissionAndThrowException('enrollment.manual.view');
        $enrolledIds = $this->service->getStudentEnrolledCourseIds($request->user_id ?? '');
        return response()->json(['enrolled_course_ids' => $enrolledIds]);
    }
}
