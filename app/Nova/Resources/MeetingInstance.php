<?php

namespace App\Nova\Resources;

use Field;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MeetingInstance extends Resource
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
    public static $model = \App\Models\MeetingInstance::class;

    /**
     * The default ordering to use when listing this resource.
     *
     * @var array
     */
    public static $defaultOrderings = [
        'effective_date' => 'desc'
    ];

    /**
     * The relationship counts that should be eager loaded when performing an index query.
     *
     * @var array
     */
    public static $withCount = [
        'participants'
    ];

    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return 'Meetings';
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

            Field::text('Name', 'name')->required()->sortable(),

            Field::date('Effective', 'effective_date')->required()->sortable(),

            Field::time('Start Time', 'start_time', function($value, $resource) {
                return $value ? Carbon::createFromFormat('H:i:s', $value)->format('g:i A') : null;
            })->required(),

            Field::time('End Time', 'end_time', function($value, $resource) {
                return $value ? Carbon::createFromFormat('H:i:s', $value)->format('g:i A') : null;
            })->required()->rules('after:start_time'),

            Field::number('Length', 'length_in_seconds', function($value, $resource) {
                return round($value / 3600, 2);
            })->exceptOnForms()->sortable(),

            Field::multiselect('Participants', 'participants')
                ->options(array_flip(User::selection()))
                ->placeholder('Chose participants...')
                ->required()
                ->onlyOnForms()
                ->fillAfterCreate()
                ->resolveUsing(function($value, $resource) {
                    return collect($value)->pluck('id')->toArray();
                }),

            Field::belongsToMany('Participants', 'participants', User::class)
                ->onlyOnDetail(),

            Field::number('Participants', 'participants_count')->onlyOnIndex()->sortable(),
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
            static::newPastMeetingsTrend(),
            static::newFutureMeetingsTrend(),
            static::newMeetingYtdValue()
        ];
    }

    /**
     * Creates and returns a new past meeting trend metric.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function newPastMeetingsTrend()
    {
        return (new \App\Nova\Metrics\FluentTrend)
            ->model(static::$model)
            ->label('Past Meetings')
            ->useCount()
            ->dateColumn('effective_date')
            ->suffix('meetings')
            ->help('This metric shows the recent meetings per day.');
    }

    /**
     * Creates and returns a new future meeting trend metric.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function newFutureMeetingsTrend()
    {
        return (new \App\Nova\Metrics\FluentTrend)
            ->model(static::$model)
            ->label('Future Meetings')
            ->useCount()
            ->dateColumn('effective_date')
            ->suffix('meetings')
            ->futuristic()
            ->help('This metric shows the upcoming meetings per day.');
    }

    /**
     * Creates and returns a new meeting year-to-date partition metric.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function newMeetingYtdValue()
    {
        return (new \App\Nova\Metrics\FluentValue)
            ->model(static::$model)
            ->label('Meetings YTD')
            ->useCount()
            ->dateColumn('effective_date')
            ->whereYear('effective_date', date('Y'))
            ->where('effective_date', '<=', carbon())
            ->noRanges()
            ->help('This metric shows the aggregate total meetings since the start of the current year.');
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
            new \App\Nova\Filters\WhereDateFilter('On or After', 'effective_date', '>=', carbon()->toDateString()),
            new \App\Nova\Filters\WhereDateFilter('On or Before', 'effective_date', '<='),
            (new \App\Nova\Filters\FluentSelectFilter('Has Participant', 'users.id', User::selection(), $request->user()->getKey()))->relation('participants')
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
