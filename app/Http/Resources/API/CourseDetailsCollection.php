<?php

namespace App\Http\Resources\API;

use App\Http\Resources\API\ChapterResource;
use App\Http\Resources\API\CurrentProgressResource;
use App\Http\Resources\API\InstructorResource;
use App\Models\CourseProgress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseDetailsCollection extends JsonResource {
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array {
        $courseId = data_get($this->resource, 'id');
        $thumbnail = data_get($this->resource, 'thumbnail');
        $title = data_get($this->resource, 'title');
        $description = data_get($this->resource, 'description');
        $instructor = data_get($this->resource, 'instructor');
        $chapters = collect(data_get($this->resource, 'chapters', []));
        $languages = collect(data_get($this->resource, 'languages', []));
        $priceValue = data_get($this->resource, 'price', 0);
        $discountValue = data_get($this->resource, 'discount', 0);
        $demoVideoSource = data_get($this->resource, 'demo_video_source');
        $demoVideoStorage = data_get($this->resource, 'demo_video_storage');
        $updatedAt = data_get($this->resource, 'updated_at');
        $duration = data_get($this->resource, 'duration');
        $certificate = data_get($this->resource, 'certificate');
        $slug = data_get($this->resource, 'slug');
        $averageRating = data_get($this->resource, 'average_rating', 0);
        $reviewsCount = data_get($this->resource, 'reviews_count', 0);
        $studentsCount = data_get($this->resource, 'enrollments_count', 0);
        $lessonsCount = data_get($this->resource, 'lessons_count', 0);
        $quizzesCount = data_get($this->resource, 'quizzes_count', 0);
        $isWishlist = data_get($this->resource, 'is_wishlist');

        if ($request->routeIs('api.learning')) {
            $user_id = auth()->id();
            $currentProgress = CourseProgress::where('user_id', $user_id)
                ->where('course_id', $courseId)
                ->where('current', 1)
                ->orderBy('id', 'desc')
                ->first();

            if (!$currentProgress) {
                $firstChapter = $chapters->first();
                $firstChapterItems = collect(data_get($firstChapter, 'chapterItems', []));
                $lessonId = data_get($firstChapterItems->first(), 'lesson.id');
                if ($lessonId) {
                    $currentProgress = CourseProgress::create([
                        'user_id'    => $user_id,
                        'course_id'  => $courseId,
                        'chapter_id' => data_get($firstChapter, 'id'),
                        'lesson_id'  => $lessonId,
                        'current'    => 1,
                    ]);
                }
            }

            $alreadyWatchedLectures = CourseProgress::where('user_id', $user_id)
                ->where('course_id', $courseId)
                ->where('type', 'lesson')
                ->where('watched', 1)
                ->pluck('lesson_id')
                ->toArray();

            $alreadyCompletedQuiz = CourseProgress::where('user_id', $user_id)
                ->where('course_id', $courseId)
                ->where('type', 'quiz')
                ->where('watched', 1)
                ->pluck('lesson_id')
                ->toArray();

            return [
                'thumbnail'                => (string) $thumbnail,
                'title'                    => (string) $title,
                'description'              => (string) $description,
                'instructor'               => $instructor ? new InstructorResource($instructor) : null,
                'curriculums'              => ChapterResource::collection($chapters),
                'current_progress'         => $currentProgress ? new CurrentProgressResource($currentProgress) : null,
                'already_watched_lectures' => (array) $alreadyWatchedLectures,
                'already_completed_quiz'   => (array) $alreadyCompletedQuiz,
            ];
        }
        $currency = strtoupper($request->query('currency', getSessionCurrency()));
        $price = $priceValue == 0 ? (int) $priceValue : (string) apiCurrency($priceValue, $currency);
        $discount = $discountValue == 0 ? (int) $discountValue : (string) apiCurrency($discountValue, $currency);
        return [
            'demo_video_storage' => (string) $demoVideoStorage,
            'demo_video_source'  => (string) $demoVideoSource,
            'demo_video'         => (string) generateVideoEmbedUrl($demoVideoSource, $demoVideoStorage),
            'thumbnail'          => (string) $thumbnail,
            'is_wishlist'        => (bool) $isWishlist,
            'title'              => (string) $title,
            'slug'               => (string) $slug,
            'instructor'         => $instructor ? new InstructorResource($instructor) : null,
            'average_rating'     => (float) $averageRating,
            'reviews_count'      => (int) $reviewsCount,
            'students'           => (int) $studentsCount,
            'last_updated'       => (string) formatDate($updatedAt),
            'duration'           => (string) convertMinutesToHoursAndMinutes($duration),
            'certificate'        => (bool) $certificate,
            'lessons_count'      => (int) $lessonsCount,
            'quizzes_count'      => (int) $quizzesCount,
            'languages'          => (string) $languages->pluck('language.name')->implode(', '),
            'price'              => $price,
            'discount'           => $discount,
            'description'        => (string) $description,
            'curriculums'        => ChapterResource::collection($chapters),
        ];
    }
}
