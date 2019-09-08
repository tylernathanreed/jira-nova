<?php

namespace App\Nova\Dashboards;

class DevelopmentDashboard extends Dashboard
{
    /**
     * The displayable name for this dashboard.
     *
     * @var string
     */
    protected static $label = 'Development';

    /** 
     * The prior status types that indicate standard workflow.
     *
     * @var array
     */
    protected static $priorStatuses = [
        'New',
        'In Design',
        'Need Client Clarification',
        'Dev Help Needed',
        'Waiting for approval',
        'Validating'
    ];

    /** 
     * The primary status types for this dashboard.
     *
     * @var array
     */
    protected static $statuses = [
        'Assigned',
        'Dev Hold',
        'Dev Complete',
        'In Development',
        'Testing Failed'
    ];

    /**
     * Get the cards for the dashboard.
     *
     * @return array
     */
    public function cards()
    {
        return [
            (new \App\Nova\Metrics\IssueStatusTransitionByDateValue)
                ->onlyTo(static::$statuses)
                ->exceptFrom(array_merge(static::$priorStatuses, static::$statuses))
                ->setName(static::$label . ' Kickbacks'),

            (new \App\Nova\Metrics\IssueStatusTransitionByDateTrend)
                ->onlyTo(static::$statuses)
                ->exceptFrom(array_merge(static::$priorStatuses, static::$statuses))
                ->setName(static::$label . ' Kickbacks')
                ->width('2/3'),

            (new \App\Nova\Metrics\IssueStatusTransitionByDateTrend)
                ->onlyTo(static::$statuses)
                ->setName(static::$label . ' Inflow'),

            (new \App\Nova\Metrics\IssueStatusTransitionByDateTrend)
                ->onlyFrom(static::$statuses)
                ->setName(static::$label . ' Outflow'),

            (new \App\Nova\Metrics\IssueStatusResolutionByDateValue)
                ->statuses(static::$statuses)
                ->setName(static::$label . ' Equilibrium'),

            (new \App\Nova\Metrics\IssueDelinquentByDueDateTrend)
                ->whereIn('status_name', static::$statuses)
                ->setName(static::$label . ' Act. Delinquencies'),

            (new \App\Nova\Metrics\IssueDelinquentByEstimatedDateTrend)
                ->whereIn('status_name', static::$statuses)
                ->setName(static::$label . ' Est. Delinquencies'),

            (new \App\Nova\Metrics\IssueStatusSatisfactionByDateValue)
                ->statuses(static::$statuses)
                ->setName(static::$label . ' Commitments Kept'),

            (new \App\Nova\Metrics\IssueWorkloadByEpicPartition)
                ->whereIn('status_name', static::$statuses)
                ->setName(static::$label . ' Rem. Workload (by Epic)'),

            (new \App\Nova\Metrics\IssueWorkloadByPriorityPartition)
                ->whereIn('status_name', static::$statuses)
                ->setName(static::$label . ' Rem. Workload (by Priority)'),

            (new \App\Nova\Metrics\IssueWorkloadByAssigneePartition)
                ->whereIn('status_name', static::$statuses)
                ->setName(static::$label . ' Rem. Workload (by Assignee)')

        ];
    }
}
