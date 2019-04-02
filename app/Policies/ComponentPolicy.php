<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Component;

class ComponentPolicy extends Policy
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
     * @param  \App\Models\Component  $component
     *
     * @return mixed
     */
    public function view(User $user, Component $component)
    {
        return true;
    }
}