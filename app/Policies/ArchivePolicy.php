<?php

namespace App\Policies;

use App\Models\Archive;
use App\Models\User;

class ArchivePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Administrator', 'Pimpinan']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Archive $archive): bool
    {
        if ($user->hasRole('Pimpinan')) {
            return true;
        }

        if ($user->hasRole('Administrator')) {
            // Assume default level 1 if not explicitly loaded
            $level = $archive->classification?->level ?? 1;

            return $level <= 3;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Archive $archive): bool
    {
        return $this->view($user, $archive);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Archive $archive): bool
    {
        return $this->view($user, $archive);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Archive $archive): bool
    {
        return $this->view($user, $archive);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Archive $archive): bool
    {
        return false;
    }
}
