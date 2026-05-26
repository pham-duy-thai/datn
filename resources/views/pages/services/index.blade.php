@extends('layouts.app')

@section('title', 'Dịch vụ')

@section('content')
    @php
        $serviceImages = ['images/appointment-image.jpg', 'images/news-image2.jpg', 'images/news-image3.jpg'];
    @endphp

    <section class="page-hero compact">
        <div class="container">
            <span class="section-kicker">Dịch vụ</span>
            <h1>Dịch vụ khám và tư vấn</h1>
            <p>Tra cứu dịch vụ theo khoa, thời lượng và chi phí dự kiến.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <form class="filter-bar" method="GET" action="{{ route('services.index') }}">
                <label>
                    <span>Từ khóa</span>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Tên dịch vụ hoặc mô tả">
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
                <a class="button button-secondary" href="{{ route('services.index') }}">Xóa</a>
            </form>

            <div class="content-panel">
                <div class="section-heading-row">
                    <div>
                        <span class="section-kicker">Danh sách</span>
                        <h2>Dịch vụ phù hợp</h2>
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
                                <small>{{ str($service->description ?: 'Đang cập nhật mô tả dịch vụ.')->limit(100) }}</small>
                            </span>
                            <span>
                                <small>{{ $service->department?->name ?? 'Chưa gắn khoa' }}</small>
                                <small>{{ $service->duration_minutes ?: 30 }} phút</small>
                            </span>
                            <strong>{{ number_format((float) $service->price) }} VNĐ</strong>
                        </a>
                    @empty
                        <div class="empty-state">Không tìm thấy dịch vụ phù hợp.</div>
                    @endforelse
                </div>
            </div>

            @if (method_exists($services, 'links'))
                <div class="pagination-wrap">
                    {{ $services->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
