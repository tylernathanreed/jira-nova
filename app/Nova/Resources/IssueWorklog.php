<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;

class IssueWorklog extends Resource
{
    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Meta';

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\IssueWorklog::class;

    /**
     * The number of resources to show per page via relationships.
     *
     * @var int
     */
    public static $perPageViaRelationship = 10;

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'author_name'
    ];

    /**
     * The default ordering to use when listing this resource.
     *
     * @var array
     */
    public static $defaultOrderings = [
        'started_at' => 'desc'
    ];

    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return 'Worklogs';
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [

            Field::id()->onlyOnDetail(),

            Field::number('Jira ID', 'jira_id')->onlyOnDetail(),

            Field::belongsTo('Issue', 'issue', Issue::class)->exceptOnForms()->sortable(),

            Field::avatar('A')->thumbnail(function() {
                return $this->author_icon_url;
            })->maxWidth(16)->onlyOnIndex(),

            Field::text('Author', 'author_name')->exceptOnForms()->sortable(),

            Field::date('Started At', 'started_at')->exceptOnForms()->format('M/D/YY')->sortable(),

            Field::number('Time Spent', 'time_spent')->displayUsing(function($value) {
                return round($value / 3600, 2);
            })->exceptOnForms()->sortable()

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
            $this->getWorklogTrend(),
            $this->getWorklogPartition(),
            $this->getEfficiencyValue()
        ];
    }

    /**
     * Creates and returns a new worklog trend.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public function getWorklogTrend()
    {
        return (new \App\Nova\Metrics\FluentTrend)
            ->model(static::$model)
            ->label('Worklog')
            ->sumOf('time_spent')
            ->dateColumn('started_at')
            ->suffix('hours')
            ->divideBy(3600)
            ->precision(2);
    }

    /**
     * Creates and returns a new worklog partition.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public function getWorklogPartition()
    {
        return (new \App\Nova\Metrics\FluentPartition)
            ->model(static::$model)
            ->label('Worklog by Author (Past 30 Days)')
            ->sumOf('time_spent')
            ->groupBy('author_name')
            ->range(30)
            ->dateColumn('started_at')
            ->divideBy(3600)
            ->sortDesc();
    }

    /**
     * Creates and returns a new efficiency value.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public function getEfficiencyValue()
    {
        return (new \App\Nova\Metrics\FluentValue)
            ->model(static::$model)
            ->label('Efficiency')
            ->select('(sum(issue_worklogs.time_spent) / 3600.0) / (sum(schedules.simple_weekly_allocation) / 5 * 20)')
            ->leftJoinRelation('author')
            ->join('schedules', function($join) {
                $join->where(function($join) {
                    $join->on('schedules.id', '=', 'users.schedule_id');
                    $join->orWhere(function($join) {
                        $join->whereNull('users.schedule_id');
                        $join->where('schedules.system_name', '=', 'simple');
                    });
                });
            })
            ->dateColumn('started_at')
            ->precision(2)
            ->format([
                'output' => 'percent',
                'mantissa' => 0
            ])
            ->useScalarDelta();
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
        return [];
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
