<?php

namespace App\Nova\Dashboards\Concerns;

use App\Nova\Resources\Issue;
use App\Nova\Resources\IssueChangelogItem;

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
        return IssueChangelogItem::getIssueStatusTransitionByDateTrend([
            'only_to' => static::$statuses,
            'except_from' => array_merge(static::$priorStatuses, static::$statuses)
        ])->label(static::$label . ' Kickbacks by Day');
    }

    /**
     * Returns the inflow trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getInflowTrendMetric()
    {
        return IssueChangelogItem::getIssueStatusTransitionByDateTrend([
            'only_to' => static::$statuses
        ])->label(static::$label . ' Inflow');
    }

    /**
     * Returns the outflow trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getOutflowTrendMetric()
    {
        return IssueChangelogItem::getIssueStatusTransitionByDateTrend([
            'only_from' => static::$statuses
        ])->label(static::$label . ' Outflow');
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
        return Issue::getIssueDeliquenciesByDueDateTrend()
            ->label(static::$label . ' Act. Delinquencies')
            ->whereIn('status_name', static::$statuses);
    }

    /**
     * Returns the estimated delinquencies trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getEstimatedDelinquenciesTrendMetric()
    {
        return Issue::getIssueDeliquenciesByEstimatedDateTrend()
            ->label(static::$label . ' Est. Delinquencies')
            ->whereIn('status_name', static::$statuses);
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
        return (new \App\Nova\Metrics\IssueWorkloadPartition)
            ->groupByEpic()
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
        return (new \App\Nova\Metrics\IssueWorkloadPartition)
            ->groupByPriority()
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
        return (new \App\Nova\Metrics\IssueWorkloadPartition)
            ->groupByAssignee()
            ->whereIn('status_name', static::$statuses)
            ->setName(static::$label . ' Rem. Workload (by Assignee)');
    }
}