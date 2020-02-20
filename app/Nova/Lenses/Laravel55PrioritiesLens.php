<?php

namespace App\Nova\Lenses;

use Field;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Nova\Resources\Issue;
use Laravel\Nova\Http\Requests\LensRequest;
use App\Models\WorkflowStatusGroup as WorkflowStatusGroupModel;

class Laravel55PrioritiesLens extends Lens
{
    /**
     * The displayable name of the lens.
     *
     * @var string
     */
    public $name = 'Laravel 5.5 Roadmap';

    /**
     * Get the query builder / paginator for the lens.
     *
     * @param  \Laravel\Nova\Http\Requests\LensRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder    $query
     *
     * @return mixed
     */
    public static function query(LensRequest $request, $query)
    {
        // Determine the scope
        $scope = static::scope();

        // Apply the scope
        $scope($query);

        // Select the relevant columns
        $query->select([
            'issues.type_icon_url',
            'issues.key',
            'issues.summary',
            'issues.status_name',
            'issues.status_color',
            'workflow_status_groups.display_name as status_group_name',
            'workflow_status_groups.color as status_group_color',
            'issues.assignee_icon_url',
            'issues.reporter_icon_url',
            'issues.due_date',
            'issues.estimate_date',
            'issues.labels',
            'issues.resolution_date',
            'issues.entry_date'
        ]);

        // Check for default ordering
        if(!$request->orderBy || !$request->orderByDirection) {

            // Order by estimate, then by rank
            $query->orderBy('estimate_date', 'asc');
            $query->orderBy('rank', 'asc');

        }

        // Return the query
        return $request->withOrdering($request->withFilters(
            $query
        ));
    }

    /**
     * Get the fields available to the lens.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [

            Field::avatar('T')->thumbnail(function() {
                return $this->type_icon_url;
            })->maxWidth(16),

            Field::text('Key', 'key')->sortable(),

            Field::text('Summary', 'summary', function() {
                return strlen($this->summary) > 100 ? substr($this->summary, 0, 100) . '...' : $this->summary;
            }),

            Field::badgeUrl('Status', 'status_group_name')->backgroundUsing(function($value, $resource) {
                return $resource->status_group_color ?? null;
            })->foregroundUsing(function($value, $resource) {
                return '#000';
            })->style([
                'fontFamily' => '\'Segoe UI\'',
                'fontSize' => '12px',
                'fontWeight' => '600',
                'borderRadius' => '3px',
                'textTransform' => 'uppercase',
                'marginTop' => '0.25rem'
            ]),

            Field::text('Week', 'labels')->displayUsing(function($value) {

                $week = Arr::first($value, function($label) {
                    return Str::startsWith($label, 'Week');
                });

                return empty($week) ? null : substr($week, 4);

            }),

            Field::avatar('A')->thumbnail(function() {
                return $this->assignee_icon_url;
            })->maxWidth(16),

            Field::avatar('R')->thumbnail(function() {
                return $this->reporter_icon_url;
            })->maxWidth(16),

            Field::date('Due', 'due_date')->format('M/D')->sortable(),

            Field::date('Estimate', 'estimate_date')->format('M/D')->sortable(),

        ];

    }

    /**
     * Returns the filters available for the lens.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function filters(Request $request)
    {
        return [
            $this->getStatusFilter()
        ];
    }

    /**
     * Creates and returns a new status filter.
     *
     * @return \Laravel\Nova\Filters\Filter
     */
    public function getStatusFilter()
    {
        // Determine the options
        $options = WorkflowStatusGroupModel::orderBy('transition_order')->pluck('display_name', 'display_name')->all();

        // Return the filter
        return new \App\Nova\Filters\FluentSelectFilter('Status', 'status_group_name', $options);
    }

    /**
     * Returns the cards available on the entity.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function cards(Request $request)
    {
        // Determine the scope
        $scope = static::scope(true);

        // Return the cards
        return [
            (new \App\Nova\Metrics\FluentPartition)
                ->model(\App\Models\Issue::class)
                ->label('Remaining Issues')
                ->joinRelation('labels', function($join) {
                    $join->whereIn('labels.name', ['Laravel5.2', 'Laravel5.3', 'Laravel5.4', 'Laravel5.5']);
                })
                ->useCount()
                ->groupBy('labels.name')
                ->scope($scope)
                ->resultLabel(function($label) {
                    return 'Laravel ' . substr($label, -3);
                }),

            (new \App\Nova\Metrics\Laravel55RoadmapGantt),

            (new \App\Nova\Metrics\FluentTrend)
                ->model(\App\Models\Issue::class)
                ->label('Upcoming Completions')
                ->countOf('issues.id')
                ->dateColumn('estimate_date')
                ->suffix('issue')
                ->futuristic()
                ->scope($scope),

            (new \App\Nova\Metrics\FluentPartition)
                ->model(\App\Models\Issue::class)
                ->label('Remaining Workload')
                ->incomplete()
                ->joinRelation('labels', function($join) {
                    $join->whereIn('labels.name', ['Laravel5.2', 'Laravel5.3', 'Laravel5.4', 'Laravel5.5']);
                })
                ->sumOf('estimate_remaining')
                ->divideBy(3600)
                ->groupBy('labels.name')
                ->scope($scope)
                ->resultLabel(function($label) {
                    return 'Laravel ' . substr($label, -3);
                }),

            (new \App\Nova\Metrics\FluentPartition)
                ->model(\App\Models\Issue::class)
                ->label('Completed Workload')
                ->complete()
                ->joinRelation('labels', function($join) {
                    $join->whereIn('labels.name', ['Laravel5.2', 'Laravel5.3', 'Laravel5.4', 'Laravel5.5']);
                })
                ->sumOf('estimate_remaining')
                ->divideBy(3600)
                ->groupBy('labels.name')
                ->scope($scope)
                ->resultLabel(function($label) {
                    return 'Laravel ' . substr($label, -3);
                }),

            (new \App\Nova\Metrics\FluentPartition)
                ->model(\App\Models\Issue::class)
                ->label('Est. Delinquencies by Assignee')
                ->willBeDelinquent()
                ->useCount()
                ->groupBy('assignee_name')
                ->scope($scope)
                ->resultClass(\App\Nova\Metrics\Results\UserPartitionResult::class),

            Issue::getIssueStatusPartition()->scope($scope)->hasLabel('Laravel5.2')->label('Laravel 5.2 Issues')->width('1/4'),
            Issue::getIssueStatusPartition()->scope($scope)->hasLabel('Laravel5.3')->label('Laravel 5.3 Issues')->width('1/4'),
            Issue::getIssueStatusPartition()->scope($scope)->hasLabel('Laravel5.4')->label('Laravel 5.4 Issues')->width('1/4'),
            Issue::getIssueStatusPartition()->scope($scope)->hasLabel('Laravel5.5')->label('Laravel 5.5 Issues')->width('1/4')

        ];
    }

    /**
     * Returns the actions available on the lens.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }

    /**
     * Returns the scope of this lens.
     *
     * @param  boolean  $withComplete
     *
     * @return \Closure
     */
    public static function scope($withComplete = false)
    {
        return function($query) use ($withComplete) {

            // Only look at incomplete issues
            if(!$withComplete) {
                $query->incomplete();
            }

            // Filter to epic
            $query->where('epic_key', '=', 'UAS-3575');

            // Join into status groups
            $query->joinRelation('status.group', function($join) {
                $join->whereNull('workflow_status_types.scope_id');
            });

        };
    }
}
