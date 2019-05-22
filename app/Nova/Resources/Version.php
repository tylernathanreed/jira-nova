<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;

class Version extends Resource
{
    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Issues';

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Version::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'display_name';

    /**
     * Indicates if the resource should be displayed in the sidebar.
     *
     * @var bool
     */
    public static $displayInNavigation = false;

    /**
     * Indicates if the resoruce should be globally searchable.
     *
     * @var bool
     */
    public static $globallySearchable = false;

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
            Field::id()->sortable(),

            Field::belongsTo('Project', 'project'),

            Field::text('Jira ID', 'jira_id')->hideFromIndex(),

            Field::text('Display Name', 'display_name'),

            Field::date('Start Date', 'start_date'),

            Field::date('Release Date', 'release_date'),

            Field::boolean('Archived', 'archived')->hideFromIndex(),

            Field::boolean('Released', 'released')->hideFromIndex(),

            Field::boolean('Overdue', 'overdue')->hideFromIndex(),

            Field::text('Status', function() {

                return $this->archived
                    ? 'Archived'
                    : (
                        $this->released
                            ? 'Released'
                            : 'Overdue'
                    );

            })->onlyOnIndex()

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
        return [];
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
        return [
            // new \App\Nova\Actions\UpdateFromJira
        ];
    }
}
