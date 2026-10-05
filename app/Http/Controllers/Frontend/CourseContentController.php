<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\ChapterLessonRequest;
use App\Models\Course;
use App\Models\CourseChapter;
use App\Models\CourseChapterItem;
use App\Models\CourseChapterLesson;
use App\Models\CourseLiveClass;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\MailSenderService;
use App\Services\Storage\CourseMediaStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Assignment\app\Services\AssignmentService;
use Modules\Order\app\Models\Enrollment;
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
        $chapter->instructor_id = auth('web')->id();
        $chapter->status = 'active';
        $chapter->order = CourseChapter::where('course_id', $courseId)->max('order') + 1;
        $chapter->save();

        return redirect()->back()->with(['messege' => __('Chapter created successfully'), 'alert-type' => 'success']);
    }

    function chapterEdit(string $chapterId)
    {
        $chapter = CourseChapter::find($chapterId);
        return view('frontend.instructor-dashboard.course.partials.edit-section-modal', compact('chapter'))->render();
    }

    function chapterUpdate(Request $request, string $chapterId)
    {
        $chapter = CourseChapter::findOrFail($chapterId);
        abort_if($chapter->instructor_id != auth('web')->user()->id, 403, __('unauthorized access'));
        $chapter->title = $request->title;
        $chapter->save();
        return redirect()->back()->with(['messege' => __('Updated successfully'), 'alert-type' => 'success']);
    }

    function chapterDestroy(string $chapterId)
    {
        $chapter = CourseChapter::findOrFail($chapterId);
        abort_if($chapter->instructor_id != auth('web')->user()->id, 403, __('unauthorized access'));
        $chapterItems = CourseChapterItem::where('chapter_id', $chapterId)
            ->where('instructor_id', auth('web')->user()->id)
            ->get();
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
            if (\File::exists(asset($lesson->file_path))) {
                \File::delete(asset($lesson->file_path));
            }

        }

        // delete chapter items and chapter
        CourseChapterItem::whereIn('id', $chapterItems->pluck('id'))->delete();
        $chapter->delete();

        return response()->json(['status' => 'success', 'message' => __('Question deleted successfully')]);
    }

    function chapterSorting(string $courseId)
    {
        $chapters = CourseChapter::where('course_id', $courseId)->orderBy('order', 'ASC')->get();
        return view('frontend.instructor-dashboard.course.partials.chapter-sorting-index', compact('chapters', 'courseId'))->render();
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
        $chapters = CourseChapter::where('course_id', $courseId)->orderBy('order')->get();
        $type = $request->type;
        if ($request->type == 'lesson') {
            return view('frontend.instructor-dashboard.course.partials.lesson-create-modal', [
                'courseId' => $courseId,
                'chapterId' => $chapterId,
                'chapters' => $chapters,
                'type' => $type,
            ])->render();
        } elseif ($request->type == 'document') {
            return view('frontend.instructor-dashboard.course.partials.document-create-modal', [
                'courseId' => $courseId,
                'chapterId' => $chapterId,
                'chapters' => $chapters,
                'type' => $type,
            ])->render();
        } elseif ($request->type == 'quiz') {
            return view('frontend.instructor-dashboard.course.partials.quiz-create-modal', [
                'courseId' => $courseId,
                'chapterId' => $chapterId,
                'chapters' => $chapters,
                'type' => $type,
            ])->render();
        } elseif ($request->type == 'live') {
            return view('frontend.instructor-dashboard.course.partials.live-create-modal', [
                'courseId' => $courseId,
                'chapterId' => $chapterId,
                'chapters' => $chapters,
                'type' => $type,
            ])->render();
        } elseif ($request->type == 'assignment') {
            return view('frontend.instructor-dashboard.course.partials.assignment-create-modal', [
                'courseId' => $courseId,
                'chapterId' => $chapterId,
                'chapters' => $chapters,
                'type' => $type,
            ])->render();
        }
    }

    function lessonStore(ChapterLessonRequest $request)
    {
        $chapterItem = CourseChapterItem::create([
            'instructor_id' => auth('web')->id(),
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
                    'instructor_id' => auth('web')->id(),
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
            CourseChapterLesson::create([
                'title' => $request->title,
                'description' => $request->description,
                'instructor_id' => auth('web')->id(),
                'course_id' => $request->course_id,
                'chapter_id' => $request->chapter_id,
                'chapter_item_id' => $chapterItem->id,
                'file_path' => $request->upload_path,
                'file_type' => $request->file_type,
            ]);
        } elseif ($request->type == 'live') {
            $chapter_lesson = CourseChapterLesson::create([
                'title' => $request->title,
                'description' => $request->description,
                'instructor_id' => auth('web')->id(),
                'course_id' => $request->course_id,
                'chapter_id' => $request->chapter_id,
                'chapter_item_id' => $chapterItem->id,
                'duration' => $request->duration,
                'storage' => 'live',
                'file_type' => 'live',
            ]);
            $start_time = date('Y-m-d H:i:s', strtotime($request->start_time));
            $join_url = $request?->live_type === 'jitsi' ? null : $request->join_url;
            $liveClass = CourseLiveClass::create([
                'lesson_id' => $chapter_lesson->id,
                'start_time' => $start_time,
                'meeting_id' => $request->meeting_id,
                'password' => $request?->password ?? null,
                'join_url' => $join_url,
                'type' => $request->live_type,
            ]);

            // Add to Google Calendar if instructor has connected
            $this->createCalendarEvent($request->course_id, $start_time, $chapter_lesson, $liveClass);

            if ($request?->student_mail_sent == 'on') {
                $user_ids = Enrollment::where('course_id', $chapter_lesson->course_id)->pluck('user_id')->toArray();
                $users = User::select('name', 'email')->whereIn('id', $user_ids)->get();
                $data = (object) [
                    'course' => Course::select('title')->where('id', $chapter_lesson->course_id)->first()->title,
                    'lesson' => $chapter_lesson->title,
                    'start_time' => formattedDateTime($start_time),
                    'join_url' => $join_url,
                ];
                (new MailSenderService)->sendLiveClassNotificationMailTrait($users, $data);

            }
        } elseif ($request->type == 'quiz') {
            Quiz::create([
                'chapter_item_id' => $chapterItem->id,
                'instructor_id' => auth('web')->id(),
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
            return view('frontend.instructor-dashboard.course.partials.lesson-edit-modal', [
                'chapters' => $chapters,
                'courseId' => $courseId,
                'chapterItem' => $chapterItem,
            ])->render();
        } elseif ($request->type == 'document') {
            return view('frontend.instructor-dashboard.course.partials.document-edit-modal', [
                'chapters' => $chapters,
                'courseId' => $courseId,
                'chapterItem' => $chapterItem,
            ])->render();
        } elseif ($request->type == 'live') {
            return view('frontend.instructor-dashboard.course.partials.live-edit-modal', [
                'chapters' => $chapters,
                'courseId' => $courseId,
                'chapterItem' => $chapterItem,
            ])->render();
        } elseif ($request->type == 'quiz') {
            return view('frontend.instructor-dashboard.course.partials.quiz-edit-modal', [
                'chapters' => $chapters,
                'courseId' => $courseId,
                'chapterItem' => $chapterItem,
            ])->render();
        } else {
            return view('frontend.instructor-dashboard.course.partials.assignment-edit-modal', [
                'chapters' => $chapters,
                'courseId' => $courseId,
                'chapterItem' => $chapterItem,
            ])->render();
        }
    }

    function lessonUpdate(ChapterLessonRequest $request)
    {
        $chapterItem = CourseChapterItem::findOrFail($request->chapter_item_id);
        abort_if($chapterItem->instructor_id != auth('web')->user()->id, 403, __('unauthorized access'));
        if ($request->type !== 'lesson') {
            $chapterItem->update([
                'chapter_id' => $request->chapter,
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
        } elseif ($request->type == 'live') {

            $courseChapterLesson = CourseChapterLesson::where('chapter_item_id', $chapterItem->id)->first();
            $courseChapterLesson->update([
                'title' => $request->title,
                'description' => $request->description,
                'chapter_id' => $chapterItem->chapter_id,
                'chapter_item_id' => $chapterItem->id,
                'duration' => $request->duration,
                'storage' => $request->source ? $request->source : 'live',
                'file_type' => $request->source ? 'video' : 'live',
                'file_path' => $request->source ? ($request->source == 'upload' ? $request->upload_path : $request->link_path) : null,
            ]);

            $start_time = date('Y-m-d H:i:s', strtotime($request->start_time));
            $join_url = $request?->live_type === 'jitsi' ? null : $request->join_url;
            $liveClass = CourseLiveClass::where('lesson_id', $courseChapterLesson->id)->first();
            $liveClass->update([
                'start_time' => $start_time,
                'meeting_id' => $request->meeting_id,
                'password' => $request?->password ?? null,
                'join_url' => $join_url,
                'type' => $request->live_type,
            ]);

            // Update Google Calendar if instructor has connected
            $this->updateCalendarEvent($start_time, $courseChapterLesson, $liveClass);

            if ($request?->student_mail_sent == 'on') {
                $user_ids = Enrollment::where('course_id', $courseChapterLesson->course_id)->pluck('user_id')->toArray();
                $users = User::select('name', 'email')->whereIn('id', $user_ids)->get();
                $data = (object) [
                    'course' => Course::select('title')->where('id', $courseChapterLesson->course_id)->first()->title,
                    'lesson' => $courseChapterLesson->title,
                    'start_time' => formattedDateTime($start_time),
                    'join_url' => $join_url,
                ];
                (new MailSenderService)->sendLiveClassNotificationMailTrait($users, $data);

                if (cache()->get('setting')?->sms_qna_reply_mail) {
                    foreach ($users as $user) {
                        // send sms
                        if ($user?->phone) {
                            $message = SMSTemplate('live_class_mail', [
                                'lesson' => $data?->lesson,
                                'start_time' => $data?->start_time,
                                'join_url' => $data?->join_url,
                            ]);
                            sendSMS($user->phone, $message);
                        }
                    }
                }

            }
        } elseif ($request->type == 'document') {
            $courseChapterLesson = CourseChapterLesson::where('chapter_item_id', $chapterItem->id)->first();
            $courseChapterLesson->update([
                'title' => $request->title,
                'description' => $request->description,
                'course_id' => $chapterItem->course_id,
                'chapter_id' => $chapterItem->chapter_id,
                'chapter_item_id' => $chapterItem->id,
                'file_path' => $request->upload_path,
                'file_type' => $request->file_type,
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
        $chapterItem = CourseChapterItem::findOrFail($chapterItemId);
        abort_if($chapterItem->instructor_id != auth('web')->user()->id, 403, __('unauthorized access'));

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
            if ($chapterItem->type == 'live') {
                $this->destroyCalendarEvent($chapterItem);
            }

            if ($chapterItem->lesson->storage === 'bunny_stream') {
                $this->courseMediaStorage->deleteLessonVideo($chapterItem->lesson->file_path);
            } elseif (in_array($chapterItem->lesson->storage, ['wasabi', 'aws'])) {
                $disk = Storage::disk($chapterItem->lesson->storage);
                $filePath = $chapterItem->lesson->file_path;
                $disk->exists($filePath) && $disk->delete($filePath);
            }
            // delete chapter item lesson if file exists
            if (\File::exists(asset($chapterItem->lesson->file_path))) {
                \File::delete(asset($chapterItem->lesson->file_path));
            }

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
                'instructor',
                (int) auth('web')->id(),
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
        return view('frontend.instructor-dashboard.course.partials.quiz-question-create-modal', ['quizId' => $quizId])->render();
    }

    function storeQuizQuestion(Request $request, string $quizId)
    {
        $request->validate([
            'title' => ['required', 'max:255'],
            'answers.*' => ['required', 'max:255'],
            'grade' => ['required', 'numeric', 'min:0'],
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
            'grade' => $request->grade,
        ]);

        foreach ($request->answers as $key => $answer) {
            $question->answers()->create([
                'title' => $answer,
                'correct' => isset($request->correct[$key]) ? 1 : 0,
                'question_id' => $question->id,
            ]);
        }

        return response()->json(['status' => 'success', 'message' => __('Question created successfully')]);

    }

    function editQuizQuestion(string $questionId)
    {
        $question = QuizQuestion::findOrFail($questionId);
        return view('frontend.instructor-dashboard.course.partials.quiz-question-edit-modal', ['question' => $question])->render();
    }

    function updateQuizQuestion(Request $request, string $questionId)
    {
        $request->validate([
            'title' => ['required', 'max:255'],
            'answers.*' => ['required', 'max:255'],
            'grade' => ['required', 'numeric', 'min:0'],
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
            'grade' => $request->grade,
        ]);
        // update or delete answers
        $question->answers()->delete();
        foreach ($request->answers as $key => $answer) {
            $question->answers()->create([
                'title' => $answer,
                'correct' => isset($request->correct[$key]) ? 1 : 0,
                'question_id' => $question->id,
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

    private function createCalendarEvent($courseId, $start_time, $chapter_lesson, $liveClass): void
    {
        $setting = Cache::get('setting');
        if ($setting?->google_calendar_status == 'active') {
            try {
                $course = Course::select('title', 'slug')->where('id', $courseId)->first();
                $attendees = Enrollment::where('course_id', $courseId)
                    ->join('users', 'users.id', '=', 'enrollments.user_id')->pluck('users.email')
                    ->map(fn($email) => ['email' => $email])->toArray();

                $startTime = \Carbon\Carbon::parse($start_time, $setting?->timezone);
                $endTime = $startTime->copy()->addMinutes((int) $chapter_lesson->duration);

                $calendarService = GoogleCalendarService::forInstructor(auth('web')->user());

                $lessonUrl = route('student.learning.live', ['slug' => $course->slug, 'lesson_id' => $chapter_lesson->id]);

                $calendarDescription = <<<TEXT
                    Course: {$course->title}
                    Lesson: {$chapter_lesson->title}
                    Timezone: {$setting?->timezone}
                    Lesson URL: {$lessonUrl}
                    TEXT;

                $eventId = $calendarService->createEvent([
                    'summary' => $chapter_lesson->title,
                    'description' => $calendarDescription,
                    'start' => $startTime->format('Y-m-d H:i:s'),
                    'end' => $endTime->format('Y-m-d H:i:s'),
                    'meet' => false,
                    'attendees' => $attendees,
                ]);

                if ($eventId) {
                    $liveClass->update([
                        'google_event_id' => $eventId,
                    ]);
                }
            } catch (\Exception $e) {
            }
        }
    }
    private function updateCalendarEvent($start_time, $chapter_lesson, $liveClass): void
    {
        $setting = Cache::get('setting');
        if ($setting?->google_calendar_status == 'active') {
            try {
                $course = $chapter_lesson?->course;
                $start_time = \Carbon\Carbon::parse($start_time, $setting?->timezone);
                $endTime = $start_time->copy()->addMinutes((int) $chapter_lesson->duration);

                $calendarService = GoogleCalendarService::forInstructor(auth('web')->user());

                $lessonUrl = route('student.learning.live', ['slug' => $course->slug, 'lesson_id' => $chapter_lesson->id]);

                $calendarDescription = <<<TEXT
                    Course: {$course?->title}
                    Lesson: {$chapter_lesson->title}
                    Timezone: {$setting?->timezone}
                    Lesson URL: {$lessonUrl}
                    TEXT;

                $calendarService->updateEvent($liveClass?->google_event_id ?? '', [
                    'summary' => $chapter_lesson->title,
                    'description' => $calendarDescription,
                    'start' => $start_time->toDateTimeString(),
                    'end' => $endTime->toDateTimeString(),
                ]);
            } catch (\Exception $e) {
            }
        }
    }
    private function destroyCalendarEvent($chapterItem): void
    {
        $setting = Cache::get('setting');
        if ($setting?->google_calendar_status == 'active') {
            try {
                $liveClass = $chapterItem->lesson->live;
                $calendarService = GoogleCalendarService::forInstructor(auth('web')->user());
                $calendarService->deleteEvent($liveClass?->google_event_id ?? '');
            } catch (\Exception $e) {
            }
        }
    }
}
