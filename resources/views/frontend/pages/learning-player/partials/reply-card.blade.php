<div class="video_qna_list_item">
    <div class="img">
        <img src="{{ asset($question->user->image) }}" alt="img">
    </div>
    <div class="text">
        <a class="qna_title" href="javascript:;">{{ $question->question_title }}</a>
        {!! clean(replaceImageSources($question->question_description)) !!}
        <ul>
            <li><a href="javascript:;">{{ $question->user->name }}</a></li>
            <li>{{ formatDate($question->created_at, 'd M, Y : H:i') }}</li>
        </ul>
    </div>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center">
    <h4>{{ count($replies) }} {{ __('replies ') }}</h4>
    <a class="flow" href="#">{{ __('Follow replies') }}</a>
</div>
@forelse ($replies as $reply)
    <div class="qns_details_list_item video_qna_list_item">
        <div class="img">
            <img src="{{ asset($reply->user->image ?: 'uploads/website-images/avatar.png') }}" alt="img">
        </div>
        <div class="text">
            <a class="qna_title" href="javascript:;">{{ $reply->user->name }}</a>
            @if($reply->is_ai)
                <span class="badge badge-soft-info ms-2">{{ __('AI Tutor') }}</span>
            @endif
            <span>{{ formatDate($reply->created_at, 'd M, Y : H:i') }}</span>
            {!! clean(replaceImageSources($reply->reply)) !!}
            @if($reply->is_ai)
                @php
                    $retrieval = (array) data_get($reply->metadata, 'retrieval', []);
                    $citations = collect(data_get($retrieval, 'results', []))->take(3)->filter();
                @endphp
                @if($citations->isNotEmpty())
                    <details class="mt-3" style="border:1px solid rgba(13,110,253,.18); border-radius:1rem; background:linear-gradient(180deg,#f8fbff 0%,#ffffff 100%); padding:0.85rem 1rem;">
                        <summary class="ai-citations__summary" style="display:flex; align-items:center; justify-content:space-between; gap:1rem; cursor:pointer; list-style:none; font-weight:600;">
                            <span>{{ __('Citations') }}</span>
                            <span style="display:inline-flex; align-items:center; justify-content:center; min-width:2rem; height:2rem; border-radius:999px; background:#dbeafe; color:#1d4ed8; font-size:.8rem; font-weight:700;">{{ $citations->count() }}</span>
                        </summary>
                        <div class="mt-3 d-grid gap-2">
                            @foreach($citations as $citation)
                                <article style="padding:.85rem 1rem; border:1px solid rgba(15,23,42,.08); border-radius:.9rem; background:#fff; box-shadow:0 1px 2px rgba(15,23,42,.04);">
                                    <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start">
                                        <strong>{{ $citation['document']['source_name'] ?? __('Unknown source') }}</strong>
                                        <span class="text-muted small">
                                            @if(!empty($citation['page_start']))
                                                {{ __('Pages') }} {{ $citation['page_start'] }}
                                                @if(!empty($citation['page_end']) && $citation['page_end'] != $citation['page_start'])
                                                    -{{ $citation['page_end'] }}
                                                @endif
                                            @elseif(!empty($citation['chunk_index']))
                                                {{ __('Chunk') }} {{ $citation['chunk_index'] }}
                                            @endif
                                        </span>
                                    </div>
                                    @if(!empty($citation['snippet']))
                                        <p class="mb-0 mt-2 text-muted small">{{ \Illuminate\Support\Str::limit(strip_tags($citation['snippet']), 180) }}</p>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </details>
                @endif
            @endif
            @if($reply->user_id == auth()->user()->id)
            <div class="dot">
                <i class="fas fa-ellipsis-v"></i>
                <ul>
                    <li><a href="{{ route('student.destroy-reply', $reply->id) }}" class="delete-item">{{ __('Delete') }}</a></li>
                </ul>
            </div>
            @endif
        </div>
    </div>
@empty
    <p class="text-center ps-5">{{ __('No replies yet') }}</p>
@endforelse
