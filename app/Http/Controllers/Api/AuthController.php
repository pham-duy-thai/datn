<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $data['role'] = 'patient';
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);
        $token = $this->issueToken($user);

        return response()->json([
            'message' => 'Đăng ký thành công.',
            'token_type' => 'Bearer',
            'token' => $token,
            'data' => $user->fresh(),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Thư điện tử hoặc mật khẩu không đúng.'], 422);
        }

        $token = $this->issueToken($user);

        return response()->json([
            'message' => 'Đăng nhập thành công.',
            'token_type' => 'Bearer',
            'token' => $token,
            'data' => $user->fresh()->load('doctor'),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->load('doctor')]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:255'],
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Đã cập nhật thông tin cá nhân.',
            'data' => $user->fresh()->load('doctor'),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'Mật khẩu hiện tại không đúng.'], 422);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        return response()->json(['message' => 'Đã đổi mật khẩu.']);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->forceFill(['api_token' => null])->save();

        return response()->json(['message' => 'Đăng xuất thành công.']);
    }

    private function issueToken(User $user): string
    {
        $token = Str::random(80);

        $user->forceFill([
            'api_token' => hash('sha256', $token),
        ])->save();

        return $token;
    }
}
