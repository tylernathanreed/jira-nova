<?php

namespace App\Nova\Metrics;

use App\Models\Issue;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class IssueWorkloadByEpicPartition extends Partition
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
        // Create a new remaining workload query
        $query = (new Issue)->newRemainingWorkloadQuery();

        // Make sure the issues are part of an epic
        $query->whereNotNull('epic_name');

        // Determine the workload per epic
        $result = $this->sum($request, $query, 'estimate_remaining', 'epic_name');

        // Determine the result value
        $value = $result->value;

        // Sort the result by workload
        arsort($value);

        // Merge the last 10 results into an "other" category
        $value = array_merge(array_slice($value, 0, 9), ['Other' => array_sum(array_slice($value, 9))]);

        // Update the value
        $result->value = $value;

        // Determine the epic colors
        $colors = (new Issue)->select(['epic_name', 'epic_color'])->whereNotNull('epic_name')->distinct()->getQuery()->get()->pluck('epic_color', 'epic_name');

        // Determine the epic color hex map
        $map = Issue::getEpicColorHexMap();

        // Map the colors into hex values
        $colors->transform(function($color) use ($map) {
            return $map[$color ?? 'ghx-label-0'] ?? '#000';
        });

        // Add the "Other" color
        $colors['Other'] = '#777';

        // Reduce the color list to only present values
        $colors = $colors->only(array_keys($value));

        // Initialize the color counts
        $counts = $colors->flip()->map(function($color) {
            return 0;
        });

        // If a color is repeated, force a different color
        $colors->transform(function($color, $epic) use (&$counts) {

            // Increase the color count
            $counts[$color] = $counts[$color] + 1;

            // If this is the first of its kind, keep it
            if($counts[$color] == 1) {
                return $color;
            }

            // Detemrine the count
            $count = $counts[$color];

            // Offset each digit
            return '#' . implode('', array_map(function($v) use ($count) {
                return dechex((hexdec($v) - $count + 16) % 16);
            }, str_split(substr($color, 1))));

        });

        // Assign the colors
        $result->colors($colors->all());

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
        return 'Workload (By Epic)';
    }

}
