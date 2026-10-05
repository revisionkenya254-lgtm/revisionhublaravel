<?php

use Illuminate\Support\Facades\Route;

use Modules\Assignment\app\Http\Controllers\InstructorAssignmentController;
use Modules\Assignment\app\Http\Controllers\StudentAssignmentController;

Route::group(['middleware' => ['auth', 'verified']], function () {
    // Instructor Routes
    Route::group(['prefix' => 'instructor/assignment', 'as' => 'instructor.assignment.'], function () {
        Route::get('{assignment}/submissions', [InstructorAssignmentController::class, 'submissions'])->name('submissions');

        // Modal load routes
        Route::get('grade-modal/{submission}', [InstructorAssignmentController::class, 'gradeModal'])->name('grade-modal');
        Route::get('text-modal/{submission}', [InstructorAssignmentController::class, 'textModal'])->name('text-modal');

        Route::post('grade', [InstructorAssignmentController::class, 'gradeSubmission'])->name('grade');
        Route::get('download-submission-file', [StudentAssignmentController::class, 'downloadSubmissionFile'])->name('download-submission');
        Route::delete('submission/{submission}', [InstructorAssignmentController::class, 'destroy'])->name('destroy-submission');
    });

    // Student Routes
    Route::group(['prefix' => 'student/assignment', 'as' => 'student.assignment.'], function () {
        Route::get('{assignment}', [StudentAssignmentController::class, 'show'])->name('show');
        Route::post('submit', [StudentAssignmentController::class, 'submitAssignment'])->name('submit');
        Route::post('upload-chunk', [StudentAssignmentController::class, 'uploadChunk'])->name('upload-chunk');
        Route::get('download-submission-file', [StudentAssignmentController::class, 'downloadSubmissionFile'])->name('download-submission');
    });
});
