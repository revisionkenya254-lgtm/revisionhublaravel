<?php

namespace Tests\Unit;

use App\Http\Requests\Frontend\ChapterLessonRequest as InstructorChapterLessonRequest;
use App\Services\Storage\BunnyStreamService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Modules\Course\app\Http\Requests\ChapterLessonRequest as AdminChapterLessonRequest;
use Tests\TestCase;

class LessonVideoSourceTest extends TestCase
{
    public function test_lesson_validation_accepts_youtube_and_bunny_stream_only(): void
    {
        foreach ([InstructorChapterLessonRequest::class, AdminChapterLessonRequest::class] as $requestClass) {
            $youtubeRequest = $requestClass::create('/', 'POST', [
                'type' => 'lesson',
                'title' => 'Public lesson',
                'source' => 'youtube',
                'file_type' => 'video',
                'duration' => 10,
                'link_path' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ]);

            $this->assertTrue(Validator::make($youtubeRequest->all(), $youtubeRequest->rules())->passes());

            $bunnyRequest = $requestClass::create('/', 'POST', [
                'type' => 'lesson',
                'title' => 'Private lesson',
                'source' => 'bunny_stream',
                'file_type' => 'video',
                'duration' => 10,
            ]);

            $this->assertTrue(Validator::make($bunnyRequest->all(), $bunnyRequest->rules())->passes());

            $legacyRequest = $requestClass::create('/', 'POST', [
                'type' => 'lesson',
                'title' => 'Legacy lesson',
                'source' => 'vimeo',
                'file_type' => 'video',
                'duration' => 10,
                'link_path' => 'https://vimeo.com/12345678',
            ]);

            $this->assertFalse(Validator::make($legacyRequest->all(), $legacyRequest->rules())->passes());
        }
    }

    public function test_youtube_source_rejects_non_youtube_urls(): void
    {
        $request = InstructorChapterLessonRequest::create('/', 'POST', [
            'type' => 'lesson',
            'title' => 'Wrong provider',
            'source' => 'youtube',
            'file_type' => 'video',
            'duration' => 10,
            'link_path' => 'https://example.com/video',
        ]);

        $this->assertFalse(Validator::make($request->all(), $request->rules())->passes());
    }

    public function test_bunny_stream_upload_returns_a_video_id_and_embed_url(): void
    {
        config()->set('bunny.stream_library_id', '12345');
        config()->set('bunny.stream_api_key', 'secret');
        config()->set('bunny.stream_cdn_hostname', 'player.mediadelivery.net');

        Http::fake([
            'https://video.bunnycdn.com/library/12345/videos' => Http::response(['guid' => 'video-guid'], 200),
            'https://video.bunnycdn.com/library/12345/videos/video-guid' => Http::response([], 200),
        ]);

        $result = app(BunnyStreamService::class)->uploadVideo(
            UploadedFile::fake()->create('lesson.mp4', 100, 'video/mp4'),
            'Private lesson'
        );

        $this->assertSame('bunny_stream', $result['storage']);
        $this->assertSame('video-guid', $result['video_id']);
        $this->assertSame('https://player.mediadelivery.net/embed/12345/video-guid', $result['embed_url']);
        Http::assertSentCount(2);
    }

    public function test_bunny_video_ids_are_converted_to_player_embeds(): void
    {
        config()->set('bunny.stream_library_id', '12345');
        config()->set('bunny.stream_cdn_hostname', 'player.mediadelivery.net');

        $this->assertSame(
            'https://player.mediadelivery.net/embed/12345/video-guid',
            generateVideoEmbedUrl('video-guid', 'bunny_stream')
        );
    }

    public function test_youtube_shorts_are_converted_to_player_embeds(): void
    {
        $this->assertSame(
            'https://www.youtube.com/embed/dQw4w9WgXcQ?rel=0',
            generateVideoEmbedUrl('https://www.youtube.com/shorts/dQw4w9WgXcQ', 'youtube')
        );
    }
}
