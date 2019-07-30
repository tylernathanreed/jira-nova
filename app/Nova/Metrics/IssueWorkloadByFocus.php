<?php

namespace App\Nova\Metrics;

use App\Models\Issue;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class IssueWorkloadByFocus extends Partition
{
    use Concerns\DashboardCaching;

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
        $query = (new Issue)->newRemainingWorkloadQuery();

        $result = $this->sum($request, $query, 'estimate_remaining', 'focus')->colors([
            'Dev' => '#5b9bd5',
            'Ticket' => '#ffc000',
            'Other' => '#cc0000'
        ]);

        $result->value = [
            'Dev' => $result->value['Dev'] ?? 0,
            'Ticket' => $result->value['Ticket'] ?? 0,
            'Other' => $result->value['Other'] ?? 0
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

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Workload (By Focus)';
    }

}
