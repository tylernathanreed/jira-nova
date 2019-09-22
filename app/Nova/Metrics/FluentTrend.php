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

    use Concerns\InlineFilterable;

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

        // Apply the filter
        $this->applyFilter($query);

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

        $possibleDateResults = $this->getAllPossibleDateResults(
            $startingDate = $this->futuristic ? Chronos::now() : $this->getAggregateStartingDate($request, $unit),
            $endingDate = $this->futuristic ? $this->getAggregateEndingDate($request, $unit) : Chronos::now(),
            $unit,
            $timezone,
            $request->twelveHourTime === 'true'
        );

        $wrappedColumn = $query->getQuery()->getGrammar()->wrap($column);

        $results = $query
                ->select(DB::raw("{$expression} as date_result, {$function}({$wrappedColumn}) as aggregate"))
                ->whereBetween($dateColumn, [$startingDate, $endingDate])
                ->groupBy(DB::raw($expression))
                ->orderBy('date_result')
                ->get();

        $results = array_merge($possibleDateResults, $results->mapWithKeys(function ($result) use ($request, $unit) {
            return [$this->formatAggregateResultDate(
                $result->date_result, $unit, $request->twelveHourTime === 'true'
            ) => round($result->aggregate, $this->precision)];
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
}
