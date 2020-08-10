<?php

namespace App\Nova\Lenses;

use App\Nova\Resources\Issue;
use DB;
use Field;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\LensRequest;

class HealthDebtLens extends Lens
{
    /**
     * The displayable name of the lens.
     *
     * @var string
     */
    public $name = 'Health Debt';

    /**
     * Returns the query builder / paginator for the lens.
     *
     * @param  \Laravel\Nova\Http\Requests\LensRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder    $query
     *
     * @return mixed
     */
    public static function query(LensRequest $request, $query)
    {
        (static::scope(['*']))($query);

        // Check for default ordering
        if(!$request->orderBy || !$request->orderByDirection) {

            // Apply default ordering
            $query->orderBy('due_date', 'asc');
            $query->orderBy(DB::raw("case when priority_name = 'High' then 1 when priority_name = 'Medium' then 2 else 3 end"), 'asc');
            $query->orderBy('assignee_name', 'asc');
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

            Field::avatar('P')->thumbnail(function() {
                return $this->priority_icon_url;
            })->maxWidth(16),

            Field::badgeUrl('Key', 'key')
                ->linkUsing(function($value, $resource) {
                    return $resource->getExternalUrl();
                })
                ->foreground('var(--primary)')
                ->style([
                    'fontFamily' => '\'Segoe UI\'',
                    'fontSize' => '14px',
                    'fontWeight' => '400',
                ]),

            Field::text('Summary', 'summary'),

            Field::badgeUrl('Status', 'relative_status_name')
                ->backgroundUsing(function($value, $resource) {
                    return [
                        'Not Started' => '#dfe1e6',
                        'Not Prioritized' => '#998dd9',
                        'Stuck' => '#ffc400',
                        'At Risk' => '#ff8252',
                        'Past Due' => '#ff5252',
                        'On Target' => '#00875a'
                    ][$value];
                })
                ->foregroundUsing(function($value, $resource) {
                    return [
                        'Not Started' => '#172B4D',
                        'Not Prioritized' => '#fff',
                        'Stuck' => '#172B4D',
                        'At Risk' => '#fff',
                        'Past Due' => '#fff',
                        'On Target' => '#fff'
                    ][$value];
                })
                ->style([
                    'fontFamily' => '\'Segoe UI\'',
                    'fontSize' => '12px',
                    'fontWeight' => '600',
                    'borderRadius' => '3px',
                    'textTransform' => 'uppercase',
                    'marginTop' => '0.25rem'
                ]),

            Field::avatar('A')->thumbnail(function() {
                return $this->assignee_icon_url;
            })->maxWidth(16),

            Field::text('Assignee', 'assignee_name'),

            Field::date('Due', 'due_date')->format('M/D'),

            Field::text('Week', 'week_date')->displayUsing(function($value) {
                return is_null($value) ? $value : carbon($value)->format('n/j');
            }),

            Field::date('Estimate', 'estimate_date')->format('M/D'),

            Field::text('Lead', 'lead_days')->displayUsing(function($value) {
                return is_null($value) ? null : ('<span class="text-' . ($value > 0 ? 'success' : 'danger') . '">' . $value . '</span>');
            })->asHtml(),

            Field::number('Remaining', 'estimate_remaining')->displayUsing(function($value) {
                return number_format($value / 3600, 2);
            })
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
        return [];
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
        return [
            (new \App\Nova\Metrics\FluentPartition)
                ->model(\App\Models\Issue::class)
                ->label('Issues by Status')
                ->useCount()
                ->groupBy('relative_status_name')
                ->resultClass(\App\Nova\Metrics\Results\RelativeStatusPartitionResult::class)
                ->help('This metric shows the total number of issues in each status group.')
                ->scope(static::scope(['relative_status' => true])),

            (new \App\Nova\Metrics\FluentPartition)
                ->model(\App\Models\Issue::class)
                ->label('Remaining Workload')
                ->sumOf('estimate_remaining')
                ->divideBy(3600)
                ->groupBy('assignee_name')
                ->resultClass(\App\Nova\Metrics\Results\UserPartitionResult::class)
                ->help('This metric shows the aggregate remaining hours for the top Assignees.')
                ->scope(static::scope()),

            (new \App\Nova\Metrics\FluentPartition)
                ->model(\App\Models\Issue::class)
                ->label('Threats by Assignee')
                ->useCount()
                ->groupBy('assignee_name')
                ->resultClass(\App\Nova\Metrics\Results\UserPartitionResult::class)
                ->help('This metric shows the number of issues in threatening statuses per Assignee.')
                ->scope(static::scope(['relative_status' => true]))
                ->whereIn('relative_status_name', ['Stuck', 'At Risk', 'Past Due']),
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
     * @param  array  $options
     *
     * @return \Closure
     */
    public static function scope($options = [])
    {
        return function($query) use ($options) {

            // Select the base stuff
            $query->select('issues.*');

            // Check for week dates
            if(($options['week_dates'] ?? false) || ($options['lead_days'] ?? false) || ($options['relative_status'] ?? false) || $options == ['*']) {

                // Join into issue week dates
                $query->leftJoin('vw_issue_week_dates', function($join) {
                    $join->on('vw_issue_week_dates.issue_id', '=', 'issues.id');
                });

            }

            // Check for lead days
            if(($options['lead_days'] ?? false) || $options == ['*']) {

                // Add the lead time
                $query->addSelect(DB::raw("
                    case
                        when estimate_date is null
                            then null
                        when due_date is null and week_date is null
                            then null
                        when due_date is null or week_date is null
                            then datediff(ifnull(due_date, week_date), estimate_date)
                        else datediff(case when due_date < week_date then due_date else week_date end, estimate_date)
                    end as lead_days
                "));

                // Flag query for sub-select
                $useSubSelect = true;

            }

            // Check for relative status
            if(($options['relative_status'] ?? false) || $options == ['*']) {

                // Add the relative status
                $query->addSelect(DB::raw("
                    case
                        when estimate_date > week_date
                            then 'At Risk'
                        when estimate_date > due_date
                            then 'At Risk'
                        when due_date < '" . carbon()->toDateString() . "'
                            then 'Past Due'
                        when status_name = 'Can''t Test'
                            then 'Stuck'
                        when assignee_name is null
                            then 'Not Started'
                        when estimate_date is null
                            then 'Not Prioritized'
                        when estimate_date < '" . carbon()->toDateString() . "'
                            then 'Not Prioritized'
                        when status_name in ('New', 'Assigned')
                            then 'Not Started'
                        else 'On Target'
                    end as relative_status_name
                "));

                // Flag query for sub-select
                $useSubSelect = true;

            }

            // Apply the scope
            $query->incomplete()->hasLabel('Health-Debt');

            // Check for any case statements
            if($useSubSelect ?? false) {

                // Flip the query into its own subquery
                $query->fromSub($query, 'issues');

            }

        };
    }
}
