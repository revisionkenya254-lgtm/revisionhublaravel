@extends('frontend.instructor-dashboard.layouts.master')

@section('dashboard-contents')
    <div class="dashboard__content-wrap">

        <div class="dashboard__content-title d-flex justify-content-between">
            <h4 class="title">{{ __('Create Course') }}</h4>
        </div>
        <div class="row">
            <div class="col-12">
                @include('frontend.instructor-dashboard.course.navigation')
                <div class="instructor__profile-form-wrap">
                    <form method="POST" action="{{ route('instructor.courses.store', ['id' => @$course?->id]) }}"
                        class="instructor__profile-form course-form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="step" value="1">
                        <input type="hidden" name="next_step" value="2">
                        <input type="hidden" name="edit_mode"
                            value="{{ isset($editMode) && $editMode == true ? true : false }}">

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-grp">
                                    <label for="title">{{ __('Title') }} <code>*</code></label>
                                    <input id="title" name="title" type="text" value="{{ @$course?->title }}">
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-grp">
                                    <label for="seo_description">{{ __('Seo description') }} <code></code></label>
                                    <input id="seo_description" name="seo_description" type="text"
                                        value="{{ @$course?->seo_description }}"
                                        placeholder="{{ __('150 - 160 characters recommended') }}">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="from-group mb-3">
                                    <label class="form-file-manager-label" for="thumbnail_file">{{ __('Thumbnail Upload') }}
                                        <code>* ({{ __('Recommended') }}: 690X420 PX)</code></label>
                                    <input id="thumbnail_file" name="thumbnail_file" type="file" class="form-control"
                                        accept="image/*">
                                    <small class="text-muted d-block mt-1">
                                        {{ __('Upload a thumbnail image to Bunny Storage. The legacy path field stays available below as a fallback.') }}
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="from-group mb-3">
                                    <label class="form-file-manager-label" for="thumbnail_url">
                                        {{ __('Thumbnail from Link') }} <code>{{ __('optional') }}</code>
                                    </label>
                                    <div class="input-group">
                                        <input id="thumbnail_url" class="form-control" type="url" name="thumbnail_url"
                                            value="{{ filter_var(@$course?->thumbnail, FILTER_VALIDATE_URL) ? $course->thumbnail : '' }}"
                                            placeholder="{{ __('Paste an image URL, or generate one from a YouTube demo link') }}">
                                        <button class="btn btn-outline-secondary" type="button" id="generate-thumbnail-from-demo">
                                            {{ __('Generate from demo link') }}
                                        </button>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        {{ __('Use this for a hosted image, or generate a YouTube thumbnail from the demo video link. A manual upload takes priority.') }}
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="from-group mb-3">
                                    <label class="form-file-manager-label" for="">{{ __('Thumbnail Path') }}
                                        <code>{{ __('Legacy fallback') }}</code></label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="basic-addon1">
                                            <a data-input="thumbnail" data-preview="holder" class="file-manager-image">
                                                <i class="fa fa-picture-o"></i> {{ __('Choose') }}
                                            </a>
                                        </span>
                                        <input id="thumbnail" readonly class="form-control file-manager-input"
                                            type="text" name="thumbnail" value="{{ @$course?->thumbnail }}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-grp">
                                    <label for="demo_video_storage">{{ __('Demo Video Storage') }}
                                        <code>({{ __('optional') }})</code></label>
                                    <select name="demo_video_storage" id="demo_video_storage" class="form-select">
                                        <option @selected(@$course?->demo_video_storage == 'bunny_stream') value="bunny_stream">
                                            {{ __('Bunny Stream') }}</option>
                                        <option @selected(@$course?->demo_video_storage == 'upload') value="upload">
                                            {{ __('Legacy upload') }}</option>
                                        <option @selected(@$course?->demo_video_storage == 'youtube') value="youtube">{{ __('youtube') }}</option>
                                        <option @selected(@$course?->demo_video_storage == 'vimeo') value="vimeo">{{ __('vimeo') }}</option>
                                        <option @selected(@$course?->demo_video_storage == 'external_link') value="external_link">
                                            {{ __('external_link') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6 bunny_stream upload {{ in_array(@$course?->demo_video_storage, ['upload', 'bunny_stream']) ? '' : 'd-none' }}">
                                <div class="from-group mb-3">
                                    <label class="form-file-manager-label" for="demo_video_file">{{ __('Demo Video Upload') }}
                                        <code>{{ __('Bunny Stream') }}</code></label>
                                    <input id="demo_video_file" name="demo_video_file" type="file" class="form-control"
                                        accept="video/*">
                                    <small class="text-muted d-block mt-1">
                                        {{ __('Upload a video file to Bunny Stream. If you keep the legacy path field below, it will still be used as a fallback.') }}
                                    </small>
                                </div>
                                <div class="from-group mb-3">
                                    <label class="form-file-manager-label" for="">{{ __('Legacy Upload Path') }}
                                        <code></code></label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="basic-addon1">
                                            <a data-input="path" data-preview="holder" class="file-manager">
                                                <i class="fa fa-picture-o"></i> {{ __('Choose') }}
                                            </a>
                                        </span>
                                        <input id="path" readonly class="form-control file-manager-input"
                                            type="text" name="upload_path" value="{{ @$course?->demo_video_source }}">
                                    </div>
                                </div>
                            </div>

                            <div
                                class="col-md-6 external_link {{ @$course?->demo_video_storage != 'upload' ? '' : 'd-none' }}">
                                <div class="form-grp">
                                    <label for="meta_description">{{ __('External Link') }} <code></code></label>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon1"><i class="fas fa-link"></i></span>
                                        <input type="text" class="form-control" name="external_path"
                                            placeholder="{{ __('peste your external link') }}"
                                            value="{{ @$course?->demo_video_source }}">
                                    </div>
                                </div>
                            </div>


                            <div class="col-md-6">
                                <div class="form-grp">
                                    <label for="price">{{ __('Price') }} <code>*</code></label>
                                    <input id="price" name="price" type="text" value="{{ @$course?->price }}">
                                    <code>{{ __('Put 0 for free') }}</code>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-grp">
                                    <label for="discount_price">{{ __('Discount Price') }} <code></code></label>
                                    <input id="discount_price" name="discount_price" type="text"
                                        value="{{ @$course?->discount_price }}">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-grp">
                                    <label for="description">{{ __('Description') }} <code></code></label>
                                    <textarea name="description" class="text-editor">{!! clean(@$course?->description) !!}</textarea>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-primary" type="submit">
                            {{ __('Save & Next') }} <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('frontend/js/default/courses.js') }}"></script>
    <script>
        $(function() {
            $('#generate-thumbnail-from-demo').on('click', function() {
                const demoUrl = $('input[name="external_path"]').val().trim();
                const match = demoUrl.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{11})/i);

                if (!match) {
                    toastr.error("{{ __('Add a valid YouTube demo video link first.') }}");
                    return;
                }

                $('#thumbnail_url').val('https://img.youtube.com/vi/' + match[1] + '/maxresdefault.jpg');
                toastr.success("{{ __('Thumbnail link generated. It will be saved when you continue.') }}");
            });
        });
    </script>
@endpush
