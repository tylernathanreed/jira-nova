<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Issue;
use App\Models\Version;

class VersionPolicy extends Policy
{
    /**
     * Returns whether the user can view any labels.
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
     * Returns whether the user can view any labels.
     *
     * @param  \App\Models\User     $user
     * @param  \App\Models\Version  $label
     *
     * @return mixed
     */
    public function view(User $user, Version $label)
    {
        return true;
    }

    /**
     * Returns whether the user can attach any issue to the given label.
     *
     * @param  \App\Models\User     $user
     * @param  \App\Models\Version  $label
     *
     * @return mixed
     */
    public function attachAnyIssue(User $user, Version $label)
    {
        return false;
    }

    /**
     * Returns whether the user can attach the specified issue to the given label.
     *
     * @param  \App\Models\User     $user
     * @param  \App\Models\Version  $label
     * @param  \App\Models\Issue    $issue
     *
     * @return mixed
     */
    public function attachIssue(User $user, Version $label, Issue $issue)
    {
        return false;
    }

    /**
     * Returns whether the user can detach the specified issue to the given label.
     *
     * @param  \App\Models\User     $user
     * @param  \App\Models\Version  $label
     * @param  \App\Models\Issue    $issue
     *
     * @return mixed
     */
    public function detachIssue(User $user, Version $label, Issue $issue)
    {
        return false;
    }
}