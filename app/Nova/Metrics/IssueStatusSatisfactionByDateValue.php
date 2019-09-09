<?php

namespace App\Nova\Metrics;

use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Value;
use App\Models\IssueChangelogItem;
use Laravel\Nova\Metrics\ValueResult;

class IssueStatusSatisfactionByDateValue extends Value
{
    use Concerns\Nameable;
    use Concerns\InlineFilterable;

    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'value-metric';

    /**
     * The displayable name of the metric.
     *
     * @var string
     */
    public $name = 'Commitments Kept';

    /**
     * The statuses for resolution.
     *
     * @var string|array
     */
    public $statuses = [];

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        // Determine the current and previous values
        foreach(['current', 'previous'] as $reference) {

            // Create both queries
            foreach(['made', 'kept'] as $type) {

                // Create a new status transition query
                $query = (new IssueChangelogItem)->newStatusCommitmentsQuery($this->statuses, [
                    'kept' => $type == 'kept' ? true : null,
                    'range' => $this->{$reference . 'Range'}($request->range)
                ]);

                // Apply any additional filters
                $this->applyFilter($query);

                // Return the result
                $$reference[$type] = $query->count();

            }

        }

        // Determine the value results
        $currentValue = $current['made'] == 0 ? 1 : round($current['kept'] / $current['made'], 2);
        $previousValue = $previous['made'] == 0 ? 1 : round($previous['kept'] / $previous['made'], 2);

        // Given that the value is already a percentage, we're going to
        // modify the "previous" value that we pass to the front-end
        // so that the amount will be a delta, not a percentage.

        // Determine the substituted previous
        $substitute = ($currentValue - $previousValue + 1) == 0 ? -1 : $currentValue / ($currentValue - $previousValue + 1);

        // Return the result
        return (new ValueResult($currentValue))
            ->previous($substitute)
            ->format(['output' => 'percent', 'mantissa' => 0]);
    }

    /**
     * Returns the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        return [
            30 => '30 Days',
            60 => '60 Days',
            90 => '90 Days',
            365 => '1 Year'
        ];
    }

    /**
     * Sets the statuses for resolution.
     *
     * @param  string|array  $statuses
     *
     * @return $this
     */
    public function statuses($statuses)
    {
        $this->statuses = $statuses;

        return $this;
    }
}
