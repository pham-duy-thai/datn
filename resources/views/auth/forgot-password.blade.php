@extends('layouts.auth')

@section('title', 'Quên mật khẩu')
@section('heading', 'Quên mật khẩu')

@section('content')
    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <h3 class="auth-title">Khôi phục tài khoản</h3>
        <p class="auth-subtitle">Nhập Gmail đăng ký để nhận mã xác nhận đặt lại mật khẩu.</p>

        <div class="form-group">
            <label for="email">Gmail đăng ký</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" placeholder="ban@gmail.com" autocomplete="email" required autofocus>
        </div>

        <div class="form-group">
            <button class="form-control btn btn-primary submit px-3" type="submit">Gửi mã</button>
        </div>

        <p class="auth-bottom-link">
            Nhớ mật khẩu?
            <a href="{{ route('login') }}">Quay lại đăng nhập</a>
        </p>
    </form>
@endsection
