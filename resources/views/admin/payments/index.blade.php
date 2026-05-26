@extends('layouts.admin')

@section('title', 'Quản lý thanh toán')

@section('content')
    <section class="admin-page-heading">
        <div>
            <span>Thanh toán</span>
            <h1>Quản lý thanh toán</h1>
            <p>Theo dõi thanh toán qua tiền mặt, VNPay sandbox và MoMo sandbox cho từng lịch hẹn.</p>
        </div>
    </section>

    <form class="admin-filter" method="GET" action="{{ route('admin.payments.index') }}">
        <label>
            <span>Tìm kiếm</span>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Tên bệnh nhân, mã giao dịch, mã đơn">
        </label>
        <label>
            <span>Phương thức</span>
            <select name="method">
                <option value="">Tất cả</option>
                @foreach ($methodLabels as $method => $label)
                    <option value="{{ $method }}" @selected(request('method') === $method)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span>Trạng thái</span>
            <select name="status">
                <option value="">Tất cả</option>
                @foreach ($statusLabels as $status => $label)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit">Lọc</button>
        <a href="{{ route('admin.payments.index') }}">Xóa</a>
    </form>

    <section class="admin-panel">
        <div class="table-responsive">
            <table class="admin-table wide">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Lịch hẹn</th>
                        <th>Người thanh toán</th>
                        <th>Phí dịch vụ</th>
                        <th>Số tiền thanh toán</th>
                        <th>Phương thức</th>
                        <th>Trạng thái</th>
                        <th>Mã giao dịch</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>{{ $payment->id }}</td>
                            <td>
                                <strong>#{{ $payment->appointment_id }} - {{ $payment->appointment?->service?->name ?? 'Dịch vụ y tế' }}</strong>
                                <small>{{ $payment->appointment?->appointment_date?->format('d/m/Y') }} {{ substr($payment->appointment?->appointment_time, 0, 5) }}</small>
                                <small>{{ $payment->appointment?->doctor?->name ?? 'Chưa có bác sĩ' }}</small>
                            </td>
                            <td>
                                <strong>{{ $payment->appointment?->patient_name ?? $payment->user?->name }}</strong>
                                <small>{{ $payment->appointment?->patient_email ?? $payment->user?->email }}</small>
                                <small>{{ $payment->appointment?->patient_phone }}</small>
                            </td>
                            <td>
                                {{ number_format((float) ($payment->total_amount ?? $payment->amount), 0, ',', '.') }} VNĐ
                                <small>Còn lại: {{ number_format((float) $payment->remaining_amount, 0, ',', '.') }} VNĐ</small>
                            </td>
                            <td>{{ number_format((float) ($payment->deposit_amount ?? $payment->amount), 0, ',', '.') }} VNĐ</td>
                            <td>{{ $methodLabels[$payment->method] ?? $payment->method }}</td>
                            <td>
                                <span class="badge-soft {{ $payment->status }}">{{ $statusLabels[$payment->status] ?? $payment->deposit_status_label }}</span>
                                <small>{{ $payment->deposit_paid_at?->format('d/m/Y H:i') ?? 'Chưa có thời gian thanh toán' }}</small>
                            </td>
                            <td>
                                {{ $payment->transaction_code ?: 'Chưa có' }}
                                <small>{{ $payment->gateway_order_id ?: 'Chưa có mã đơn' }}</small>
                            </td>
                            <td>
                                <div class="admin-actions">
                                    @if ($payment->method === 'cash' && $payment->status !== 'paid')
                                        <form method="POST" action="{{ route('admin.payments.cash-paid', $payment) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit">Đã thu tiền</button>
                                        </form>
                                    @endif

                                    @if (! in_array($payment->status, ['paid', 'cancelled'], true))
                                        <form method="POST" action="{{ route('admin.payments.cancel', $payment) }}" onsubmit="return confirm('Bạn chắc chắn muốn hủy thanh toán này?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit">Hủy</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">Không có thanh toán phù hợp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-pagination">
            {{ $payments->links() }}
        </div>
    </section>
@endsection
