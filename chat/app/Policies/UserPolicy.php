<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function delete(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return false;
        }
        if ($user->isAdmin()) {
            return in_array($target->role, ['user', 'moderator'], true);
        }
        if ($user->isPureModerator()) {
            return $target->role === 'user';
        }
        return false;
    }
}