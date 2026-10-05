<?php

namespace App\Http\Requests\Frontend;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVideoLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');
        $course = $course instanceof Course ? $course : Course::find($course);

        if (! $course) {
            return false;
        }

        return auth('admin')->check()
            || (auth('web')->check() && (int) $course->instructor_id === (int) auth('web')->id());
    }

    public function rules(): array
    {
        $courseId = $this->route('course') instanceof Course
            ? $this->route('course')->id
            : $this->route('course');

        return [
            'chapter_id' => ['required', Rule::exists('course_chapters', 'id')->where('course_id', $courseId)],
            'lecture_number' => ['required', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:255'],
            'overview' => ['required', 'string', 'max:10000'],
            'duration_display' => ['required', 'regex:/^(?:(?:\d{1,2}):)?[0-5]?\d:[0-5]\d$/'],
            'duration' => ['required', 'numeric', 'min:0.01'],
            'source' => ['required', Rule::in(['bunny_stream', 'youtube'])],
            'video_file' => [
                Rule::requiredIf(fn () => $this->input('source') === 'bunny_stream'),
                'nullable', 'file',
                'mimetypes:video/mp4,video/webm,video/quicktime,video/x-m4v',
                'max:512000',
            ],
            'link_path' => [
                Rule::requiredIf(fn () => $this->input('source') === 'youtube'),
                'nullable', 'url:http,https', 'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value) {
                        return;
                    }

                    $host = strtolower((string) parse_url((string) $value, PHP_URL_HOST));
                    if (! in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
                        $fail(__('The video link must be a valid YouTube URL.'));
                    }
                },
            ],
            'resources' => ['nullable', 'array', 'max:10'],
            'resources.*' => ['file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,txt,jpg,jpeg,png,webp', 'max:51200'],
            'qna_enabled' => ['nullable', 'boolean'],
            'qna_allow_questions' => ['nullable', 'boolean'],
            'qna_allow_replies' => ['nullable', 'boolean'],
            'qna_notify_instructor' => ['nullable', 'boolean'],
            'qna_instructions' => ['nullable', 'string', 'max:2000'],
            'access' => ['required', Rule::in(['included_with_course', 'free_preview'])],
            'include_in_curriculum' => ['nullable', 'boolean'],
            'publication_status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'chapter_id.required' => __('Choose the section for this lesson.'),
            'chapter_id.exists' => __('The selected section does not belong to this course.'),
            'lecture_number.required' => __('Enter the lecture number.'),
            'overview.required' => __('Enter a lesson overview.'),
            'duration_display.regex' => __('Use MM:SS or HH:MM:SS for the duration.'),
            'video_file.required' => __('Choose a video to upload.'),
            'video_file.mimetypes' => __('Use an MP4, WebM, MOV, or M4V video.'),
            'video_file.max' => __('The video must be 500MB or smaller.'),
            'resources.*.mimes' => __('Resources may be PDF, Office, ZIP, text, or image files.'),
            'resources.*.max' => __('Each resource must be 50MB or smaller.'),
        ];
    }
}
