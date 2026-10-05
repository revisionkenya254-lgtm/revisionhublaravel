<?php

namespace Modules\Assignment\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Assignment\app\Http\Requests\AssignmentSubmissionRequest;
use Modules\Assignment\app\Models\Assignment;
use Modules\Assignment\app\Models\AssignmentSubmission;
use Modules\Assignment\app\Services\AssignmentService;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Illuminate\Support\Str;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;

class StudentAssignmentController extends Controller
{
    private AssignmentService $assignmentService;

    public function __construct(AssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    public function show($id)
    {
        $assignment = Assignment::findOrFail($id);

        if (!$assignment->course || !hasCourseInPurchased(auth('web')->user(), $assignment->course)) {
            abort(404);
        }

        $submission = AssignmentSubmission::where('assignment_id', $id)
            ->where('student_id', auth('web')->id())
            ->first();

        return view('frontend.pages.student-dashboard.assignment.show', compact('assignment', 'submission'));
    }

    public function submitAssignment(AssignmentSubmissionRequest $request)
    {
        try {
            $studentId = auth('web')->user()->id;
            $this->assignmentService->submitAssignment($request->validated(), $studentId);
            return response()->json(['status' => 'success', 'message' => __('Assignment submitted successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function uploadChunk(Request $request)
    {
        if ($request->has('assignment_id')) {
            $assignment = Assignment::find($request->assignment_id);
            if ($assignment && $assignment->due_date && now()->isAfter($assignment->due_date) && !$assignment->late_submission_allowed) {
                $submission = AssignmentSubmission::where('assignment_id', $assignment->id)
                    ->where('student_id', auth('web')->id())
                    ->first();
                $canResubmit = $submission && $submission->feedback && $submission->feedback->allow_resubmission;

                if (!$canResubmit) {
                    return response()->json(['status' => 'error', 'message' => __('Upload failed. Deadline passed.')], 403);
                }
            }
        }

        $receiver = new FileReceiver("file", $request, HandlerFactory::classFromRequest($request));

        if ($receiver->isUploaded() === false) {
            throw new UploadMissingFileException();
        }
        $save = $receiver->receive();
        if ($save->isFinished()) {
            $file = $save->getFile();
            $mime = $file->getClientOriginalExtension();
            $student = auth('web')->user();
            $assignment = Assignment::find($request->assignment_id);
            $studentName = Str::slug($student->name ?? 'student');
            $assignmentTitle = Str::slug($assignment->title ?? 'assignment');
            $name = Str::slug($studentName . ' ' . $assignmentTitle, '_');
            $fileName = Str::limit($name, 100, '') . '_' . time() . '.' . strtolower($mime);

            $path = $file->storeAs('assignments', $fileName, 'local');

            return response()->json([
                'status' => true,
                'file_path' => $path
            ], 200);
        }
        $handler = $save->handler();
        return response()->json([
            "done" => $handler->getPercentageDone(),
            'status' => true,
        ]);
    }

    public function downloadSubmissionFile(Request $request)
    {
        $path = $request->query('path');

        if (!$path || !Storage::disk('local')->exists($path)) {
            abort(404, 'File not found');
        }

        return response()->download(storage_path('app/' . $path));
    }
}
