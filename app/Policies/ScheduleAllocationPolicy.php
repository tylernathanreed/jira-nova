<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ScheduleAllocation;

class ScheduleAllocationPolicy extends Policy
{
    /**
     * Returns whether the user can view any schedule allocations.
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
     * Returns whether the user can view any schedule allocations.
     *
     * @param  \App\Models\User                $user
     * @param  \App\Models\ScheduleAllocation  $allocation
     *
     * @return mixed
     */
    public function view(User $user, ScheduleAllocation $allocation)
    {
        return true;
    }
}