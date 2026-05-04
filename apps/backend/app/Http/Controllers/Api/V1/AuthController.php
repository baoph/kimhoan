<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => $request->role ?? 'staff',
            'phone' => $request->phone,
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'token' => $token,
        ], 'Đăng ký thành công', 201);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Email hoặc mật khẩu không đúng', null, 401);
        }

        if (! $user->is_active) {
            return $this->errorResponse('Tài khoản của bạn đã bị khóa', null, 403);
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('api-token')->plainTextToken;

        auth()->setUser($user);
        logActivity('login', 'Đăng nhập hệ thống', 'auth', $user->id);

        return $this->successResponse([
            'user' => $user->fresh(),
            'token' => $token,
        ], 'Đăng nhập thành công');
    }

    public function profile(Request $request)
    {
        return $this->successResponse($request->user(), 'Lấy thông tin tài khoản thành công');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->successResponse(null, 'Đăng xuất thành công');
    }
}
