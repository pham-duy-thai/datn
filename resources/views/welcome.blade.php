@extends('layouts.app')

@section('title', 'Hệ thống chăm sóc bệnh viện')

@section('content')
    <section class="page-hero compact">
        <div class="container">
            <span class="section-kicker">Hệ thống bệnh viện</span>
            <h1>Giao diện công khai</h1>
            <p>Tổng quan các trang công khai của hệ thống đặt lịch khám và chăm sóc người bệnh.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="booking-callout">
                <span class="section-kicker">Bắt đầu</span>
                <h2>Truy cập các trang chính</h2>
                <p>Chọn trang cần xem hoặc gửi lịch khám mới.</p>
                <div class="hero-actions">
                    <a class="button button-primary" href="{{ route('home') }}">Trang chủ</a>
                    <a class="button button-secondary" href="{{ route('appointments.create') }}">Đặt lịch khám</a>
                    <a class="button button-secondary" href="{{ route('doctors.index') }}">Danh sách bác sĩ</a>
                </div>
            </div>
        </div>
    </section>
@endsection
