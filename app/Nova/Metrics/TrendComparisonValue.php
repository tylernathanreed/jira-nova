<?php

namespace App\Nova\Metrics;

use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Value;

class TrendComparisonValue extends Value
{
    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'value-metric';

    /**
     * The trends being compared.
     *
     * @var array
     */
    public $trends = [];

    /**
     * The precision of aggregate values.
     *
     * @var integer
     */
    public $precision = 0;

    /**
     * Whether or not the date range is futuristic.
     *
     * @var boolean
     */
    public $futuristic = false;

    /**
     * Whether or not this metric should use ranges.
     *
     * @var boolean
     */
    public $noRanges = false;

    /**
     * The value formatting for this metric.
     *
     * @var string|array
     */
    public $format;

    /**
     * Whether or not to use a scalar delta, as opposed to a percent-based one.
     *
     * @var boolean
     */
    public $useScalarDelta = false;

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        // Calculate the current result of the trends
        $currentResults = array_map(function($trend) use ($request) {
            return $trend->calculate($request)->value;
        }, $this->trends);

        // Determine the current value
        $currentValue = $currentResults[1] == 0 ? 1 : $currentResults[0] / $currentResults[1];

        // Calculate the previous result of the trends
        $previousResults = array_map(function($trend) use ($request) {
            return $trend->calculate(tap(clone $request, function($request) { $request['range'] = $request['range'] * 2; }))->value;
        }, $this->trends);

        // Offset the previous results by the current results
        foreach($previousResults as $index => &$result) {
            $result -= $currentResults[$index];
        }

        // Determine the previous value
        $previousValue = $previousResults[1] == 0 ? 1 : $previousResults[0] / $previousResults[1];

        // Check if we're using a scalar difference
        if($this->useScalarDelta) {

            // Given that the value is already a percentage, we're going to
            // modify the "previous" value that we pass to the front-end
            // so that the amount will be a scalar, not a percentage.

            // Determine the substituted previous
            $previousValue = ($currentValue - $previousValue + 1) == 0
                ? -1
                : round($currentValue / ($currentValue - $previousValue + 1), $this->precision + 2);

        }

        // Return the result
        return $this->result($currentValue)
            ->previous($previousValue)
            ->format($this->format);
    }

    /**
     * Sets the trends to compare.
     *
     * @param  array  $trends
     *
     * @return $this
     */
    public function trends($trends)
    {
        $this->trends = $trends;

        return $this;
    }

    /**
     * Sets the displayable name of the metric.
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
     * Sets the precision for this metric.
     *
     * @param  integer  $precision
     *
     * @return $this
     */
    public function precision($precision)
    {
        $this->precision = $precision;

        return $this;
    }

    /**
     * Sets whether or not the date range is future-looking.
     *
     * @param  boolean  $futuristic
     *
     * @return $this
     */
    public function futuristic($futuristic = true)
    {
        $this->futuristic = $futuristic;

        return $this;
    }

    /**
     * Sets whether or not ranges should be using in this metric.
     *
     * @param  boolean  $noRanges
     *
     * @return $this
     */
    public function noRanges($noRanges = true)
    {
        $this->noRanges = $noRanges;

        return $this;
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        if($this->noRanges) {
            return [];
        }

        $adjective = $this->futuristic ? 'Next' : 'Past';

        return [
            30 => $adjective . ' 30 Days',
            60 => $adjective . ' 60 Days',
            90 => $adjective . ' 90 Days',
            365 => $adjective . ' 1 Year'
        ];
    }

    /**
     * Sets the value formatting for this metric.
     *
     * @param  string|array  $format
     *
     * @return $this
     */
    public function format($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Sets whether or not to use a scalar delta.
     *
     * @param  boolean  $useScalarDelta
     *
     * @return $this
     */
    public function useScalarDelta($useScalarDelta = true)
    {
        $this->useScalarDelta = $useScalarDelta;

        return $this;
    }
}
