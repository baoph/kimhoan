<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $warehouseIds = $data['warehouse_ids'] ?? [];
            unset($data['warehouse_ids']);

            $data['password'] = Hash::make($data['password']);

            /** @var User $user */
            $user = $this->userRepository->create($data);

            if (! empty($warehouseIds)) {
                $user->warehouses()->sync($warehouseIds);
            }

            return $user->fresh(['warehouses']);
        });
    }

    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $warehouseIds = null;
            if (array_key_exists('warehouse_ids', $data)) {
                $warehouseIds = collect($data['warehouse_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();
                unset($data['warehouse_ids']);
            }

            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            /** @var User $updated */
            $updated = $this->userRepository->update($user, $data);

            if ($warehouseIds !== null) {
                $updated->warehouses()->sync($warehouseIds);
            }

            return $updated->fresh(['warehouses']);
        });
    }

    public function changePassword(User $user, string $newPassword): User
    {
        return DB::transaction(function () use ($user, $newPassword) {
            /** @var User $updated */
            $updated = $this->userRepository->update($user, [
                'password' => Hash::make($newPassword),
            ]);

            return $updated;
        });
    }

    public function getUsersByWarehouse(int $warehouseId): Collection
    {
        return $this->userRepository->getByWarehouse($warehouseId);
    }

    public function getUsersByRole(UserRole $role): Collection
    {
        return $this->userRepository->getByRole($role);
    }
}
