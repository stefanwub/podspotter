<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WhisperJob;
use Illuminate\Auth\Access\Response;

class WhisperJobPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->super_admin;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WhisperJob $whisperJob): bool
    {
        return $user->super_admin;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->super_admin;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WhisperJob $whisperJob): bool
    {
        return $user->super_admin;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WhisperJob $whisperJob): bool
    {
        return $user->super_admin;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, WhisperJob $whisperJob): bool
    {
        return $user->super_admin;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, WhisperJob $whisperJob): bool
    {
        return $user->super_admin;
    }
}