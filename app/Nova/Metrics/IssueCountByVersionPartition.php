<?php

namespace App\Nova\Metrics;

use App\Models\Issue;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Partition;

class IssueCountByVersionPartition extends Partition
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
        $query->where('labels', '!=', '[]');

        // Join into fix versions
        $query->joinRelation('versions');

        // Determine the count per version
        $result = $this->count($request, $query, 'versions.name');

        // Limit the results
        $this->limitPartitionResult($result);

        // Return the partition result
        return $result;
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Remaining Issues (By Version)';
    }

}
