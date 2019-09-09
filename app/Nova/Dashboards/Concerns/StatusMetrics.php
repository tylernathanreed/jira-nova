<?php

namespace App\Nova\Dashboards\Concerns;

trait StatusMetrics
{
    /**
     * Returns the kickback value metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getKickbacksValueMetric()
    {
        return (new \App\Nova\Metrics\IssueStatusTransitionByDateValue)
            ->onlyTo(static::$statuses)
            ->exceptFrom(array_merge(static::$priorStatuses, static::$statuses))
            ->setName(static::$label . ' Kickbacks');
    }

    /**
     * Returns the kickback trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getKickbacksTrendMetric()
    {
        return (new \App\Nova\Metrics\IssueStatusTransitionByDateTrend)
            ->onlyTo(static::$statuses)
            ->exceptFrom(array_merge(static::$priorStatuses, static::$statuses))
            ->setName(static::$label . ' Kickbacks');
    }
}