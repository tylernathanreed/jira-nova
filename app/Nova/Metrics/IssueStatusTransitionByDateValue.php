<?php

namespace App\Nova\Metrics;

use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Value;
use App\Models\IssueChangelogItem;

class IssueStatusTransitionByDateValue extends Value
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
    public $name = 'Status Transitions';

    /**
     * The options for the status transition query.
     *
     * @var array
     */
    public $options = [];

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        // Create a new status transition query
        $query = (new IssueChangelogItem)->newStatusTransitionQuery($this->options);

        // Join into the changelogs
        $query->joinRelation('changelog');

        // Apply any additional filters
        $this->applyFilter($query);

        // Return the result
        return $this->count($request, $query, null, 'created_at')->suffix('transitions');
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
     * Sets the "only_to" option.
     *
     * @param  string|array  $onlyTo
     *
     * @return $this
     */
    public function onlyTo($onlyTo)
    {
        $this->options['only_to'] = $onlyTo;

        return $this;
    }

    /**
     * Sets the "except_to" option.
     *
     * @param  string|array  $exceptTo
     *
     * @return $this
     */
    public function exceptTo($exceptTo)
    {
        $this->options['except_to'] = $exceptTo;

        return $this;
    }

    /**
     * Sets the "only_from" option.
     *
     * @param  string|array  $onlyFrom
     *
     * @return $this
     */
    public function onlyFrom($onlyFrom)
    {
        $this->options['only_from'] = $onlyFrom;

        return $this;
    }

    /**
     * Sets the "except_from" option.
     *
     * @param  string|array  $exceptFrom
     *
     * @return $this
     */
    public function exceptFrom($exceptFrom)
    {
        $this->options['except_from'] = $exceptFrom;

        return $this;
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return parent::uriKey() . '-value';
    }
}
