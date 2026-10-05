<?php

namespace App\Observers;

use App\Models\Course;
use App\Services\CacheService;

class CourseObserver
{
    public function __construct(
        protected CacheService $cacheService
    ) {}

    /**
     * Handle the Course "created" event.
     */
    public function created(Course $course): void
    {
        // Clear courses cache when a new course is created
        $this->cacheService->clearCoursesCache();
    }

    /**
     * Handle the Course "updated" event.
     */
    public function updated(Course $course): void
    {
        // Clear courses cache when a course is updated
        $this->cacheService->clearCoursesCache();
    }

    /**
     * Handle the Course "deleted" event.
     */
    public function deleted(Course $course): void
    {
        // Clear courses cache when a course is deleted
        $this->cacheService->clearCoursesCache();
    }

    /**
     * Handle the Course "restored" event.
     */
    public function restored(Course $course): void
    {
        // Clear courses cache when a course is restored
        $this->cacheService->clearCoursesCache();
    }
}