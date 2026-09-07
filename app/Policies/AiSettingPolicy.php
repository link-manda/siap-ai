<?php

namespace App\Policies;

use App\Models\AiSetting;
use App\Models\User;

class AiSettingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Pimpinan');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ?AiSetting $aiSetting = null): bool
    {
        return $user->hasRole('Pimpinan');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Pimpinan');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ?AiSetting $aiSetting = null): bool
    {
        return $user->hasRole('Pimpinan');
    }

    /**
     * Determine whether the user can manage or test connection.
     */
    public function manage(User $user): bool
    {
        return $user->hasRole('Pimpinan');
    }
}
