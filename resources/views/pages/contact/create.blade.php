@extends('layouts.app')

@section('title', 'Liên hệ')

@section('content')
    <section class="page-hero compact">
        <div class="container">
            <span class="section-kicker">Liên hệ</span>
            <h1>Gửi câu hỏi hoặc yêu cầu tư vấn</h1>
            <p>Để lại thông tin, bệnh viện sẽ phản hồi về lịch khám, dịch vụ hoặc quy trình tiếp nhận.</p>
        </div>
    </section>

    <section class="section">
        <div class="container form-layout">
            <form class="form-panel" method="POST" action="{{ route('contact.store') }}">
                @csrf

                <div>
                    <span class="section-kicker">Thông tin liên hệ</span>
                    <h2>Nội dung cần hỗ trợ</h2>
                </div>

                <div class="form-grid">
                    <label>
                        <span>Họ tên</span>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Nguyễn Thúy An" required>
                    </label>

                    <label>
                        <span>Gmail liên hệ</span>
                        <input id="email" type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" placeholder="lienhe@gmail.com" required>
                    </label>

                    <label>
                        <span>Số điện thoại</span>
                        <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" placeholder="0900000000">
                    </label>

                    <label>
                        <span>Chủ đề</span>
                        <input id="subject" type="text" name="subject" value="{{ old('subject') }}" placeholder="Tư vấn đặt lịch">
                    </label>

                    <label class="field-wide">
                        <span>Nội dung</span>
                        <textarea id="message" name="message" rows="6" placeholder="Nhập nội dung cần hỗ trợ" required>{{ old('message') }}</textarea>
                    </label>
                </div>

                <div class="form-actions">
                    <button class="button button-primary" type="submit">Gửi liên hệ</button>
                    <a class="button button-secondary" href="{{ route('appointments.create') }}">Đặt lịch khám</a>
                </div>
            </form>

            <aside class="booking-callout">
                <img class="panel-image" src="{{ asset('images/frontend/news-image.jpg') }}" alt="Ảnh hỗ trợ liên hệ">
                <span class="section-kicker">Hỗ trợ</span>
                <h2>Kênh tiếp nhận</h2>
                <p>Các yêu cầu liên hệ được ghi nhận để nhân viên kiểm tra và phản hồi.</p>
                <div class="detail-list">
                    <div>
                        <span>Đặt lịch</span>
                        <strong>Qua biểu mẫu đặt lịch khám</strong>
                    </div>
                    <div>
                        <span>Tư vấn</span>
                        <strong>Qua biểu mẫu liên hệ</strong>
                    </div>
                    <div>
                        <span>Trạng thái</span>
                        <strong>Mới, đã đọc, đã phản hồi</strong>
                    </div>
                </div>
            </aside>
        </div>
    </section>
@endsection
