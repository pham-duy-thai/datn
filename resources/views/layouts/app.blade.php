<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Hệ thống chăm sóc bệnh viện')</title>

        <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
        <link rel="stylesheet" href="{{ asset('css/font-awesome.min.css') }}">
        <link rel="stylesheet" href="{{ asset('css/animate.css') }}">
        <link rel="stylesheet" href="{{ asset('css/owl.carousel.css') }}">
        <link rel="stylesheet" href="{{ asset('css/owl.theme.default.min.css') }}">
        <link rel="stylesheet" href="{{ asset('css/tooplate-style.css') }}">
        <link rel="stylesheet" href="{{ asset('css/frontend.css') }}">
    </head>
    <body id="top">
        <section class="preloader">
            <div class="spinner"></div>
        </section>

        <header>
            <div class="container">
                <div class="row">
                    <div class="col-md-4 col-sm-5">
                        <p>Hệ thống chăm sóc bệnh viện - đặt lịch khám trực tuyến</p>
                    </div>
                    <div class="col-md-8 col-sm-7 text-align-right">
                        <span class="phone-icon"><i class="fa fa-phone"></i> 1900 1000</span>
                        <span class="date-icon"><i class="fa fa-calendar-plus-o"></i> Hỗ trợ mỗi ngày</span>
                        <span class="email-icon"><i class="fa fa-envelope-o"></i> support@hospital.test</span>
                    </div>
                </div>
            </div>
        </header>

        <section class="navbar navbar-default navbar-static-top" role="navigation">
            <div class="container">
                <div class="navbar-header">
                    <button class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse" type="button" aria-label="Mở menu">
                        <span class="icon icon-bar"></span>
                        <span class="icon icon-bar"></span>
                        <span class="icon icon-bar"></span>
                    </button>

                    <a class="navbar-brand" href="{{ auth()->check() ? route('home') : route('login') }}">
                        <i class="fa fa-h-square"></i> Hệ thống bệnh viện
                    </a>
                </div>

                <div class="collapse navbar-collapse">
                    <ul class="nav navbar-nav navbar-right">
                        @auth
                            <li @class(['active' => request()->routeIs('home')])><a href="{{ route('home') }}">Trang chủ</a></li>
                            <li @class(['active' => request()->routeIs('departments.*')])><a href="{{ route('departments.index') }}">Chuyên khoa</a></li>
                            <li @class(['active' => request()->routeIs('doctors.*')])><a href="{{ route('doctors.index') }}">Bác sĩ</a></li>
                            <li @class(['active' => request()->routeIs('services.*')])><a href="{{ route('services.index') }}">Dịch vụ</a></li>
                            <li @class(['active' => request()->routeIs('news.*')])><a href="{{ route('news.index') }}">Tin tức</a></li>
                            <li @class(['active' => request()->routeIs('contact.*')])><a href="{{ route('contact.create') }}">Liên hệ</a></li>
                            <li @class(['appointment-btn', 'active' => request()->routeIs('appointments.*')])><a href="{{ route('appointments.create') }}">Đặt lịch</a></li>
                            <li @class(['active' => request()->routeIs('account.*')])><a href="{{ route('account.show') }}"><i class="fa fa-user-o"></i> Tài khoản</a></li>
                            @if (in_array(auth()->user()->role, ['admin', 'receptionist'], true))
                                <li @class(['active' => request()->routeIs('admin.*')])><a href="{{ route('admin.dashboard') }}">Quản trị</a></li>
                            @endif
                            <li class="user-menu">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit">Đăng xuất</button>
                                </form>
                            </li>
                        @else
                            <li @class(['active' => request()->routeIs('login')])><a href="{{ route('login') }}">Đăng nhập</a></li>
                            <li @class(['appointment-btn', 'active' => request()->routeIs('register')])><a href="{{ route('register') }}">Đăng ký</a></li>
                        @endauth
                    </ul>
                </div>
            </div>
        </section>

        <main>
            @if (session('success'))
                <div class="container flash-wrap">
                    <div class="alert alert-success" role="status">
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="container flash-wrap">
                    <div class="alert alert-danger" role="alert">
                        <strong>Dữ liệu chưa hợp lệ.</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>

        <section class="frontend-map-layer" aria-label="Bản đồ định vị bệnh viện">
            <div class="map-info-panel">
                <span class="section-kicker">Bản đồ</span>
                <h2>Bệnh viện Minh An</h2>
                <p>Quốc Lộ 1A - Quỳnh Giang - Quỳnh Lưu - Nghệ An</p>
                <div class="map-info-actions">
                    <a href="https://www.google.com/maps/search/?api=1&query=Qu%E1%BB%91c%20L%E1%BB%99%201A%20Qu%E1%BB%B3nh%20Giang%20Qu%E1%BB%B3nh%20L%C6%B0u%20Ngh%E1%BB%87%20An" target="_blank" rel="noopener">
                        <i class="fa fa-location-arrow"></i> Chỉ đường
                    </a>
                    <a href="{{ route('contact.create') }}">
                        <i class="fa fa-envelope-o"></i> Liên hệ
                    </a>
                </div>
            </div>
            <iframe
                title="Bản đồ định vị Bệnh viện Minh An"
                src="https://maps.google.com/maps?q=Qu%E1%BB%91c%20L%E1%BB%99%201A%20Qu%E1%BB%B3nh%20Giang%20Qu%E1%BB%B3nh%20L%C6%B0u%20Ngh%E1%BB%87%20An&z=15&output=embed"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen>
            </iframe>
        </section>

        <footer>
            <div class="container">
                <div class="row">
                    <div class="col-md-4 col-sm-6">
                        <div class="footer-thumb">
                            <h4>Hệ thống bệnh viện</h4>
                            <p>Tìm chuyên khoa, chọn bác sĩ và gửi lịch hẹn khám trong một quy trình rõ ràng.</p>
                            <div class="contact-info">
                                <p><i class="fa fa-phone"></i> 1900 1000</p>
                                <p><i class="fa fa-envelope-o"></i> support@hospital.test</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-6">
                        <div class="footer-thumb">
                            <h4>Điều hướng</h4>
                            <p><a href="{{ auth()->check() ? route('departments.index') : route('login') }}">Chuyên khoa</a></p>
                            <p><a href="{{ auth()->check() ? route('doctors.index') : route('login') }}">Bác sĩ</a></p>
                            <p><a href="{{ auth()->check() ? route('services.index') : route('login') }}">Dịch vụ</a></p>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-12">
                        <div class="footer-thumb">
                            <h4>Hỗ trợ</h4>
                            <p><a href="{{ auth()->check() ? route('appointments.create') : route('login') }}">Đặt lịch khám</a></p>
                            <p><a href="{{ auth()->check() ? route('contact.create') : route('login') }}">Liên hệ</a></p>
                            <p><a href="{{ url('/api/home') }}">Dữ liệu trang chủ</a></p>
                        </div>
                    </div>
                </div>

                <div class="row border-top">
                    <div class="col-md-8 col-sm-8">
                        <div class="copyright-text">
                            <p>Hệ thống bệnh viện - dữ liệu và giao diện người dùng</p>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-4 text-align-center">
                        <div class="angle-up-btn">
                            <a class="smoothScroll wow fadeInUp" href="#top" data-wow-delay="1s">
                                <i class="fa fa-angle-up"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </footer>

        @include('partials.chatbot')

        <script src="{{ asset('js/jquery.js') }}"></script>
        <script src="{{ asset('js/bootstrap.min.js') }}"></script>
        <script src="{{ asset('js/jquery.sticky.js') }}"></script>
        <script src="{{ asset('js/jquery.stellar.min.js') }}"></script>
        <script src="{{ asset('js/wow.min.js') }}"></script>
        <script src="{{ asset('js/smoothscroll.js') }}"></script>
        <script src="{{ asset('js/owl.carousel.min.js') }}"></script>
        <script src="{{ asset('js/custom.js') }}"></script>
        <script src="{{ asset('js/frontend.js') }}"></script>
    </body>
</html>
