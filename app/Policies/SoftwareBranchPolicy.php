<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SoftwareBranch;

class SoftwareBranchPolicy extends Policy
{
    /**
     * Returns whether the user can view any software branches.
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
     * Returns whether the user can view the specified software branch.
     *
     * @param  \App\Models\User            $user
     * @param  \App\Models\SoftwareBranch  $branch
     *
     * @return mixed
     */
    public function view(User $user, SoftwareBranch $branch)
    {
        // Don't allow in lenses
        if(request()->route()->uri() == 'nova-api/{resource}/lens/{lens}') {
            return false;
        }

        // Otherwise, allow
        return true;
    }

    /**
     * Returns whether the user can create a new software branch.
     *
     * @param  \App\Models\User  $user
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Returns whether the user can update the specified software branch.
     *
     * @param  \App\Models\User            $user
     * @param  \App\Models\SoftwareBranch  $branch
     *
     * @return mixed
     */
    public function update(User $user, SoftwareBranch $branch)
    {
        // Don't allow in lenses
        if(request()->route()->uri() == 'nova-api/{resource}/lens/{lens}') {
            return false;
        }

        // Otherwise, allow
        return true;
    }

    /**
     * Returns whether the user can delete the specified software branch.
     *
     * @param  \App\Models\User            $user
     * @param  \App\Models\SoftwareBranch  $branch
     *
     * @return mixed
     */
    public function delete(User $user, SoftwareBranch $branch)
    {
        // Don't allow in lenses
        if(request()->route()->uri() == 'nova-api/{resource}/lens/{lens}') {
            return false;
        }

        // Otherwise, allow
        return true;
    }
}