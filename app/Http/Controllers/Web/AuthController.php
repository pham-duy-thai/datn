<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Thư điện tử hoặc mật khẩu không đúng.',
            ]);
        }

        $request->session()->regenerate();

        if ($request->user()->must_change_password) {
            return redirect()
                ->route('account.show')
                ->with('password_change_prompt', 'Bạn nên đổi mật khẩu mới để bảo mật tài khoản hoặc bỏ qua bước này.');
        }

        $destination = in_array($request->user()->role, ['admin', 'receptionist'], true)
            ? route('admin.dashboard')
            : route('home');

        return redirect()
            ->intended($destination)
            ->with('success', 'Đăng nhập thành công.');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate(
            ['email' => ['required', 'email', 'exists:users,email']],
            ['email.exists' => 'Không tìm thấy tài khoản với Gmail này.']
        );

        $user = User::where('email', $data['email'])->firstOrFail();
        $code = (string) random_int(100000, 999999);

        if (
            blank(config('mail.mailers.smtp.username'))
            || blank(config('mail.mailers.smtp.password'))
            || str_contains((string) config('mail.mailers.smtp.username'), 'your-')
            || str_contains((string) config('mail.mailers.smtp.password'), 'your-')
        ) {
            throw ValidationException::withMessages([
                'email' => 'Chưa cấu hình Gmail SMTP thật. Vui lòng cập nhật MAIL_USERNAME và MAIL_PASSWORD trong file .env.',
            ]);
        }

        try {
            DB::transaction(function () use ($user, $code): void {
                DB::table('password_reset_codes')
                    ->where('email', $user->email)
                    ->whereNull('verified_at')
                    ->delete();

                DB::table('password_reset_codes')->insert([
                    'email' => $user->email,
                    'code_hash' => Hash::make($code),
                    'expires_at' => now()->addMinutes(10),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Mail::raw(
                    "Xin chào {$user->name},\n\n"
                    ."Mã xác nhận đặt lại mật khẩu của bạn là: {$code}\n\n"
                    ."Mã này có hiệu lực trong 10 phút. Không chia sẻ mã này cho người khác.\n\n"
                    ."Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.",
                    function ($message) use ($user): void {
                        $message->to($user->email, $user->name)
                            ->subject('Mã xác nhận đặt lại mật khẩu - Hệ thống bệnh viện');
                    }
                );
            });
        } catch (Throwable $exception) {
            report($exception);

            $message = str_contains($exception->getMessage(), '535')
                ? 'Gmail từ chối đăng nhập SMTP. Vui lòng tạo Gmail App Password mới và cập nhật MAIL_PASSWORD trong file .env.'
                : 'Không gửi được email. Vui lòng kiểm tra Gmail App Password trong file .env.';

            throw ValidationException::withMessages([
                'email' => $message,
            ]);
        }

        $request->session()->put('password_reset_email', $user->email);

        return redirect()
            ->route('password.code')
            ->with('success', 'Đã gửi mã xác nhận về Gmail đăng ký.');
    }

    public function showVerifyCode(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        return view('auth.verify-code', ['email' => $email]);
    }

    public function verifyResetCode(Request $request): RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $record = DB::table('password_reset_codes')
            ->where('email', $email)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $record || ! Hash::check($data['code'], $record->code_hash)) {
            return back()
                ->withInput()
                ->withErrors(['code' => 'Mã xác nhận không đúng hoặc đã hết hạn.']);
        }

        DB::table('password_reset_codes')
            ->where('id', $record->id)
            ->update([
                'verified_at' => now(),
                'updated_at' => now(),
            ]);

        $request->session()->put('password_reset_verified_email', $email);

        return redirect()
            ->route('password.reset')
            ->with('success', 'Mã xác nhận hợp lệ. Vui lòng tạo mật khẩu mới.');
    }

    public function showResetPassword(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('password_reset_verified_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password', ['email' => $email]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $email = $request->session()->get('password_reset_verified_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::where('email', $email)->firstOrFail();
        $user->forceFill([
            'password' => Hash::make((string) $request->input('password')),
            'must_change_password' => false,
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('password_reset_codes')->where('email', $email)->delete();

        $request->session()->forget([
            'password_reset_email',
            'password_reset_verified_email',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        $destination = in_array($user->role, ['admin', 'receptionist'], true)
            ? route('admin.dashboard')
            : route('home');

        return redirect()
            ->to($destination)
            ->with('success', 'Đã cập nhật mật khẩu mới. Bạn đã được đăng nhập.');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => 'patient',
            'password' => $data['password'],
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('home')
            ->with('success', 'Đăng ký thành công. Bạn đã được đăng nhập.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Đã đăng xuất.');
    }

}
