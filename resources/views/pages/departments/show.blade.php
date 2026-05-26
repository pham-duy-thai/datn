@extends('layouts.app')

@section('title', $department->name)

@section('content')
    @php
        $departmentImage = $department->image && file_exists(public_path($department->image)) ? $department->image : 'images/about-bg.jpg';
        $doctorImages = ['images/team-image1.jpg', 'images/team-image2.jpg', 'images/team-image3.jpg'];
        $serviceImages = ['images/appointment-image.jpg', 'images/news-image2.jpg', 'images/news-image3.jpg'];
    @endphp

    <section class="page-hero compact">
        <div class="container">
            <span class="section-kicker">Chuyên khoa</span>
            <h1>{{ $department->name }}</h1>
            <p>{{ $department->description ?: 'Thông tin chi tiết của chuyên khoa đang được cập nhật.' }}</p>
        </div>
    </section>

    <section class="section">
        <div class="container split-layout">
            <div class="content-panel">
                <img class="panel-image" src="{{ asset($departmentImage) }}" alt="Ảnh chuyên khoa {{ $department->name }}">
                <div class="section-heading-row">
                    <div>
                        <span class="section-kicker">Bác sĩ</span>
                        <h2>Bác sĩ thuộc khoa</h2>
                    </div>
                    <a class="text-link" href="{{ route('doctors.index', ['department_id' => $department->id]) }}">Lọc danh sách</a>
                </div>

                <div class="stack-list">
                    @forelse ($department->doctors as $doctor)
                        @php
                            $doctorImage = $doctor->avatar && file_exists(public_path($doctor->avatar))
                                ? $doctor->avatar
                                : $doctorImages[$loop->index % count($doctorImages)];
                        @endphp
                        <a class="doctor-row" href="{{ route('doctors.show', $doctor) }}">
                            <img class="doctor-avatar small" src="{{ asset($doctorImage) }}" alt="Ảnh bác sĩ {{ $doctor->name }}">
                            <span>
                                <strong>{{ $doctor->name }}</strong>
                                <small>{{ $doctor->specialization ?: $doctor->degree ?: 'Đang cập nhật chuyên môn.' }}</small>
                            </span>
                            <small>{{ $doctor->experience_years ?? 0 }} năm kinh nghiệm</small>
                        </a>
                    @empty
                        <div class="empty-state">Khoa này chưa có bác sĩ đang hoạt động.</div>
                    @endforelse
                </div>
            </div>

            <aside class="content-panel">
                <div class="section-heading-row">
                    <div>
                        <span class="section-kicker">Dịch vụ</span>
                        <h2>Dịch vụ của khoa</h2>
                    </div>
                    <a class="text-link" href="{{ route('services.index', ['department_id' => $department->id]) }}">Xem dịch vụ</a>
                </div>

                <div class="stack-list">
                    @forelse ($department->services as $service)
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
                        <div class="empty-state">Khoa này chưa có dịch vụ đang hoạt động.</div>
                    @endforelse
                </div>
            </aside>
        </div>
    </section>
@endsection
