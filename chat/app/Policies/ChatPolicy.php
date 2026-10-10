<?php

namespace App\Policies;

use App\Models\Chat;
use App\Models\User;

class ChatPolicy
{
    public function delete(User $user, Chat $chat): bool
    {
        return $user->isModerator();
    }

    public function restore(User $user, Chat $chat): bool
    {
        return $user->isAdmin();
    }
}