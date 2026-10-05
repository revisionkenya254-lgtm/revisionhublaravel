<?php

namespace Modules\Assignment\app\Services;

use Modules\Assignment\app\Models\Assignment;
use Modules\Assignment\app\Models\AssignmentSubmission;
use Modules\Assignment\app\Models\AssignmentFeedback;
use App\Models\CourseChapterItem;
use Illuminate\Support\Facades\Storage;

class AssignmentService
{
    /**
     * Store a newly created assignment from Course Content builder.
     */
    public function storeFromChapterItem(array $data, CourseChapterItem $chapterItem)
    {
        return Assignment::create([
            'course_id' => $data['course_id'],
            'chapter_id' => $data['chapter_id'],
            'chapter_item_id' => $chapterItem->id,
            'instructor_id' => $chapterItem->instructor_id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'instructions' => $data['instructions'] ?? null,
            'submission_type' => $data['submission_type'] ?? 'file',
            'due_date' => $data['due_date'] ?? null,
            'late_submission_allowed' => isset($data['late_submission_allowed']) ? true : false,
            'late_penalty' => $data['late_penalty'] ?? null,
            'max_marks' => $data['max_marks'] ?? 100,
            'status' => 'published',
        ]);
    }

    /**
     * Update an assignment from Course Content builder.
     */
    public function updateFromChapterItem(array $data, CourseChapterItem $chapterItem)
    {
        /** @var \Modules\Assignment\app\Models\Assignment $assignment */
        $assignment = Assignment::where('chapter_item_id', $chapterItem->id)->firstOrFail();

        $assignment->update([
            'chapter_id' => $data['chapter_id'] ?? $assignment->chapter_id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'instructions' => $data['instructions'] ?? null,
            'submission_type' => $data['submission_type'] ?? 'file',
            'due_date' => $data['due_date'] ?? null,
            'late_submission_allowed' => isset($data['late_submission_allowed']) ? true : false,
            'late_penalty' => $data['late_penalty'] ?? null,
            'max_marks' => $data['max_marks'] ?? 100,
        ]);

        return $assignment;
    }

    /**
     * Handle student submission.
     */
    public function submitAssignment(array $data, int $studentId)
    {
        $assignment = Assignment::findOrFail($data['assignment_id']);
        $user = auth('web')->user();

        if (!$assignment->course || !hasCourseInPurchased($user, $assignment->course)) {
            throw new \Exception(__('You do not have access to this course.'));
        }

        $isLate = false;
        if ($assignment->due_date && now()->gt($assignment->due_date)) {
            $isLate = true;
            $submission = AssignmentSubmission::where('assignment_id', $assignment->id)->where('student_id', auth('web')->id())->first();
            $canResubmit = $submission && $submission->feedback && $submission->feedback->allow_resubmission;
            if (!$assignment->late_submission_allowed && !$canResubmit) {
                throw new \Exception(__('Late submissions are not allowed for this assignment.'));
            }
        }

        // Check if previously submitted
        $submission = AssignmentSubmission::firstOrNew([
            'assignment_id' => $assignment->id,
            'student_id' => $studentId,
        ]);

        if ($submission->exists && in_array($submission->status, ['graded'])) {
            // Cannot resubmit if graded unless allowed
            $feedback = $submission->feedback;
            if ($feedback && !$feedback->allow_resubmission) {
                throw new \Exception(__('Assignment is already graded and resubmission is not allowed.'));
            }
        }

        if ($submission->exists) {
            // Delete old files if they exist
            if ($submission->file_paths) {
                $oldFiles = is_array($submission->file_paths) ? $submission->file_paths : json_decode($submission->file_paths, true);
                if (is_array($oldFiles)) {
                    foreach ($oldFiles as $oldFile) {
                        Storage::disk('local')->delete($oldFile);
                    }
                }
            }

            // Reset allow_resubmission flag and update status
            if ($submission->feedback) {
                $submission->feedback->update(['allow_resubmission' => false]);
            }
            $submission->status = 'resubmission';
        } else {
            $submission->status = $isLate ? 'late_submitted' : 'submitted';
        }

        $submission->file_paths = $data['file_paths'] ?? null;
        $submission->text_submission = $data['text_submission'] ?? null;
        $submission->link_submission = $data['link_submission'] ?? null;
        $submission->is_late = $isLate;

        $submission->save();

        return $submission;
    }

    /**
     * Handle instructor feedback / grading.
     */
    public function gradeSubmission(array $data, int $instructorId)
    {
        $submission = AssignmentSubmission::findOrFail($data['submission_id']);

        // Setup feedback
        $feedback = AssignmentFeedback::updateOrCreate(
            ['submission_id' => $submission->id],
            [
                'instructor_id' => $instructorId,
                'marks' => $data['marks'],
                'written_feedback' => $data['written_feedback'] ?? null,
                'file_feedback' => $data['file_feedback'] ?? null,
                'allow_resubmission' => isset($data['allow_resubmission']) ? true : false,
            ]
        );

        $submission->update(['status' => 'graded']);

        return $feedback;
    }
}
