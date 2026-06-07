@extends('layouts.app')

@section('title', 'Hệ thống chăm sóc bệnh viện')

@section('content')
    @php
        $primaryBanner = $banners->first();
        $bannerImage = $primaryBanner?->image && file_exists(public_path($primaryBanner->image)) ? $primaryBanner->image : 'images/frontend/slider1.jpg';
        $departmentImages = ['images/frontend/about-bg.jpg', 'images/frontend/slider2.jpg', 'images/frontend/slider3.jpg'];
        $doctorImages = ['images/frontend/team-image1.jpg', 'images/frontend/team-image2.jpg', 'images/frontend/team-image3.jpg'];
        $serviceImages = ['images/frontend/appointment-image.jpg', 'images/frontend/news-image2.jpg', 'images/frontend/news-image3.jpg'];
        $newsImages = ['images/frontend/news-image1.jpg', 'images/frontend/news-image2.jpg', 'images/frontend/news-image3.jpg'];
    @endphp

    <section class="hero-section">
        <div class="container hero-grid">
            <div class="hero-copy">
                <span class="section-kicker">Hệ thống đặt lịch khám</span>
                <h1>{{ $primaryBanner?->title ?? 'Đặt lịch khám và quản lý chăm sóc sức khỏe' }}</h1>
                <p>{{ $primaryBanner?->subtitle ?? 'Tìm chuyên khoa, chọn bác sĩ, đặt lịch khám và theo dõi kết quả khám trong một quy trình rõ ràng.' }}</p>

                <div class="hero-actions">
                    <a class="button button-primary" href="{{ route('appointments.create') }}">Đặt lịch khám</a>
                    <a class="button button-secondary" href="{{ route('doctors.index') }}">Tìm bác sĩ</a>
                </div>
            </div>

            <div class="hero-panel hero-media-panel" aria-label="Hình ảnh hệ thống đặt lịch khám">
                <img class="hero-image" src="{{ asset($bannerImage) }}" alt="Hình ảnh hệ thống đặt lịch khám">
                <div class="hero-welcome">
                    <span>Welcome to</span>
                    <strong>Minh An Hospital</strong>
                    <p>Đồng hành cùng sức khỏe của bạn với quy trình đặt lịch khám nhanh chóng và rõ ràng.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-heading-row">
                <div>
                    <span class="section-kicker">Chuyên khoa</span>
                    <h2>Chọn đúng nơi tiếp nhận</h2>
                </div>
                <a class="text-link" href="{{ route('departments.index') }}">Xem tất cả</a>
            </div>

            <div class="card-grid">
                @forelse ($departments as $department)
                    @php
                        $departmentImage = $department->image && file_exists(public_path($department->image))
                            ? $department->image
                            : $departmentImages[$loop->index % count($departmentImages)];
                    @endphp
                    <article class="info-card">
                        <div class="card-visual">
                            <img src="{{ asset($departmentImage) }}" alt="Ảnh chuyên khoa {{ $department->name }}">
                        </div>
                        <div class="card-body">
                            <h3>
                                <a href="{{ route('departments.show', $department->slug) }}">{{ $department->name }}</a>
                            </h3>
                            <p>{{ str($department->description ?: 'Đang cập nhật mô tả chuyên khoa.')->limit(110) }}</p>
                            <div class="card-meta">
                                <span>{{ $department->doctors_count ?? 0 }} bác sĩ</span>
                                <span>{{ $department->services_count ?? 0 }} dịch vụ</span>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="empty-state">Chưa có chuyên khoa đang hoạt động.</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="section section-muted">
        <div class="container">
            <div class="section-heading-row">
                <div>
                    <span class="section-kicker">Bác sĩ</span>
                    <h2>Bác sĩ đang tiếp nhận lịch</h2>
                </div>
                <a class="text-link" href="{{ route('doctors.index') }}">Xem tất cả</a>
            </div>

            <div class="doctor-grid">
                @forelse ($doctors as $doctor)
                    @php
                        $doctorImage = $doctor->avatar && file_exists(public_path($doctor->avatar))
                            ? $doctor->avatar
                            : $doctorImages[$loop->index % count($doctorImages)];
                    @endphp
                    <article class="doctor-card" tabindex="0">
                        <div class="profile-card">
                            <img class="doctor-avatar" src="{{ asset($doctorImage) }}" alt="Ảnh bác sĩ {{ $doctor->name }}">
                            <div>
                                <h3>
                                    <a href="{{ route('doctors.show', $doctor) }}">{{ $doctor->name }}</a>
                                </h3>
                                <p>{{ $doctor->department?->name ?? 'Chưa gắn khoa' }}</p>
                            </div>
                        </div>
                        <p>{{ $doctor->specialization ?: $doctor->degree ?: 'Đang cập nhật chuyên môn.' }}</p>
                        <div class="card-meta">
                            <span>{{ $doctor->experience_years ?? 0 }} năm kinh nghiệm</span>
                            <span>{{ number_format((float) $doctor->consultation_fee) }} VNĐ</span>
                        </div>
                        <div class="card-actions">
                            <a class="button button-primary" href="{{ route('appointments.create', ['doctor_id' => $doctor->id]) }}">Đặt lịch</a>
                            <a class="button button-secondary" href="{{ route('doctors.show', $doctor) }}">Chi tiết</a>
                        </div>
                        <div class="doctor-hover-panel" aria-hidden="true">
                            <span>Bác sĩ</span>
                            <strong>{{ $doctor->name }}</strong>
                            <p>Chuyên môn: {{ $doctor->specialization ?: 'Đang cập nhật' }}</p>
                            <p>Học vị: {{ $doctor->degree ?: 'Đang cập nhật' }}</p>
                        </div>
                    </article>
                @empty
                    <div class="empty-state">Chưa có bác sĩ đang hoạt động.</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container split-layout">
            <div class="content-panel">
                <div class="section-heading-row">
                    <div>
                        <span class="section-kicker">Dịch vụ</span>
                        <h2>Dịch vụ phổ biến</h2>
                    </div>
                    <a class="text-link" href="{{ route('services.index') }}">Xem dịch vụ</a>
                </div>

                <div class="stack-list">
                    @forelse ($services as $service)
                        @php
                            $serviceImage = $service->image && file_exists(public_path($service->image))
                                ? $service->image
                                : $serviceImages[$loop->index % count($serviceImages)];
                        @endphp
                        <a class="service-row" href="{{ route('services.show', $service->slug) }}">
                            <img class="row-thumb" src="{{ asset($serviceImage) }}" alt="Ảnh dịch vụ {{ $service->name }}">
                            <span>
                                <strong>{{ $service->name }}</strong>
                                <small>{{ $service->department?->name ?? 'Chưa gắn khoa' }}</small>
                            </span>
                            <strong>{{ number_format((float) $service->price) }} VNĐ</strong>
                        </a>
                    @empty
                        <div class="empty-state">Chưa có dịch vụ đang hoạt động.</div>
                    @endforelse
                </div>
            </div>

            <aside class="booking-callout">
                <span class="section-kicker">Thao tác nhanh</span>
                <h2>Gửi lịch hẹn ngay khi đã chọn bác sĩ hoặc dịch vụ</h2>
                <p>Biểu mẫu đặt lịch sẽ ghi nhận thông tin người khám, ngày giờ mong muốn và nhu cầu khám để bệnh viện xử lý.</p>
                <div class="hero-actions">
                    <a class="button button-primary" href="{{ route('appointments.create') }}">Đặt lịch khám</a>
                    <a class="button button-secondary" href="{{ route('contact.create') }}">Liên hệ tư vấn</a>
                </div>
            </aside>
        </div>
    </section>

    <section class="section section-muted">
        <div class="container">
            <div class="section-heading-row">
                <div>
                    <span class="section-kicker">Tin tức</span>
                    <h2>Cập nhật mới</h2>
                </div>
                <a class="text-link" href="{{ route('news.index') }}">Xem tin tức</a>
            </div>

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
                        <h3>
                            <a href="{{ route('news.show', $article->slug) }}">{{ $article->title }}</a>
                        </h3>
                        <p>{{ str($article->excerpt ?: $article->content)->limit(130) }}</p>
                        <div class="card-actions">
                            <a class="text-link" href="{{ route('news.show', $article->slug) }}">Đọc tiếp</a>
                        </div>
                    </article>
                @empty
                    <div class="empty-state">Chưa có tin tức đã xuất bản.</div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
