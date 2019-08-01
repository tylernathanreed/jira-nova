<?php

namespace App\Nova\Metrics\Concerns;

use DB;
use Cake\Chronos\Chronos;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Nova\Metrics\TrendDateExpressionFactory;

trait FutureRange
{
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
            $startingDate = Chronos::now(),
            $endingDate = $this->getAggregateEndingDate($request, $unit),
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
            ) => round($result->aggregate, 0)];
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