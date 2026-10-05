<?php

namespace Modules\Course\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseChapter;
use App\Models\CourseChapterItem;
use App\Models\CourseChapterLesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\Storage\CourseMediaStorageService;
use Illuminate\Validation\ValidationException;
use Modules\Assignment\app\Services\AssignmentService;
use Modules\Course\app\Http\Requests\ChapterLessonRequest;
use Throwable;

class CourseContentController extends Controller
{
    public function __construct(private readonly CourseMediaStorageService $courseMediaStorage)
    {
    }

    function chapterStore(Request $request, string $courseId): RedirectResponse
    {
        $request->validate([
            'title' => ['required', 'max:255'],
        ], [
            'title.required' => __('Title is required'),
            'title.max' => __('Title is too long'),
        ]);

        $chapter = new CourseChapter();
        $chapter->title = $request->title;
        $chapter->course_id = $courseId;
        $chapter->instructor_id = Course::find($courseId)->instructor_id;
        $chapter->status = 'active';
        $chapter->order = CourseChapter::where('course_id', $courseId)->max('order') + 1;
        $chapter->save();

        return redirect()->back()->with(['messege' => __('Chapter created successfully'), 'alert-type' => 'success']);
    }

    function chapterEdit(string $chapterId)
    {
        $chapter = CourseChapter::find($chapterId);
        return view('course::course.partials.edit-section-modal', compact('chapter'))->render();
    }

    function chapterUpdate(Request $request, string $chapterId)
    {
        checkAdminHasPermissionAndThrowException('course.management');
        $chapter = CourseChapter::findOrFail($chapterId);
        $chapter->title = $request->title;
        $chapter->save();
        return redirect()->back()->with(['messege' => __('Updated successfully'), 'alert-type' => 'success']);
    }

    function chapterDestroy(string $chapterId)
    {
        checkAdminHasPermissionAndThrowException('course.management');
        $chapter = CourseChapter::findOrFail($chapterId);
        $chapterItems = CourseChapterItem::where('chapter_id', $chapterId)->get();
        $lessonFiles = CourseChapterLesson::whereIn('chapter_item_id', $chapterItems->pluck('id'))->get();
        $quizIds = Quiz::whereIn('chapter_item_id', $chapterItems->pluck('id'))->pluck('id');
        $questionIds = QuizQuestion::whereIn('quiz_id', $quizIds)->pluck('id');

        // delete quizzes, questions, answers and lesson files
        QuizQuestion::whereIn('id', $questionIds)->delete();
        Quiz::whereIn('id', $quizIds)->delete();
        foreach ($lessonFiles->where('storage', 'bunny_stream') as $lesson) {
            $this->courseMediaStorage->deleteLessonVideo($lesson->file_path);
        }
        foreach ($lessonFiles as $lesson) {
            $this->deleteLessonResources($lesson);
        }
        CourseChapterLesson::whereIn('id', $lessonFiles->pluck('id'))->delete();
        foreach ($lessonFiles as $lesson) {
            if (\File::exists(asset($lesson->file_path)))
                \File::delete(asset($lesson->file_path));
        }

        // delete chapter items and chapter
        CourseChapterItem::whereIn('id', $chapterItems->pluck('id'))->delete();
        $chapter->delete();

        return response()->json(['status' => 'success', 'message' => __('Question deleted successfully')]);
    }

    function chapterSorting(string $courseId)
    {
        $chapters = CourseChapter::where('course_id', $courseId)->orderBy('order', 'ASC')->get();
        return view('course::course.partials.chapter-sorting-index', compact('chapters', 'courseId'))->render();
    }

    function chapterSortingStore(Request $request, string $courseId)
    {
        $newOrder = $request->chapter_ids;

        foreach ($newOrder as $key => $value) {
            $chapter = CourseChapter::where('course_id', $courseId)->find($value);
            $chapter->order = $key + 1;
            $chapter->save();
        }

        return redirect()->back()->with(['messege' => __('Updated successfully'), 'alert-type' => 'success']);
    }

    function lessonCreate(Request $request)
    {
        $courseId = $request->courseId;
        $chapterId = $request->chapterId;
        $chapters = CourseChapter::where('course_id', $courseId)->get();
        $type = $request->type;
        if ($request->type == 'lesson') {
            return view('course::course.partials.lesson-create-modal', [
                'courseId' => $courseId,
                'chapterId' => $chapterId,
                'chapters' => $chapters,
                'type' => $type
            ])->render();
            } elseif ($request->type == 'document') {
                return view('course::course.partials.document-create-modal', [
                    'courseId' => $courseId,
                    'chapterId' => $chapterId,
                    'chapters' => $chapters,
                'type' => $type
            ])->render();
        } elseif ($request->type == 'quiz') {
            return view('course::course.partials.quiz-create-modal', [
                'courseId' => $courseId,
                'chapterId' => $chapterId,
                'chapters' => $chapters,
                'type' => $type
            ])->render();
        } elseif ($request->type == 'assignment') {
            return view('course::course.partials.assignment-create-modal', [
                'courseId' => $courseId,
                'chapterId' => $chapterId,
                'chapters' => $chapters,
                'type' => $type
            ])->render();
        }
    }

    function lessonStore(ChapterLessonRequest $request)
    {
        $chapterItem = CourseChapterItem::create([
            'instructor_id' => Course::find(session()->get('course_create'))->instructor_id,
            'chapter_id' => $request->chapter_id,
            'type' => $request->type,
            'order' => CourseChapterItem::whereChapterId($request->chapter_id)->count() + 1,
        ]);

        if ($request->type == 'lesson') {
            $video = null;

            try {
                $video = $this->resolveLessonVideo($request);
                CourseChapterLesson::create([
                    'title' => $request->title,
                    'description' => $request->description,
                    'instructor_id' => $chapterItem->instructor_id,
                    'course_id' => $request->course_id,
                    'chapter_id' => $request->chapter_id,
                    'chapter_item_id' => $chapterItem->id,
                    'file_path' => $video['file_path'],
                    'storage' => $video['storage'],
                    'file_type' => 'video',
                    'volume' => $request->volume,
                    'duration' => $request->duration,
                    'is_free' => $request->is_free,
                ]);
            } catch (Throwable $exception) {
                if (($video['uploaded'] ?? false) === true) {
                    $this->courseMediaStorage->deleteLessonVideo($video['file_path']);
                }
                $chapterItem->delete();

                throw $exception;
            }
            } elseif ($request->type == 'document') {
                $documentPath = $this->documentPath($request);
                CourseChapterLesson::create([
                    'title' => $request->title,
                    'description' => $request->description,
                    'instructor_id' => $chapterItem->instructor_id,
                'course_id' => $request->course_id,
                'chapter_id' => $request->chapter_id,
                'chapter_item_id' => $chapterItem->id,
                'file_path' => $documentPath,
                'file_type' => $request->hasFile('pdf_attachment') ? 'pdf' : $request->file_type,
                'storage' => $request->hasFile('pdf_attachment') ? 'bunny' : 'upload',
            ]);
            Quiz::create([
                'chapter_item_id' => $chapterItem->id,
                'instructor_id' => $chapterItem->instructor_id,
                'chapter_id' => $request->chapter,
                'course_id' => $request->course_id,
                'title' => $request->title,
                'time' => $request->time_limit,
                'attempt' => $request->attempts,
                'pass_mark' => $request->pass_mark,
                'total_mark' => $request->total_mark,
            ]);
        } elseif ($request->type == 'assignment') {
            $assignmentService = app(AssignmentService::class);
            $assignmentService->storeFromChapterItem($request->all(), $chapterItem);
        }

        return response()->json(['status' => 'success', 'message' => __('Lesson created successfully')]);
    }

    function lessonEdit(Request $request)
    {
        $courseId = $request->courseId;
        $chapterItemId = $request->chapterItemId;
        $chapterItem = CourseChapterItem::with(['lesson', 'quiz'])->find($chapterItemId);
        $chapters = CourseChapter::where('course_id', $courseId)->get();
        if ($request->type == 'lesson') {
            return view('course::course.partials.lesson-edit-modal', [
                'chapters' => $chapters,
                'courseId' => $courseId,
                'chapterItem' => $chapterItem
            ])->render();
        } elseif ($request->type == 'document') {
            return view('course::course.partials.document-edit-modal', [
                'chapters' => $chapters,
                'courseId' => $courseId,
                'chapterItem' => $chapterItem
            ])->render();
        } elseif ($request->type == 'quiz') {
            return view('course::course.partials.quiz-edit-modal', [
                'chapters' => $chapters,
                'courseId' => $courseId,
                'chapterItem' => $chapterItem
            ])->render();
        } else {
            return view('course::course.partials.assignment-edit-modal', [
                'chapters' => $chapters,
                'courseId' => $courseId,
                'chapterItem' => $chapterItem
            ])->render();
        }
    }

    function lessonUpdate(ChapterLessonRequest $request)
    {
        checkAdminHasPermissionAndThrowException('course.management');

        $chapterItem = CourseChapterItem::findOrFail($request->chapter_item_id);

        if ($request->type !== 'lesson') {
            $chapterItem->update([
                'chapter_id' => $request->chapter
            ]);
        }

        if ($request->type == 'lesson') {
            $courseChapterLesson = CourseChapterLesson::where('chapter_item_id', $chapterItem->id)->first();
            $oldStorage = $courseChapterLesson->storage;
            $oldFilePath = $courseChapterLesson->file_path;
            $video = null;

            try {
                $video = $this->resolveLessonVideo($request, $courseChapterLesson);
                DB::transaction(function () use ($chapterItem, $courseChapterLesson, $request, $video) {
                    $chapterItem->update([
                        'chapter_id' => $request->chapter,
                    ]);
                    $courseChapterLesson->update([
                        'title' => $request->title,
                        'description' => $request->description,
                        'course_id' => $request->course_id,
                        'chapter_id' => $request->chapter,
                        'chapter_item_id' => $chapterItem->id,
                        'file_path' => $video['file_path'],
                        'storage' => $video['storage'],
                        'file_type' => 'video',
                        'volume' => $request->volume,
                        'duration' => $request->duration,
                        'is_free' => $request->is_free,
                    ]);
                });
            } catch (Throwable $exception) {
                if (($video['uploaded'] ?? false) === true) {
                    $this->courseMediaStorage->deleteLessonVideo($video['file_path']);
                }

                throw $exception;
            }

            if ($oldStorage === 'bunny_stream' && $oldFilePath !== $video['file_path']) {
                $this->courseMediaStorage->deleteLessonVideo($oldFilePath);
            } elseif (in_array($oldStorage, ['wasabi', 'aws'], true) && $oldFilePath !== $video['file_path']) {
                $disk = Storage::disk($oldStorage);
                $disk->exists($oldFilePath) && $disk->delete($oldFilePath);
            }
        } elseif ($request->type == 'document') {
            $courseChapterLesson = CourseChapterLesson::where('chapter_item_id', $chapterItem->id)->first();
            if ($request->hasFile('pdf_attachment') && $courseChapterLesson->storage === 'bunny') {
                $this->courseMediaStorage->deleteDocument($courseChapterLesson->file_path);
            }
            $documentPath = $this->documentPath($request, $courseChapterLesson->file_path);
            $courseChapterLesson->update([
                'title' => $request->title,
                'description' => $request->description,
                'course_id' => $chapterItem->course_id,
                'chapter_id' => $chapterItem->chapter_id,
                'chapter_item_id' => $chapterItem->id,
                'file_path' => $documentPath,
                'file_type' => $request->hasFile('pdf_attachment') ? 'pdf' : $request->file_type,
                'storage' => $request->hasFile('pdf_attachment') ? 'bunny' : $courseChapterLesson->storage,
            ]);
        } elseif ($request->type == 'quiz') {
            $quiz = Quiz::where('chapter_item_id', $chapterItem->id)->first();
            $quiz->update([
                'chapter_item_id' => $chapterItem->id,
                'title' => $request->title,
                'time' => $request->time_limit,
                'attempt' => $request->attempts,
                'pass_mark' => $request->pass_mark,
                'total_mark' => $request->total_mark,
            ]);
        } else {
            $assignmentService = app(AssignmentService::class);
            $assignmentService->updateFromChapterItem($request->all(), $chapterItem);
        }

        return response()->json(['status' => 'success', 'message' => __('Lesson updated successfully')]);
    }

    function sortLessons(Request $request, string $chapterId)
    {
        $newOrder = $request->orderIds;
        foreach ($newOrder as $key => $itemId) {
            $chapterItem = CourseChapterItem::where(['chapter_id' => $chapterId, 'id' => $itemId])->first();
            $chapterItem->order = $key + 1;
            $chapterItem->save();
        }

        return response()->json(['status' => 'success', 'message' => __('Lesson sorted successfully')]);
    }

    function chapterLessonDestroy(string $chapterItemId)
    {
        checkAdminHasPermissionAndThrowException('course.management');
        $chapterItem = CourseChapterItem::findOrFail($chapterItemId);

        if ($chapterItem->type == 'quiz') {
            $quiz = $chapterItem->quiz;
            $question = $quiz->questions;
            foreach ($question as $key => $question) {
                $question->answers()->delete();
                $question->delete();
            }
            $quiz->delete();
            $chapterItem->delete();
        } elseif ($chapterItem->type == 'assignment') {
            if ($chapterItem->assignment) {
                $chapterItem->assignment->delete();
            }
            $chapterItem->delete();
        } else {
            if ($chapterItem->lesson->storage === 'bunny_stream') {
                $this->courseMediaStorage->deleteLessonVideo($chapterItem->lesson->file_path);
            } elseif ($chapterItem->lesson->storage === 'bunny') {
                $this->courseMediaStorage->deleteDocument($chapterItem->lesson->file_path);
            } elseif (in_array($chapterItem->lesson->storage, ['wasabi', 'aws'])) {
                $disk = Storage::disk($chapterItem->lesson->storage);
                $filePath = $chapterItem->lesson->file_path;
                $disk->exists($filePath) && $disk->delete($filePath);
            }
            // delete chapter item lesson if file exists
            if (\File::exists(asset($chapterItem->lesson->file_path)))
                \File::delete(asset($chapterItem->lesson->file_path));
            $this->deleteLessonResources($chapterItem->lesson);
            // delete lesson row
            $chapterItem->lesson()->delete();
            $chapterItem->delete();
        }

        return response()->json(['status' => 'success', 'message' => __('Lesson deleted successfully')]);
    }

    private function deleteLessonResources(CourseChapterLesson $lesson): void
    {
        foreach ($lesson->resources as $resource) {
            try {
                $this->courseMediaStorage->deleteDocument($resource->file_path);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    private function resolveLessonVideo(ChapterLessonRequest $request, ?CourseChapterLesson $lesson = null): array
    {
        if ($request->source === 'youtube') {
            return [
                'storage' => 'youtube',
                'file_path' => trim((string) $request->link_path),
                'uploaded' => false,
            ];
        }

        if (! $request->hasFile('video_file')) {
            if ($lesson?->storage === 'bunny_stream' && filled($lesson->file_path)) {
                return [
                    'storage' => 'bunny_stream',
                    'file_path' => $lesson->file_path,
                    'uploaded' => false,
                ];
            }

            throw ValidationException::withMessages([
                'video_file' => __('A Bunny Stream video file is required.'),
            ]);
        }

        try {
            $video = $this->courseMediaStorage->storeLessonVideo(
                $request->file('video_file'),
                (string) $request->title,
                'admin',
                (int) auth()->id(),
                (int) $request->course_id,
                $lesson?->id
            );
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'video_file' => __('The video could not be uploaded to Bunny Stream. Please try again.'),
            ]);
        }

        return [
            'storage' => $video['storage'],
            'file_path' => $video['video_id'],
            'uploaded' => true,
        ];
    }

    function createQuizQuestion(string $quizId)
    {
        return view('course::course.partials.quiz-question-create-modal', ['quizId' => $quizId])->render();
    }

    function storeQuizQuestion(Request $request, string $quizId)
    {
        $request->validate([
            'title' => ['required', 'max:255'],
            'answers.*' => ['required', 'max:255'],
            'grade' => ['required', 'numeric', 'min:0']
        ], [
            'title.required' => __('Question title is required'),
            'title.max' => __('Question title should not be more than 255 characters'),
            'answers.*.required' => __('At least one answer is required'),
            'answers.*.max' => __('Answer should not be more than 255 characters'),
            'grade.required' => __('Grade is required'),
            'grade.numeric' => __('Grade should be a number'),
            'grade.min' => __('Grade should be greater than or equal to 0'),
        ]);

        $question = QuizQuestion::create([
            'quiz_id' => $quizId,
            'title' => $request->title,
            'grade' => $request->grade
        ]);

        foreach ($request->answers as $key => $answer) {
            $question->answers()->create([
                'title' => $answer,
                'correct' => isset($request->correct[$key]) ? 1 : 0,
                'question_id' => $question->id
            ]);
        }

        return response()->json(['status' => 'success', 'message' => __('Question created successfully')]);
    }

    function editQuizQuestion(string $questionId)
    {
        $question = QuizQuestion::findOrFail($questionId);
        return view('course::course.partials.quiz-question-edit-modal', ['question' => $question])->render();
    }

    function updateQuizQuestion(Request $request, string $questionId)
    {
        $request->validate([
            'title' => ['required', 'max:255'],
            'answers.*' => ['required', 'max:255'],
            'grade' => ['required', 'numeric', 'min:0']
        ], [
            'title.required' => __('Question title is required'),
            'title.max' => __('Question title should not be more than 255 characters'),
            'answers.*.required' => __('At least one answer is required'),
            'answers.*.max' => __('Answer should not be more than 255 characters'),
            'grade.required' => __('Grade is required'),
            'grade.numeric' => __('Grade should be a number'),
            'grade.min' => __('Grade should be greater than or equal to 0'),
        ]);

        $question = QuizQuestion::findOrFail($questionId);
        $question->update([
            'title' => $request->title,
            'grade' => $request->grade
        ]);
        // update or delete answers
        $question->answers()->delete();
        foreach ($request->answers as $key => $answer) {
            $question->answers()->create([
                'title' => $answer,
                'correct' => isset($request->correct[$key]) ? 1 : 0,
                'question_id' => $question->id
            ]);
        }

        return response()->json(['status' => 'success', 'message' => __('Question updated successfully')]);
    }

    function destroyQuizQuestion(string $questionId)
    {
        $question = QuizQuestion::findOrFail($questionId);
        $question->answers()->delete();
        $question->delete();
        return response()->json(['status' => 'success', 'message' => __('Question deleted successfully')]);
    }

    /** Store a directly uploaded PDF in Bunny, or retain the file-manager path. */
    private function documentPath(Request $request, ?string $existingPath = null): string
    {
        if (! $request->hasFile('pdf_attachment')) {
            return $request->upload_path ?: $existingPath;
        }

        $course = Course::findOrFail($request->course_id);
        $isAdmin = auth('admin')->check();
        $actorId = $isAdmin ? auth('admin')->id() : $course->instructor_id;
        $actorRole = $isAdmin ? 'admin' : 'instructor';

        return $this->courseMediaStorage->storeNoteAttachment(
            $request->file('pdf_attachment'),
            $actorRole,
            $actorId,
            $course->id,
        )['url'];
    }
}
