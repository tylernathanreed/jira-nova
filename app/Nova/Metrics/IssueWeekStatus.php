<?php

namespace App\Nova\Metrics;

use Carbon\Carbon;
use App\Models\Issue;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Partition;

class IssueWeekStatus extends Partition
{
    use Concerns\DashboardCaching;
    use Concerns\WeeklyLabels;

    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'partition-metric';

    /**
     * The reference for the current week.
     *
     * @var string|null
     */
    public $reference;

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        // Create a new query
        $query = (new Issue)->newQuery();

        // Filter by week
        $this->filterByWeek($query);

        // Determine the result
        $result = $this->count($request, $query, 'status_name');

        // Condense the values
        $result->value = [

            'Planning' => (
                ($result->value['In Design'] ?? 0) +
                ($result->value['New'] ?? 0)
            ),

            'Ready' => (
                $result->value['Assigned'] ?? 0
            ),

            'In Progress' => (
                ($result->value['Dev Complete'] ?? 0) +
                ($result->value['In Development'] ?? 0) +
                ($result->value['Ready to Test [Test]'] ?? 0) +
                ($result->value['Ready to test [UAT]'] ?? 0) +
                ($result->value['Testing Failed'] ?? 0)
            ),

            'Stuck' => (
                ($result->value['Dev Help Needed'] ?? 0) +
                ($result->value['Dev Hold'] ?? 0) +
                ($result->value['Need Client Clarification'] ?? 0) +
                ($result->value['Test Help Needed'] ?? 0) +
                ($result->value['Waiting for approval'] ?? 0)
            ),

            'Done' => (
                ($result->value['Cancelled'] ?? 0) +
                ($result->value['Done'] ?? 0) +
                ($result->value['Testing Passed [Test]'] ?? 0)
            )

        ];

        // Assign the colors
        $result->colors([
            'Planning' => '#f99037',
            'Ready' => '#5b9bd5',
            'In Progress' => '#ffc000',
            'Stuck' => '#cc0000',
            'Done' => '#098f56',
        ]);

        // Return the result
        return $result;
    }

    /**
     * Filters the query by the week label.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     *
     * @return void
     */
    protected function filterByWeek($query)
    {
        // Determine the week label
        $label = $this->getWeekLabel($this->reference ? Carbon::parse($this->reference) : Carbon::now());

        // Filter the query
        $query->where('labels', 'like', "%\"{$label}%");
    }

    /**
     * Sets the name of this metric.
     *
     * @param  string  $name
     *
     * @return $this
     */
    public function label($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the reference of this metric.
     *
     * @param  string  $reference
     *
     * @return $this
     */
    public function reference($reference)
    {
        $this->reference = $reference;

        return $this;
    }

}
