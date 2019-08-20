<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Label;

class LabelPolicy extends Policy
{
    /**
     * Returns whether the user can view any labels.
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
     * Returns whether the user can view any labels.
     *
     * @param  \App\Models\User   $user
     * @param  \App\Models\Label  $label
     *
     * @return mixed
     */
    public function view(User $user, Label $label)
    {
        return true;
    }
}