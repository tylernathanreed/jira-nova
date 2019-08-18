<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Cache;

class CachePolicy extends Policy
{
    /**
     * Returns whether the user can view any caches.
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
     * Returns whether the user can view the specified cache.
     *
     * @param  \App\Models\User   $user
     * @param  \App\Models\Cache  $cache
     *
     * @return mixed
     */
    public function view(User $user, Cache $cache)
    {
        return true;
    }

    /**
     * Returns whether the user can create any caches.
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
     * Returns whether the user can update the specified cache.
     *
     * @param  \App\Models\User   $user
     * @param  \App\Models\Cache  $cache
     *
     * @return mixed
     */
    public function update(User $user, Cache $cache)
    {
        return true;
    }

    /**
     * Returns whether the user can delete the specified cache.
     *
     * @param  \App\Models\User   $user
     * @param  \App\Models\Cache  $cache
     *
     * @return mixed
     */
    public function delete(User $user, Cache $cache)
    {
        return true;
    }
}