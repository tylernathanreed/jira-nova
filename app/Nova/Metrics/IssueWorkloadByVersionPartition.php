<?php

namespace App\Nova\Metrics;

use App\Models\Issue;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Partition;

class IssueWorkloadByVersionPartition extends Partition
{
    use Concerns\PartitionLimits;
    use Concerns\QualifiedGroupByPartitionFix;

    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'partition-metric';

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

        // Make sure the issues are using fix versions
        $query->where('fix_versions', '!=', '[]');

        // Join into fix versions
        $query->joinRelation('versions');

        // Determine the workload per version
        $result = $this->sum($request, $query, 'estimate_remaining', 'versions.name');

        // Limit the results
        $this->limitPartitionResult($result);

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

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Remaining Workload (By Version)';
    }

}
