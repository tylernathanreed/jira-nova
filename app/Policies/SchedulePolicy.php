<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Schedule;

class SchedulePolicy extends Policy
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
     * @param  \App\Models\User      $user
     * @param  \App\Models\Schedule  $schedule
     *
     * @return mixed
     */
    public function view(User $user, Schedule $schedule)
    {
        return true;
    }

    /**
     * Returns whether the user can view any projects.
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
     * Returns whether the user can view any projects.
     *
     * @param  \App\Models\User        $user
     * @param  \App\Models\Schedule    $schedule
     *
     * @return mixed
     */
    public function update(User $user, Schedule $schedule)
    {
        return true;
    }

    /**
     * Returns whether the user can view any projects.
     *
     * @param  \App\Models\User        $user
     * @param  \App\Models\Schedule    $schedule
     *
     * @return mixed
     */
    public function delete(User $user, Schedule $schedule)
    {
        if($schedule->system_name == 'standard') {
            return false;
        }

        return true;
    }
}