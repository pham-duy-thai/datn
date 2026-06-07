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

        $imageStatusLabels = [
            'pending' => 'Chờ phân tích',
            'completed' => 'Đã phân tích',
            'failed' => 'Lỗi phân tích',
        ];

        $modalityLabels = [
            'xray' => 'X-quang',
            'ct' => 'CT',
            'mri' => 'MRI',
            'ultrasound' => 'Siêu âm',
            'endoscopy' => 'Nội soi',
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
            @if (session('password_change_prompt') || $user->must_change_password)
                <article class="content-panel account-password-panel">
                    <div>
                        <span class="section-kicker">Bảo mật</span>
                        <h2>Đổi mật khẩu</h2>
                        <p>{{ session('password_change_prompt', 'Bạn có thể đổi mật khẩu mới hoặc bỏ qua bước này.') }}</p>
                    </div>

                    <form class="account-password-form" method="POST" action="{{ route('account.password.update') }}">
                        @csrf
                        @method('PATCH')

                        <label>
                            <span>Mật khẩu hiện tại</span>
                            <input type="password" name="current_password" autocomplete="current-password" required>
                        </label>

                        <label>
                            <span>Mật khẩu mới</span>
                            <input type="password" name="password" autocomplete="new-password" required>
                        </label>

                        <label>
                            <span>Nhập lại mật khẩu mới</span>
                            <input type="password" name="password_confirmation" autocomplete="new-password" required>
                        </label>

                        <div class="account-password-actions">
                            <button class="button button-primary" type="submit">Đổi mật khẩu</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('account.password.skip') }}">
                        @csrf
                        <button class="button button-secondary account-skip-password" type="submit">Bỏ qua</button>
                    </form>
                </article>
            @endif

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
                <img class="panel-image" src="{{ asset('images/frontend/about-bg.jpg') }}" alt="Ảnh tài khoản cá nhân">
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

    @if ($user->role === 'doctor')
        <section class="section doctor-ai-section">
            <div class="container">
                <article class="content-panel doctor-ai-panel" data-medical-ai data-ai-url="{{ route('doctor.ai.assist') }}">
                    <div class="section-heading-row compact-row">
                        <div>
                            <span class="section-kicker">AI hỗ trợ bác sĩ</span>
                            <h2>Trợ lý lâm sàng</h2>
                            <p>AI chỉ đưa gợi ý tham khảo. Bác sĩ là người quyết định cuối cùng dựa trên thăm khám và dữ liệu đầy đủ.</p>
                        </div>
                    </div>

                    <form class="doctor-ai-form" data-medical-ai-form>
                        <div class="doctor-ai-mode-grid">
                            <label>
                                <input type="radio" name="mode" value="diagnosis" checked>
                                <span>Gợi ý chẩn đoán</span>
                            </label>
                            <label>
                                <input type="radio" name="mode" value="summary">
                                <span>Tóm tắt bệnh án</span>
                            </label>
                            <label>
                                <input type="radio" name="mode" value="prescription">
                                <span>Hỗ trợ kê đơn</span>
                            </label>
                            <label>
                                <input type="radio" name="mode" value="record_draft">
                                <span>Viết bệnh án</span>
                            </label>
                        </div>

                        <label class="field-wide">
                            <span>Hồ sơ bệnh án gần đây</span>
                            <select name="record_id">
                                <option value="">Không chọn hồ sơ</option>
                                @foreach ($doctorRecords as $record)
                                    <option value="{{ $record->id }}">
                                        #{{ $record->id }} - {{ $record->user?->name ?? $record->appointment?->patient_name ?? 'Chưa rõ bệnh nhân' }}
                                        {{ $record->examined_at ? '('.$record->examined_at->format('d/m/Y').')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <div class="doctor-ai-grid">
                            <label>
                                <span>Triệu chứng</span>
                                <textarea name="symptoms" rows="4" placeholder="Sốt 38.5, ho, đau ngực, khó thở..."></textarea>
                            </label>
                            <label>
                                <span>Tiền sử bệnh</span>
                                <textarea name="medical_history" rows="4" placeholder="Tăng huyết áp, đái tháo đường, bệnh thận..."></textarea>
                            </label>
                            <label>
                                <span>Kết quả xét nghiệm</span>
                                <textarea name="lab_results" rows="4" placeholder="BC, CRP, glucose, creatinine, X-quang..."></textarea>
                            </label>
                            <label>
                                <span>Chỉ số sinh tồn</span>
                                <textarea name="vital_signs" rows="4" placeholder="Mạch, huyết áp, nhiệt độ, SpO2, nhịp thở..."></textarea>
                            </label>
                            <label>
                                <span>Dị ứng thuốc</span>
                                <textarea name="allergies" rows="3" placeholder="Penicillin, NSAIDs..."></textarea>
                            </label>
                            <label>
                                <span>Thuốc đang dùng / đơn thuốc cần kiểm tra</span>
                                <textarea name="current_medications" rows="3" placeholder="Tên thuốc, hàm lượng, liều dùng..."></textarea>
                            </label>
                            <label class="field-wide">
                                <span>Ghi chú thêm</span>
                                <textarea name="note" rows="4" placeholder="Yêu cầu cận lâm sàng, hướng điều trị dự kiến, lời dặn..."></textarea>
                            </label>
                        </div>

                        <div class="doctor-ai-actions">
                            <button class="button button-primary" type="submit">Nhận gợi ý AI</button>
                            <button class="button button-secondary" type="reset">Xóa nội dung</button>
                        </div>
                    </form>

                    <div class="doctor-ai-result" data-medical-ai-result hidden></div>
                </article>
            </div>
        </section>
    @endif

    @if ($user->role === 'patient')
        <section class="section patient-image-ai-section">
            <div class="container patient-image-ai-layout">
                <article class="content-panel patient-image-upload-panel">
                    <div class="section-heading-row compact-row">
                        <div>
                            <span class="section-kicker">AI ảnh y tế</span>
                            <h2>Tải ảnh để AI đọc sơ bộ</h2>
                            <p>AI hỗ trợ đọc ảnh y tế và giải thích bằng ngôn ngữ dễ hiểu. Kết quả chỉ để tham khảo và cần bác sĩ xác nhận.</p>
                        </div>
                    </div>

                    <form class="patient-image-form" method="POST" action="{{ route('medical-images.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="patient-image-grid">
                            <label>
                                <span>Loại ảnh</span>
                                <select name="modality" required>
                                    <option value="xray">X-quang</option>
                                    <option value="ct">CT</option>
                                    <option value="mri">MRI</option>
                                    <option value="ultrasound">Siêu âm</option>
                                    <option value="endoscopy">Nội soi</option>
                                </select>
                            </label>

                            <label>
                                <span>Vùng chụp</span>
                                <input type="text" name="body_part" placeholder="Phổi, ngực, xương cổ tay...">
                            </label>

                            <label class="field-wide">
                                <span>Ảnh y tế</span>
                                <input type="file" name="image" accept="image/png,image/jpeg,image/webp" required>
                                <small class="form-hint">Hỗ trợ JPG, PNG, WEBP. Tối đa 8MB.</small>
                            </label>

                            <label class="field-wide">
                                <span>Ghi chú cho bác sĩ</span>
                                <textarea name="note" rows="4" placeholder="Triệu chứng, thời gian chụp, bệnh viện chụp, vị trí đau..."></textarea>
                            </label>
                        </div>

                        <div class="doctor-ai-actions">
                            <button class="button button-primary" type="submit">Tải lên và đọc ảnh</button>
                        </div>
                    </form>
                </article>

                <article class="content-panel patient-image-results-panel">
                    <div class="section-heading-row compact-row">
                        <div>
                            <span class="section-kicker">Kết quả gần đây</span>
                            <h2>Ảnh đã tải lên</h2>
                        </div>
                    </div>

                    <div class="patient-image-list">
                        @forelse ($user->medicalImages as $image)
                            <div class="patient-image-row">
                                <img src="{{ asset('storage/'.$image->image_path) }}" alt="Ảnh y tế {{ $image->id }}">
                                <div>
                                    <strong>{{ $modalityLabels[$image->modality] ?? $image->modality }}{{ $image->body_part ? ' - '.$image->body_part : '' }}</strong>
                                    <small>{{ $image->created_at->format('d/m/Y H:i') }}</small>
                                    <em class="status-pill {{ $image->analysis_status }}">{{ $imageStatusLabels[$image->analysis_status] ?? $image->analysis_status }}</em>
                                    <p>{{ $image->summary ?: 'Chưa có kết quả phân tích.' }}</p>

                                    @if (! empty($image->findings))
                                        <ul class="patient-image-findings">
                                            @foreach ($image->findings as $finding)
                                                <li>
                                                    {{ $finding['label'] ?? $finding['class'] ?? 'Bất thường nghi ngờ' }}
                                                    @if (isset($finding['confidence']))
                                                        - {{ round((float) $finding['confidence'] * 100) }}%
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">Bạn chưa tải ảnh y tế nào.</div>
                        @endforelse
                    </div>
                </article>
            </div>
        </section>
    @endif

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
