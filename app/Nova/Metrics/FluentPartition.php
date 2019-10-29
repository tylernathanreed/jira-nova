<?php

namespace App\Nova\Metrics;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

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
     * The common concerns.
     */
    use Concerns\Nameable,
        Concerns\QueryCallbacks;

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
     * The callback used to display the result.
     *
     * @var callback|null
     */
    public $displayCallback;

    /**
     * The result class to use.
     *
     * @var string|null
     */
    public $resultClass;

    /**
     * The direction to sort the results.
     *
     * @var string|null
     */
    public $sort;

    /**
     * The result limit after sorting.
     *
     * @var integer|null
     */
    public $limit;

    /**
     * The colors to assign to the partition.
     *
     * @var array
     */
    public $colors;

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

        // Apply the result limit
        $this->applyResultLimit($result);

        // Apply the result colors
        $this->applyResultColors($result);

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
        // Make sure a range was specified
        if(is_null($range = $this->getRangeEndpoints())) {
            return;
        }

        // Determine the date column
        $dateColumn = $this->dateColumn ?? $query->getModel()->getCreatedAtColumn();

        // Apply the filter
        $query->whereBetween($dateColumn, $range);
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
     * Sets the result class to a custom class.
     *
     * @param  string  $class
     *
     * @return $this
     */
    public function resultClass($resultClass)
    {
        $this->resultClass = $resultClass;

        return $this;
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
     * Limits the results to the specified number of entries.
     *
     * @param  integer  $limit
     *
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Applies the limit on the specified result.
     *
     * @param  \Laravel\Nova\Metrics\PartitionResult  $result
     *
     * @return void
     */
    public function applyResultLimit($result)
    {
        // Make sure a limit has been specified
        if(is_null($this->limit)) {
            return;
        }

        // Determine the result value
        $value = $result->value;

        // Merge the last results into a labelled category
        if(count($value) >= $this->limit) {
            $value = array_merge(array_slice($value, 0, $this->limit - 1), [($label ?? 'Other') => array_sum(array_slice($value, $this->limit - 1))]);
        }

        // Update the value
        $result->value = $value;
    }

    /**
     * Assigns the colors for this partition.
     *
     * @param  array  $colors
     *
     * @return $this
     */
    public function colors($colors)
    {
        $this->colors = $colors;

        return $this;
    }

    /**
     * Sets the colors on the partition result.
     *
     * @param  \Laravel\Nova\Metrics\PartitionResult  $result
     *
     * @return void
     */
    public function applyResultColors($result)
    {
        // Make sure colors have been provided
        if(is_null($this->colors)) {
            return;
        }

        // Determine the colors
        $colors = collect($this->colors);

        // Determine the labels
        $labels = array_keys($result->value);

        // Reduce the color list to only present values
        $colors = collect($colors)->only($labels);

        // Initialize the color counts
        $counts = $colors->flip()->map(function($color) {
            return 0;
        });

        // If a color is repeated, force a different color
        $colors->transform(function($color, $label) use (&$counts) {

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
     * Create a new partition metric result.
     *
     * @param  array  $value
     *
     * @return \Laravel\Nova\Metrics\PartitionResult
     */
    public function result(array $value)
    {
        // Determine the result class
        $class = $this->resultClass ?: PartitionResult::class;

        // Create and return the result
        return new $class($value);
    }
}
