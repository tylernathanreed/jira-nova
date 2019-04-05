<?php

namespace App\Policies;

use App\Models\User;
use App\Models\IssueStatusType;

class IssueStatusTypePolicy extends Policy
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
     * @param  \App\Models\User             $user
     * @param  \App\Models\IssueStatusType  $issueStatusType
     *
     * @return mixed
     */
    public function view(User $user, IssueStatusType $issueStatusType)
    {
        return true;
    }
}