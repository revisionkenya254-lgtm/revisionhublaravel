<section class="lesson-card lesson-step" data-step="2" hidden>
    <div class="lesson-card__heading">
        <span class="lesson-card__icon"><i class="fas fa-cloud-upload-alt"></i></span>
        <div><span>{{ __('Step 2 of 5') }}</span><h2>{{ __('Video & Resources') }}</h2></div>
    </div>

    <div class="lesson-section-title"><div><h3>{{ __('Video Upload') }}</h3><p>{{ __('Upload to private Bunny Stream storage or use a YouTube URL.') }}</p></div></div>
    <div class="lesson-source-picker" role="radiogroup" aria-label="{{ __('Video source') }}">
        <label><input type="radio" name="source" value="bunny_stream" checked><span><i class="fas fa-cloud-upload-alt"></i>{{ __('Upload video') }}</span></label>
        <label><input type="radio" name="source" value="youtube"><span><i class="fab fa-youtube"></i>{{ __('YouTube URL') }}</span></label>
    </div>

    <div data-upload-source="bunny_stream">
        <label class="lesson-dropzone lesson-dropzone--video" for="lesson-video" data-video-dropzone>
            <input id="lesson-video" name="video_file" type="file" accept="video/mp4,video/webm,video/quicktime,video/x-m4v" data-video-input>
            <span class="lesson-dropzone__art"><i class="fas fa-film"></i></span>
            <strong>{{ __('Drag and drop your video file here') }}</strong>
            <span>{{ __('or') }}</span>
            <b>{{ __('Choose Video') }}</b>
            <small>{{ __('MP4, WebM, MOV or M4V · up to 500MB') }}</small>
        </label>
        <div class="lesson-upload-card" data-video-card hidden>
            <div class="lesson-upload-card__preview"><video muted playsinline data-video-preview></video><i class="fas fa-play"></i></div>
            <div class="lesson-upload-card__body"><strong data-video-name></strong><span data-video-meta></span><div class="lesson-upload-card__status"><i class="fas fa-check-circle"></i><span>{{ __('Ready to upload') }}</span></div></div>
            <div class="lesson-upload-card__actions"><button type="button" data-replace-video>{{ __('Replace') }}</button><button type="button" data-remove-video>{{ __('Remove') }}</button></div>
        </div>
    </div>
    <div class="lesson-field" data-upload-source="youtube" hidden>
        <label for="lesson-youtube-url">{{ __('YouTube URL') }} <em>*</em></label>
        <div class="lesson-field__with-icon"><i class="fas fa-link"></i><input id="lesson-youtube-url" name="link_path" type="url" placeholder="https://www.youtube.com/watch?v=..."></div>
        <span class="lesson-field__error" data-error-for="link_path"></span>
    </div>
    <span class="lesson-field__error" data-error-for="video_file"></span>

    <div class="lesson-upload-progress" data-upload-progress hidden>
        <div><span data-upload-label>{{ __('Uploading') }}</span><strong data-upload-percent>0%</strong></div>
        <div class="lesson-upload-progress__track"><span data-upload-bar></span></div>
        <small>{{ __('Keep this page open while your files upload. Video processing may continue after saving.') }}</small>
    </div>

    <div class="lesson-divider"></div>
    <div class="lesson-section-title"><div><h3>{{ __('Resource Attachments') }}</h3><p>{{ __('Add PDF, ZIP or other files for this lesson. These will be available from the Resources tab in the Android app.') }}</p></div><button type="button" class="lesson-button lesson-button--outline" data-add-resource><i class="fas fa-plus"></i> {{ __('Add Resource') }}</button></div>
    <input type="file" name="resources[]" multiple accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.txt,.jpg,.jpeg,.png,.webp" data-resource-input hidden>
    <div class="lesson-resource-list" data-resource-list>
        <div class="lesson-resource-empty" data-resource-empty><i class="far fa-folder-open"></i><span>{{ __('No resources added yet') }}</span><small>{{ __('PDF, Office, ZIP, text or image · up to 50MB each') }}</small></div>
    </div>
    <span class="lesson-field__error" data-error-for="resources"></span>
</section>
