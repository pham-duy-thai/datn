@extends('layouts.app')

@section('title', 'Thông tin thanh toán')

@section('content')
    @php
        $methodLabels = [
            'cash' => 'Tiền mặt',
            'vnpay' => 'VNPay sandbox',
            'momo' => 'MoMo sandbox',
        ];

        $statusLabels = [
            'unpaid' => 'Chưa thanh toán',
            'pending' => 'Chưa thanh toán',
            'paid' => 'Đã thanh toán',
            'failed' => 'Thanh toán thất bại',
            'cancelled' => 'Đã hủy',
            'refunded' => 'Đã hoàn tiền',
        ];

        $totalAmount = (float) ($payment->total_amount ?? $payment->amount);
        $depositAmount = (float) ($payment->deposit_amount ?? $payment->amount);
    @endphp

    <section class="page-hero compact">
        <div class="container">
            <span class="section-kicker">Thanh toán</span>
            <h1>Thông tin thanh toán</h1>
            <p>Theo dõi phí dịch vụ, số tiền thanh toán và trạng thái xác nhận lịch hẹn.</p>
        </div>
    </section>

    <section class="section">
        <div class="container form-layout">
            <article class="content-panel">
                <span class="section-kicker">Lịch hẹn #{{ $payment->appointment_id }}</span>
                <h2>{{ $payment->appointment?->service?->name ?? 'Dịch vụ y tế' }}</h2>
                <div class="detail-list">
                    <div>
                        <span>Người khám</span>
                        <strong>{{ $payment->appointment?->patient_name }}</strong>
                    </div>
                    <div>
                        <span>Bác sĩ</span>
                        <strong>{{ $payment->appointment?->doctor?->name ?? 'Chưa chọn bác sĩ' }}</strong>
                    </div>
                    <div>
                        <span>Ngày giờ khám</span>
                        <strong>{{ $payment->appointment?->appointment_date?->format('d/m/Y') }} {{ substr($payment->appointment?->appointment_time, 0, 5) }}</strong>
                    </div>
                    <div>
                        <span>Phương thức</span>
                        <strong>{{ $methodLabels[$payment->method] ?? $payment->method }}</strong>
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
                        <strong>{{ $statusLabels[$payment->status] ?? $payment->status }}</strong>
                    </div>
                    <div>
                        <span>Còn lại khi đến khám</span>
                        <strong>{{ number_format((float) $payment->remaining_amount, 0, ',', '.') }} VNĐ</strong>
                    </div>
                </div>
            </article>

            <aside class="booking-callout">
                <img class="panel-image" src="{{ asset('images/appointment-image.jpg') }}" alt="Ảnh thanh toán">
                <span class="section-kicker">Hướng dẫn</span>
                @if ($payment->method === 'cash' && $payment->status === 'unpaid')
                    <h2>Thanh toán tại bệnh viện</h2>
                    <p>Bạn vui lòng thanh toán tại quầy lễ tân. Sau khi thu tiền, lễ tân sẽ xác nhận lịch hẹn.</p>
                @elseif ($payment->method === 'momo' && $payment->status !== 'paid')
                    <h2>Thanh toán bằng MoMo Test</h2>
                    <p>MoMo sandbox cần dùng ứng dụng MoMo Test để quét mã. Nếu dùng app MoMo thật hoặc quét lại mã cũ đã hết hạn, hệ thống có thể báo mã QR không hợp lệ.</p>
                @elseif ($payment->status === 'paid')
                    <h2>Đã thanh toán</h2>
                    <p>Lịch hẹn đã được xác nhận sau khi thanh toán thành công.</p>
                @else
                    <h2>Đang xử lý</h2>
                    <p>Nếu thanh toán online chưa hoàn tất, bạn có thể thanh toán lại hoặc liên hệ bệnh viện để được hỗ trợ.</p>
                @endif
                <div class="form-actions">
                    @if (in_array($payment->method, ['vnpay', 'momo'], true) && ! in_array($payment->status, ['paid', 'cancelled'], true))
                        <a class="button button-primary" href="{{ route('payments.retry', $payment) }}">Thanh toán lại</a>
                    @endif
                    <a class="button button-primary" href="{{ route('appointments.create') }}">Đặt lịch khác</a>
                    <a class="button button-secondary" href="{{ route('home') }}">Về trang chủ</a>
                </div>
            </aside>
        </div>
    </section>
@endsection
