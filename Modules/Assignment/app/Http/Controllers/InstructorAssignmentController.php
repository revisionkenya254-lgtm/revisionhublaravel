<?php

namespace Modules\Assignment\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Assignment\app\Models\Assignment;
use Modules\Assignment\app\Models\AssignmentSubmission;
use Modules\Assignment\app\Services\AssignmentService;
use Modules\Assignment\app\Http\Requests\AssignmentFeedbackRequest;

class InstructorAssignmentController extends Controller
{
    private AssignmentService $assignmentService;

    public function __construct(AssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    private function checkInstructorAccess($assignment)
    {
        if (auth('web')->id() != $assignment->instructor_id) {
            abort(404);
        }
    }

    public function gradeModal(AssignmentSubmission $submission)
    {
        $this->checkInstructorAccess($submission->assignment);
        $assignment = $submission->assignment;
        return view('frontend.instructor-dashboard.assignment.partials.grade-modal', compact('assignment', 'submission'))->render();
    }

    public function textModal(AssignmentSubmission $submission)
    {
        $this->checkInstructorAccess($submission->assignment);
        return view('frontend.instructor-dashboard.assignment.partials.text-modal', compact('submission'))->render();
    }

    public function submissions(Assignment $assignment)
    {
        $this->checkInstructorAccess($assignment);

        $submissions = $assignment->submissions()->with('student')->paginate(20);
        return view('frontend.instructor-dashboard.assignment.submissions', compact('assignment', 'submissions'));
    }

    public function gradeSubmission(AssignmentFeedbackRequest $request)
    {
        try {
            $submission = AssignmentSubmission::findOrFail($request->submission_id);
            $this->checkInstructorAccess($submission->assignment);

            $instructorId = auth('web')->user()->id ?? auth('admin')->user()->id;
            $this->assignmentService->gradeSubmission($request->validated(), $instructorId);
            return response()->json(['status' => 'success', 'message' => __('Assignment graded successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function destroy(AssignmentSubmission $submission)
    {
        try {
            $this->checkInstructorAccess($submission->assignment);

            if ($submission->file_paths) {
                $files = is_string($submission->file_paths) ? json_decode($submission->file_paths) : $submission->file_paths;
                if (is_array($files)) {
                    foreach ($files as $file) {
                        \Illuminate\Support\Facades\Storage::disk('local')->delete($file);
                    }
                }
            }

            if ($submission->feedback) {
                $submission->feedback->delete();
            }

            $submission->delete();

            return response()->json([
                'status' => 'success',
                'message' => __('Submission deleted successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Failed to delete submission')
            ], 500);
        }
    }
}
