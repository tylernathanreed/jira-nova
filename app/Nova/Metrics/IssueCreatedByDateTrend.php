<?php

namespace App\Nova\Metrics;

use App\Models\Issue;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Trend;

class IssueCreatedByDateTrend extends Trend
{
    use Concerns\DashboardCaching;

    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'trend-metric';

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $result = $this->countByDays($request, Issue::class, 'entry_date')->suffix('issues');

        $result->result(
            array_sum($result->trend)
        );

        return $result;
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        return [
            30 => '30 Days',
            60 => '60 Days',
            90 => '90 Days',
            365 => '1 Year'
        ];
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Issues Created Per Day';
    }

}
