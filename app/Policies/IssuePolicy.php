<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Issue;

class IssuePolicy extends Policy
{
    /**
     * Returns whether the user can view any projects.
     *
     * @param  \App\Models\User  $user
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }
}