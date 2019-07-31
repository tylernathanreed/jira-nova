<?php

namespace App\Nova\Metrics\Concerns;

trait DashboardCaching
{
    /**
     * Determine for how many minutes the metric should be cached.
     *
     * @return  \DateTimeInterface|\DateInterval|float|int
     */
    public function cacheFor()
    {
        return 15;
    }
}