@props(['commentPlaceholderText' => __('Comment'),'subjectPlaceholderText' => __('Subject'),'namePlaceholderText' => __('Name'),'emailPlaceholderText' => __('E-mail'),'button_text' => __('Subscribe Now')])
<form id="contact-form" action="" method="POST">
    @csrf
    <div class="form-grp">
        <textarea name="message" placeholder="{{ $commentPlaceholderText }} *" required></textarea>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-grp">
                <input name="subject" type="text" placeholder="{{ $subjectPlaceholderText }} *" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-grp">
                <input name="name" type="text" placeholder="{{ $namePlaceholderText }} *" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-grp">
                <input name="email" type="email" placeholder="{{ $emailPlaceholderText }} *" required>
            </div>
        </div>
        <!-- g-recaptcha -->
        @if (Cache::get('setting')->recaptcha_status === 'active')
            <div class="form-grp mt-3">
                <div class="g-recaptcha" data-sitekey="{{ Cache::get('setting')->recaptcha_site_key }}"></div>
            </div>
        @endif
    </div>
    <button type="submit" class="btn btn-two arrow-btn">{{ $button_text }}<img
            src="{{ asset('frontend/img/icons/right_arrow.svg') }}" alt="img" class="injectable"></button>
</form>
<p class="ajax-response mb-0"></p>
