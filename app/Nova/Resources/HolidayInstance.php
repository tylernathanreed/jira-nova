<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;

class HolidayInstance extends Resource
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
    public static $model = \App\Models\HolidayInstance::class;

    /**
     * The default ordering to use when listing this resource.
     *
     * @var array
     */
    public static $defaultOrderings = [
        'observed_date' => 'desc'
    ];

    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return 'Holidays';
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

            Field::text('Name', 'name'),

            Field::date('Effective', 'effective_date')->sortable(),

            Field::date('Observed', 'observed_date')->exceptOnForms()->sortable(),

            Field::dateTime('Created At', 'created_at')
                ->onlyOnDetail(),

            Field::dateTime('Updated At', 'updated_at')
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
            static::newPastHolidaysTrend(),
            static::newFutureHolidaysTrend(),
            static::newHolidayYtdValue()
        ];
    }

    /**
     * Creates and returns a new past holiday trend metric.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function newPastHolidaysTrend()
    {
        return (new \App\Nova\Metrics\FluentTrend)
            ->model(static::$model)
            ->label('Past Holidays')
            ->useCount()
            ->dateColumn('observed_date')
            ->suffix('holidays')
            ->help('This metric shows the recent holidays per day.');
    }

    /**
     * Creates and returns a new future holiday trend metric.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function newFutureHolidaysTrend()
    {
        return (new \App\Nova\Metrics\FluentTrend)
            ->model(static::$model)
            ->label('Future Holidays')
            ->useCount()
            ->dateColumn('observed_date')
            ->suffix('holidays')
            ->futuristic()
            ->help('This metric shows the upcoming holidays per day.');
    }

    /**
     * Creates and returns a new holiday year-to-date partition metric.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function newHolidayYtdValue()
    {
        return (new \App\Nova\Metrics\FluentValue)
            ->model(static::$model)
            ->label('Holidays YTD')
            ->useCount()
            ->dateColumn('observed_date')
            ->whereYear('observed_date', date('Y'))
            ->where('observed_date', '<=', carbon())
            ->noRanges()
            ->help('This metric shows the aggregate total holidays since the start of the current year.');
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
            new \App\Nova\Filters\WhereDateFilter('On or After', 'observed_date', '>=', carbon()),
            new \App\Nova\Filters\WhereDateFilter('On or Before', 'observed_date', '<=', carbon()->addYear())
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
