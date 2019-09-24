<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;

class TimeOff extends Resource
{
    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Scheduling';

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\TimeOff::class;

    /**
     * The default ordering to use when listing this resource.
     *
     * @var array
     */
    public static $defaultOrderings = [
        'date' => 'desc'
    ];

    /**
     * The default attributes for new model instances.
     *
     * @var array
     */
    public static $defaultAttributes = [
        'percent' => 1
    ];

    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return 'Time Off';
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            Field::id()
                ->sortable()
                ->onlyOnDetail(),

            Field::belongsTo('User', 'user', User::class)->sortable(),

            Field::date('Date', 'date')->sortable(),

            Field::percent('Percent', 'percent')
                ->help('Enter a whole number between 0 (0%) and 100 (100%).')
                ->onlyOnForms()
                ->withMeta([
                    'defaultValue' => 100
                ]),

            Field::percent('Percent', 'percent')->exceptOnForms(),

            Field::textarea('Description', 'description')
                ->hideFromIndex()
                ->rules('nullable', 'string', 'max:255'),

            Field::dateTime('Created At', 'created_at')
                ->onlyOnDetail(),

            Field::dateTime('Updated At', 'updated_at')
                ->onlyOnDetail(),

            Field::dateTime('Deleted At', 'deleted_at')
                ->onlyOnDetail()

        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function cards(Request $request)
    {
        return [
            $this->newPastTimeOffTrend(),
            $this->newFutureTimeOffTrend(),
            $this->newTimeOffYtdPartition()
        ];
    }

    /**
     * Creates and returns a new past time off trend metric.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public function newPastTimeOffTrend()
    {
        return (new \App\Nova\Metrics\FluentTrend)
            ->model(static::$model)
            ->label('Past Time Off')
            ->sumOf('percent')
            ->dateColumn('date')
            ->precision(2)
            ->suffix('days');
    }

    /**
     * Creates and returns a new future time off trend metric.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public function newFutureTimeOffTrend()
    {
        return (new \App\Nova\Metrics\FluentTrend)
            ->model(static::$model)
            ->label('Future Time Off')
            ->sumOf('percent')
            ->dateColumn('date')
            ->precision(2)
            ->suffix('days')
            ->futuristic();
    }

    /**
     * Creates and returns a new time off year-to-date partition metric.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public function newTimeOffYtdPartition()
    {
        return (new \App\Nova\Metrics\FluentPartition)
            ->model(static::$model)
            ->label('Time Off YTD')
            ->joinRelation('user')
            ->sumOf('percent')
            ->groupBy('users.display_name')
            ->ytd('date')
            ->precision(2);
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function filters(Request $request)
    {
        return [
            new \App\Nova\Filters\WhereDateFilter('On or After', 'date', '>='),
            new \App\Nova\Filters\WhereDateFilter('On or Before', 'date', '<=')
        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
