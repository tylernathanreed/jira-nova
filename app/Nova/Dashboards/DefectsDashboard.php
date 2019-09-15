<?php

namespace App\Nova\Dashboards;

class DefectsDashboard extends Dashboard
{
    /**
     * The displayable name for this dashboard.
     *
     * @var string
     */
    protected static $label = 'Defects';

    /**
     * Get the cards for the dashboard.
     *
     * @return array
     */
    public function cards()
    {
        return [
            static::getCreatedValueMetric(),
            static::getCountByLabelPartitionMetric(),
            static::getCountByVersionPartitionMetric(),

            static::getInflowTrendMetric(),
            static::getOutflowTrendMetric(),
            static::getEquilibriumTrendMetric(),

            static::getActualDelinquenciesTrendMetric(),
            static::getEstimatedDelinquenciesTrendMetric(),
            static::getSatisfactionValueMetric(),

            static::getCountByEpicPartitionMetric(),
            static::getCountByPriorityPartitionMetric(),
            static::getCountByAssigneePartitionMetric()
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
     * Returns the created value metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getCreatedValueMetric()
    {
        return (new \App\Nova\Metrics\IssueCreatedByDateValue)
            ->filter(static::scope())
            ->setName(static::$label . ' Created');
    }

    /**
     * Returns the count by label partition metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getCountByLabelPartitionMetric()
    {
        return (new \App\Nova\Metrics\IssueCountPartition)
            ->groupByLabel()
            ->filter(static::scope())
            ->setName(static::$label . ' Rem. Count (by Label)');
    }

    /**
     * Returns the count by version partition metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getCountByVersionPartitionMetric()
    {
        return (new \App\Nova\Metrics\IssueCountPartition)
            ->groupByVersion()
            ->filter(static::scope())
            ->setName(static::$label . ' Rem. Count (by Version)');
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
     * Returns the outflow trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getOutflowTrendMetric()
    {
        return (new \App\Nova\Metrics\IssueCountByDateTrend)
            ->filter(static::scope())
            ->column('resolution_date')
            ->setName(static::$label . ' Outflow');
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
    public static function getCountByEpicPartitionMetric()
    {
        return (new \App\Nova\Metrics\IssueCountPartition)
            ->groupByEpic()
            ->filter(static::scope())
            ->setName(static::$label . ' Rem. Count (by Epic)');
    }

    /**
     * Returns the count by priority partition metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getCountByPriorityPartitionMetric()
    {
        return (new \App\Nova\Metrics\IssueCountPartition)
            ->groupByPriority()
            ->filter(static::scope())
            ->setName(static::$label . ' Rem. Count (by Priority)');
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
}
