<?php

namespace Tests\Unit;

use App\Http\Resources\API\LessonResource;
use App\Models\CourseChapterLesson;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\TestCase;

class LessonApiCompatibilityTest extends TestCase
{
    public function test_detailed_lesson_keeps_legacy_fields_and_adds_mobile_builder_fields(): void
    {
        $lesson = new CourseChapterLesson([
            'title' => 'Introduction to Quadratic Expressions',
            'lecture_number' => '1.1',
            'description' => 'Lesson overview copy.',
            'file_path' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'storage' => 'youtube',
            'file_type' => 'video',
            'duration' => 12.4167,
            'downloadable' => true,
            'is_free' => false,
            'include_in_curriculum' => true,
            'qna_enabled' => true,
            'qna_allow_questions' => true,
            'qna_allow_replies' => false,
            'qna_instructions' => 'Include the question number.',
        ]);
        $lesson->id = 42;
        $lesson->setRelation('resources', collect());

        $request = Request::create('/api/learning/course/items/lesson/42');
        $route = new Route('GET', 'api/learning/{slug}/items/{type}/{lesson_id}', fn () => null);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $data = (new LessonResource($lesson))->resolve($request);

        $this->assertSame('Lesson overview copy.', $data['description']);
        $this->assertSame($data['description'], $data['overview']);
        $this->assertSame('1.1', $data['lecture_number']);
        $this->assertArrayHasKey('file_path', $data);
        $this->assertSame($data['file_path'], $data['video_url']);
        $this->assertTrue($data['qna']['enabled']);
        $this->assertFalse($data['qna']['allow_replies']);
        $this->assertArrayHasKey('resources', $data);
    }

    public function test_curriculum_lesson_payload_remains_compact(): void
    {
        $lesson = new CourseChapterLesson([
            'title' => 'Completing the Square',
            'lecture_number' => '1.2',
            'description' => 'Private overview.',
            'file_path' => 'private-video-id',
            'storage' => 'bunny_stream',
            'file_type' => 'video',
            'duration' => 18,
            'is_free' => false,
            'include_in_curriculum' => true,
            'qna_enabled' => true,
        ]);
        $lesson->id = 43;

        $data = (new LessonResource($lesson))->resolve(Request::create('/api/catalog/courses/course'));

        $this->assertSame('1.2', $data['lecture_number']);
        $this->assertArrayNotHasKey('file_path', $data);
        $this->assertArrayNotHasKey('description', $data);
        $this->assertTrue($data['qna_enabled']);
    }
}
