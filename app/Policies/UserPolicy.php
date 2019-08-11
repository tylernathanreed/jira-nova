<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends Policy
{
    /**
     * Returns whether the user can view any users.
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
     * Returns whether the user can view any users.
     *
     * @param  \App\Models\User  $auth
     * @param  \App\Models\User  $user
     *
     * @return mixed
     */
    public function view(User $auth, User $user)
    {
        return true;
    }

    /**
     * Returns whether the user can update any users.
     *
     * @param  \App\Models\User  $auth
     * @param  \App\Models\User  $user
     *
     * @return mixed
     */
    public function update(User $auth, User $user)
    {
        return true;
    }
}