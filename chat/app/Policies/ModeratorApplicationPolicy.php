<?php

namespace App\Policies;

use App\Models\ModeratorApplication;
use App\Models\User;

class ModeratorApplicationPolicy
{
    public function view(User $user, ModeratorApplication $application): bool
    {
        return $user->isModerator() || $user->id === $application->user_id;
    }

    public function vote(User $user, ModeratorApplication $application): bool
    {
        return $user->isModerator()
            && $application->status === 'pending'
            && $user->id !== $application->user_id;
    }
}