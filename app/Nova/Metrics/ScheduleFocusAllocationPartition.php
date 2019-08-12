<?php

namespace App\Nova\Metrics;

use DB;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Partition;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Views\ScheduleFocusDailyAllocation;

class ScheduleFocusAllocationPartition extends Partition
{
    use Concerns\FocusGroupBranding;

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
        // Create a new schedule focus daily allocation query
        $query = (new ScheduleFocusDailyAllocation)->newQuery();

        // Exclude Saturday and Sunday
        $query->whereNotIn('day_of_week', [
            'Saturday',
            'Sunday'
        ]);

        // Filter by the given focus group
        $query->joinRelation('focusGroup');

        // Join into users to increase counts per use
        $query->joinRelation('schedule.users');

        // Order by the display order
        $query->orderBy('focus_groups.display_order');

        // Determine the result
        $result = $this->sum($request, $query, 'allocation', 'focus_groups.system_name');

        // Brand the results as focus groups
        $this->brandPartitionResultAsFocusGroups($result);

        // Return the result
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
        $key = $result->group_by;

        return [$key => (float) number_format($result->aggregate / 3600, 2)];
    }

    /**
     * Return a partition result showing the segments of a aggregate.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|string  $model
     * @param  string  $function
     * @param  string  $column
     * @param  string  $groupBy
     * @return \Laravel\Nova\Metrics\PartitionResult
     */
    protected function aggregate($request, $model, $function, $column, $groupBy)
    {
        $query = $model instanceof Builder ? $model : (new $model)->newQuery();

        $wrappedColumn = $query->getQuery()->getGrammar()->wrap(
            $column = $column ?? $query->getModel()->getQualifiedKeyName()
        );

        $results = $query->select(
            "{$groupBy} as group_by", DB::raw("{$function}({$wrappedColumn}) as aggregate")
        )->groupBy($groupBy)->get();

        return $this->result($results->mapWithKeys(function ($result) use ($groupBy) {
            return $this->formatAggregateResult($result, $groupBy);
        })->all());
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Focus Allocation (By Focus)';
    }
}
