<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Tài khoản') - Hệ thống bệnh viện</title>

        <link rel="stylesheet" href="{{ asset('css/font-awesome.min.css') }}">
        <link rel="stylesheet" href="{{ asset('asset/css/style.css') }}">
        <link rel="stylesheet" href="{{ asset('css/auth-template.css') }}">
    </head>
    <body class="img js-fullheight auth-template-body" style="background-image: url('{{ asset('asset/images/bg.jpg') }}');">
        <section class="ftco-section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-7 text-center mb-5">
                        <a class="auth-brand" href="{{ route('login') }}">
                            <i class="fa fa-h-square"></i>
                            Hệ thống bệnh viện
                        </a>
                        <h2 class="heading-section">@yield('heading', 'Tài khoản')</h2>
                    </div>
                </div>

                @if (session('success'))
                    <div class="row justify-content-center">
                        <div class="col-md-7 col-lg-5">
                            <div class="alert alert-success auth-alert" role="status">
                                {{ session('success') }}
                            </div>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="row justify-content-center">
                        <div class="col-md-7 col-lg-5">
                            <div class="alert alert-danger auth-alert" role="alert">
                                <strong>Dữ liệu chưa hợp lệ.</strong>
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="row justify-content-center">
                    <div class="col-md-7 col-lg-5">
                        <div class="login-wrap p-0">
                            @yield('content')
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <script src="{{ asset('asset/js/jquery.min.js') }}"></script>
        <script src="{{ asset('asset/js/popper.js') }}"></script>
        <script src="{{ asset('asset/js/bootstrap.min.js') }}"></script>
        <script src="{{ asset('asset/js/main.js') }}"></script>
        <script src="{{ asset('js/frontend.js') }}"></script>
    </body>
</html>
