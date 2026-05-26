@extends('layouts.auth')

@section('title', 'Đăng nhập')
@section('heading', 'Đăng nhập')

@section('content')
    <form method="POST" action="{{ route('login.store') }}">
        @csrf

        <h3 class="auth-title">Truy cập hệ thống</h3>
        <p class="auth-subtitle">Đăng nhập để đặt lịch khám và theo dõi thông tin cá nhân.</p>

        <div class="form-group">
            <label for="email">Gmail đăng ký</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" placeholder="ban@gmail.com" autocomplete="email" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Mật khẩu</label>
            <input id="password-field" class="form-control" type="password" name="password" placeholder="Mật khẩu" autocomplete="current-password" required>
            <span toggle="#password-field" class="fa fa-fw fa-eye field-icon toggle-password"></span>
        </div>

        <div class="auth-form-actions">
            <label class="checkbox-wrap checkbox-primary">
                Ghi nhớ đăng nhập
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                <span class="checkmark"></span>
            </label>

            <a class="auth-link" href="{{ route('password.request') }}">Quên mật khẩu?</a>
        </div>

        <div class="form-group">
            <button class="form-control btn btn-primary submit px-3" type="submit">Đăng nhập</button>
        </div>

        <p class="auth-bottom-link">
            Chưa có tài khoản?
            <a href="{{ route('register') }}">Đăng ký ngay</a>
        </p>
    </form>
@endsection
