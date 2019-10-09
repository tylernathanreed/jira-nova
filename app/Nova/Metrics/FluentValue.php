<?php

namespace App\Nova\Metrics;

use DB;
use Closure;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Value;
use Illuminate\Database\Eloquent\Builder;

class FluentValue extends Value
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
    public $component = 'value-metric';

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
     * The raw select statement of the aggregate for this metric.
     *
     * @var string|null
     */
    public $select;

    /**
     * The column for this metric.
     *
     * @var string
     */
    public $column;

    /**
     * The date column for this metric.
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
     * The callback used to format the results.
     *
     * @var \Closure|null
     */
    public $displayCallback;

    /**
     * The value suffix for this metric.
     *
     * @var string|null
     */
    public $suffix;

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
     * Whether or not to use the current "to" range in the previous range.
     *
     * @var boolean
     */
    public $useCurrentToRange = false;

    /**
     * The resolver to create the base query.
     *
     * @var \Closure|null
     */
    public $queryResolver;

    /**
     * The resolver to create the base query using a range.
     *
     * @var \Closure|null
     */
    public $queryWithRangeResolver;

    /**
     * The value accessor for the query.
     *
     * @var \Closure|null
     */
    public $valueAccessor;

    /**
     * The query with range callbacks for this metric.
     *
     * @var array
     */
    public $queryWithRangeCallbacks = [];

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
        // Determine the result
        $result = $this->aggregate($request, $this->model, $this->function, $this->column, $this->dateColumn);

        // Check for a suffix
        if(!is_null($this->suffix)) {
            $result->suffix($this->suffix);
        }

        // // Determine the trend value
        // $result->result(
        //     $this->applyResultFormat(array_sum($result->trend))
        // );

        // Format the value
        $result->format($this->format);

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
     * Sets the raw select statement of the aggregate for this metric.
     *
     * @param  string  $select
     *
     * @return $this
     */
    public function select($select)
    {
        $this->select = $select;

        return $this;
    }

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
     * Sets the date column for this metric.
     *
     * @param  string  $dateColumn
     *
     * @return $this
     */
    public function dateColumn($dateColumn)
    {
        $this->dateColumn = $dateColumn;

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
            return round($value / $quotient, $this->precision);
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
     * Sets the suffix for this metric.
     *
     * @param  string|null  $suffix
     *
     * @return $this
     */
    public function suffix($suffix)
    {
        $this->suffix = $suffix;

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
     * Sets whether or not to use the current "to" range in the previous range.
     *
     * @param  boolean  $useCurrentToRange
     *
     * @return $this
     */
    public function useCurrentToRange($useCurrentToRange = true)
    {
        $this->useCurrentToRange = $useCurrentToRange;

        return $this;
    }

    /**
     * Sets the query resolver for this metric.
     *
     * @param  \Closure  $callback
     *
     * @return $this
     */
    public function query(Closure $callback)
    {
        $this->queryResolver = $callback;

        return $this;
    }

    /**
     * Sets the query with range resolver for this metric.
     *
     * @param  \Closure  $callback
     *
     * @return $this
     */
    public function queryWithRange(Closure $callback)
    {
        $this->queryWithRangeResolver = $callback;

        return $this;
    }

    /**
     * Creates and returns a new query without applying any callbacks.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $reference
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQueryWithoutCallbacks(Request $request, $reference = 'current')
    {
        // If a query with range resolver exists, use it
        if(!is_null($resolver = $this->queryWithRangeResolver)) {

            // Determine the range
            $range = $this->{$reference . 'Range'}($request->range);

            // Return the query
            return $resolver($range);

        }

        // If a query resolver exists, use it
        if(!is_null($resolver = $this->queryResolver)) {
            return $resolver();
        }

        // Determine the model
        $model = $this->model;

        // Create a new query from the model
        return (new $model)->newQuery();
    }

    /**
     * Creates and returns a new query.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $reference
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQueryWithoutRange(Request $request, $reference = 'current')
    {
        // Create a new query
        $query = $this->newQueryWithoutCallbacks($request, $reference);

        // Apply the query callbacks
        $this->applyQueryCallbacks($query);

        // Return the query
        return $query;
    }

    /**
     * Creates and returns a new query.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $reference
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQuery(Request $request, $reference = 'current')
    {
        // Create a new query
        $query = $this->newQueryWithoutRange($request, $reference);

        // Determine the date column
        $dateColumn = $this->dateColumn ?? $query->getModel()->getCreatedAtColumn();

        // Determine the range
        $range = $this->{$reference . 'Range'}($request->range);

        // Apply the range to the query
        if(!$this->noRanges && $dateColumn !== false && is_null($this->queryWithRangeResolver)) {
            $query->whereBetween($dateColumn, $range);
        }

        // Apply the range callback
        if(!$this->noRanges) {
            $this->applyQueryWithRangeCallbacks($query, $range);
        }

        // Return the query
        return $query;
    }

    /**
     * Creates and returns a new current query.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newCurrentQuery(Request $request)
    {
        return $this->newQuery($request, 'current');
    }

    /**
     * Creates and returns a new previous query.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newPreviousQuery(Request $request)
    {
        return $this->newQuery($request, 'previous');
    }

    /**
     * Sets the value accessor for this metric.
     *
     * @param  \Closure  $accessor
     *
     * @return $this
     */
    public function setValueAccessor(Closure $accessor)
    {
        $this->valueAccessor = $accessor;

        return $this;
    }

    /**
     * Sets the value accessor to use the sum of aggregates.
     *
     * @return $this
     */
    public function useSumOfAggregates()
    {
        return $this->setValueAccessor(function($query) {
            return DB::query()->fromSub($query, 'aggregates')->sum('aggregate');
        });
    }

    /**
     * Sets the value accessor to use the average of aggregates.
     *
     * @return $this
     */
    public function useAverageOfAggregates()
    {
        return $this->setValueAccessor(function($query) {
            return DB::query()->fromSub($query, 'aggregates')->average('aggregate');
        });
    }

    /**
     * Returns the aggregate value from the specified query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *
     * @return mixed
     */
    public function getValueFromQuery($query)
    {
        // Determine the value accessor
        $accessor = $this->valueAccessor ?? function($query) {
            return $query->first()->aggregate ?? null;
        };

        // Return the value
        return $accessor($query);
    }

    /**
     * Applies the query with range callbacks to the specified query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *
     * @return void
     */
    public function applyQueryWithRangeCallbacks($query, $range)
    {
        foreach($this->queryWithRangeCallbacks as $callback) {
            $callback($query, $range);
        }
    }

    /**
     * Adds the specified closure as a query with range callback.
     *
     * @param  \Closure  $callback
     *
     * @return $this
     */
    public function scopeWithRange(Closure $callback)
    {
        $this->queryWithRangeCallbacks[] = $callback;

        return $this;
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

    /**
     * Return a value result showing the growth of a model over a given time frame.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|string  $model
     * @param  string  $function
     * @param  string|null  $column
     * @param  string|null  $dateColumn
     * @return \Laravel\Nova\Metrics\ValueResult
     */
    protected function aggregate($request, $model, $function, $column = null, $dateColumn = null)
    {
        // Determine the current and previous queries
        $currentQuery = $this->newCurrentQuery($request);
        $previousQuery = $this->newPreviousQuery($request);

        // Determine the aggregate column
        $column = $column ?? $currentQuery->getModel()->getQualifiedKeyName();

        // Determine the select statement
        $select = $this->select ?? "{$function}({$column})";

        // Select the aggregate
        $currentQuery->select(DB::raw("{$select} as aggregate"));
        $previousQuery->select(DB::raw("{$select} as aggregate"));

        // Determine the current and previous values
        $currentValue = round($this->getValueFromQuery($currentQuery), $this->precision);
        $previousValue = round($this->getValueFromQuery($previousQuery), $this->precision);

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
        return $this->result($currentValue)->previous($previousValue);
    }

    /**
     * Calculate the previous range and calculate any short-cuts.
     *
     * @param  string|int  $range
     *
     * @return array
     */
    protected function previousRange($range)
    {
        $previous = parent::previousRange($range);

        if($this->useCurrentToRange) {
            $previous[1] = parent::currentRange($range)[1];
        }

        return $previous;
    }
}
