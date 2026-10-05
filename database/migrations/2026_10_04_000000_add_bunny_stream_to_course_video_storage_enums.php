<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE courses MODIFY demo_video_storage ENUM('upload', 'bunny_stream', 'youtube', 'vimeo', 'external_link', 'aws', 'wasabi') NOT NULL DEFAULT 'upload'");
        DB::statement("ALTER TABLE course_chapter_lessons MODIFY storage ENUM('upload', 'bunny_stream', 'youtube', 'vimeo', 'external_link', 'google_drive', 'iframe', 'aws', 'wasabi', 'live') NOT NULL DEFAULT 'upload'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (
            DB::table('courses')->where('demo_video_storage', 'bunny_stream')->exists()
            || DB::table('course_chapter_lessons')->where('storage', 'bunny_stream')->exists()
        ) {
            throw new RuntimeException('Cannot remove Bunny Stream enum values while Bunny videos are still stored.');
        }

        DB::statement("ALTER TABLE courses MODIFY demo_video_storage ENUM('upload', 'youtube', 'vimeo', 'external_link', 'aws', 'wasabi') NOT NULL DEFAULT 'upload'");
        DB::statement("ALTER TABLE course_chapter_lessons MODIFY storage ENUM('upload', 'youtube', 'vimeo', 'external_link', 'google_drive', 'iframe', 'aws', 'wasabi', 'live') NOT NULL DEFAULT 'upload'");
    }
};
