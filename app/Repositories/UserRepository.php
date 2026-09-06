<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function getAllPaginated(int $perPage = 10)
    {
        return User::with('roles')->latest()->paginate($perPage);
    }

    public function findById($id)
    {
        return User::findOrFail($id);
    }

    public function create(array $data)
    {
        return User::create($data);
    }

    public function update(User $user, array $data)
    {
        $user->update($data);
        return $user;
    }

    public function delete(User $user)
    {
        return $user->delete();
    }
}
