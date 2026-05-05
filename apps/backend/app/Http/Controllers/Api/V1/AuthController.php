<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function register(RegisterRequest $request)
    {
        $result = $this->authService->register($request->validated());

        return $this->successResponse([
            'user' => (new UserResource($result['user']))->resolve(),
            'token' => $result['token'],
        ], 'Đăng ký thành công', 201);
    }

    public function login(LoginRequest $request)
    {
        try {
            $result = $this->authService->login($request->email, $request->password);

            auth()->setUser($result['user']);
            logActivity('login', 'Đăng nhập hệ thống', 'auth', $result['user']->id);

            return $this->successResponse([
                'user' => (new UserResource($result['user']))->resolve(),
                'token' => $result['token'],
            ], 'Đăng nhập thành công');
        } catch (RuntimeException $exception) {
            $message = $exception->getMessage();
            $statusCode = str_contains($message, 'khóa') ? 403 : 401;

            return $this->errorResponse($message, null, $statusCode);
        }
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không hợp lệ.',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Dữ liệu gửi lên không hợp lệ', $validator->errors(), 422);
        }

        $status = $this->authService->sendPasswordResetLink($validator->validated()['email']);

        if ($status !== Password::RESET_LINK_SENT) {
            return $this->errorResponse(__($status), null, 422);
        }

        return $this->successResponse(null, __($status));
    }

    public function profile(Request $request)
    {
        return $this->successResponse((new UserResource($request->user()))->resolve(), 'Lấy thông tin tài khoản thành công');
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return $this->successResponse(null, 'Đăng xuất thành công');
    }
}
