<?php

namespace App\Policies;

use App\Models\User;
use App\Models\FocusGroup;

class FocusGroupPolicy extends Policy
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
     * @param  \App\Models\User        $user
     * @param  \App\Models\FocusGroup  $group
     *
     * @return mixed
     */
    public function view(User $user, FocusGroup $group)
    {
        return true;
    }

    /**
     * Returns whether the user can view any projects.
     *
     * @param  \App\Models\User        $user
     * @param  \App\Models\FocusGroup  $group
     *
     * @return mixed
     */
    public function update(User $user, FocusGroup $group)
    {
        return true;
    }
}