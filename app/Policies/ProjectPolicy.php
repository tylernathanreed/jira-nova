<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Project;

class ProjectPolicy extends Policy
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
     * @param  \App\Models\User     $user
     * @param  \App\Models\Project  $project
     *
     * @return mixed
     */
    public function view(User $user, Project $project)
    {
        return true;
    }
}