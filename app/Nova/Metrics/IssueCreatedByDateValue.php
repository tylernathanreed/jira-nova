<?php

namespace App\Nova\Metrics;

use App\Models\Issue;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Value;

class IssueCreatedByDateValue extends Value
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
    public $name = 'New Issues';

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $query = (new Issue)->newQuery();

        $this->applyFilter($query);

        return $this->count($request, $query, null, 'entry_date');
    }

    /**
     * Get the ranges available for the metric.
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
}
