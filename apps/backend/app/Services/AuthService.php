<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use RuntimeException;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function register(array $data): array
    {
        $warehouseIds = $data['warehouse_ids'] ?? [];

        /** @var User $user */
        $user = $this->userRepository->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? UserRole::STAFF,
            'phone' => $data['phone'] ?? null,
        ]);

        if (! empty($warehouseIds)) {
            $user->warehouses()->sync($warehouseIds);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user->fresh(['warehouses']),
            'token' => $token,
        ];
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new RuntimeException('Email hoặc mật khẩu không đúng');
        }

        if (! $user->is_active) {
            throw new RuntimeException('Tài khoản của bạn đã bị khóa');
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user->fresh(['warehouses']),
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }

    public function sendPasswordResetLink(string $email): string
    {
        return Password::sendResetLink(['email' => $email]);
    }
}
