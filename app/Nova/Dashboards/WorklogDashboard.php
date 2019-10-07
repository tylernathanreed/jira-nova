<?php

namespace App\Nova\Dashboards;

use App\Nova\Resources\IssueWorklog;
use App\Nova\Resources\IssueChangelog;

class WorklogDashboard extends Dashboard
{
    /**
     * The displayable name for this dashboard.
     *
     * @var string
     */
    protected static $label = 'Worklog';

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

            static::getWorklogByEpicPartition(),
            static::getWorklogByPriorityPartition(),
            static::getWorklogByAuthorPartition()
        ];
    }

    /**
     * Returns the time extensions metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getEstimateExtensionsValue()
    {
        return IssueChangelog::getEstimateExtensionsValue();
    }

    /**
     * Returns the time reductions metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getEstimateReductionsValue()
    {
        return IssueChangelog::getEstimateReductionsValue();
    }

    /**
     * Returns the time inflations metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getEstimateInflationValue()
    {
        return IssueChangelog::getEstimateInflationValue();
    }

    /**
     * Returns the inflow trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getFeatureWorklogTrend()
    {
        return IssueWorklog::getFeatureWorklogTrend();
    }

    /**
     * Returns the count by assignee partition metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getDefectWorklogTrend()
    {
        return IssueWorklog::getDefectWorklogTrend();
    }

    /**
     * Returns the equilibrium trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getUpkeepValue()
    {
        return IssueWorklog::getUpkeepValue();
    }

    /**
     * Returns the worklog trend metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getWorklogTrend()
    {
        return IssueWorklog::getWorklogTrend();
    }

    /**
     * Returns the expected worklog value metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getExpectedWorklogTrend()
    {
        return IssueWorklog::getExpectedWorklogTrend();
    }

    /**
     * Returns the efficiency value metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getEfficiencyValue()
    {
        return IssueWorklog::getEfficiencyValue();
    }

    /**
     * Returns the count by epic partition metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getWorklogByEpicPartition()
    {
        return IssueWorklog::getWorklogByEpicPartition();
    }

    /**
     * Returns the worklog by priority partition metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getWorklogByPriorityPartition()
    {
        return IssueWorklog::getWorklogByPriorityPartition();
    }

    /**
     * Returns the worklog by author partition metric for this dashboard.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getWorklogByAuthorPartition()
    {
        return IssueWorklog::getWorklogByAuthorPartition();
    }
}
