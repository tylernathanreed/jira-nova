<?php

namespace App\Nova\Metrics;

use DB;
use Closure;
use Cake\Chronos\Chronos;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Trend;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Nova\Metrics\TrendDateExpressionFactory;

class FluentTrend extends Trend
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
    public $component = 'trend-metric';

    /**
     * The model class for this metric.
     *
     * @var string
     */
    public $model;

    /**
     * The unit for this metric.
     *
     * @var string
     */
    public $unit = self::BY_DAYS;

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
     * The select statements to add before calculating the result.
     *
     * @var array
     */
    public $addSelects = [];

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
     * The resolver to create the base query.
     *
     * @var \Closure|null
     */
    public $queryResolver;

    /**
     * The query callbacks for this metric.
     *
     * @var array
     */
    public $queryCallbacks = [];

    /**
     * The query with range callbacks for this metric.
     *
     * @var array
     */
    public $queryWithRangeCallbacks = [];

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
     * Whether or not to use a date range query.
     *
     * @var boolean
     */
    public $useDateRangeQuery = false;

    /**
     * The callback used to provide additional date-based columns.
     *
     * @var \Closure|null
     */
    public $dateRangeCallback;

    /**
     * The additional columns to group by.
     *
     * @var array
     */
    public $groupByColumns = [];

    /**
     * The callback to reduce grouped results by date.
     *
     * @var \Closure|null
     */
    public $groupByResolver;

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
        $query = $this->newQuery($request);

        // Apply the filter
        $this->applyQueryCallbacks($query);

        // Determine the result
        $result = $this->aggregate($request, $query, $this->unit, $this->function, $this->column, $this->dateColumn);

        // Check for a suffix
        if(!is_null($this->suffix)) {
            $result->suffix($this->suffix);
        }

        // Determine the trend value
        $result->result(
            $this->applyResultFormat(array_sum($result->trend))
        );

        // Format each trend value
        foreach($result->trend as &$value) {
            $value = $this->applyResultFormat($value);
        }

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
     * Sets the unit for this metric.
     *
     * @param  string  $unit
     *
     * @return $this
     */
    public function unit($unit)
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * Variants of {@see $this->unit()}.
     *
     * @return $this
     */
    public function byMonths()  { return $this->unit(self::BY_MONTHS); }
    public function byWeeks()   { return $this->unit(self::BY_WEEKS); }
    public function byDays()    { return $this->unit(self::BY_DAYS); }
    public function byHours()   { return $this->unit(self::BY_HOURS); }
    public function byMinutes() { return $this->unit(self::BY_MINUTES); }

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
     * Adds the specified select statements before calculating the result.
     *
     * @param  string|array  $select
     *
     * @return $this
     */
    public function addSelect($addSelects)
    {
        $addSelects = (array) $addSelects;

        $this->addSelects = array_merge($this->addSelects, $addSelects);

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
     * Creates and returns a new query.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQuery(Request $request)
    {
        // If a query resolver exists, use it
        if(!is_null($resolver = $this->queryResolver)) {
            return $resolver();
        }

        // If we're using a date range query, return it
        if($this->useDateRangeQuery) {
            return $this->newDateRangeQuery($request);
        }

        // Determine the model
        $model = $this->model;

        // Create a new query from the model
        return (new $model)->newQuery();
    }

    /**
     * Adds the specified closure as a query callback.
     *
     * @param  \Closure  $callback
     *
     * @return $this
     */
    public function scope(Closure $callback)
    {
        $this->queryCallbacks[] = $callback;

        return $this;
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
     * Returns the aggregate value from the specified result.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $result
     *
     * @return mixed
     */
    public function getValueFromResult($result)
    {
        // Determine the value accessor
        $accessor = $this->valueAccessor ?? function($result) {
            return $result->aggregate ?? null;
        };

        // Return the value
        return $accessor($result);
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
     * Sets whether or not to use a date range query as the base query.
     *
     * @param  \Closure|boolean  $callback
     *
     * @return $this
     */
    public function useDateRangeQuery($callback = true)
    {
        $this->useDateRangeQuery = $callback !== false;

        $this->dateRangeCallback = $callback instanceof Closure ? $callback : null;

        return $this;
    }

    /**
     * Creates and returns a new date range query.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newDateRangeQuery(Request $request)
    {
        // Determine the date range
        $range = $this->getDateRange($request);

        // Determine the date range callback
        $callback = $this->dateRangeCallback;

        // Determine the date handler
        $handler = function($query, $date) use ($callback) {

            // Check if a callback was provided
            if(!is_null($callback)) {

                // Select the result of the callback
                $query->select($callback($date));

            }

            // Select the date
            $query->selectRaw('? as date', [$date]);

            // Return the query
            return $query;
        };

        // Create a new query
        $query = $handler(DB::query(), $range[0]);

        // Iterate through the range
        for($date = $range[0]->addDay(1); $date->lt($range[1]); $date = $date->addDay(1)) {
            $query->unionAll($handler(DB::query(), $date));
        }

        // Convert the query to a subselect
        $query = DB::query()->fromSub($query, 'dates');

        // Determine the eloquent model
        $model = $this->model;

        // Convert the query to an eloquent query
        $query = (new $model)->newQuery()->setQuery($query);

        // Return the query
        return $query;
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        $adjective = $this->futuristic ? 'Next' : 'Past';

        return [
            30 => $adjective . ' 30 Days',
            60 => $adjective . ' 60 Days',
            90 => $adjective . ' 90 Days',
            365 => $adjective . ' 1 Year'
        ];
    }

    /**
     * Returns the date range for the specified request.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function getDateRange($request)
    {
        return [
            $this->futuristic ? Chronos::now() : $this->getAggregateStartingDate($request, $this->unit),
            $this->futuristic ? $this->getAggregateEndingDate($request, $this->unit) : Chronos::now()
        ];
    }

    /**
     * Adds additional columns to group by and specifies how to reduce the groups afterwards.
     *
     * @param  array     $columns
     * @param  \Closure  $callback
     */
    public function groupBy($columns, Closure $callback)
    {
        $this->groupByColumns = $columns;
        $this->groupByResolver = $callback;

        return $this;
    }

    /**
     * Return a value result showing a aggregate over time.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|string  $model
     * @param  string  $unit
     * @param  string  $function
     * @param  string  $column
     * @param  string  $dateColumn
     *
     * @return \Laravel\Nova\Metrics\TrendResult
     */
    protected function aggregate($request, $model, $unit, $function, $column, $dateColumn = null)
    {
        $query = $model instanceof Builder ? $model : (new $model)->newQuery();

        $timezone = $request->timezone;

        $expression = (string) TrendDateExpressionFactory::make(
            $query, $dateColumn = $dateColumn ?? $query->getModel()->getCreatedAtColumn(),
            $unit, $timezone
        );

        $dateRange = $this->getDateRange($request, $unit);

        $possibleDateResults = $this->getAllPossibleDateResults(
            $startingDate = $dateRange[0],
            $endingDate = $dateRange[1],
            $unit,
            $timezone,
            $request->twelveHourTime === 'true'
        );

        $wrappedColumn = $query->getQuery()->getGrammar()->wrap($column);

        // Apply the ranged scopes
        $this->applyQueryWithRangeCallbacks($query, $dateRange);

        // Determine the select statement
        $select = $this->select ?? "{$function}({$wrappedColumn})";

        // Select the expression
        $query->select(DB::raw("{$expression} as date_result, {$select} as aggregate"));

        // Apply the date range
        $query->whereBetween($dateColumn, [$startingDate, $endingDate]);

        // Group by the expression
        $query->groupBy(DB::raw($expression));

        // Add additional groupings
        if(!empty($this->groupByColumns)) {
            $query->groupBy($this->groupByColumns);
        }

        // Order by date
        $query->orderBy('date_result');

        // Add any additional select statements
        if(!empty($this->addSelects)) {
            $query->addSelect($this->addSelects);
        }

        // Determine the query results
        $results = $query->get();

        // If we grouped by additional columns, then we'll need to group
        // the results by the date result, and reduce each group to a
        // single set of columns. How we do that is up to the dev.

        // If we had additional groupings, reduce the groups
        if(!empty($this->groupByColumns)) {

            // Determine the base model
            $model = $this->model;

            // Determine the resolver
            $resolver = $this->groupByResolver;

            // Reduce the groups
            $results = $results->groupBy('date_result')->map(function($group, $date) use ($model, $resolver) {
                return (new $model)->forceFill(array_merge(['date_result' => $date], $resolver($group)));
            });

        }

        // Determine the trend results
        $results = array_merge($possibleDateResults, $results->mapWithKeys(function ($result) use ($request, $unit) {
            return [$this->formatAggregateResultDate(
                $result->date_result, $unit, $request->twelveHourTime === 'true'
            ) => round($this->getValueFromResult($result), $this->precision)];
        })->all());

        if (count($results) > $request->range) {
            array_shift($results);
        }

        return $this->result()->trend(
            $results
        );
    }

    /**
     * Determine the proper aggregate ending date.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $unit
     * @return \Cake\Chronos\Chronos
     */
    protected function getAggregateEndingDate($request, $unit)
    {
        $now = Chronos::now();

        switch ($unit) {
            case 'month':
                return $now->addMonths($request->range - 1)->firstOfMonth()->setTime(0, 0);

            case 'week':
                return $now->addWeeks($request->range - 1)->startOfWeek()->setTime(0, 0);

            case 'day':
                return $now->addDays($request->range - 1)->setTime(0, 0);

            case 'hour':
                return with($now->addHours($request->range - 1), function ($now) {
                    return $now->setTimeFromTimeString($now->hour.':00');
                });

            case 'minute':
                return with($now->addMinutes($request->range - 1), function ($now) {
                    return $now->setTimeFromTimeString($now->hour.':'.$now->minute.':00');
                });

            default:
                throw new InvalidArgumentException('Invalid trend unit provided.');
        }
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
