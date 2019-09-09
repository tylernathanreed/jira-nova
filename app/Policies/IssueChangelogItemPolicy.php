<?php

namespace App\Policies;

use App\Models\User;
use App\Models\IssueChangelogItem;

class IssueChangelogItemPolicy extends Policy
{
    /**
     * Returns whether the user can view any issue changelog items.
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
     * Returns whether the user can view the specified issue changelog item.
     *
     * @param  \App\Models\User                $user
     * @param  \App\Models\IssueChangelogItem  $item
     *
     * @return mixed
     */
    public function view(User $user, IssueChangelogItem $item)
    {
        return true;
    }
}