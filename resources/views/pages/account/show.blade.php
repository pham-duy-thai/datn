@extends('layouts.app')

@section('title', 'Tài khoản cá nhân')

@section('content')
    @php
        $roleLabels = [
            'admin' => 'Admin',
            'doctor' => 'Bác sĩ',
            'patient' => 'Bệnh nhân',
            'receptionist' => 'Nhân viên lễ tân',
        ];

        $genderLabels = [
            'male' => 'Nam',
            'female' => 'Nữ',
            'other' => 'Khác',
        ];

        $appointmentLabels = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'completed' => 'Hoàn tất',
            'cancelled' => 'Đã hủy',
        ];
    @endphp

    <section class="page-hero compact account-hero">
        <div class="container">
            <span class="section-kicker">Tài khoản</span>
            <h1>Thông tin cá nhân</h1>
            <p>Xem thông tin tài khoản, Gmail đăng ký, lịch khám gần đây và đăng xuất khỏi hệ thống.</p>
        </div>
    </section>

    <section class="section">
        <div class="container account-layout">
            <article class="content-panel account-profile-panel">
                <div class="profile-card">
                    @if ($user->avatar)
                        <img class="doctor-avatar large" src="{{ asset($user->avatar) }}" alt="Ảnh đại diện {{ $user->name }}">
                    @else
                        <span class="doctor-avatar large">{{ str($user->name)->substr(0, 1)->upper() }}</span>
                    @endif
                    <div>
                        <span class="section-kicker">{{ $roleLabels[$user->role] ?? $user->role }}</span>
                        <h2>{{ $user->name }}</h2>
                        <p>{{ $user->email }}</p>
                    </div>
                </div>

                <div class="detail-list account-detail-list">
                    <div>
                        <span>Gmail đăng ký</span>
                        <strong>{{ $user->email }}</strong>
                    </div>
                    <div>
                        <span>Số điện thoại</span>
                        <strong>{{ $user->phone ?: 'Chưa cập nhật' }}</strong>
                    </div>
                    <div>
                        <span>Giới tính</span>
                        <strong>{{ $genderLabels[$user->gender] ?? 'Chưa cập nhật' }}</strong>
                    </div>
                    <div>
                        <span>Ngày sinh</span>
                        <strong>{{ $user->date_of_birth?->format('d/m/Y') ?? 'Chưa cập nhật' }}</strong>
                    </div>
                    <div>
                        <span>Địa chỉ</span>
                        <strong>{{ $user->address ?: 'Chưa cập nhật' }}</strong>
                    </div>
                    <div>
                        <span>Ngày tạo tài khoản</span>
                        <strong>{{ $user->created_at->format('d/m/Y H:i') }}</strong>
                    </div>
                </div>
            </article>

            <aside class="booking-callout account-actions-panel">
                <img class="panel-image" src="{{ asset('images/about-bg.jpg') }}" alt="Ảnh tài khoản cá nhân">
                <span class="section-kicker">Thao tác</span>
                <h2>Quản lý phiên đăng nhập</h2>
                <p>Đăng xuất khi bạn dùng máy tính chung hoặc muốn chuyển sang tài khoản khác.</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="button button-primary account-logout" type="submit">Đăng xuất</button>
                </form>
                <a class="button button-secondary account-link" href="{{ route('appointments.create') }}">Đặt lịch khám</a>
            </aside>
        </div>
    </section>

    <section class="section section-muted">
        <div class="container account-layout">
            <article class="content-panel">
                <div class="section-heading-row compact-row">
                    <div>
                        <span class="section-kicker">Lịch khám</span>
                        <h2>Lịch hẹn gần đây</h2>
                    </div>
                    <a class="text-link" href="{{ route('appointments.create') }}">Đặt lịch mới</a>
                </div>

                <div class="stack-list">
                    @forelse ($user->appointments as $appointment)
                        @php
                            $payment = $appointment->payment;
                        @endphp
                        <div class="service-row account-record-row">
                            <span>
                                <strong>{{ $appointment->doctor?->name ?? 'Chưa chọn bác sĩ' }}</strong>
                                <small>
                                    {{ $appointment->appointment_date?->format('d/m/Y') }}
                                    {{ substr($appointment->appointment_time, 0, 5) }}
                                    - {{ $appointment->service?->name ?? 'Chưa chọn dịch vụ' }}
                                </small>
                            </span>
                            <em class="status-pill {{ $appointment->status }}">
                                {{ $appointmentLabels[$appointment->status] ?? $appointment->status }}
                            </em>
                            <em class="status-pill {{ $payment?->is_deposit_paid ? 'completed' : 'pending' }}">
                                {{ $payment?->deposit_status_label ?? 'Chưa thanh toán' }}
                            </em>
                        </div>
                    @empty
                        <div class="empty-state">Bạn chưa có lịch hẹn nào.</div>
                    @endforelse
                </div>
            </article>

            <article class="content-panel">
                <div class="section-heading-row compact-row">
                    <div>
                        <span class="section-kicker">Bệnh án</span>
                        <h2>Hồ sơ khám gần đây</h2>
                    </div>
                </div>

                <div class="stack-list">
                    @forelse ($user->medicalRecords as $record)
                        <div class="service-row account-record-row">
                            <span>
                                <strong>{{ $record->diagnosis ?: 'Chưa cập nhật chẩn đoán' }}</strong>
                                <small>
                                    {{ $record->examined_at?->format('d/m/Y') ?? 'Chưa có ngày khám' }}
                                    - {{ $record->doctor?->name ?? 'Chưa có bác sĩ' }}
                                </small>
                            </span>
                            <small>{{ str($record->treatment ?: $record->note ?: 'Chưa có ghi chú điều trị')->limit(70) }}</small>
                        </div>
                    @empty
                        <div class="empty-state">Bạn chưa có hồ sơ khám nào.</div>
                    @endforelse
                </div>
            </article>
        </div>
    </section>
@endsection
