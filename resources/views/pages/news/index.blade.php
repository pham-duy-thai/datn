@extends('layouts.app')

@section('title', 'Tin tức')

@section('content')
    @php
        $newsImages = ['images/news-image1.jpg', 'images/news-image2.jpg', 'images/news-image3.jpg'];
    @endphp

    <section class="page-hero compact">
        <div class="container">
            <span class="section-kicker">Tin tức</span>
            <h1>Cập nhật sức khỏe</h1>
            <p>Đọc các thông tin mới về dịch vụ, quy trình đặt lịch và chăm sóc sức khỏe.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <form class="filter-bar" method="GET" action="{{ route('news.index') }}">
                <label>
                    <span>Từ khóa</span>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Tiêu đề hoặc nội dung">
                </label>
                <button class="button button-primary" type="submit">Tìm</button>
                <a class="button button-secondary" href="{{ route('news.index') }}">Xóa</a>
            </form>

            <div class="news-grid">
                @forelse ($news as $article)
                    @php
                        $articleImage = $article->thumbnail && file_exists(public_path($article->thumbnail))
                            ? $article->thumbnail
                            : $newsImages[$loop->index % count($newsImages)];
                    @endphp
                    <article class="news-card">
                        <img class="news-thumb" src="{{ asset($articleImage) }}" alt="Ảnh tin tức {{ $article->title }}">
                        <small class="muted">{{ optional($article->published_at)->format('d/m/Y') ?: 'Tin mới' }}</small>
                        <h2>
                            <a href="{{ route('news.show', $article->slug) }}">{{ $article->title }}</a>
                        </h2>
                        <p>{{ str($article->excerpt ?: $article->content)->limit(150) }}</p>
                        <div class="card-actions">
                            <a class="button button-secondary" href="{{ route('news.show', $article->slug) }}">Đọc tiếp</a>
                        </div>
                    </article>
                @empty
                    <div class="empty-state">Không tìm thấy tin tức phù hợp.</div>
                @endforelse
            </div>

            @if (method_exists($news, 'links'))
                <div class="pagination-wrap">
                    {{ $news->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
