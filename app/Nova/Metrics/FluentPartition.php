<?php

namespace App\Nova\Metrics;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Partition;

class FluentPartition extends Partition
{
    /**
     * The function constants.
     *
     * @var string
     */
    const USE_COUNT = 'count';
    const USE_AVERAGE = 'avg';
    const USE_SUM = 'sum';
    const USE_MAX = 'max';
    const USE_MIN = 'min';

    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'partition-metric';

    /**
     * The model class for this metric.
     *
     * @var string
     */
    public $model;

    /**
     * The function for this metric.
     *
     * @var string
     */
    public $function;

    /**
     * The column for this metric.
     *
     * @var string
     */
    public $column;

    /**
     * The group by column for this metric.
     *
     * @var string
     */
    public $groupBy;

    /**
     * The range (expression or in days) to filter the results by.
     *
     * @var string|null
     */
    public $range;

    /**
     * The date column to filter the range by.
     *
     * @var string|null
     */
    public $dateColumn;

    /**
     * The precision of aggregate values.
     *
     * @var integer
     */
    public $precision = 0;

    /**
     * The query callbacks for this metric.
     *
     * @var array
     */
    public $queryCallbacks = [];

    /**
     * The direction to sort the results.
     *
     * @var  string|null
     */
    public $sort;

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        // Determine the model
        $model = $this->model;

        // Create a new query
        $query = (new $model)->newQuery();

        // Apply the query callbacks
        $this->applyQueryCallbacks($query);

        // Apply the range filter
        $this->applyRangeFilter($query);

        // Determine the result
        $result = $this->aggregate($request, $query, $this->function, $this->column, $this->groupBy);

        // Apply the result sort
        $this->applyResultSort($result);

        // Return the result
        return $result;
    }

    /**
     * Sets the model for this metric.
     *
     * @param  string  $model
     *
     * @return $this
     */
    public function model($model)
    {
        $this->model = $model;

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
     * Sets the function for this metric.
     *
     * @param  string  $function
     *
     * @return $this
     */
    public function use($function)
    {
        $this->function = $function;

        return $this;
    }

    /**
     * Variants of {@see $this->use()}.
     */
    public function useCount()   { return $this->use(self::USE_COUNT); }
    public function useAverage() { return $this->use(self::USE_AVERAGE); }
    public function useSum()     { return $this->use(self::USE_SUM); }
    public function useMax()     { return $this->use(self::USE_MAX); }
    public function useMin()     { return $this->use(self::USE_MIN); }

    /**
     * Sets the column for this metric.
     *
     * @param  string  $column
     *
     * @return $this
     */
    public function column($column)
    {
        $this->column = $column;

        return $this;
    }

    /**
     * Column variables of {@see $this->use()}.
     */
    public function countOf($column)   { return $this->useCount()->column($column); }
    public function averageOf($column) { return $this->useAverage()->column($column); }
    public function sumOf($column)     { return $this->useSum()->column($column); }
    public function maxOf($column)     { return $this->useMax()->column($column); }
    public function minOf($column)     { return $this->useMin()->column($column); }

    /**
     * Sets the group by column for this metric.
     *
     * @param  string  $groupBy
     *
     * @return $this
     */
    public function groupBy($groupBy)
    {
        $this->groupBy = $groupBy;

        return $this;
    }

    /**
     * Sets the range (expression or in days) to filter the results by.
     *
     * @param  string|null  $range
     *
     * @return $this
     */
    public function range($range)
    {
        $this->range = $range;

        return $this;
    }

    /**
     * Sets the date column for this metric.
     *
     * @param  string|null  $dateColumn
     *
     * @return $this
     */
    public function dateColumn($dateColumn)
    {
        $this->dateColumn = $dateColumn;

        return $this;
    }

    /**
     * Sets the range and date column to filter the results by.
     *
     * @param  string|null  $range
     * @param  string|null  $dateColumn
     *
     * @return $this
     */
    public function rangeOver($range, $dateColumn)
    {
        return $this->range($range)->dateColumn($dateColumn);
    }

    /**
     * Variants of {@see $this->rangeOver()}.
     *
     * @param  string|null  $dateColumn
     *
     * @return $this
     */
    public function today($dateColumn = null) { return $this->rangeOver('TODAY', $dateColumn); }
    public function mtd($dateColumn = null) { return $this->rangeOver('MTD', $dateColumn); }
    public function qtd($dateColumn = null) { return $this->rangeOver('QTD', $dateColumn); }
    public function ytd($dateColumn = null) { return $this->rangeOver('YTD', $dateColumn); }

    /**
     * Applies the range filter to the specified query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *
     * @return void
     */
    public function applyRangeFilter($query)
    {
        // Determine the date column
        $dateColumn = $this->dateColumn ?? $query->getModel()->getCreatedAtColumn();

        // Apply the filter
        $query->whereBetween($dateColumn, $this->getRangeEndpoints());
    }

    /**
     * Returns the start and end dates of the range for this metric.
     *
     * @return array|null
     */
    public function getRangeEndpoints()
    {
        if(is_null($this->range)) {
            return null;
        }

        if ($this->range == 'TODAY') {
            return [
                now()->yesterday(),
                now(),
            ];
        }

        if ($this->range == 'MTD') {
            return [
                now()->firstOfMonth(),
                now(),
            ];
        }

        if ($this->range == 'QTD') {
            return [
                Carbon::firstDayOfQuarter(),
                now(),
            ];
        }

        if ($this->range == 'YTD') {
            return [
                now()->firstOfYear(),
                now(),
            ];
        }

        return [
            now()->subDays($this->range),
            now(),
        ];
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
     * Sets the display callback for this metric.
     *
     * @param  callable  $callback
     *
     * @return $this
     */
    public function displayUsing(callable $callback)
    {
        $this->displayCallback = $callback;

        return $this;
    }

    /**
     * Sets the dispaly callback to use division.
     *
     * @param  integer|float  $quotient
     *
     * @return $this
     */
    public function divideBy($quotient)
    {
        return $this->displayUsing(function($value) use ($quotient) {
            return $value / $quotient;
        });
    }

    /**
     * Applies the result format to the specified value.
     *
     * @param  mixed  $value
     *
     * @return mixed
     */
    public function applyResultFormat($value)
    {
        if(is_null($callback = $this->displayCallback)) {
            return $value;
        }

        return $callback($value);
    }

    /**
     * Applies the query callbacks to the specified query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *
     * @return void
     */
    public function applyQueryCallbacks($query)
    {
        foreach($this->queryCallbacks as $callback) {
            $callback($query);
        }
    }

    /**
     * Sorts the results in the specified order.
     *
     * @param  string  $direction
     *
     * @return $this
     */
    public function sort($direction = 'asc')
    {
        $this->sort = $direction;

        return $this;
    }

    /**
     * Sorts the results in descending order.
     *
     * @return $this
     */
    public function sortDesc()
    {
        return $this->sort('desc');
    }

    /**
     * Applies the sorting direction on the specified result.
     *
     * @param  \Laravel\Nova\Metrics\PartitionResult  $result
     *
     * @return void
     */
    public function applyResultSort($result)
    {
        // Make sure sorting has been specified
        if(is_null($this->sort)) {
            return;
        }

        // Determine the result value
        $value = $result->value;

        // Sort the result
        if($this->sort == 'asc') {
            asort($value);
        } else {
            arsort($value);
        }

        // Update the value
        $result->value = $value;
    }

    /**
     * Format the aggregate result for the partition.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $result
     * @param  string  $groupBy
     * @return array
     */
    protected function formatAggregateResult($result, $groupBy)
    {
        $key = $result->{last(explode('.', $groupBy))};

        return [$key => round($this->applyResultFormat($result->aggregate), $this->precision)];
    }

    /**
     * Handles dynamic method calls into this metric.
     *
     * @param  string  $method
     * @param  array   $parameters
     *
     * @return $this
     */
    public function __call($method, $parameters = [])
    {
        // Create a query callback based on the method call
        $callback = function($query) use ($method, $parameters) {
            $query->{$method}(...$parameters);
        };

        // Add the query callback
        $this->queryCallbacks[] = $callback;

        // Allow chaining
        return $this;
    }
}
