@extends('layouts.app')

@section('title', $record->exists ? 'Hồ sơ bệnh nhân' : 'Tạo hồ sơ bệnh nhân')

@section('content')
    @php
        $patient = $record->user;
        $genderLabels = ['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'];
    @endphp

    <section class="page-hero compact">
        <div class="container">
            <span class="section-kicker">Hồ sơ bệnh nhân</span>
            <h1>{{ $patient?->name ?? 'Tạo hồ sơ mới' }}</h1>
            <p>Thông tin chỉ được quản lý bởi bác sĩ phụ trách và nhân sự được phân quyền.</p>
        </div>
    </section>

    <section class="section">
        <div class="container doctor-patient-record">
            @if (isset($errors) && $errors->any())
                <div class="alert alert-danger">
                    <strong>Vui lòng kiểm tra lại thông tin.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($record->exists)
                <article class="content-panel record-section">
                    <div class="record-section-heading">
                        <span>01</span>
                        <div>
                            <small>Hồ sơ bệnh nhân</small>
                            <h2>Thông tin cá nhân</h2>
                        </div>
                    </div>
                    <div class="patient-profile-grid">
                        <div><span>Họ tên</span><strong>{{ $patient?->name ?? 'Chưa cập nhật' }}</strong></div>
                        <div><span>Tuổi</span><strong>{{ $patient?->date_of_birth?->age ?? 'Chưa cập nhật' }}</strong></div>
                        <div><span>Giới tính</span><strong>{{ $genderLabels[$patient?->gender] ?? 'Chưa cập nhật' }}</strong></div>
                        <div><span>Số điện thoại</span><strong>{{ $patient?->phone ?? $record->appointment?->patient_phone ?? 'Chưa cập nhật' }}</strong></div>
                        <div class="field-wide"><span>Địa chỉ</span><strong>{{ $patient?->address ?? 'Chưa cập nhật' }}</strong></div>
                    </div>
                </article>
            @endif

            <form class="content-panel doctor-record-form" method="POST"
                action="{{ $record->exists ? route('doctor.records.update', $record) : route('doctor.records.store') }}">
                @csrf
                @if ($record->exists)
                    @method('PUT')
                @endif

                <div class="section-heading-row compact-row">
                    <div>
                        <span class="section-kicker">Bệnh án {{ $record->exists ? '#'.$record->id : 'mới' }}</span>
                        <h2>Thông tin khám và y tế</h2>
                    </div>
                    <a class="button button-secondary" href="{{ route('doctor.records.index') }}">Quay lại danh sách</a>
                </div>

                <div class="record-subsection">
                    <h3>Thông tin y tế</h3>
                    <div class="doctor-record-form-grid">
                        <label>
                            <span>Nhóm máu</span>
                            <input type="text" name="blood_type" maxlength="10" placeholder="A+, O-, AB+..."
                                value="{{ old('blood_type', $patient?->blood_type) }}">
                        </label>
                        <label>
                            <span>Dị ứng</span>
                            <textarea name="allergies" rows="3">{{ old('allergies', $patient?->allergies) }}</textarea>
                        </label>
                        <label>
                            <span>Bệnh nền</span>
                            <textarea name="underlying_conditions" rows="3">{{ old('underlying_conditions', $patient?->underlying_conditions) }}</textarea>
                        </label>
                        <label>
                            <span>Thuốc đang dùng</span>
                            <textarea name="current_medications" rows="3">{{ old('current_medications', $patient?->current_medications) }}</textarea>
                        </label>
                    </div>
                </div>

                <div class="record-subsection">
                    <h3>Thông tin lần khám</h3>
                    <div class="doctor-record-form-grid">
                        <label class="field-wide">
                            <span>Lịch hẹn của bác sĩ</span>
                            <select name="appointment_id" required>
                                <option value="">Chọn lịch hẹn</option>
                                @foreach ($appointments as $appointment)
                                    <option value="{{ $appointment->id }}" @selected((string) old('appointment_id', $record->appointment_id) === (string) $appointment->id)>
                                        #{{ $appointment->id }} - {{ $appointment->user?->name ?? $appointment->patient_name }} -
                                        {{ $appointment->appointment_date?->format('d/m/Y') }} {{ substr($appointment->appointment_time, 0, 5) }} -
                                        {{ $appointment->service?->name ?? 'Chưa gắn dịch vụ' }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Ngày khám</span>
                            <input type="date" name="examined_at" required
                                value="{{ old('examined_at', $record->examined_at?->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
                        </label>
                        <label>
                            <span>Ngày tái khám</span>
                            <input type="date" name="follow_up_date" value="{{ old('follow_up_date', $record->follow_up_date?->format('Y-m-d')) }}">
                        </label>
                        <label class="field-wide"><span>Triệu chứng</span><textarea name="symptoms" rows="4">{{ old('symptoms', $record->symptoms) }}</textarea></label>
                        <label class="field-wide"><span>Chẩn đoán</span><textarea name="diagnosis" rows="4">{{ old('diagnosis', $record->diagnosis) }}</textarea></label>
                        <label><span>Hướng điều trị</span><textarea name="treatment" rows="6">{{ old('treatment', $record->treatment) }}</textarea></label>
                        <label><span>Đơn thuốc</span><textarea name="prescription" rows="6">{{ old('prescription', $record->prescription) }}</textarea></label>
                        <label class="field-wide"><span>Ghi chú bác sĩ</span><textarea name="note" rows="4">{{ old('note', $record->note) }}</textarea></label>
                    </div>
                </div>

                <div class="doctor-ai-actions">
                    <button class="button button-primary" type="submit">{{ $record->exists ? 'Lưu hồ sơ bệnh nhân' : 'Tạo hồ sơ' }}</button>
                </div>
            </form>

            @if ($record->exists)
                <article class="content-panel record-section">
                    <div class="record-section-heading">
                        <span>03</span>
                        <div><small>Lịch sử khám</small><h2>Các lần khám trước</h2></div>
                    </div>
                    <div class="record-table-wrap">
                        <table class="record-table">
                            <thead><tr><th>Ngày khám</th><th>Bác sĩ</th><th>Chẩn đoán</th><th>Đơn thuốc</th><th>Chi tiết</th></tr></thead>
                            <tbody>
                                @foreach ($patient?->medicalRecords ?? collect() as $history)
                                    <tr>
                                        <td>{{ $history->examined_at?->format('d/m/Y') ?? '-' }}</td>
                                        <td>{{ $history->doctor?->name ?? '-' }}</td>
                                        <td>{{ str($history->diagnosis ?: '-')->limit(80) }}</td>
                                        <td>{{ str($history->prescription ?: '-')->limit(80) }}</td>
                                        <td>
                                            @if ($history->doctor_id === auth()->user()->doctor?->id)
                                                <a href="{{ route('doctor.records.edit', $history) }}">Xem</a>
                                            @else
                                                Chỉ xem tóm tắt
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="content-panel record-section">
                    <div class="record-section-heading">
                        <span>04</span>
                        <div><small>Kết quả xét nghiệm</small><h2>Xét nghiệm và file đính kèm</h2></div>
                    </div>

                    <form class="lab-result-form" method="POST" enctype="multipart/form-data"
                        action="{{ route('doctor.records.lab-results.store', $record) }}">
                        @csrf
                        <input type="text" name="name" required placeholder="Tên xét nghiệm">
                        <input type="date" name="performed_at">
                        <input type="text" name="result" placeholder="Kết quả">
                        <input type="file" name="file" accept=".pdf,image/*">
                        <button class="button button-primary" type="submit">Thêm kết quả</button>
                    </form>

                    <div class="record-table-wrap">
                        <table class="record-table">
                            <thead><tr><th>Tên xét nghiệm</th><th>Ngày làm</th><th>Kết quả</th><th>File</th><th></th></tr></thead>
                            <tbody>
                                @forelse ($record->labResults as $result)
                                    <tr>
                                        <td>{{ $result->name }}</td>
                                        <td>{{ $result->performed_at?->format('d/m/Y') ?? '-' }}</td>
                                        <td>{{ $result->result ?: '-' }}</td>
                                        <td>
                                            @if ($result->file_path)
                                                <a href="{{ asset('storage/'.$result->file_path) }}" target="_blank">Mở file</a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('doctor.records.lab-results.destroy', [$record, $result]) }}">
                                                @csrf @method('DELETE')
                                                <button class="record-delete-button" type="submit">Xóa</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5">Chưa có kết quả xét nghiệm.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="content-panel record-section record-follow-up">
                    <div class="record-section-heading">
                        <span>05</span>
                        <div><small>Lịch tái khám</small><h2>Theo dõi tiếp theo</h2></div>
                    </div>
                    <div class="patient-profile-grid">
                        <div><span>Ngày hẹn</span><strong>{{ $record->follow_up_date?->format('d/m/Y') ?? 'Chưa đặt lịch' }}</strong></div>
                        <div><span>Nội dung</span><strong>{{ $record->note ?: 'Chưa có ghi chú' }}</strong></div>
                        <div>
                            <span>Trạng thái</span>
                            <strong>{{ ! $record->follow_up_date ? 'Chưa đặt lịch' : ($record->follow_up_date->isPast() ? 'Đã đến hạn' : 'Sắp tới') }}</strong>
                        </div>
                    </div>
                </article>
            @endif
        </div>
    </section>
@endsection
