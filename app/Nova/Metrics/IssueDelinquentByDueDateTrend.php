<?php

namespace App\Nova\Metrics;

use App\Models\Issue;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Trend;

class IssueDelinquentByDueDateTrend extends Trend
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
    public $name = 'Past Due Issues';

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

        // Make sure the due date is in the past
        $query->whereNotNull('due_date');
        $query->where('due_date', '<', carbon());

        // Make sure the issues are not complete
        $query->incomplete();

        // Apply the filter
        $this->applyFilter($query);

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
        $min = carbon($this->newCalculateQuery()->min('due_date'))->diffInDays() + 10;

        return [
            30 => 'Due up to 30 days ago',
            60 => 'Due up to 60 days ago',
            90 => 'Due up to 90 days ago',
            365 => 'Due up to 1 year ago',
            $min => 'All time',
        ];
    }
}
