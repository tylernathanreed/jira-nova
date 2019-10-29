<?php

namespace App\Nova\Lenses;

use Field;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\LensRequest;
use App\Models\WorkflowStatusGroup as WorkflowStatusGroupModel;

class VersionReleaseNotesLens extends Lens
{
    /**
     * The displayable name of the lens.
     *
     * @var string
     */
    public $name = 'Release Notes';

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
        // Make sure the version has been released
        $query->whereNotNull('versions.release_date');

        // Only look at versions released in the past year
        $query->where('versions.release_date', '>=', carbon()->subYear());

        // Join into issues
        $query->joinRelation('issues', function($join) {

            // Make sure the issue has release notes
            $join->whereNotNull('issues.release_notes');

        });

        // Join into status groups
        $query->joinThroughRelation('issues.status')->joinThroughRelation('issues.status.group', function($join) {
            $join->whereNull('workflow_status_types.scope_id');
        });

        // Select the relevant columns
        $query->select([
            'issues.type_icon_url',
            'issues.key',
            'issues.release_notes',
            'issues.status_name',
            'issues.status_color',
            'workflow_status_groups.display_name as status_group_name',
            'workflow_status_groups.color as status_group_color',
            'versions.release_date',
            'versions.name as fix_version'
        ]);

        // Check for default ordering
        if(!$request->orderBy || !$request->orderByDirection) {

            // Sort by release date
            $query->orderBy('versions.release_date', 'desc');

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

            Field::text('Release Notes', 'release_notes', function() {
                return strlen($this->release_notes) > 150 ? substr($this->release_notes, 0, 150) . '...' : $this->release_notes;
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

            Field::text('Fix Version', 'fix_version'),

            Field::date('Release Date', 'release_date')->format('M/D')->sortable()

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
            (new \App\Nova\Filters\InlineTextFilter)->label('Fix Version')->handle(function($query, $value) {
                $query->where('versions.name', 'like', "{$value}%");
            })
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
        return [];
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
}
