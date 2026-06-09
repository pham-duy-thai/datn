@extends('layouts.app')

@section('title', 'Hồ sơ bệnh nhân của tôi')

@section('content')
    <section class="page-hero compact">
        <div class="container">
            <span class="section-kicker">Dành cho bác sĩ</span>
            <h1>Hồ sơ bệnh nhân của tôi</h1>
            <p>Quản lý các bệnh án thuộc phạm vi phụ trách của bác sĩ đang đăng nhập.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-heading-row">
                <form class="doctor-record-search" method="GET" action="{{ route('doctor.records.index') }}">
                    <input type="search" name="search" value="{{ request('search') }}"
                        placeholder="Tìm bệnh nhân, email, số điện thoại, triệu chứng, chẩn đoán...">
                    <button class="button button-secondary" type="submit">Tìm kiếm</button>
                    @if (request()->filled('search'))
                        <a class="button button-secondary" href="{{ route('doctor.records.index') }}">Xóa lọc</a>
                    @endif
                </form>
                <a class="button button-primary" href="{{ route('doctor.records.create') }}">Tạo hồ sơ</a>
            </div>

            <div class="doctor-record-grid">
                @forelse ($records as $record)
                    <article class="content-panel doctor-record-card">
                        <div class="doctor-record-card-heading">
                            <div>
                                <span class="section-kicker">Hồ sơ #{{ $record->id }}</span>
                                <h2>{{ $record->user?->name ?? $record->appointment?->patient_name ?? 'Chưa rõ bệnh nhân' }}</h2>
                            </div>
                            <span class="record-date">{{ $record->examined_at?->format('d/m/Y') ?? 'Chưa có ngày khám' }}</span>
                        </div>

                        <div class="detail-list doctor-record-details">
                            <div>
                                <span>Liên hệ</span>
                                <strong>{{ $record->user?->phone ?? $record->appointment?->patient_phone ?? 'Chưa cập nhật' }}</strong>
                            </div>
                            <div>
                                <span>Dịch vụ</span>
                                <strong>{{ $record->appointment?->service?->name ?? 'Chưa gắn dịch vụ' }}</strong>
                            </div>
                        </div>

                        <div class="doctor-record-summary">
                            <strong>Chẩn đoán</strong>
                            <p>{{ str($record->diagnosis ?: 'Chưa cập nhật chẩn đoán.')->limit(150) }}</p>
                        </div>

                        <div class="card-actions">
                            <a class="button button-primary" href="{{ route('doctor.records.edit', $record) }}">Xem và cập nhật</a>
                        </div>
                    </article>
                @empty
                    <div class="empty-state">Không tìm thấy hồ sơ bệnh nhân thuộc bác sĩ này.</div>
                @endforelse
            </div>

            <div class="pagination-wrap">{{ $records->links() }}</div>
        </div>
    </section>
@endsection
