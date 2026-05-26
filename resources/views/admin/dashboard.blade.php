@extends('layouts.admin')

@section('title', 'Tổng quan quản trị')

@section('content')
    @php
        $appointmentLabels = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'cancelled' => 'Đã hủy',
            'completed' => 'Hoàn tất',
        ];

        $contactLabels = [
            'new' => 'Mới',
            'read' => 'Đã đọc',
            'replied' => 'Đã phản hồi',
        ];
    @endphp

    <section class="admin-hero" style="background-image: linear-gradient(90deg, rgba(18, 24, 38, .86), rgba(18, 24, 38, .42)), url('{{ asset('images/about-bg.jpg') }}')">
        <div>
            <span>Quản trị</span>
            <h1>Tổng quan hệ thống bệnh viện</h1>
            <p>Theo dõi dữ liệu đặt lịch, liên hệ, bác sĩ, chuyên khoa và nội dung công khai.</p>
        </div>
        <a class="admin-primary-link" href="{{ route('admin.appointments.index') }}">Xem lịch hẹn</a>
    </section>

    <section class="stat-grid">
        <article class="stat-card">
            <i class="fa fa-users"></i>
            <span>{{ $stats['users'] }}</span>
            <strong>Người dùng</strong>
        </article>
        <article class="stat-card">
            <i class="fa fa-user-md"></i>
            <span>{{ $stats['doctors'] }}</span>
            <strong>Bác sĩ</strong>
        </article>
        <article class="stat-card">
            <i class="fa fa-hospital-o"></i>
            <span>{{ $stats['departments'] }}</span>
            <strong>Chuyên khoa</strong>
        </article>
        <article class="stat-card">
            <i class="fa fa-stethoscope"></i>
            <span>{{ $stats['services'] }}</span>
            <strong>Dịch vụ</strong>
        </article>
        <article class="stat-card">
            <i class="fa fa-calendar"></i>
            <span>{{ $stats['appointments'] }}</span>
            <strong>Lịch hẹn</strong>
        </article>
        <article class="stat-card">
            <i class="fa fa-envelope"></i>
            <span>{{ $stats['contacts'] }}</span>
            <strong>Liên hệ</strong>
        </article>
    </section>

    <section class="admin-grid two-columns">
        <article class="admin-panel">
            <div class="panel-heading">
                <div>
                    <span>Trạng thái</span>
                    <h2>Lịch hẹn</h2>
                </div>
                <a href="{{ route('admin.appointments.index') }}">Quản lý</a>
            </div>

            <div class="status-grid">
                @foreach ($appointmentLabels as $status => $label)
                    <a class="status-card {{ $status }}" href="{{ route('admin.appointments.index', ['status' => $status]) }}">
                        <span>{{ $label }}</span>
                        <strong>{{ $statusCounts[$status] ?? 0 }}</strong>
                    </a>
                @endforeach
            </div>
        </article>

        <article class="admin-panel visual-panel">
            <img src="{{ asset('images/appointment-image.jpg') }}" alt="Ảnh quản lý lịch hẹn">
            <div>
                <span>Hôm nay</span>
                <h2>{{ $stats['todayAppointments'] }} lịch hẹn</h2>
                <p>Dùng trang quản lý lịch hẹn để xác nhận, hoàn tất hoặc hủy lịch.</p>
            </div>
        </article>
    </section>

    <section class="admin-grid two-columns">
        <article class="admin-panel">
            <div class="panel-heading">
                <div>
                    <span>Mới nhất</span>
                    <h2>Lịch hẹn gần đây</h2>
                </div>
                <a href="{{ route('admin.appointments.index') }}">Xem tất cả</a>
            </div>

            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Người khám</th>
                            <th>Bác sĩ</th>
                            <th>Thời gian</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($latestAppointments as $appointment)
                            <tr>
                                <td>
                                    <strong>{{ $appointment->patient_name }}</strong>
                                    <small>{{ $appointment->patient_phone }}</small>
                                </td>
                                <td>{{ $appointment->doctor?->name ?? 'Chưa chọn' }}</td>
                                <td>{{ optional($appointment->appointment_date)->format('d/m/Y') }} {{ substr($appointment->appointment_time, 0, 5) }}</td>
                                <td><span class="badge-soft {{ $appointment->status }}">{{ $appointmentLabels[$appointment->status] ?? $appointment->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">Chưa có lịch hẹn.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="admin-panel">
            <div class="panel-heading">
                <div>
                    <span>Mới nhất</span>
                    <h2>Liên hệ gần đây</h2>
                </div>
                <a href="{{ route('admin.contacts.index') }}">Xem tất cả</a>
            </div>

            <div class="contact-list">
                @forelse ($latestContacts as $contact)
                    <a href="{{ route('admin.contacts.index', ['search' => $contact->email]) }}">
                        <span>
                            <strong>{{ $contact->name }}</strong>
                            <small>{{ $contact->subject ?: 'Không có chủ đề' }}</small>
                        </span>
                        <em>{{ $contactLabels[$contact->status] ?? $contact->status }}</em>
                    </a>
                @empty
                    <div class="empty-admin">Chưa có liên hệ.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="admin-grid two-columns">
        <article class="admin-panel">
            <div class="panel-heading">
                <div>
                    <span>Tài khoản</span>
                    <h2>Người dùng mới</h2>
                </div>
            </div>

            <div class="contact-list">
                @foreach ($latestUsers as $user)
                    <div class="user-row">
                        <span class="avatar-dot">{{ str($user->name)->substr(0, 1)->upper() }}</span>
                        <span>
                            <strong>{{ $user->name }}</strong>
                            <small>{{ $user->email }}</small>
                        </span>
                        <em>{{ $user->role }}</em>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="admin-panel">
            <div class="panel-heading">
                <div>
                    <span>Nội dung</span>
                    <h2>Tin tức mới</h2>
                </div>
            </div>

            <div class="contact-list">
                @forelse ($latestNews as $article)
                    <a href="{{ route('news.show', $article->slug) }}">
                        <span>
                            <strong>{{ $article->title }}</strong>
                            <small>{{ optional($article->published_at)->format('d/m/Y') ?: 'Chưa xuất bản' }}</small>
                        </span>
                        <em>{{ $article->status }}</em>
                    </a>
                @empty
                    <div class="empty-admin">Chưa có tin tức.</div>
                @endforelse
            </div>
        </article>
    </section>
@endsection
