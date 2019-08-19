<?php

namespace App\Nova\Metrics;

use Closure;
use App\Models\Issue;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Trend;

class IssueCreatedByDateTrend extends Trend
{
    use Concerns\Nameable;
    use Concerns\InlineFilterable;

    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'trend-metric';

    /**
     * The displayable name of the metric.
     *
     * @var string
     */
    public $name = 'Issues Created Per Day';

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        // Create a new query
        $query = (new Issue)->newQuery();

        // Apply the filter
        $this->applyFilter($query);

        // Determine the result
        $result = $this->countByDays($request, $query, 'entry_date')->suffix('issues');

        // Determine the trend value
        $result->result(
            array_sum($result->trend)
        );

        // Return the result
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
}
