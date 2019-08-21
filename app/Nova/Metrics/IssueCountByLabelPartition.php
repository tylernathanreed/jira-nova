<?php

namespace App\Nova\Metrics;

use App\Models\Issue;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Partition;

class IssueCountByLabelPartition extends Partition
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

        // Make sure the issues using labels
        $query->where('labels', '!=', '[]');

        // Join into labels
        $query->joinRelation('labels');

        // Make sure the issues are part of an epic
        $query->whereNotNull('epic_name');

        // Determine the count per label
        $result = $this->count($request, $query, 'labels.name');

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
        return 'Remaining Issues (By Label)';
    }

}
