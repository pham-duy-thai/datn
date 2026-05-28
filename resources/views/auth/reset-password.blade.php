@extends('layouts.auth')

@section('title', 'Đặt lại mật khẩu')
@section('heading', 'Đặt lại mật khẩu')

@section('content')
    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <h3 class="auth-title">Tạo mật khẩu mới</h3>
        <p class="auth-subtitle">Mã xác nhận hợp lệ. Tạo mật khẩu mới cho {{ $email }}.</p>

        <div class="form-group">
            <label for="password">Mật khẩu mới</label>
            <input id="password-field" class="form-control" type="password" name="password" placeholder="Mật khẩu mới" autocomplete="new-password" required>
            <span toggle="#password-field" class="fa fa-fw fa-eye field-icon toggle-password"></span>
        </div>

        <div class="form-group">
            <label for="password_confirmation">Nhập lại mật khẩu mới</label>
            <input id="password-confirm-field" class="form-control" type="password" name="password_confirmation" placeholder="Nhập lại mật khẩu mới" autocomplete="new-password" required>
            <span toggle="#password-confirm-field" class="fa fa-fw fa-eye field-icon toggle-password"></span>
        </div>

        <div class="form-group">
            <button class="form-control btn btn-primary submit px-3" type="submit">Cập nhật mật khẩu</button>
        </div>

        <p class="auth-bottom-link">
            <a href="{{ route('login') }}">Quay lại đăng nhập</a>
        </p>
    </form>
@endsection
