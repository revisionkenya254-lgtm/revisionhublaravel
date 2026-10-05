@props(['total' => 3])
@foreach (getFeaturedBlogs((int) $total) as $blog)
    <div class="col-lg-4 col-md-6">
        <div class="blog__post-item-four shine__animate-item">
            <div class="blog__post-thumb-four">
                <a href="{{ route('blog.show', $blog?->slug) }}" class="shine__animate-link"><img
                        src="{{ asset($blog->image) }}" alt="img"></a>
            </div>
            <div class="blog__post-content-four">
                <a href="{{ route('blogs', ['category' => $blog->category->slug]) }}"
                    class="post-tag-three">{{ $blog?->category?->title }}</a>
                <h2 class="title"><a
                        href="{{ route('blog.show', $blog?->slug) }}">{{ truncate($blog?->title, 50) }}</a></h2>
                <div class="blog__post-meta">
                    <ul class="list-wrap">
                        <li><i class="flaticon-user-1"></i>{{ __('by') }} <a
                                href="javascript:;">{{ truncate($blog->author->name, 14) }}</a></li>
                        <li><i class="flaticon-calendar"></i>{{ formatDate($blog->created_at) }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endforeach
