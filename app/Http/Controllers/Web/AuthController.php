<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'Đã gửi liên kết đặt lại mật khẩu về Gmail đăng ký nếu tài khoản tồn tại.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => $this->passwordStatusMessage($status)]);
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with('success', 'Đã đặt lại mật khẩu. Bạn có thể đăng nhập bằng mật khẩu mới.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => $this->passwordStatusMessage($status)]);
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

    private function passwordStatusMessage(string $status): string
    {
        return match ($status) {
            Password::INVALID_USER => 'Không tìm thấy tài khoản với Gmail này.',
            Password::INVALID_TOKEN => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.',
            Password::RESET_THROTTLED => 'Bạn vừa yêu cầu đặt lại mật khẩu. Vui lòng chờ một lúc rồi thử lại.',
            default => 'Không thể xử lý yêu cầu đặt lại mật khẩu. Vui lòng thử lại.',
        };
    }
}
