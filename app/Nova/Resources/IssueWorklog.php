<?php

namespace App\Nova\Resources;

use DB;
use Field;
use Illuminate\Http\Request;
use App\Models\Issue as IssueModel;
use App\Models\Schedule as ScheduleModel;
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
            static::getWorklogTrend(),
            static::getWorklogByAuthorPartition(),
            static::getEfficiencyValue()
        ];
    }

    /**
     * Creates and returns a new worklog trend.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getWorklogTrend()
    {
        return (new \App\Nova\Metrics\FluentTrend)
            ->model(static::$model)
            ->label('Worklog History')
            ->sumOf('time_spent')
            ->dateColumn('started_at')
            ->suffix('hours')
            ->divideBy(3600)
            ->precision(2)
            ->help('This metric shows the number of hours recently logged.');
    }

    /**
     * Creates and returns a new feature worklog trend.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getFeatureWorklogTrend()
    {
        return static::getWorklogTrend()
            ->label('Feature Worklog')
            ->joinRelation('issue', function($join) {
                $join->features();
            })
            ->help('This metric shows the number of hours recently logged on Features.');
    }

    /**
     * Creates and returns a new defect worklog trend.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getDefectWorklogTrend()
    {
        return static::getWorklogTrend()
            ->label('Defect Worklog')
            ->joinRelation('issue', function($join) {
                $join->defects();
            })
            ->help('This metric shows the number of hours recently logged on Defects.');
    }

    /**
     * Creates and returns a new expected worklog value metric.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getExpectedWorklogTrend()
    {
        return (new \App\Nova\Metrics\FluentTrend)
            ->model(static::$model)
            ->label('Expected Worklog')
            ->useDateRangeQuery(function($date) {
                return (($date->isSunday() || $date->isSaturday()) ? 1 : 0) . ' as is_weekend';
            })
            ->scopeWithRange(function($query, $range) {

                $subquery = (new ScheduleModel)->newActiveSchedulesQuery($range);

                $query->joinSub($subquery, 'active_schedules', function($join) {
                    $join->whereRaw('\'1\' = \'1\'');
                });

                $query->leftJoin('time_off', function($join) {

                    $join->on('time_off.user_id', '=', 'active_schedules.author_id');
                    $join->on('time_off.date', '=', 'dates.date');
                    $join->whereNull('time_off.deleted_at');

                });

                $query->leftJoin('holiday_instances', function($join) {
                    $join->on('holiday_instances.observed_date', '=', 'dates.date');
                });

                $query->leftJoin('meeting_participants', function($join) {
                    $join->on('meeting_participants.user_id', '=', 'active_schedules.author_id');
                });

                $query->leftJoin('meeting_instances', function($join) {

                    $join->on('meeting_instances.id', '=', 'meeting_participants.meeting_instance_id');
                    $join->on('meeting_instances.effective_date', '=', 'dates.date');

                });

            })
            ->select(preg_replace('/\s\s+/', ' ', 'sum(active_schedules.simple_weekly_allocation / 5.0)'))
            ->addSelect([
                DB::raw('max(dates.is_weekend) as is_weekend'),
                'active_schedules.author_id',
                'active_schedules.author_key',
                'active_schedules.author_name',
                DB::raw('sum(time_off.percent) as percent_off'),
                DB::raw('sum(case when holiday_instances.id is not null then \'1\' else \'0\' end) as is_holiday'),
                DB::raw('sum(meeting_instances.length_in_seconds) / 3600.0 as cumulative_meeting_length')
            ])
            ->groupBy(['author_id', 'author_name', 'author_key'], function($group) {
                return [
                    'aggregate' => $group->reduce(function($aggregate, $result) {

                        if($result->is_weekend || $result->is_holiday) {
                            return $aggregate;
                        }

                        $value = max(($result->aggregate * (1 - $result->percent_off)) - $result->cumulative_meeting_length, 0);

                        return $aggregate + $value;

                    }, 0)
                ];
            })
            ->dateColumn('dates.date')
            ->suffix('hours')
            ->precision(2)
            ->help('This metric shows the number of hours that are expected to be logged for recent days.');
    }

    /**
     * Creates and returns a new upkeep value.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getUpkeepValue()
    {
        return (new \App\Nova\Metrics\TrendComparisonValue)
            ->label('Upkeep')
            ->trends([static::getDefectWorklogTrend(), static::getWorklogTrend()])
            ->useScalarDelta()
            ->format([
                'output' => 'percent',
                'mantissa' => 0
            ])
            ->help('This metric shows the percent-comparison between the hours logged on Defects vs. Features, where high numbers indicate significant time spent on Defects.');
    }

    /**
     * Creates and returns a new efficiency value.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getEfficiencyValue()
    {
        return (new \App\Nova\Metrics\TrendComparisonValue)
            ->label('Efficiency')
            ->trends([static::getWorklogTrend(), static::getExpectedWorklogTrend()])
            ->useScalarDelta()
            ->format([
                'output' => 'percent',
                'mantissa' => 0
            ])
            ->help('This metric shows the percent-comparison between the expected log hours against the actual logged hours, where low numbers may indicate failure to log or disruptions in the workplace.');
    }

    /**
     * Creates and returns a new worklog by epic partition.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getWorklogByEpicPartition()
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
            ->resultClass(\App\Nova\Metrics\Results\EpicPartitionResult::class)
            ->help('This metric shows the aggregate number of hours logged per Epic in the past 30 days.');
    }

    /**
     * Creates and returns a new worklog by priority partition.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getWorklogByPriorityPartition()
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
            ->resultClass(\App\Nova\Metrics\Results\PriorityPartitionResult::class)
            ->help('This metric shows the aggregate number of hours logged per Priority in the past 30 days.');
    }

    /**
     * Creates and returns a new worklog by author partition.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getWorklogByAuthorPartition()
    {
        return (new \App\Nova\Metrics\FluentPartition)
            ->model(static::$model)
            ->label('Worklog by Author (Past 30 Days)')
            ->sumOf('time_spent')
            ->groupBy('author_name')
            ->range(30)
            ->dateColumn('started_at')
            ->divideBy(3600)
            ->limit(10)
            ->sortDesc()
            ->help('This metric shows the aggregate number of hours logged per Author in the past 30 days.');
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
