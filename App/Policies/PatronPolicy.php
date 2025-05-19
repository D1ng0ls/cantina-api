<?php

namespace App\Policies;

use App\Models\User;

class PatronPolicy
{
    /**
     * Create a new policy instance.
     */

    public function patron(User $user)
    {
        return $user->role === 'patron';
    }
}
