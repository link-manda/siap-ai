<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(protected UserRepository $userRepository) {}

    public function createUser(array $data, array $roles = [])
    {
        $data['password'] = Hash::make($data['password']);
        $user = $this->userRepository->create($data);

        if (! empty($roles)) {
            $user->syncRoles($roles);
        }

        return $user;
    }

    public function updateUser(User $user, array $data, array $roles = [])
    {
        if (isset($data['password']) && ! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user = $this->userRepository->update($user, $data);

        if (isset($roles)) {
            $user->syncRoles($roles);
        }

        return $user;
    }
}
