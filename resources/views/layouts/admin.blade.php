<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Quản trị hệ thống')</title>

        <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
        <link rel="stylesheet" href="{{ asset('css/font-awesome.min.css') }}">
        <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    </head>
    <body class="admin-body">
        <nav class="admin-topbar">
            <a class="admin-brand" href="{{ route('admin.dashboard') }}">
                <span><i class="fa fa-h-square"></i></span>
                <strong>Quản trị bệnh viện</strong>
            </a>

            <div class="admin-top-actions">
                <a href="{{ route('home') }}">Giao diện người dùng</a>
                <span>{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Đăng xuất</button>
                </form>
            </div>
        </nav>

        <div class="admin-shell">
            <aside class="admin-sidebar">
                <div class="sidebar-visual">
                    <img src="{{ asset('images/slider1.jpg') }}" alt="Ảnh quản trị hệ thống">
                </div>
                <nav>
                    <a @class(['active' => request()->routeIs('admin.dashboard')]) href="{{ route('admin.dashboard') }}">
                        <i class="fa fa-dashboard"></i> Tổng quan
                    </a>
                    <a @class(['active' => request()->routeIs('admin.users.*')]) href="{{ route('admin.users.index') }}">
                        <i class="fa fa-users"></i> Quản lý người dùng
                    </a>
                    <a @class(['active' => request()->routeIs('admin.patients.*')]) href="{{ route('admin.patients.index') }}">
                        <i class="fa fa-wheelchair"></i> Quản lý bệnh nhân
                    </a>
                    <a @class(['active' => request()->routeIs('admin.departments.*')]) href="{{ route('admin.departments.index') }}">
                        <i class="fa fa-hospital-o"></i> Quản lý chuyên khoa
                    </a>
                    <a @class(['active' => request()->routeIs('admin.doctors.*')]) href="{{ route('admin.doctors.index') }}">
                        <i class="fa fa-user-md"></i> Quản lý bác sĩ
                    </a>
                    <a @class(['active' => request()->routeIs('admin.schedules.*')]) href="{{ route('admin.schedules.index') }}">
                        <i class="fa fa-calendar"></i> Quản lý lịch làm việc
                    </a>
                    <a @class(['active' => request()->routeIs('admin.appointments.*')]) href="{{ route('admin.appointments.index') }}">
                        <i class="fa fa-calendar-check-o"></i> Quản lý lịch hẹn
                    </a>
                    <a @class(['active' => request()->routeIs('admin.payments.*')]) href="{{ route('admin.payments.index') }}">
                        <i class="fa fa-credit-card"></i> Quản lý thanh toán
                    </a>
                    <a @class(['active' => request()->routeIs('admin.medical-records.*')]) href="{{ route('admin.medical-records.index') }}">
                        <i class="fa fa-file-text-o"></i> Quản lý hồ sơ khám
                    </a>
                    <a @class(['active' => request()->routeIs('admin.services.*')]) href="{{ route('admin.services.index') }}">
                        <i class="fa fa-stethoscope"></i> Quản lý dịch vụ
                    </a>
                    <a @class(['active' => request()->routeIs('admin.news.*')]) href="{{ route('admin.news.index') }}">
                        <i class="fa fa-newspaper-o"></i> Quản lý tin tức
                    </a>
                    <a @class(['active' => request()->routeIs('admin.contacts.*')]) href="{{ route('admin.contacts.index') }}">
                        <i class="fa fa-envelope-o"></i> Quản lý liên hệ
                    </a>
                </nav>
            </aside>

            <main class="admin-main">
                @if (session('success'))
                    <div class="admin-alert success">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="admin-alert danger">
                        <strong>Dữ liệu chưa hợp lệ.</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </body>
</html>
