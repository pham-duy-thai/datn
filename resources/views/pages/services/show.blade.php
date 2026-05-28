@extends('layouts.app')

@section('title', $service->name)

@section('content')
    @php
        $serviceImage = $service->image && file_exists(public_path($service->image)) ? $service->image : 'images/appointment-image.jpg';
        $doctorImages = ['images/team-image1.jpg', 'images/team-image2.jpg', 'images/team-image3.jpg'];
    @endphp

    <section class="page-hero compact">
        <div class="container">
            <span class="section-kicker">Dịch vụ</span>
            <h1>{{ $service->name }}</h1>
            <p>{{ $service->description ?: 'Thông tin dịch vụ đang được cập nhật.' }}</p>
        </div>
    </section>

    <section class="section">
        <div class="container split-layout">
            <div class="content-panel">
                <img class="panel-image" src="{{ asset($serviceImage) }}" alt="Ảnh dịch vụ {{ $service->name }}">
                <span class="section-kicker">Thông tin dịch vụ</span>
                <h2>Tổng quan</h2>
                <p>{{ $service->description ?: 'Thông tin dịch vụ đang được cập nhật.' }}</p>

                <div class="detail-list">
                    <div>
                        <span>Khoa</span>
                        <strong>{{ $service->department?->name ?? 'Chưa gắn khoa' }}</strong>
                    </div>
                    <div>
                        <span>Thời lượng</span>
                        <strong>{{ $service->duration_minutes ?: 30 }} phút</strong>
                    </div>
                    <div>
                        <span>Giá</span>
                        <strong>{{ number_format((float) $service->price) }} VNĐ</strong>
                    </div>
                </div>

                <div class="hero-actions">
                    <a class="button button-primary" href="{{ route('appointments.create', ['service_id' => $service->id]) }}">Đặt lịch dịch vụ</a>
                    @if ($service->department)
                        <a class="button button-secondary" href="{{ route('departments.show', $service->department->slug) }}">Xem khoa</a>
                    @endif
                </div>
            </div>

            <aside class="booking-callout">
                <img class="panel-image" src="{{ asset('images/appointment-image.jpg') }}" alt="Ảnh đặt lịch dịch vụ">
                <span class="section-kicker">Đặt lịch</span>
                <h2>Cần tư vấn trước khi chọn?</h2>
                <p>Gửi liên hệ nếu cần thêm thông tin về quy trình, chi phí hoặc bác sĩ phù hợp.</p>
                <div class="hero-actions">
                    <a class="button button-primary" href="{{ route('appointments.create', ['service_id' => $service->id]) }}">Đặt lịch</a>
                    <a class="button button-secondary" href="{{ route('contact.create') }}">Liên hệ</a>
                </div>
            </aside>
        </div>
    </section>

    <section class="section section-muted">
        <div class="container">
            <div class="section-heading-row">
                <div>
                    <span class="section-kicker">Bác sĩ</span>
                    <h2>Bác sĩ liên quan</h2>
                </div>
            </div>

            <div class="doctor-grid">
                @forelse ($service->department?->doctors ?? collect() as $doctor)
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
                                <p>{{ $doctor->specialization ?: $doctor->degree ?: 'Đang cập nhật chuyên môn.' }}</p>
                            </div>
                        </div>
                        <div class="card-actions">
                            <a class="button button-primary" href="{{ route('appointments.create', ['doctor_id' => $doctor->id, 'service_id' => $service->id]) }}">Đặt lịch</a>
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
                    <div class="empty-state">Chưa có bác sĩ liên quan.</div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
