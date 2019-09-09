<?php

namespace App\Policies;

use App\Models\User;
use App\Models\IssueChangelog;

class IssueChangelogPolicy extends Policy
{
    /**
     * Returns whether the user can view any issue changelogs.
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
     * Returns whether the user can view the specified issue changelog.
     *
     * @param  \App\Models\User            $user
     * @param  \App\Models\IssueChangelog  $changelog
     *
     * @return mixed
     */
    public function view(User $user, IssueChangelog $changelog)
    {
        // Don't allow in lenses
        if(request()->route()->uri() == 'nova-api/{resource}/lens/{lens}') {
            return false;
        }

        // Otherwise, allow
        return true;
    }
}