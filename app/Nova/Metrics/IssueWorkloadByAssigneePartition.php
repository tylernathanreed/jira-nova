<?php

namespace App\Nova\Metrics;

use App\Models\Issue;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class IssueWorkloadByAssigneePartition extends Partition
{
    use Concerns\Nameable;
    use Concerns\InlineFilterable;

    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'partition-metric';

    /**
     * The displayable name of the metric.
     *
     * @var string
     */
    public $name = 'Remaining Workload (By Assignee)';

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        // Create a new remaining workload query
        $query = (new Issue)->newRemainingWorkloadQuery();

        // Apply the filter
        $this->applyFilter($query);

        // Determine the result
        $result = $this->sum($request, $query, 'estimate_remaining', 'assignee_name');

        // Determine the result value
        $value = $result->value;

        // Sort the result by workload
        arsort($value);

        // Update the result
        $result->value = $value;

        // Label the results
        $result->label(function($label) {
            return $label ?: 'Unassigned';
        });

        // Return the partition result
        return $result;
    }

    /**
     * Format the aggregate result for the partition.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $result
     * @param  string                               $groupBy
     *
     * @return array
     */
    protected function formatAggregateResult($result, $groupBy)
    {
        $key = $result->{last(explode('.', $groupBy))};

        return [$key => round($result->aggregate / 3600, 0)];
    }
}
