<?php

namespace App\Policies;

use App\Models\User;
use App\Models\IssueWorklog;

class IssueWorklogPolicy extends Policy
{
    /**
     * Returns whether the user can view any issue worklogs.
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
     * Returns whether the user can view the specified issue worklog.
     *
     * @param  \App\Models\User            $user
     * @param  \App\Models\IssueWorklog    $worklog
     *
     * @return mixed
     */
    public function view(User $user, IssueWorklog $worklog)
    {
        // Don't allow in lenses
        if(request()->route()->uri() == 'nova-api/{resource}/lens/{lens}') {
            return false;
        }

        // Otherwise, allow
        return true;
    }
}