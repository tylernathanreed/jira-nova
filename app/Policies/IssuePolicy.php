<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Issue;

class IssuePolicy extends Policy
{
    /**
     * Returns whether the user can view any issues.
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
     * Returns whether the user can view the specified issue.
     *
     * @param  \App\Models\User   $user
     * @param  \App\Models\Issue  $issue
     *
     * @return mixed
     */
    public function view(User $user, Issue $issue)
    {
        return true;
    }
}