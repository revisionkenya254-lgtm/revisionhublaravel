@props(['total' => 3])
@php
    $featuredBlogs = getFeaturedBlogs((int) $total);
@endphp
<div class="col-lg-6">
    @php
        $firstBlog = $featuredBlogs->first();
    @endphp
    <div class="blog__post-item-two yoga_featured_blog">
        <div class="blog__post-thumb-two">
            <a href="{{ route('blog.show', $firstBlog?->slug) }}">
                <img src="{{ asset($firstBlog?->image) }}" alt="img">
            </a>
        </div>
        <div class="blog__post-content-two">
            <a href="{{ route('blogs', ['category' => $firstBlog?->category?->slug]) }}" class="post-tag-two">
                {{ $firstBlog?->category?->title }}
            </a>
            <div class="blog__post-meta blog__post-meta-two">
                <ul class="list-wrap">
                    <li><i class="flaticon-calendar"></i>{{ formatDate($firstBlog?->created_at) }}</li>
                    <li><i class="flaticon-user-1"></i>{{ __('by') }}
                        <a href="{{ route('blog.show', $firstBlog?->slug) }}">
                            {{ truncate($firstBlog?->author?->name, 14) }}
                        </a>
                    </li>
                </ul>
            </div>
            <h2 class="title">
                <a href="{{ route('blog.show', $firstBlog?->slug) }}">
                    {{ $firstBlog?->title }}
                </a>
            </h2>
        </div>
    </div>
</div>
<div class="col-lg-6">
    @foreach ($featuredBlogs->slice(1, 2) as $blog)
        <div class="blog__post-item-three shine__animate-item">
            <div class="blog__post-thumb-three">
                <a href="{{ route('blog.show', $blog?->slug) }}" class="shine__animate-link"><img
                        src="{{ asset($blog?->image) }}" alt="img"></a>
            </div>
            <div class="blog__post-content-three">
                <a href="{{ route('blogs', ['category' => $blog?->category?->slug]) }}"
                    class="post-tag-two">{{ $blog?->category?->title }}</a>
                <div class="blog__post-meta">
                    <ul class="list-wrap">
                        <li><i class="flaticon-calendar"></i>{{ formatDate($blog->created_at) }}</li>
                        <li><i class="flaticon-user-1"></i>{{ __('by') }} <a
                                href="{{ route('blog.show', $blog?->slug) }}">{{ truncate($blog?->author?->name, 14) }}</a>
                        </li>
                    </ul>
                </div>
                <h2 class="title"><a href="{{ route('blog.show', $blog?->slug) }}">{{ $blog?->category?->title }}</a>
                </h2>
            </div>
        </div>
    @endforeach
</div>
