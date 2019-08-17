<?php

namespace App\Support\Contracts;

use Carbon\Carbon;

interface Cacheable
{
    /**
     * Returns the information required to cache this model.
     *
     * @param  \Carbon\Carbon|null  $since
     *
     * @return array
     */
    public static function getCachePages(Carbon $since = null);
}