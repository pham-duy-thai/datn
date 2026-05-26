@extends('layouts.app')

@section('title', $article->title)

@section('content')
    @php
        $articleImage = $article->thumbnail && file_exists(public_path($article->thumbnail)) ? $article->thumbnail : 'images/news-image1.jpg';
        $newsImages = ['images/news-image1.jpg', 'images/news-image2.jpg', 'images/news-image3.jpg'];
    @endphp

    <section class="page-hero compact">
        <div class="container article-heading">
            <span class="section-kicker">Tin tức</span>
            <h1>{{ $article->title }}</h1>
            <p>{{ $article->excerpt ?: 'Thông tin cập nhật từ hệ thống chăm sóc bệnh viện.' }}</p>
        </div>
    </section>

    <section class="section" id="news-detail">
        <div class="container article-layout">
            <article class="article-content">
                <img class="article-cover" src="{{ asset($articleImage) }}" alt="Ảnh tin tức {{ $article->title }}">
                <div class="article-meta">
                    {{ optional($article->published_at)->format('d/m/Y') ?: 'Tin tức' }}
                    @if ($article->user)
                        - {{ $article->user->name }}
                    @endif
                </div>

                @if ($article->excerpt)
                    <p class="lead">{{ $article->excerpt }}</p>
                    <hr>
                @endif

                <div>
                    {!! nl2br(e($article->content)) !!}
                </div>
            </article>

            <aside class="content-panel">
                <span class="section-kicker">Liên quan</span>
                <h2>Bài viết liên quan</h2>

                <div class="stack-list">
                    @forelse ($relatedNews as $related)
                        @php
                            $relatedImage = $related->thumbnail && file_exists(public_path($related->thumbnail))
                                ? $related->thumbnail
                                : $newsImages[$loop->index % count($newsImages)];
                        @endphp
                        <a class="service-row" href="{{ route('news.show', $related->slug) }}">
                            <img class="row-thumb" src="{{ asset($relatedImage) }}" alt="Ảnh tin tức {{ $related->title }}">
                            <span>
                                <strong>{{ $related->title }}</strong>
                                <small>{{ optional($related->published_at)->format('d/m/Y') ?: 'Tin tức' }}</small>
                            </span>
                        </a>
                    @empty
                        <div class="empty-state">Chưa có bài viết liên quan.</div>
                    @endforelse
                </div>
            </aside>
        </div>
    </section>
@endsection
