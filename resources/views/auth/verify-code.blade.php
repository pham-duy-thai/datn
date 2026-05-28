@extends('layouts.auth')

@section('title', 'Nhập mã xác nhận')
@section('heading', 'Nhập mã xác nhận')

@section('content')
    <form method="POST" action="{{ route('password.code.verify') }}">
        @csrf

        <h3 class="auth-title">Xác nhận Gmail</h3>
        <p class="auth-subtitle">Nhập mã 6 số đã được gửi về {{ $email }}.</p>

        <div class="form-group">
            <label for="code">Mã xác nhận</label>
            <input id="code" class="form-control auth-code-input" type="text" name="code" value="{{ old('code') }}" placeholder="000000" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus>
        </div>

        <div class="form-group">
            <button class="form-control btn btn-primary submit px-3" type="submit">Xác nhận mã</button>
        </div>

        <p class="auth-bottom-link">
            Chưa nhận được mã?
            <a href="{{ route('password.request') }}">Gửi lại</a>
        </p>
    </form>
@endsection
