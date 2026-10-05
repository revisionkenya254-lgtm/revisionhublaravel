<div class="social-links">
    @if (count(getSocialLinks()) > 0)
        <ul class="list-wrap">
            @foreach (getSocialLinks() as $socialLink)
                <li>
                    <a href="{{ $socialLink->link }}" target="_blank">
                        <img src="{{ asset($socialLink->icon) }}" alt="img">
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>
