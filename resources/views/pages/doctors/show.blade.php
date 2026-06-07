@extends('layouts.app')

@section('title', $doctor->name)

@section('content')
    @php
        $weekdayNames = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7', 7 => 'Chủ nhật'];
        $doctorImage = $doctor->avatar && file_exists(public_path($doctor->avatar)) ? $doctor->avatar : 'images/frontend/team-image1.jpg';
        $serviceImages = ['images/frontend/appointment-image.jpg', 'images/frontend/news-image2.jpg', 'images/frontend/news-image3.jpg'];
    @endphp

    <section class="page-hero">
        <div class="container profile-grid">
            <div class="profile-card">
                <img class="doctor-avatar large" src="{{ asset($doctorImage) }}" alt="Ảnh bác sĩ {{ $doctor->name }}">
                <div>
                    <span class="section-kicker">{{ $doctor->department?->name ?? 'Bác sĩ' }}</span>
                    <h1>{{ $doctor->name }}</h1>
                    <p>{{ $doctor->specialization ?: $doctor->degree ?: 'Chuyên môn đang cập nhật.' }}</p>
                </div>
            </div>

            <div class="profile-summary">
                <div>
                    <span>{{ $doctor->experience_years ?? 0 }}</span>
                    <strong>Năm kinh nghiệm</strong>
                </div>
                <div>
                    <span>{{ number_format((float) $doctor->consultation_fee) }} VNĐ</span>
                    <strong>Phí tư vấn</strong>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container split-layout">
            <div class="content-panel">
                <img class="panel-image" src="{{ asset($doctorImage) }}" alt="Ảnh bác sĩ {{ $doctor->name }}">
                <span class="section-kicker">Thông tin chuyên môn</span>
                <h2>Giới thiệu</h2>
                <p>{{ $doctor->bio ?: 'Thông tin giới thiệu bác sĩ đang được cập nhật.' }}</p>

                <div class="detail-list">
                    <div>
                        <span>Học vị</span>
                        <strong>{{ $doctor->degree ?: 'Đang cập nhật' }}</strong>
                    </div>
                    <div>
                        <span>Điện thoại</span>
                        <strong>{{ $doctor->phone ?: 'Đang cập nhật' }}</strong>
                    </div>
                    <div>
                        <span>Thư điện tử</span>
                        <strong>{{ $doctor->email ?: 'Đang cập nhật' }}</strong>
                    </div>
                </div>
            </div>

            <aside class="booking-callout">
                <span class="section-kicker">Đặt lịch</span>
                <h2>Đặt lịch với {{ $doctor->name }}</h2>
                <p>Chọn ngày giờ mong muốn và gửi thông tin để bệnh viện xác nhận lịch hẹn.</p>
                <div class="hero-actions">
                    <a class="button button-primary" href="{{ route('appointments.create', ['doctor_id' => $doctor->id]) }}">Đặt lịch khám</a>
                    <a class="button button-secondary" href="{{ route('doctors.index') }}">Danh sách bác sĩ</a>
                </div>
            </aside>
        </div>
    </section>

    <section class="section section-muted">
        <div class="container split-layout">
            <div class="content-panel">
                <div class="section-heading-row">
                    <div>
                        <span class="section-kicker">Lịch làm việc</span>
                        <h2>Khung giờ công khai</h2>
                    </div>
                </div>

                <div class="schedule-list">
                    @forelse ($doctor->schedules as $schedule)
                        <div class="schedule-item">
                            <strong>{{ $weekdayNames[$schedule->weekday] ?? 'Thứ '.$schedule->weekday }}</strong>
                            <small>{{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }}</small>
                            <small>{{ $schedule->room ?: 'Chưa gắn phòng' }} - {{ $schedule->max_patients }} bệnh nhân</small>
                        </div>
                    @empty
                        <div class="empty-state">Bác sĩ chưa có lịch làm việc công khai.</div>
                    @endforelse
                </div>
            </div>

            <aside class="content-panel">
                <div class="section-heading-row">
                    <div>
                        <span class="section-kicker">Dịch vụ</span>
                        <h2>Dịch vụ liên quan</h2>
                    </div>
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
                                <small>{{ $service->duration_minutes ?: 30 }} phút</small>
                            </span>
                            <strong>{{ number_format((float) $service->price) }} VNĐ</strong>
                        </a>
                    @empty
                        <div class="empty-state">Chưa có dịch vụ liên quan.</div>
                    @endforelse
                </div>
            </aside>
        </div>
    </section>
@endsection
