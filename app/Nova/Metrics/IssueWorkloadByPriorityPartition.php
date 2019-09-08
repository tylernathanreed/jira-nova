<?php

namespace App\Nova\Metrics;

use App\Models\Issue;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Partition;

class IssueWorkloadByPriorityPartition extends Partition
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
    public $name = 'Remaining Workload (By Priority)';

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

        // Ignore hold and missing priorities
        $query->where('priority_name', '!=', 'Hold')->whereNotNull('priority_name');

        // Apply any additional filters
        $this->applyFilter($query);

        // Determine the results
        $result = $this->sum($request, $query, 'estimate_remaining', 'priority_name')->colors([
            'Highest' => 'firebrick',
            'High' => '#f44',
            'Medium' => 'silver',
            'Low' => 'mediumseagreen',
            'Lowest' => 'green'
        ]);

        // Order the results
        $result->value = [
            'Highest' => $result->value['Highest'] ?? 0,
            'High' => $result->value['High'] ?? 0,
            'Medium' => $result->value['Medium'] ?? 0,
            'Low' => $result->value['Low'] ?? 0,
            'Lowest' => $result->value['Lowest'] ?? 0
        ];

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
