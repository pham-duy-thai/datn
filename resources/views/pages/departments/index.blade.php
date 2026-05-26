@extends('layouts.app')

@section('title', 'Chuyên khoa')

@section('content')
    @php
        $departmentImages = ['images/about-bg.jpg', 'images/slider2.jpg', 'images/slider3.jpg'];
    @endphp

    <section class="page-hero compact">
        <div class="container">
            <span class="section-kicker">Chuyên khoa</span>
            <h1>Chuyên khoa đang tiếp nhận</h1>
            <p>Lọc và xem thông tin các khoa đang hoạt động trong hệ thống.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <form class="filter-bar" method="GET" action="{{ route('departments.index') }}">
                <label>
                    <span>Tìm kiếm</span>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm theo tên hoặc mô tả">
                </label>
                <button class="button button-primary" type="submit">Lọc</button>
                <a class="button button-secondary" href="{{ route('departments.index') }}">Xóa</a>
            </form>

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
                            <h2>
                                <a href="{{ route('departments.show', $department->slug) }}">{{ $department->name }}</a>
                            </h2>
                            <p>{{ str($department->description ?: 'Đang cập nhật mô tả chuyên khoa.')->limit(140) }}</p>
                            <div class="card-meta">
                                <span>{{ $department->doctors_count ?? 0 }} bác sĩ</span>
                                <span>{{ $department->services_count ?? 0 }} dịch vụ</span>
                            </div>
                            <div class="card-actions">
                                <a class="button button-secondary" href="{{ route('departments.show', $department->slug) }}">Xem chi tiết</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="empty-state">Không tìm thấy chuyên khoa phù hợp.</div>
                @endforelse
            </div>

            @if (method_exists($departments, 'links'))
                <div class="pagination-wrap">
                    {{ $departments->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
