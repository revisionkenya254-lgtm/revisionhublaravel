<div class="modal-header">
    <h6 class="modal-title fs-5" id="">{{ __('Add Lesson') }}</h6>
</div>

<div class="">
    <form action="{{ route('admin.course-chapter.lesson.store') }}" method="POST" enctype="multipart/form-data"
        class="add_lesson_form instructor__profile-form">
        @csrf
        <input type="hidden" name="course_id" value="{{ $courseId }}">
        <input type="hidden" name="chapter_id" value="{{ $chapterId }}">
        <input type="hidden" name="type" value="{{ $type }}">
        <input type="hidden" name="file_type" value="video">

        <div class="">
            <div class="form-group">
                <label for="chapter">{{ __('Chapter') }} <code>*</code></label>
                <select name="chapter" id="chapter" class="chapter form-control">
                    <option value="">{{ __('Select') }}</option>
                    @foreach ($chapters as $chapter)
                        <option @selected($chapterId == $chapter->id) value="{{ $chapter->id }}">{{ $chapter->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="">
            <div class="form-grp">
                <label for="title">{{ __('Title') }} <code>*</code></label>
                <input id="title" name="title" type="text" value="" class="form-control">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-grp">
                    <label for="source">{{ __('Video source') }} <code>*</code></label>
                    <select name="source" id="source" class="source form-control">
                        <option value="">{{ __('Select') }}</option>
                        <option value="youtube">{{ __('YouTube URL') }}</option>
                        <option value="bunny_stream">{{ __('Bunny Stream upload') }}</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="duration">{{ __('Duration') }} <code>* ({{ __('in minutes') }})</code></label>
                    <input class="form-control" id="duration" name="duration" type="text" value="">
                </div>
            </div>

            <div class="col-md-12 bunny_stream_upload d-none">
                <div class="from-group mb-3">
                    <label for="video_file">{{ __('Video file') }} <code>*</code></label>
                    <input id="video_file" name="video_file" type="file" class="form-control"
                        accept="video/mp4,video/webm,video/quicktime,video/x-m4v">
                    <small class="text-muted">{{ __('The video will be uploaded privately to Bunny Stream (maximum 500MB).') }}</small>
                </div>
            </div>
            <div class="col-md-12 link_path d-none">
                <div class="form-grp">
                    <label for="input_link">{{ __('YouTube URL') }} <code>*</code></label>
                    <div class="input-group mb-3">
                        <span class="input-group-text" id="basic-addon1"><i class="fas fa-link"></i></span>
                        <input type="url" class="form-control" id="input_link" name="link_path"
                            placeholder="https://www.youtube.com/watch?v=..." value="">
                    </div>
                </div>
            </div>
        </div>

        <div class="">
            <div class="form-grp">
                <label for="description">{{ __('Description') }} <code></code></label>
                <textarea name="description" class="form-control"></textarea>
            </div>
        </div>

        <div class="row is_free_wrapper">
            <div class="col-md-6 mt-2">
                <span>{{ __('Preview') }}</span>
                <div class="switcher ms-3">
                    <label for="toggle-0">
                        <input type="checkbox" id="toggle-0" value="1" name="is_free" />
                        <span><small></small></span>
                    </label>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary submit-btn">{{ __('Create') }}</button>
        </div>
    </form>
</div>
