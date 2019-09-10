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

    /**
     * Returns the inflow trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getInflowTrendMetric()
    {
        return (new \App\Nova\Metrics\IssueStatusTransitionByDateTrend)
            ->onlyTo(static::$statuses)
            ->setName(static::$label . ' Inflow');
    }

    /**
     * Returns the outflow trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getOutflowTrendMetric()
    {
        return (new \App\Nova\Metrics\IssueStatusTransitionByDateTrend)
            ->onlyFrom(static::$statuses)
            ->setName(static::$label . ' Outflow');
    }

    /**
     * Returns the equilibrium trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getEquilibriumTrendMetric()
    {
        return (new \App\Nova\Metrics\IssueStatusResolutionByDateValue)
            ->statuses(static::$statuses)
            ->setName(static::$label . ' Equilibrium');
    }

    /**
     * Returns the actual delinquencies trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getActualDelinquenciesTrendMetric()
    {
        return (new \App\Nova\Metrics\IssueDelinquentByDueDateTrend)
            ->whereIn('status_name', static::$statuses)
            ->setName(static::$label . ' Act. Delinquencies');
    }

    /**
     * Returns the estimated delinquencies trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getEstimatedDelinquenciesTrendMetric()
    {
        return (new \App\Nova\Metrics\IssueDelinquentByEstimatedDateTrend)
            ->whereIn('status_name', static::$statuses)
            ->setName(static::$label . ' Est. Delinquencies');
    }

    /**
     * Returns the satisfaction value metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getSatisfactionValueMetric()
    {
        return (new \App\Nova\Metrics\IssueStatusSatisfactionByDateValue)
            ->statuses(static::$statuses)
            ->setName(static::$label . ' Commitments Kept');
    }

    /**
     * Returns the workload by epic partition metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getWorkloadByEpicPartitionMetric()
    {
        return (new \App\Nova\Metrics\IssueWorkloadByEpicPartition)
            ->whereIn('status_name', static::$statuses)
            ->setName(static::$label . ' Rem. Workload (by Epic)');
    }

    /**
     * Returns the workload by priority partition metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getWorkloadByPriorityPartitionMetric()
    {
        return (new \App\Nova\Metrics\IssueWorkloadByPriorityPartition)
            ->whereIn('status_name', static::$statuses)
            ->setName(static::$label . ' Rem. Workload (by Priority)');
    }

    /**
     * Returns the workload by assignee partition metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getWorkloadByAssigneePartitionMetric()
    {
        return (new \App\Nova\Metrics\IssueWorkloadByAssigneePartition)
            ->whereIn('status_name', static::$statuses)
            ->setName(static::$label . ' Rem. Workload (by Assignee)');
    }
}