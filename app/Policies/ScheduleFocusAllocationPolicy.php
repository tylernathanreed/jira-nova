<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Schedule;
use App\Models\ScheduleFocusAllocation;

class ScheduleFocusAllocationPolicy extends Policy
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
        // Check for a schedule
        if(!is_null($schedule = $this->getModelFromRequest(Schedule::class))) {

            // If the schedule is simple, then we can't use allocations
            if($schedule->type == Schedule::TYPE_SIMPLE) {
                return false;
            }

        }

        // Authorized
        return true;
    }

    /**
     * Returns whether the user can view any schedule allocations.
     *
     * @param  \App\Models\User                     $user
     * @param  \App\Models\ScheduleFocusAllocation  $allocation
     *
     * @return mixed
     */
    public function view(User $user, ScheduleFocusAllocation $allocation)
    {
        return true;
    }

    /**
     * Returns whether the user can view any schedule allocations.
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
     * Returns whether the user can view any schedule allocations.
     *
     * @param  \App\Models\User                     $user
     * @param  \App\Models\ScheduleFocusAllocation  $allocation
     *
     * @return mixed
     */
    public function update(User $user, ScheduleFocusAllocation $allocation)
    {
        return true;
    }

    /**
     * Returns whether the user can view any schedule allocations.
     *
     * @param  \App\Models\User                     $user
     * @param  \App\Models\ScheduleFocusAllocation  $allocation
     *
     * @return mixed
     */
    public function delete(User $user, ScheduleFocusAllocation $allocation)
    {
        if($allocation->schedule->system_name == 'standard') {
            return false;
        }

        return true;
    }
}