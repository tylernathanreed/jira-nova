<?php

namespace App\Nova\Metrics;

use App\Models\Issue;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Trend;

class IssueDelinquentByEstimatedDateTrend extends Trend
{
    // use Concerns\DashboardCaching;
    use Concerns\FutureRange;

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
        $query = $this->newCalculateQuery();

        $result = $this->countByDays($request, $query, 'due_date')->suffix('issues');

        $result->result(
            array_sum($result->trend)
        );

        // Return the result
        return $result;
    }

    /**
     * Creates and returns a new calculate query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newCalculateQuery()
    {
        // Create a new query
        $query = (new Issue)->newQuery();

        // Make sure the issue has an estimate
        $query->whereNotNull('estimate_date');

        // Make sure the due date comes before the estimate
        $query->whereNotNull('due_date');
        $query->whereColumn('due_date', '>', 'estimate_date');

        // Make sure the issues are not complete
        $query->incomplete();

        // Return the query
        return $query;
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        $max = carbon($this->newCalculateQuery()->max('due_date'))->diffInDays() + 10;

        return [
            30 => 'Due up to 30 days from now',
            60 => 'Due up to 60 days from now',
            90 => 'Due up to 90 days from now',
            365 => 'Due up to 1 year from now',
            $max => 'All time',
        ];
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Estimated Delinquencies';
    }
}
