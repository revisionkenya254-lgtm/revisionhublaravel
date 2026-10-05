<?php

namespace App\Http\Controllers;

use App\Http\Requests\Frontend\StoreVideoLessonRequest;
use App\Models\Course;
use App\Models\CourseChapter;
use App\Models\CourseChapterItem;
use App\Models\CourseChapterLesson;
use App\Services\Storage\CourseMediaStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class LessonBuilderController extends Controller
{
    public function __construct(private readonly CourseMediaStorageService $mediaStorage)
    {
    }

    public function create(Request $request, Course $course): View
    {
        $isAdmin = auth('admin')->check();
        $this->authorizeCourse($course, $isAdmin);

        $course->load('instructor:id,name,image');
        $chapters = CourseChapter::query()
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->withCount('chapterItems')
            ->orderBy('order')
            ->get(['id', 'course_id', 'title', 'order']);

        $selectedChapterId = (int) $request->integer('chapter');
        if (! $chapters->contains('id', $selectedChapterId)) {
            $selectedChapterId = (int) ($chapters->first()?->id ?? 0);
        }

        $view = $isAdmin
            ? 'course::course.lesson-builder.create'
            : 'frontend.instructor-dashboard.course.lesson-builder.create';

        return view($view, compact('course', 'chapters', 'selectedChapterId', 'isAdmin'));
    }

    public function store(StoreVideoLessonRequest $request, Course $course): JsonResponse
    {
        $isAdmin = auth('admin')->check();
        $this->authorizeCourse($course, $isAdmin);

        $actorRole = $isAdmin ? 'admin' : 'instructor';
        $actorId = (int) ($isAdmin ? auth('admin')->id() : auth('web')->id());
        $lessonInstructorId = (int) $course->instructor_id;
        $video = null;
        $resourceUploads = [];

        try {
            if ($request->input('source') === 'bunny_stream') {
                $video = $this->mediaStorage->storeLessonVideo(
                    $request->file('video_file'),
                    $request->string('title')->toString(),
                    $actorRole,
                    $actorId,
                    (int) $course->id
                );
            }

            foreach ($request->file('resources', []) as $resourceFile) {
                $resourceUploads[] = $this->mediaStorage->storeLessonResource(
                    $resourceFile,
                    $actorRole,
                    $actorId,
                    (int) $course->id
                );
            }

            $lesson = DB::transaction(function () use ($request, $course, $lessonInstructorId, $video, $resourceUploads, $actorRole, $actorId) {
                $chapterItem = CourseChapterItem::create([
                    'instructor_id' => $lessonInstructorId,
                    'chapter_id' => $request->integer('chapter_id'),
                    'type' => 'lesson',
                    'order' => CourseChapterItem::where('chapter_id', $request->integer('chapter_id'))->max('order') + 1,
                ]);

                $videoFile = $request->file('video_file');
                $lesson = CourseChapterLesson::create([
                    'title' => $request->string('title')->toString(),
                    'lecture_number' => $request->string('lecture_number')->toString(),
                    'description' => $request->string('overview')->toString(),
                    'instructor_id' => $lessonInstructorId,
                    'course_id' => $course->id,
                    'chapter_id' => $request->integer('chapter_id'),
                    'chapter_item_id' => $chapterItem->id,
                    'file_path' => $video ? $video['video_id'] : $request->string('link_path')->toString(),
                    'storage' => $video ? 'bunny_stream' : 'youtube',
                    'file_type' => 'video',
                    'video_original_name' => $videoFile?->getClientOriginalName(),
                    'video_size' => $videoFile?->getSize(),
                    'video_mime_type' => $videoFile?->getMimeType(),
                    'duration' => $request->float('duration'),
                    'is_free' => $request->input('access') === 'free_preview',
                    'include_in_curriculum' => $request->boolean('include_in_curriculum'),
                    'qna_enabled' => $request->boolean('qna_enabled'),
                    'qna_allow_questions' => $request->boolean('qna_allow_questions'),
                    'qna_allow_replies' => $request->boolean('qna_allow_replies'),
                    'qna_notify_instructor' => $request->boolean('qna_notify_instructor'),
                    'qna_instructions' => $request->string('qna_instructions')->toString() ?: null,
                    'status' => $request->string('publication_status')->toString(),
                ]);

                foreach ($resourceUploads as $index => $upload) {
                    $lesson->resources()->create([
                        'name' => $upload['original_name'],
                        'file_path' => $upload['path'],
                        'storage' => config('bunny.storage_status') === 'active' ? 'bunny_storage' : 'local',
                        'mime_type' => $upload['mime_type'],
                        'extension' => $upload['extension'],
                        'file_size' => $upload['size'],
                        'order' => $index + 1,
                        'status' => 'active',
                        'uploaded_by_type' => $actorRole,
                        'uploaded_by_id' => $actorId,
                    ]);
                }

                return $lesson;
            });
        } catch (Throwable $exception) {
            if ($video && isset($video['video_id'])) {
                $this->mediaStorage->deleteLessonVideo($video['video_id']);
            }

            foreach ($resourceUploads as $upload) {
                $this->mediaStorage->deleteDocument($upload['path'] ?? null);
            }

            Log::error('Video lesson builder failed', [
                'course_id' => $course->id,
                'actor_role' => $actorRole,
                'actor_id' => $actorId,
                'exception' => $exception,
            ]);

            throw $exception;
        }

        $redirect = $isAdmin
            ? route('admin.courses.edit', ['id' => $course->id, 'step' => 3])
            : route('instructor.courses.edit', ['id' => $course->id, 'step' => 3]);

        return response()->json([
            'status' => 'success',
            'message' => $lesson->status === 'active'
                ? __('Lesson published successfully. The video may continue processing on Bunny Stream.')
                : __('Lesson saved as a draft.'),
            'lesson_id' => $lesson->id,
            'redirect_url' => $redirect,
        ], 201);
    }

    private function authorizeCourse(Course $course, bool $isAdmin): void
    {
        if ($isAdmin) {
            checkAdminHasPermissionAndThrowException('course.management');

            return;
        }

        abort_unless(
            auth('web')->check() && (int) $course->instructor_id === (int) auth('web')->id(),
            403,
            __('You are not allowed to manage this course.')
        );
    }
}
