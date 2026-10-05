@props(['placeholder_text' => __('Type your email'), 'button_text' => __('Subscribe Now')])
<form action="{{ route('newsletter-request') }}" method="post" class="newsletter__form-two newsletter">
    @csrf
    <input type="email" placeholder="{{ $placeholder_text }}" name="email">
    <button type="submit" class="btn arrow-btn">{{ $button_text }}</button>
</form>
