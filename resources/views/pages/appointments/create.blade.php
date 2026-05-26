@extends('layouts.app')

@section('title', 'Đặt lịch khám')

@section('content')
    @php
        $weekdayNames = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7', 7 => 'Chủ nhật'];
        $selectedDoctor = old('doctor_id', $selectedDoctorId);
        $selectedService = old('service_id', $selectedServiceId);
        $depositPercent = (float) config('payments.deposit_percent', 30);
    @endphp

    <section class="page-hero compact">
        <div class="container">
            <span class="section-kicker">Đặt lịch</span>
            <h1>Đặt lịch khám</h1>
            <p>Chọn bác sĩ, dịch vụ, ngày giờ mong muốn và gửi thông tin người khám để bệnh viện xác nhận.</p>
        </div>
    </section>

    <section class="section appointment-section" id="appointment">
        <div class="container appointment-layout">
            <aside class="appointment-doctor-visual" aria-label="Bác sĩ hỗ trợ đặt lịch">
                <img src="{{ asset('images/team-image1.jpg') }}" alt="Bác sĩ Minh An hỗ trợ đặt lịch khám">
            </aside>

            <form class="form-panel" method="POST" action="{{ route('appointments.store') }}" data-appointment-form>
                @csrf

                <div>
                    <span class="section-kicker">Thông tin lịch hẹn</span>
                    <h2>Gửi yêu cầu đặt lịch</h2>
                </div>

                <div class="form-grid">
                    <label>
                        <span>Bác sĩ</span>
                        <select id="doctor_id" name="doctor_id" required data-doctor-select>
                            <option value="">Chọn bác sĩ</option>
                            @foreach ($doctors as $doctor)
                                <option value="{{ $doctor->id }}" @selected((string) $selectedDoctor === (string) $doctor->id)>
                                    {{ $doctor->name }} - {{ $doctor->department?->name ?? 'Chưa gắn khoa' }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span>Lịch làm việc</span>
                        <select id="doctor_schedule_id" name="doctor_schedule_id" data-schedule-select>
                            <option value="">Chọn sau nếu chưa rõ</option>
                            @foreach ($doctors as $doctor)
                                @foreach ($doctor->schedules as $schedule)
                                    <option
                                        value="{{ $schedule->id }}"
                                        data-doctor="{{ $doctor->id }}"
                                        @selected((string) old('doctor_schedule_id') === (string) $schedule->id)
                                    >
                                        {{ $doctor->name }} - {{ $weekdayNames[$schedule->weekday] ?? 'Thứ '.$schedule->weekday }}
                                        {{ substr($schedule->start_time, 0, 5) }}-{{ substr($schedule->end_time, 0, 5) }}
                                        {{ $schedule->room ? '('.$schedule->room.')' : '' }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span>Dịch vụ</span>
                        <select id="service_id" name="service_id" required>
                            <option value="">Chọn dịch vụ thanh toán</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}" @selected((string) $selectedService === (string) $service->id)>
                                    {{ $service->name }} - {{ number_format((float) $service->price) }} VNĐ
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span>Phương thức thanh toán</span>
                        <select id="payment_method" name="payment_method" required>
                            <option value="cash" @selected(old('payment_method') === 'cash')>Tiền mặt tại bệnh viện</option>
                            <option value="vnpay" @selected(old('payment_method') === 'vnpay')>VNPay sandbox</option>
                            <option value="momo" @selected(old('payment_method') === 'momo')>MoMo sandbox (app MoMo Test)</option>
                        </select>
                        <small class="form-hint">Số tiền thanh toán trước là {{ rtrim(rtrim(number_format($depositPercent, 2, ',', '.'), '0'), ',') }}% phí dịch vụ. Phần còn lại thanh toán tại bệnh viện.</small>
                    </label>

                    <label>
                        <span>Ngày khám</span>
                        <input id="appointment_date" type="date" name="appointment_date" value="{{ old('appointment_date') }}" min="{{ now()->toDateString() }}" required>
                    </label>

                    <label>
                        <span>Giờ khám</span>
                        <input id="appointment_time" type="time" name="appointment_time" value="{{ old('appointment_time') }}" required>
                    </label>

                    <label>
                        <span>Họ tên người khám</span>
                        <input id="patient_name" type="text" name="patient_name" value="{{ old('patient_name', auth()->user()?->name) }}" placeholder="Nguyễn Thúy An" required>
                    </label>

                    <label>
                        <span>Số điện thoại</span>
                        <input id="patient_phone" type="tel" name="patient_phone" value="{{ old('patient_phone', auth()->user()?->phone) }}" placeholder="0900000000" required>
                    </label>

                    <label>
                        <span>Gmail người khám</span>
                        <input id="patient_email" type="email" name="patient_email" value="{{ old('patient_email', auth()->user()?->email) }}" placeholder="nguoikham@gmail.com" required>
                    </label>

                    <label class="field-wide">
                        <span>Lý do khám</span>
                        <textarea id="reason" name="reason" rows="5" placeholder="Mô tả ngắn gọn triệu chứng hoặc nhu cầu khám">{{ old('reason') }}</textarea>
                    </label>
                </div>

                <div class="form-actions">
                    <button class="button button-primary" type="submit">Gửi yêu cầu đặt lịch</button>
                    <a class="button button-secondary" href="{{ route('doctors.index') }}">Chọn lại bác sĩ</a>
                </div>
            </form>
        </div>
    </section>
@endsection
