@extends('layouts.auth')

@section('title', 'Đăng ký')
@section('heading', 'Đăng ký tài khoản')

@section('content')
    <form method="POST" action="{{ route('register.store') }}">
        @csrf

        <h3 class="auth-title">Tạo tài khoản người bệnh</h3>
        <p class="auth-subtitle">Sau khi đăng ký, bạn sẽ được đăng nhập để đặt lịch khám ngay.</p>

        <div class="form-group">
            <label for="name">Họ tên</label>
            <input id="name" class="form-control" type="text" name="name" value="{{ old('name') }}" placeholder="Nguyễn Thúy An" autocomplete="name" required autofocus>
        </div>

        <div class="form-group">
            <label for="email">Gmail đăng ký</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" placeholder="ban@gmail.com" autocomplete="email" required>
        </div>

        <div class="form-group">
            <label for="phone">Số điện thoại</label>
            <input id="phone" class="form-control" type="tel" name="phone" value="{{ old('phone') }}" placeholder="0900000000" autocomplete="tel">
        </div>

        <div class="form-group">
            <label for="password">Mật khẩu</label>
            <input id="password-field" class="form-control" type="password" name="password" placeholder="Mật khẩu" autocomplete="new-password" required>
            <span toggle="#password-field" class="fa fa-fw fa-eye field-icon toggle-password"></span>
        </div>

        <div class="form-group">
            <label for="password_confirmation">Nhập lại mật khẩu</label>
            <input id="password-confirm-field" class="form-control" type="password" name="password_confirmation" placeholder="Nhập lại mật khẩu" autocomplete="new-password" required>
            <span toggle="#password-confirm-field" class="fa fa-fw fa-eye field-icon toggle-password"></span>
        </div>

        <div class="form-group">
            <button class="form-control btn btn-primary submit px-3" type="submit">Đăng ký và đăng nhập</button>
        </div>

        <p class="auth-bottom-link">
            Đã có tài khoản?
            <a href="{{ route('login') }}">Đăng nhập</a>
        </p>
    </form>
@endsection
