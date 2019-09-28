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
     * The secondary resource for this dashboard.
     *
     * @var string
     */
    protected static $changelog = \App\Nova\Resources\IssueChangelog::class;

    /**
     * Get the cards for the dashboard.
     *
     * @return array
     */
    public function cards()
    {
        return [
            static::getEstimateExtensionsValue(),
            static::getEstimateReductionsValue(),
            static::getEstimateInflationValue(),

            static::getFeatureWorklogTrend(),
            static::getDefectWorklogTrend(),
            static::getUpkeepValue(),

            static::getWorklogTrend(),
            static::getExpectedWorklogTrend(),
            static::getEfficiencyValue(),

            // static::getActualDelinquenciesTrendMetric(),
            // static::getEstimatedDelinquenciesTrendMetric(),
            // static::getSatisfactionValueMetric(),

            static::getWorklogByEpicPartition(),
            static::getWorklogByPriorityPartition(),
            static::getWorklogByAuthorPartition()
        ];
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
     * Creates and returns a new secondary resource.
     *
     * @return \App\Nova\Resources\Resource
     */
    public static function changelog()
    {
        $class = static::$changelog;

        return new $class($class::newModel());
    }

    /**
     * Returns the time extensions metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getEstimateExtensionsValue()
    {
        return static::changelog()->getEstimateExtensionsValue();
    }

    /**
     * Returns the time reductions metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getEstimateReductionsValue()
    {
        return static::changelog()->getEstimateReductionsValue();
    }

    /**
     * Returns the time inflations metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getEstimateInflationValue()
    {
        return static::changelog()->getEstimateInflationValue();
    }

    /**
     * Returns the inflow trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getFeatureWorklogTrend()
    {
        return static::resource()->getFeatureWorklogTrend();
    }

    /**
     * Returns the count by assignee partition metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getDefectWorklogTrend()
    {
        return static::resource()->getDefectWorklogTrend();
    }

    /**
     * Returns the equilibrium trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getUpkeepValue()
    {
        return static::resource()->getUpkeepValue();
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
    public static function getExpectedWorklogTrend()
    {
        return static::resource()->getExpectedWorklogTrend();
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
