<?php

namespace App\Nova\Resources;

use DB;
use Field;
use Illuminate\Http\Request;
use App\Models\Epic as EpicModel;
use App\Models\Label as LabelModel;

class Issue extends Resource
{
    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = self::class;

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Issue::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'key';

    /**
     * The additional value that can be used to provide context for the resource when being displayed.
     *
     * @var string
     */
    public static $subtitle = 'summary';

    /**
     * Indicates if the resoruce should be globally searchable.
     *
     * @var bool
     */
    public static $globallySearchable = true;

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
        'key', 'summary'
    ];

    /**
     * The default ordering to use when listing this resource.
     *
     * @var array
     */
    public static $defaultOrderings = [
        'rank' => 'asc'
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        if($request->viaRelationship == 'releaseNotes') {

            return [

                Field::avatar('T')->thumbnail(function() {
                    return $this->type_icon_url;
                })->maxWidth(16)->onlyOnIndex(),

                Field::avatar('P')->thumbnail(function() {
                    return $this->priority_icon_url;
                })->maxWidth(16)->onlyOnIndex(),

                Field::text('Key', 'key')->sortable(),

                Field::textWrap('Release Notes', 'release_notes')->asMarkdown()->withMeta(['maxWidth' => '1080px'])

            ];

        }

        return [

            Field::id()->onlyOnDetail(),

            Field::avatar('T')->thumbnail(function() {
                return $this->type_icon_url;
            })->maxWidth(16)->onlyOnIndex(),

            Field::text('Type', 'type_name')->onlyOnDetail(),

            Field::avatar('P')->thumbnail(function() {
                return $this->priority_icon_url;
            })->maxWidth(16)->onlyOnIndex(),

            Field::text('Priority', 'priority_name')->onlyOnDetail(),

            Field::badgeUrl('Key', 'key')->toUsing(function($value, $resource) {
                return [
                    'name' => 'detail',
                    'params' => [
                        'resourceName' => 'issues',
                        'resourceId' => $resource->id,
                    ],
                ];
            })->style([
                'fontFamily' => '\'Segoe UI\'',
                'fontSize' => '14px',
                'fontWeight' => '400',
            ])->onlyOnIndex(),

            Field::url('Key', function() {
                return $this->getExternalUrl();
            })->labelUsing(function() {
                return $this->key;
            })->alwaysClickable()->onlyOnDetail(),

            Field::badgeUrl('Epic', 'epic_name')->backgroundUsing(function($value, $resource) {
                return config("jira.colors.{$resource->epic_color}.background");
            })->foregroundUsing(function($value, $resource) {
                return config("jira.colors.{$resource->epic_color}.color");
            })->linkUsing(function($value, $resource) {
                return !is_null($resource->epic_id) ? EpicModel::getInternalUrlForId($resource->epic_id) : $resource->epic_url;
            })->style([
                'borderRadius' => '3px',
                'fontFamily' => '\'Segoe UI\'',
                'fontSize' => '12px',
                'fontWeight' => 'normal',
                'marginTop' => '0.25rem'
            ])->exceptOnForms(),

            Field::text('Summary', 'summary', function() {
                return strlen($this->summary) > 80 ? substr($this->summary, 0, 80) . '...' : $this->summary;
            })->onlyOnIndex(),

            Field::text('Summary', 'summary')->onlyOnDetail(),

            Field::badgeUrl('Status', 'status_name')->backgroundUsing(function($value, $resource) {
                return config("jira.colors.{$resource->status_color}.background");
            })->foregroundUsing(function($value, $resource) {
                return config("jira.colors.{$resource->status_color}.color");
            })->style([
                'fontFamily' => '\'Segoe UI\'',
                'fontSize' => '12px',
                'fontWeight' => '600',
                'borderRadius' => '3px',
                'textTransform' => 'uppercase',
                'marginTop' => '0.25rem'
            ])->exceptOnForms(),

            // Field::text('status_color', 'status_color'),

            Field::text('Issue Category', 'issue_category')->onlyOnDetail(),
            Field::text('Focus', 'focus')->onlyOnDetail(),

            // Field::text('assignee_key', 'assignee_key'),
            Field::text('Assignee', 'assignee_name')->onlyOnDetail(),

            Field::avatar('A')->thumbnail(function() {
                return $this->assignee_icon_url;
            })->maxWidth(16)->onlyOnIndex(),

            // Field::text('reporter_key', 'reporter_key'),
            Field::text('Reporter', 'reporter_name')->onlyOnDetail(),

            Field::avatar('R')->thumbnail(function() {
                return $this->reporter_icon_url;
            })->maxWidth(16)->onlyOnIndex(),

            Field::date('Due', 'due_date')->format('M/D')->onlyOnIndex()->sortable(),
            Field::date('Due', 'due_date')->hideFromIndex(),

            Field::date('Estimate', 'estimate_date')->format('M/D')->onlyOnIndex()->sortable(),
            Field::date('Estimate', 'estimate_date')->onlyOnDetail(),

            Field::number('Remaining', 'estimate_remaining')->displayUsing(function($value) {
                return number_format($value / 3600, 2);
            })->exceptOnForms()->sortable(),

            // Field::text('estimate_diff', 'estimate_diff'),

            Field::text('URL', 'url')->onlyOnDetail(),

            // Field::text('is_subtask', 'is_subtask'),
            Field::text('Parent', 'parent_key')->onlyOnDetail(),
            // Field::text('parent_url', 'parent_url'),

            Field::code('Labels', 'labels')->json()->onlyOnDetail(),

            Field::code('Links', 'links')->json()->onlyOnDetail(),
            // Field::text('blocks', 'blocks'),

            (
                DB::connection()->getDriverName() == 'mysql'
                    ? Field::text('Rank Index', 'rank_index')->sortable()->exceptOnForms()
                    : Field::text('Rank', 'rank')->sortable()->exceptOnForms()

            ),

            Field::date('Created', 'entry_date')->onlyOnDetail()

        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [
            static::getTicketEntryValue(),
            static::getIssueCreatedByDateTrend()->width('2/3')
        ];
    }

    /**
     * Creates and returns a new issue created by date value.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getIssueCreatedByDateValue()
    {
        return (new \App\Nova\Metrics\FluentValue)
            ->model(static::$model)
            ->label('Issues Created')
            ->useCount()
            ->dateColumn('entry_date')
            ->suffix('issues')
            ->help('This metric shows the total number of issues recently created.');
    }

    /**
     * Creates and returns a new ticket entry value.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getTicketEntryValue()
    {
        return static::getIssueCreatedByDateValue()
            ->label('Ticket Entry')
            ->where('focus', '=', 'Ticket')
            ->help('This metric shows the number of Ticket issues recently created.');
    }

    /**
     * Creates and returns a new issue created by date trend.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getIssueCreatedByDateTrend()
    {
        return (new \App\Nova\Metrics\FluentTrend)
            ->model(static::$model)
            ->label('Issues Created Per Day')
            ->useCount()
            ->dateColumn('entry_date')
            ->suffix('issues')
            ->help('This metric shows the number of issues recently created by day.');
    }

    /**
     * Creates and returns a new issue deliquencies by due date trend.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getIssueDeliquenciesByDueDateTrend()
    {
        return (new \App\Nova\Metrics\FluentTrend)
            ->model(static::$model)
            ->label('Delinquencies')
            ->useCount()
            ->dateColumn('due_date')
            ->whereNotNull('due_date')
            ->where('due_date', '<', carbon())
            ->incomplete()
            ->suffix('issues')
            ->help('This metric shows the number of issues that have recently become delinquent (i.e. not completed in time).');
    }

    /**
     * Creates and returns a new issue deliquencies by estimated date trend.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getIssueDeliquenciesByEstimatedDateTrend()
    {
        return (new \App\Nova\Metrics\FluentTrend)
            ->model(static::$model)
            ->label('Estimated Delinquencies')
            ->useCount()
            ->dateColumn('due_date')
            ->whereNotNull('estimate_date')
            ->whereNotNull('due_date')
            ->whereColumn('due_date', '>', 'estimate_date')
            ->incomplete()
            ->futuristic()
            ->suffix('issues')
            ->help('This metric shows the number of issues that will soon become delinquent (i.e. estimated to not be completed in time).');
    }

    /**
     * Creates and returns a new issue status partition.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getIssueStatusPartition()
    {
        return (new \App\Nova\Metrics\FluentPartition)
            ->model(static::$model)
            ->label('Issues by Status')
            ->useCount()
            ->groupBy('status_name')
            ->resultClass(\App\Nova\Metrics\Results\StatusPartitionResult::class)
            ->help('This metric shows the total number of issues in each status group.');
    }

    /**
     * Creates and returns a new issue type partition.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getIssueCountByTypePartition()
    {
        return (new \App\Nova\Metrics\FluentPartition)
            ->model(static::$model)
            ->label('Issue Counts by Type')
            ->useCount()
            ->groupBy('type_name')
            ->sort()
            ->help('This metric shows the total number of issues for each issue type.');
    }

    /**
     * Creates and returns a new issue priority partition.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getIssueCountByPriorityPartition()
    {
        return (new \App\Nova\Metrics\FluentPartition)
            ->model(static::$model)
            ->label('Issue Counts by Priority')
            ->useCount()
            ->groupBy('priority_name')
            ->resultClass(\App\Nova\Metrics\Results\PriorityPartitionResult::class)
            ->help('This metric shows the total number of issues for each issue priority.');
    }

    /**
     * Creates and returns a new issue status partition.
     *
     * @param  mixed  $reference
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getIssueWeekStatusPartition($reference = 'now')
    {
        // Determine the week label
        $label = LabelModel::getWeekLabel($reference ? carbon($reference) : carbon());

        // Determine the week index
        $index = LabelModel::getWeekLabelIndex($reference ? carbon($reference) : carbon());

        // Determine the week range
        $range = LabelModel::getWeekRange($index);

        // Return the partition
        return static::getIssueStatusPartition()
            ->where('labels', 'like', "%\"{$label}%")
            ->labelSuffix(" (#{$index})")
            ->help(sprintf('This metric shows the status group counts for the issues committed to Week #%s (%s - %s)',
                $index,
                $range[0]->format('n/j'),
                $range[1]->format('n/j')
            ));
    }

    /**
     * Creates and returns a new issue weekly satisfaction trend.
     *
     * @param  mixed  $reference
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getIssueWeeklySatisfactionTrend()
    {
        // Determine the current week label index
        $index = LabelModel::getWeekLabelIndex();

        // Determine the weekly labels
        $labels = array_map(function($index) {
            return 'Week' . $index;
        }, range(0, $index));

        // Convert the labels to date results
        $dateResults = array_combine($labels, array_fill(0, $index + 1, 0));

        // Return the trend
        return (new \App\Nova\Metrics\FluentTrend)
            ->model(static::$model)
            ->label('Weekly Satisfaction (Percent)')
            ->useCount()
            ->noRanges()
            ->select('1.0 * sum(case when issues.resolution_date is not null then 1 else 0 end) / count(*)')
            ->dateResult('labels.name')
            ->allDateResults($dateResults)
            ->queryWithRange(function() use ($labels) {

                return static::newModel()->newQuery()
                    ->where('labels', 'like', '%"Week%')
                    ->joinRelation('labels', function($join) use ($labels) {
                        $join->whereIn('labels.name', $labels);
                    });

            })
            ->useForValues(function($trend) {
                return array_sum($trend) / count($trend);
            })
            ->precision(2)
            ->format([
                'output' => 'percent',
                'mantissa' => 0
            ])
            ->help('This metric shows the average percent completion for issues committed to past weeks.');
    }

    /**
     * Creates and returns a new issue workload partition.
     *
     * @param  mixed  $reference
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getIssueWorkloadPartition()
    {
        return new \App\Nova\Metrics\IssueWorkloadPartition;
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [
            (new \App\Nova\Filters\JiraUserFilter)->useAssignee(),
            new \App\Nova\Filters\StatusIssueFilter,
            $this->newFixVersionFilter()
        ];
    }

    public function newFixVersionFilter()
    {
        return (new \App\Nova\Filters\InlineTextFilter)->label('Fix Version')->handle(function($query, $value) {

            // Explode the values
            $values = explode(',', $value);

            // Wrap everything within a nested "where" clause
            $query->where(function($query) use ($values) {

                // Use a "like" clause for each value
                foreach($values as $value) {
                    $query->orWhere('fix_versions', 'like', "%{$value}%");
                }

            });

        });
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [
            \App\Nova\Lenses\FilterLens::make($this, 'Backlog')->scope(function($query) { $query->hasLabel('Backlog')->assigned()->incomplete(); })->addScopedCards([
                (new \App\Nova\Metrics\IssueWorkloadPartition)->groupByAssignee(),
                (new \App\Nova\Metrics\IssueCountPartition)->groupByAssignee(),
                static::getIssueStatusPartition(),
            ]),

            /**
             * This is a temporary lens and must eventually be removed!
             */
            (new \App\Nova\Lenses\IssueSingleEpicPrioritiesLens)->label('CASL Priorities')->epic('UAS-8950'),

            \App\Nova\Lenses\FilterLens::make($this, 'Defects')->scope(function($query) { $query->defects()->incomplete(); })->addScopedCards([
                (new \App\Nova\Metrics\IssueWorkloadPartition)->groupByAssignee(),
                (new \App\Nova\Metrics\IssueCountPartition)->groupByAssignee(),
                static::getIssueStatusPartition(),
            ]),

            \App\Nova\Lenses\FilterLens::make($this, 'Delinquencies')->scope(function($query) { $query->delinquent(); })->addScopedCards([
                static::getIssueDeliquenciesByDueDateTrend(),
                (new \App\Nova\Metrics\IssueCountPartition)->groupByAssignee(),
                static::getIssueStatusPartition(),
            ]),

            \App\Nova\Lenses\FilterLens::make($this, 'Estimated Delinquencies')->scope(function($query) { $query->willBeDelinquent(); })->addScopedCards([
                (new \App\Nova\Metrics\IssueWorkloadPartition)->groupByAssignee(),
                (new \App\Nova\Metrics\IssueCountPartition)->groupByAssignee(),
                static::getIssueStatusPartition(),
            ]),

            /**
             * This is a temporary lens and must eventually be removed!
             */
            (new \App\Nova\Lenses\Laravel55PrioritiesLens)->label('Laravel 5.5 Roadmap'),

            \App\Nova\Lenses\FilterLens::make($this, 'Stale Issues')->scope(function($query) { $query->hasLabel('Stale')->incomplete(); })->addScopedCards([
                (new \App\Nova\Metrics\IssueWorkloadPartition)->groupByAssignee(),
                (new \App\Nova\Metrics\IssueCountPartition)->groupByAssignee(),
                static::getIssueStatusPartition(),
            ]),

            \App\Nova\Lenses\FilterLens::make($this, 'Stretch Items')->scope(function($query) { $query->hasLabel('Stretch')->incomplete(); })->addScopedCards([
                (new \App\Nova\Metrics\IssueWorkloadPartition)->groupByAssignee(),
                (new \App\Nova\Metrics\IssueCountPartition)->groupByAssignee(),
                static::getIssueStatusPartition(),
            ]),

            \App\Nova\Lenses\FilterLens::make($this, 'Tech Debt')->scope(function($query) { $query->hasLabel('Tech-Debt')->incomplete(); })->addScopedCards([
                (new \App\Nova\Metrics\IssueWorkloadPartition)->groupByAssignee(),
                (new \App\Nova\Metrics\IssueCountPartition)->groupByAssignee(),
                static::getIssueStatusPartition(),
            ]),

            \App\Nova\Lenses\FilterLens::make($this, 'Unassigned')->scope(function($query) { $query->unassigned(); })->addScopedCards([
                (new \App\Nova\Metrics\IssueWorkloadPartition)->groupByAssignee(),
                (new \App\Nova\Metrics\IssueCountPartition)->groupByAssignee(),
                static::getIssueStatusPartition(),
            ]),

            \App\Nova\Lenses\FilterLens::make($this, 'Weekly Commitments')->scope(function($query) { $query->hasLabelLike('Week%')->incomplete(); })->addScopedCards([
                (new \App\Nova\Metrics\IssueWorkloadPartition)->groupByAssignee(),
                (new \App\Nova\Metrics\IssueCountPartition)->groupByAssignee(),
                static::getIssueStatusPartition(),
            ]),
        ];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [
            // new \App\Nova\Actions\SyncIssueFromJira
        ];
    }
}
