<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChapterLessonRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->type == 'lesson') {
            return $this->lessonRules();
        } elseif ($this->type == 'document') {
            return $this->documentRules();
        } elseif ($this->type == 'live') {
            return $this->liveRules();
        } elseif ($this->type == 'assignment') {
            return $this->assignmentRules();
        } else {
            return $this->quizRules();
        }
    }

    function lessonRules(): array
    {
        $rules = [
            'title' => ['required', 'max:255'],
            'description' => ['nullable', 'max:10000'],
            'source' => ['required', Rule::in(['youtube', 'bunny_stream'])],
            'file_type' => ['required', Rule::in(['video'])],
            'duration' => ['required', 'numeric', 'min:0'],
            'video_file' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime,video/x-m4v', 'max:512000'],
            'live_status' => ['sometimes'],
            'student_mail_sent' => ['sometimes'],
        ];

        if ($this->source === 'youtube') {
            $rules['link_path'] = [
                'required',
                'url:http,https',
                'max:2048',
                function ($attribute, $value, $fail) {
                    $host = strtolower((string) parse_url((string) $value, PHP_URL_HOST));

                    if (! in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
                        $fail(__('The video link must be a valid YouTube URL.'));
                    }
                },
            ];
        }

        return $rules;
    }
    function documentRules(): array
    {
        $rules = [
            'chapter' => ['required', 'exists:course_chapters,id'],
            'title' => ['required', 'max:255'],
            'description' => ['nullable', 'max:10000'],
            'file_type' => ['required', 'in:txt,pdf,docx'],
            'upload_path' => [
                'required',
                function ($attribute, $value, $fail) {
                    $fileType = request()->input('file_type');
                    $extension = strtolower(pathinfo($value, PATHINFO_EXTENSION));

                    if ($extension !== strtolower($fileType)) {
                        $fail(__('The upload file extension does not match the required file type.'));
                    }
                },
            ],
        ];

        return $rules;
    }

    function liveRules(): array
    {
        $rules = [
            'chapter' => ['required', 'exists:course_chapters,id'],
            'title' => ['required', 'max:255'],
            'description' => ['nullable', 'max:10000'],
            'duration' => ['required', 'numeric', 'min:0'],
            'live_type' => ['required'],
            'start_time' => 'required',
        ];
        if (!$this->live_status && !empty($this->source)) {
            $rules[$this->source === 'upload' ? 'upload_path' : 'link_path'] = ['required'];
        }
        if ($this->live_type == 'jitsi') {
            $rules['meeting_id'] = ['required'];
        }
        if ($this->live_type == 'zoom') {
            $rules['meeting_id'] = ['required'];
            $rules['password'] = ['required'];
            $rules['join_url'] = ['sometimes'];
        }
        if ($this->live_type == 'google_meet') {
            $rules['join_url'] = ['required'];
        }

        return $rules;
    }
    function quizRules(): array
    {
        $rules = [
            'chapter' => ['required', 'exists:course_chapters,id'],
            'title' => ['required', 'max:255', 'string'],
            'time_limit' => ['required', 'numeric', 'min:1'],
            'attempts' => ['required', 'numeric', 'min:1'],
            'pass_mark' => ['required', 'numeric', 'min:1'],
            'total_mark' => ['required', 'numeric', 'min:1'],
        ];

        return $rules;
    }

    public function messages()
    {
        $messages = [
            'title.required' => __('The title field is required.'),
            'title.max' => __('The title may not be greater than 255 characters.'),
            'description.required' => __('The description field is required.'),
            'description.max' => __('Description must be less than 10000 characters long'),
            'source.required' => __('The source field is required.'),
            'file_type.required' => __('The file type field is required.'),
            'chapter.required' => __('Chapter is required'),
            'chapter.exists' => __('Chapter doesnt exist'),
            'time_limit.required' => __('Time limit is required'),
            'time_limit.numeric' => __('Time limit must be a number'),
            'time_limit.min' => __('Time limit must be at least 0 minute'),
            'attempts.required' => __('Number of attempts is required'),
            'attempts.numeric' => __('Number of attempts must be a number'),
            'attempts.min' => __('Number of attempts must be at least 1'),
            'pass_mark.required' => __('Pass mark is required'),
            'pass_mark.numeric' => __('Pass mark must be a number'),
            'pass_mark.min' => __('Pass mark must be at least 1'),
            'upload_path.required' => __('The upload path field is required.'),
            'link_path.required' => __('The link path field is required.'),
            'video_file.file' => __('The Bunny Stream upload must be a valid file.'),
            'video_file.mimetypes' => __('The Bunny Stream upload must be an MP4, WebM, MOV, or M4V video.'),
            'video_file.max' => __('The Bunny Stream upload must be less than 500MB.'),
            'duration.required' => __('The duration field is required.'),
            'live_type.required' => __('Live lesson platform is required'),
            'start_time.required' => __('Live lesson start time is required'),
            'meeting_id.required' => __('Meeting ID is required'),
            'password.required' => __('Password is required'),
            'join_url.required' => __('Join URL is required'),
            'title.string' => __('The title must be a string.'),
            'description.string' => __('The description must be a string.'),
            'instructions.string' => __('The instructions must be a string.'),
            'submission_type.required' => __('The submission type field is required.'),
            'submission_type.in' => __('The submission type must be either file, text, or link.'),
            'due_date.date' => __('The due date must be a valid date.'),
            'late_submission_allowed.boolean' => __('The late submission allowed field must be a boolean.'),
            'late_penalty.numeric' => __('The late penalty must be a number.'),
            'late_penalty.min' => __('The late penalty must be at least 0.'),
            'max_marks.required' => __('The max marks field is required.'),
            'max_marks.numeric' => __('The max marks must be a number.'),
            'max_marks.min' => __('The max marks must be at least 0.'),
            'total_mark.required' => __('Total mark is required'),
            'total_mark.numeric' => __('Total mark must be a number'),
            'total_mark.min' => __('Total mark must be at least 1'),
        ];

        return $messages;
    }

    function assignmentRules(): array
    {
        return [
            'chapter' => ['required', 'exists:course_chapters,id'],
            'title' => ['required', 'max:255', 'string'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'submission_type' => ['required', 'in:file,text,link'],
            'due_date' => ['nullable', 'date'],
            'late_submission_allowed' => ['nullable', 'boolean'],
            'late_penalty' => ['nullable', 'numeric', 'min:0'],
            'max_marks' => ['required', 'numeric', 'min:0'],
        ];
    }
}
