<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Priority;

class PriorityPolicy extends Policy
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

    /**
     * Returns whether the user can view any projects.
     *
     * @param  \App\Models\User       $user
     * @param  \App\Models\Priority   $priority
     *
     * @return mixed
     */
    public function view(User $user, Priority $priority)
    {
        return true;
    }
}