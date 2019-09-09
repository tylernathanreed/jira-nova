<?php

namespace App\Nova\Metrics;

use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Value;
use App\Models\IssueChangelogItem;
use Laravel\Nova\Metrics\ValueResult;

class IssueStatusResolutionByDateValue extends Value
{
    use Concerns\Nameable;

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
    public $name = 'Equilibrium Distribution';

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
        // Create both queries
        foreach(['inflow', 'outflow'] as $direction) {

            // Create a new status transition query
            $query = (new IssueChangelogItem)->newStatusTransitionQuery([
                $direction == 'inflow' ? 'only_to' : 'only_from' => $this->statuses
            ]);

            // Join into the changelogs
            $query->joinRelation('changelog');

            // Return the result
            $$direction = $this->count($request, $query, null, 'created_at');

        }

        // Determine the value results
        $value = $inflow->value == 0 ? 1 : round($outflow->value / $inflow->value, 2);
        $previous = $inflow->previous == 0 ? 1 : round($outflow->previous / $inflow->previous, 2);

        // Given that the value is already a percentage, we're going to
        // modify the "previous" value that we pass to the front-end
        // so that the amount will be a delta, not a percentage.

        // Determine the substituted previous
        $substitute = ($value - $previous + 1) == 0 ? -1 : round($value / ($value - $previous + 1), 2);

        // Return the result
        return (new ValueResult($value))
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
