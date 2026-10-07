<ol class="lesson-stepper" aria-label="{{ __('Lesson creation progress') }}">
    @foreach ([__('Lesson Details'), __('Video & Resources'), __('Set Access'), __('Publish')] as $index => $label)
        <li class="lesson-stepper__item {{ $index === 0 ? 'is-active' : '' }}" data-step-indicator="{{ $index + 1 }}">
            <button type="button" data-go-step="{{ $index + 1 }}" aria-current="{{ $index === 0 ? 'step' : 'false' }}">
                <span class="lesson-stepper__number">{{ $index + 1 }}</span>
                <span class="lesson-stepper__label">{{ $label }}</span>
            </button>
        </li>
    @endforeach
</ol>
