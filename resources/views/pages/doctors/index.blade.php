@extends('layouts.app')

@section('title', 'Bác sĩ')

@section('content')
    @php
        $doctorImages = ['images/team-image1.jpg', 'images/team-image2.jpg', 'images/team-image3.jpg'];
    @endphp

    <section class="page-hero compact">
        <div class="container">
            <span class="section-kicker">Bác sĩ</span>
            <h1>Tìm bác sĩ phù hợp</h1>
            <p>Lọc theo tên, chuyên môn hoặc khoa để đặt lịch khám nhanh hơn.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <form class="filter-bar" method="GET" action="{{ route('doctors.index') }}">
                <label>
                    <span>Từ khóa</span>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Tên, chuyên môn, học vị">
                </label>

                <label>
                    <span>Khoa</span>
                    <select name="department_id">
                        <option value="">Tất cả khoa</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <button class="button button-primary" type="submit">Lọc</button>
                <a class="button button-secondary" href="{{ route('doctors.index') }}">Xóa</a>
            </form>

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
                                <h2>
                                    <a href="{{ route('doctors.show', $doctor) }}">{{ $doctor->name }}</a>
                                </h2>
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
                    <div class="empty-state">Không tìm thấy bác sĩ phù hợp.</div>
                @endforelse
            </div>

            @if (method_exists($doctors, 'links'))
                <div class="pagination-wrap">
                    {{ $doctors->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
