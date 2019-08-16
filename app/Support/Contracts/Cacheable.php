<?php

namespace App\Support\Contracts;

use Carbon\Carbon;

interface Cacheable
{
    /**
     * Creates the cache records in this table from the cache source.
     *
     * @return void
     */
    public static function createFromCache();

    /**
     * Updates the cache records in this table from the cache source.
     *
     * @param  \Carbon\Carbon  $since
     *
     * @return void
     */
    public static function updateFromCache(Carbon $since);

    /**
     * Returns the number of records that will be created or updated from busting the cache.
     *
     * @param  \Carbon\Carbon  $since
     *
     * @return integer
     */
    public static function getNextCacheUpdateCount(Carbon $since) : integer;
}