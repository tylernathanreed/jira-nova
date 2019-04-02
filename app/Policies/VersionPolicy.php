<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Version;

class VersionPolicy extends Policy
{
    /**
     * Returns whether the user can view any versions.
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
     * Returns whether the user can view any versions.
     *
     * @param  \App\Models\User     $user
     * @param  \App\Models\Version  $version
     *
     * @return mixed
     */
    public function view(User $user, Version $version)
    {
        return true;
    }
}