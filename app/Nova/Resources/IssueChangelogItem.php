<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;

class IssueChangelogItem extends Resource
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
    public static $model = \App\Models\IssueChangelogItem::class;

    /**
     * The number of resources to show per page via relationships.
     *
     * @var int
     */
    public static $perPageViaRelationship = 10;

    /**
     * Indicates if the resource should be displayed in the sidebar.
     *
     * @var bool
     */
    public static $displayInNavigation = false;

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'item_field_name'
    ];

    /**
     * The default ordering to use when listing this resource.
     *
     * @var array
     */
    public static $defaultOrderings = [
        'issue_changelog_id' => 'asc',
        'item_index' => 'asc'
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

            Field::belongsTo('Changelog', 'changelog', IssueChangelog::class)->exceptOnForms()->sortable(),

            Field::number('Item Index', 'item_index')->exceptOnForms()->sortable(),

            Field::text('Field', 'item_field_name')->exceptOnForms()->sortable(),

            Field::text('From', 'item_from', function() {
                return strlen($this->item_from) > 80 ? substr($this->item_from, 0, 80) . '...' : $this->item_from;
            })->onlyOnIndex(),

            Field::text('From', 'item_from')->onlyOnDetail(),

            Field::text('To', 'item_to', function() {
                return strlen($this->item_to) > 80 ? substr($this->item_to, 0, 80) . '...' : $this->item_to;
            })->onlyOnIndex(),

            Field::text('To', 'item_to')->onlyOnDetail()

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
