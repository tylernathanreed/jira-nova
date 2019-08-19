<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Epic;

class EpicPolicy extends Policy
{
    /**
     * Returns whether the user can view any epics.
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
     * Returns whether the user can view any epics.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Epic  $epic
     *
     * @return mixed
     */
    public function view(User $user, Epic $epic)
    {
        return true;
    }
}