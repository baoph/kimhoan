<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng đăng nhập',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = auth()->user();

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản của bạn đã bị khóa',
            ], Response::HTTP_FORBIDDEN);
        }

        if (! in_array($user->role, $roles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập chức năng này',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
