<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;

class IssueChangelog extends Resource
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
    public static $model = \App\Models\IssueChangelog::class;

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
        'issue_key'
    ];

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

            Field::belongsTo('Issue', 'issue', Issue::class)->exceptOnForms(),

            // Field::text('author_key', 'author_key'),
            Field::text('Assignee', 'author_name')->onlyOnDetail(),

            Field::avatar('A')->thumbnail(function() {
                return $this->author_icon_url;
            })->maxWidth(16)->onlyOnIndex(),

            Field::date('Created', 'created_at')->exceptOnForms()

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
        return [];
    }
}
