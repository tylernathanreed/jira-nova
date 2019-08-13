<?php

namespace App\Nova\Metrics;

use App\Models\FocusGroup;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Partition;
use App\Models\Views\ScheduleFocusDailyAllocation;

class ScheduleFocusAllocationDailyByFocusPartition extends Partition
{
    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'partition-metric';

    /**
     * The focus group system name.
     *
     * @var \App\Models\FocusGroup
     */
    public $focusGroup;

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
        $query->joinRelation('focusGroup', function($join) {
            $join->where('focus_groups.id', '=', $this->focusGroup->id);
        });

        // Join into users to increase counts per use
        $query->joinRelation('schedule.users');

        // Order by the day order
        $query->orderBy('day_order');

        dump($query->toSql());

        // Return the result
        return $this->sum($request, $query, 'allocation', 'day_of_week');
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

        return [$key => (float) number_format($result->aggregate / 3600, 2)];
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Focus Allocation (' . $this->focusGroup->display_name . ')';
    }

    /**
     * Sets the focus group for this metric.
     *
     * @param  string  $systemName
     *
     * @return $this
     */
    public function focus($systemName)
    {
        $this->focusGroup = FocusGroup::where('system_name', '=', $systemName)->first();

        return $this;
    }
}
