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
        return IssueChangelogItem::getIssueStatusTransitionByDateValue([
            'only_to' => static::$statuses,
            'except_from' => array_merge(static::$priorStatuses, static::$statuses)
        ])->label(static::$label . ' Kickbacks')
          ->help('This metric shows the total number of recent transition that went backwards in the workflow.');
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
        ])->label(static::$label . ' Kickbacks by Day')
          ->help('This metric shows the number per day of recent transitions that went backwards in the workflow.');
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
        ])->label(static::$label . ' Inflow')
          ->help('This metric shows the number per day of recent transitions that entered the ' . static::$label . ' phase.');
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
        ])->label(static::$label . ' Outflow')
          ->help('This metric shows the number per day of recent transitions that left the ' . static::$label . ' phase.');
    }

    /**
     * Returns the equilibrium trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getEquilibriumTrendMetric()
    {
        return IssueChangelogItem::getIssueStatusEquilibriumTrend(static::$statuses)
            ->label(static::$label . ' Equilibrium')
            ->help('This metric shows the percent-comparison between the inflow and outflow of the ' . static::$label . ' phase, where 100% indicates stagnation.');
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
     * Returns the promises made value metric.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getPromisesMadeValueMetric()
    {
        return IssueChangelogItem::getPromisesMadeValue(static::$statuses)
            ->label(static::$label . ' Promises Made');
    }

    /**
     * Returns the promises kept value metric.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getPromisesKeptValueMetric()
    {
        return IssueChangelogItem::getPromisesKeptValue(static::$statuses)
            ->label(static::$label . ' Promises Kept');
    }

    /**
     * Returns the satisfaction value metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getSatisfactionValueMetric()
    {
        return IssueChangelogItem::getPromiseIntegrityValue(static::$statuses)
            ->label(static::$label . ' Commitments Kept')
            ->help('This metric shows the percentage of issues with recent due dates that were transitioned out of the ' . static::$label . ' phase prior to becoming delinquent.');
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