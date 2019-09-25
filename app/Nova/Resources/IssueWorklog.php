<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;

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
        'author_name',
        'issues.key'
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
     * Build an "index" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder    $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        // Join into issues
        $query->joinRelation('issue');

        // Select the worklog attributes
        $query->select('issue_worklogs.*');

        // Call the parent method
        $query = parent::indexQuery($request, $query);

        // Return the query
        return $query;
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
            $this->getWorklogByAuthorPartition(),
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
     * Creates and returns a new worklog by epic partition.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public function getWorklogByEpicPartition()
    {
        return (new \App\Nova\Metrics\FluentPartition)
            ->model(static::$model)
            ->label('Worklog by Epic (Past 30 Days)')
            ->sumOf('time_spent')
            ->joinRelation('issue')
            ->whereNotNull('issues.epic_name')
            ->groupBy('issues.epic_name')
            ->range(30)
            ->dateColumn('started_at')
            ->divideBy(3600)
            ->sortDesc();
    }

    /**
     * Creates and returns a new worklog by priority partition.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public function getWorklogByPriorityPartition()
    {
        return (new \App\Nova\Metrics\FluentPartition)
            ->model(static::$model)
            ->label('Worklog by Priority (Past 30 Days)')
            ->sumOf('time_spent')
            ->joinRelation('issue')
            ->groupBy('issues.priority_name')
            ->range(30)
            ->dateColumn('started_at')
            ->divideBy(3600)
            ->sortDesc();
    }

    /**
     * Creates and returns a new worklog by author partition.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public function getWorklogByAuthorPartition()
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
     * Creates and returns a new expected worklog value metric.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public function getExpectedWorklogValue()
    {
        return (new \App\Nova\Metrics\FluentValue)
            ->model(static::$model)
            ->label('Expected Worklog')
            ->select('schedules.simple_weekly_allocation / 5.0 * 20.0')
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
            ->groupBy([
                'issue_worklogs.author_name',
                'schedules.simple_weekly_allocation'
            ])
            ->useSumOfAggregates()
            ->dateColumn('started_at')
            ->precision(2);
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
        return [
            new \App\Nova\Filters\WhereDateFilter('Started On or After', 'started_at', '>='),
            new \App\Nova\Filters\WhereDateFilter('Started On or Before', 'started_at', '<=')
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
