<?php

namespace App\Nova\Metrics;

use App\Models\Issue;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Partition;

class IssueCountByAssigneePartition extends Partition
{
    use Concerns\PartitionLimits;
    use Concerns\InlineFilterable;
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

        // Apply the filter
        $this->applyFilter($query);

        // Determine the count per assignee
        $result = $this->count($request, $query, 'assignee_name');

        // Limit the results
        $this->limitPartitionResult($result);

        // Label the results
        $result->label(function($label) {
            return $label ?: 'Unassigned';
        });

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
        return 'Remaining Issues (By Assignee)';
    }

}
