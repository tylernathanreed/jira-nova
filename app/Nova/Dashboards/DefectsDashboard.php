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
     * The primary resource for this dashboard.
     *
     * @var string
     */
    protected static $resource = \App\Nova\Resources\Issue::class;

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
     * Creates and returns a new primary resource.
     *
     * @return \App\Nova\Resources\Resource
     */
    public static function resource()
    {
        $class = static::$resource;

        return new $class($class::newModel());
    }

    /**
     * Returns the created value metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getCreatedValueMetric()
    {
        return static::resource()->getIssueCreatedByDateValue()
            ->label(static::$label . ' Created')
            ->scope(static::scope());
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
        return static::resource()->getIssueCreatedByDateTrend()
            ->label(static::$label . ' Inflow')
            ->scope(static::scope());
    }

    /**
     * Returns the outflow trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getOutflowTrendMetric()
    {
        return static::resource()->getIssueCreatedByDateTrend()
            ->label(static::$label . ' Outflow')
            ->dateColumn('resolution_date')
            ->scope(static::scope());
    }

    /**
     * Returns the equilibrium trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getEquilibriumTrendMetric()
    {
        return (new \App\Nova\Metrics\TrendComparisonValue)
            ->label(static::$label . ' Equilibrium')
            ->trends([
                static::getOutflowTrendMetric(),
                static::getInflowTrendMetric()
            ])
            ->format([
                'output' => 'percent',
                'mantissa' => 0
            ]);
    }

    /**
     * Returns the actual delinquencies trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getActualDelinquenciesTrendMetric()
    {
        return static::resource()->getIssueDeliquenciesByDueDateTrend()
            ->label(static::$label . ' Act. Delinquencies')
            ->scope(static::scope());
    }

    /**
     * Returns the estimated delinquencies trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getEstimatedDelinquenciesTrendMetric()
    {
        return static::resource()->getIssueDeliquenciesByEstimatedDateTrend()
            ->label(static::$label . ' Est. Delinquencies')
            ->scope(static::scope());
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
