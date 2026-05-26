@extends('layouts.admin')

@section('title', 'Quản lý lịch hẹn')

@section('content')
    @php
        $labels = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'cancelled' => 'Đã hủy',
            'completed' => 'Hoàn tất',
        ];
    @endphp

    <section class="admin-page-heading">
        <div>
            <span>Lịch hẹn</span>
            <h1>Quản lý lịch hẹn</h1>
        </div>
        <a class="admin-primary-link" href="{{ route('admin.appointments.create') }}">Thêm lịch hẹn</a>
    </section>

    <form class="admin-filter" method="GET" action="{{ route('admin.appointments.index') }}">
        <label>
            <span>Tìm kiếm</span>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Tên, số điện thoại, bác sĩ, dịch vụ">
        </label>
        <label>
            <span>Trạng thái</span>
            <select name="status">
                <option value="">Tất cả</option>
                @foreach ($labels as $status => $label)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit">Lọc</button>
        <a href="{{ route('admin.appointments.index') }}">Xóa</a>
    </form>

    <section class="admin-panel">
        <div class="table-responsive">
            <table class="admin-table wide">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Người khám</th>
                        <th>Bác sĩ / dịch vụ</th>
                        <th>Ngày giờ</th>
                        <th>Thanh toán</th>
                        <th>Lý do</th>
                        <th>Cập nhật</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($appointments as $appointment)
                        <tr>
                            <td>{{ $appointment->id }}</td>
                            <td>
                                <strong>{{ $appointment->patient_name }}</strong>
                                <small>{{ $appointment->patient_phone }}</small>
                                <small>{{ $appointment->patient_email ?: 'Chưa có thư điện tử' }}</small>
                            </td>
                            <td>
                                <strong>{{ $appointment->doctor?->name ?? 'Chưa chọn bác sĩ' }}</strong>
                                <small>{{ $appointment->service?->name ?? 'Chưa chọn dịch vụ' }}</small>
                            </td>
                            <td>
                                <strong>{{ optional($appointment->appointment_date)->format('d/m/Y') }}</strong>
                                <small>{{ substr($appointment->appointment_time, 0, 5) }}</small>
                            </td>
                            <td>
                                @if ($appointment->payment)
                                    <span class="badge-soft {{ $appointment->payment->status }}">{{ $appointment->payment->deposit_status_label }}</span>
                                    <small>{{ number_format((float) ($appointment->payment->deposit_amount ?? $appointment->payment->amount), 0, ',', '.') }} VNĐ</small>
                                @else
                                    <span class="badge-soft unpaid">Chưa thanh toán</span>
                                @endif
                            </td>
                            <td>{{ str($appointment->reason ?: 'Không có ghi chú')->limit(70) }}</td>
                            <td>
                                <form class="inline-update" method="POST" action="{{ route('admin.appointments.status', $appointment) }}">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status">
                                        @foreach ($labels as $status => $label)
                                            <option value="{{ $status }}" @selected($appointment->status === $status)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="note" value="{{ $appointment->note }}" placeholder="Ghi chú">
                                    <button type="submit">Lưu</button>
                                </form>
                            </td>
                            <td>
                                <div class="admin-actions">
                                    <a href="{{ route('admin.appointments.edit', $appointment->id) }}">Sửa</a>
                                    <form method="POST" action="{{ route('admin.appointments.destroy', $appointment->id) }}" onsubmit="return confirm('Bạn chắc chắn muốn xóa lịch hẹn này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">Không có lịch hẹn phù hợp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-pagination">
            {{ $appointments->links() }}
        </div>
    </section>
@endsection
