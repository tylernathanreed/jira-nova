<?php

namespace App\Nova\Dashboards;

class WorklogDashboard extends Dashboard
{
    /**
     * The displayable name for this dashboard.
     *
     * @var string
     */
    protected static $label = 'Worklog';

    /**
     * The primary resource for this dashboard.
     *
     * @var string
     */
    protected static $resource = \App\Nova\Resources\IssueWorklog::class;

    /**
     * Get the cards for the dashboard.
     *
     * @return array
     */
    public function cards()
    {
        return [
            static::getInflowTrendMetric(),
            static::getCountByAssigneePartitionMetric(),
            static::getEfficiencyValue(),

            static::getWorklogTrend(),
            static::getExpectedWorklogValue(),
            static::getEquilibriumTrendMetric(),

            static::getActualDelinquenciesTrendMetric(),
            static::getEstimatedDelinquenciesTrendMetric(),
            static::getSatisfactionValueMetric(),

            static::getWorklogByEpicPartition(),
            static::getWorklogByPriorityPartition(),
            static::getWorklogByAuthorPartition()
        ];
    }

    /**
     * Returns the scope for this dashboard.
     *
     * @return \Closure
     */
    public static function scope()
    {
        return function($query) {
            $query->defects();
        };
    }

    /**
     * Creates and returns a new resource.
     *
     * @return \App\Nova\Resources\Resource
     */
    public static function resource()
    {
        $class = static::$resource;

        return new $class($class::newModel());
    }

    /**
     * Returns the inflow trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getInflowTrendMetric()
    {
        return (new \App\Nova\Metrics\IssueCreatedByDateTrend)
            ->filter(static::scope())
            ->setName(static::$label . ' Inflow');
    }

    /**
     * Returns the count by assignee partition metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getCountByAssigneePartitionMetric()
    {
        return (new \App\Nova\Metrics\IssueCountPartition)
            ->groupByAssignee()
            ->filter(static::scope())
            ->setName(static::$label . ' Rem. Count (by Assignee)');
    }

    /**
     * Returns the efficiency value metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getEfficiencyValue()
    {
        return static::resource()->getEfficiencyValue();
    }

    /**
     * Returns the worklog trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getWorklogTrend()
    {
        return static::resource()->getWorklogTrend();
    }

    /**
     * Returns the expected worklog value metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getExpectedWorklogValue()
    {
        return static::resource()->getExpectedWorklogValue();
    }

    /**
     * Returns the equilibrium trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getEquilibriumTrendMetric()
    {
        return (new \App\Nova\Metrics\IssueCountResolutionByDateValue)
            ->filter(static::scope())
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
            ->filter(static::scope())
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
            ->filter(static::scope())
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
            ->filter(static::scope())
            ->statuses([
                'New',
                'In Design',
                'Need Client Clarification',
                'Dev Help Needed',
                'Waiting for approval',
                'Validating',
                'Assigned',
                'Dev Hold',
                'Dev Complete',
                'In Development',
                'Testing Failed',
                'Ready to Test [Test]',
                'Ready to test [UAT]',
                'Test Help Needed'
            ])
            ->setName(static::$label . ' Commitments Kept');
    }

    /**
     * Returns the count by epic partition metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getWorklogByEpicPartition()
    {
        return static::resource()->getWorklogByEpicPartition();
    }

    /**
     * Returns the worklog by priority partition metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getWorklogByPriorityPartition()
    {
        return static::resource()->getWorklogByPriorityPartition();
    }

    /**
     * Returns the worklog by author partition metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getWorklogByAuthorPartition()
    {
        return static::resource()->getWorklogByAuthorPartition();
    }
}
