<?php

namespace App\Nova\Lenses;

use Field;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\LensRequest;

class IssueSingleEpicPrioritiesLens extends Lens
{
    /**
     * The displayable name of the lens.
     *
     * @var string
     */
    public $name = 'Priorities';

    /**
     * The epic to filter the issues to.
     *
     * @var string
     */
    public $epic;

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
        $scope = static::scope($request->lens()->epic);

        // Apply the scope
        $scope($query);

        // Order by estimate, then by rank
        $query->orderBy('estimate_date', 'asc');
        $query->orderBy('rank', 'asc');

        // Return the query
        return $request->withFilters(
            $query
        );
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

            Field::text('Key', 'key'),

            Field::text('Summary', 'summary', function() {
                return strlen($this->summary) > 100 ? substr($this->summary, 0, 100) . '...' : $this->summary;
            }),

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
            ]),

            Field::avatar('A')->thumbnail(function() {
                return $this->assignee_icon_url;
            })->maxWidth(16),

            Field::avatar('R')->thumbnail(function() {
                return $this->reporter_icon_url;
            })->maxWidth(16),

            Field::date('Due', 'due_date')->format('M/D'),

            Field::date('Estimate', 'estimate_date')->format('M/D'),

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
            new \App\Nova\Filters\StatusIssueFilter
        ];
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
        $scope = static::scope($this->epic);

        // Return the cards
        return [
            (new \App\Nova\Metrics\IssueWeekStatusPartition)->setName('Last Week')->reference('-1 week')->filter($scope),
            (new \App\Nova\Metrics\IssueWeekStatusPartition)->setName('This Week')->filter($scope),
            (new \App\Nova\Metrics\IssueWeekStatusPartition)->setName('Next Week')->reference('+1 week')->filter($scope),
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
     * Sets the epic for this lens.
     *
     * @param  string  $epic
     *
     * @return $this
     */
    public function epic($epic)
    {
        $this->epic = $epic;

        return $this;
    }

    /**
     * Returns the scope of this lens.
     *
     * @return \Closure
     */
    public static function scope($epic)
    {
        return function($query) use ($epic) {

            // Only look at incomplete issues
            $query->incomplete();

            // Require an assignee
            $query->assigned();

            // Filter to epic
            $query->where('epic_key', '=', $epic);

        };
    }
}
