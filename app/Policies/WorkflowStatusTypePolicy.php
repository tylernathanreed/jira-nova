<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkflowStatusType;

class WorkflowStatusTypePolicy extends Policy
{
    /**
     * Returns whether the user can view any statuses.
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
     * Returns whether the user can view the specified status.
     *
     * @param  \App\Models\User                $user
     * @param  \App\Models\WorkflowStatusType  $status
     *
     * @return mixed
     */
    public function view(User $user, WorkflowStatusType $status)
    {
        return true;
    }

    /**
     * Returns whether the user can update the specified status.
     *
     * @param  \App\Models\User                $user
     * @param  \App\Models\WorkflowStatusType  $status
     *
     * @return mixed
     */
    public function update(User $user, WorkflowStatusType $status)
    {
        return true;
    }

}