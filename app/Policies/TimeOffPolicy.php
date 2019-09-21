<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TimeOff;

class TimeOffPolicy extends Policy
{
    /**
     * Returns whether the user can view any timeoffs.
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
     * Returns whether the user can view any timeoffs.
     *
     * @param  \App\Models\User     $user
     * @param  \App\Models\TimeOff  $timeoff
     *
     * @return mixed
     */
    public function view(User $user, TimeOff $timeoff)
    {
        return true;
    }

    /**
     * Returns whether the user can view any timeoffs.
     *
     * @param  \App\Models\User  $user
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Returns whether the user can view any timeoffs.
     *
     * @param  \App\Models\User       $user
     * @param  \App\Models\TimeOff    $timeoff
     *
     * @return mixed
     */
    public function update(User $user, TimeOff $timeoff)
    {
        return true;
    }

    /**
     * Returns whether the user can view any timeoffs.
     *
     * @param  \App\Models\User       $user
     * @param  \App\Models\TimeOff    $timeoff
     *
     * @return mixed
     */
    public function delete(User $user, TimeOff $timeoff)
    {
        return true;
    }
}