<?php

namespace App\Policies;

use App\Models\User;
use App\Models\IssueType;

class IssueTypePolicy extends Policy
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
     * @param  \App\Models\IssueType  $issueType
     *
     * @return mixed
     */
    public function view(User $user, IssueType $issueType)
    {
        return true;
    }
}