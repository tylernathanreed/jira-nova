<?php

namespace App\Nova\Metrics;

use App\Models\Issue;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Partition;

class IssueWorkloadByEpicPartition extends Partition
{
    use Concerns\EpicColors;
    use Concerns\InlineFilterable;
    use Concerns\Nameable;
    use Concerns\PartitionLimits;

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
    public $name = 'Remaining Workload (By Epic)';

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

        // Make sure the issues are part of an epic
        $query->whereNotNull('epic_name');

        // Apply the filter
        $this->applyFilter($query);

        // Determine the workload per epic
        $result = $this->sum($request, $query, 'estimate_remaining', 'epic_name');

        // Limit the results
        $this->limitPartitionResult($result);

        // Assign the epic colors
        $this->assignEpicColors($result);

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
