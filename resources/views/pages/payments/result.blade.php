@extends('layouts.app')

@section('title', 'Kết quả thanh toán')

@section('content')
    @php
        $methodLabels = [
            'vnpay' => 'VNPay sandbox',
            'momo' => 'MoMo sandbox',
            'cash' => 'Tiền mặt',
        ];

        $appointmentStatusLabels = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'completed' => 'Hoàn tất',
            'cancelled' => 'Đã hủy',
        ];

        $totalAmount = (float) ($payment->total_amount ?? $payment->amount);
        $depositAmount = (float) ($payment->deposit_amount ?? $payment->amount);
    @endphp

    <section class="page-hero compact">
        <div class="container">
            <span class="section-kicker">Kết quả thanh toán</span>
            <h1>{{ $payment->isPaid() ? 'Đặt lịch thành công' : 'Thanh toán chưa thành công' }}</h1>
            <p>{{ $message }}</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <article class="content-panel">
                <span class="section-kicker">{{ $methodLabels[$payment->method] ?? $payment->method }}</span>
                <h2>Lịch hẹn #{{ $payment->appointment_id }}</h2>
                <div class="detail-list">
                    <div>
                        <span>Dịch vụ</span>
                        <strong>{{ $payment->appointment?->service?->name ?? 'Dịch vụ y tế' }}</strong>
                    </div>
                    <div>
                        <span>Tổng phí dịch vụ</span>
                        <strong>{{ number_format($totalAmount, 0, ',', '.') }} VNĐ</strong>
                    </div>
                    <div>
                        <span>Số tiền thanh toán</span>
                        <strong>{{ number_format($depositAmount, 0, ',', '.') }} VNĐ</strong>
                    </div>
                    <div>
                        <span>Trạng thái</span>
                        <strong>{{ $payment->deposit_status_label }}</strong>
                    </div>
                    <div>
                        <span>Còn lại khi đến khám</span>
                        <strong>{{ number_format((float) $payment->remaining_amount, 0, ',', '.') }} VNĐ</strong>
                    </div>
                    <div>
                        <span>Mã giao dịch</span>
                        <strong>{{ $payment->transaction_code ?: 'Chưa có' }}</strong>
                    </div>
                    <div>
                        <span>Trạng thái lịch hẹn</span>
                        <strong>{{ $appointmentStatusLabels[$payment->appointment?->status] ?? $payment->appointment?->status }}</strong>
                    </div>
                </div>
                <div class="form-actions">
                    <a class="button button-primary" href="{{ route('home') }}">Về trang chủ</a>
                    @auth
                        <a class="button button-secondary" href="{{ route('account.show') }}">Xem tài khoản</a>
                    @endauth
                </div>
            </article>
        </div>
    </section>
@endsection
