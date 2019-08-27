<?php

namespace App\Nova\Lenses;

use Nova;
use Field;
use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;
use App\Models\Epic as EpicModel;
use Laravel\Nova\Http\Requests\LensRequest;

class PastDueIssuesLens extends Lens
{
    /**
     * The displayable name of the lens.
     *
     * @var string
     */
    public $name = 'Delinquencies';

    /**
     * Get the query builder / paginator for the lens.
     *
     * @param  \Laravel\Nova\Http\Requests\LensRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return mixed
     */
    public static function query(LensRequest $request, $query)
    {
        $query->where('due_date', '<', carbon())->incomplete();

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
            })->maxWidth(16)->onlyOnIndex(),

            Field::avatar('P')->thumbnail(function() {
                return $this->priority_icon_url;
            })->maxWidth(16)->onlyOnIndex(),

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
            ])->exceptOnForms(),

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
            ])->exceptOnForms(),

            Field::avatar('A')->thumbnail(function() {
                return $this->assignee_icon_url;
            })->maxWidth(16)->onlyOnIndex(),

            Field::avatar('R')->thumbnail(function() {
                return $this->reporter_icon_url;
            })->maxWidth(16)->onlyOnIndex(),

            Field::date('Due', 'due_date')->sortable(),

            Field::date('Estimate', 'estimate_date')->sortable(),

            Field::number('Remaining', 'estimate_remaining')->displayUsing(function($value) {
                return number_format($value / 3600, 2);
            })->exceptOnForms()->sortable(),

            Field::text('Rank', 'rank')->exceptOnForms()->sortable()
        ];
    }

    /**
     * Get the filters available for the lens.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available on the lens.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return parent::actions($request);
    }
}
