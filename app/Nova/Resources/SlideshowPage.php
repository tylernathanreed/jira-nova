<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;

class SlideshowPage extends Resource
{
    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'System';

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\SlideshowPage::class;

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
     * The number of resources to show per page via relationships.
     *
     * @var int
     */
    public static $perPageViaRelationship = 15;

    /**
     * The default ordering to use when listing this resource.
     *
     * @var array
     */
    public static $defaultOrderings = [
        'order' => 'asc'
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        if($request->is_slideshow) {

            return [
                Field::text('URL', function() {
                    return config('app.url') . config('nova.path') . '/' . ltrim($this->uri, '/') . '?fullscreen=1';
                })
            ];

        }

        return [

            Field::id()->onlyOnDetail(),

            Field::belongsTo('Slideshow', 'slideshow', Slideshow::class),

            Field::text('Display Name', 'display_name')
                ->rules('unique:slideshow_pages,display_name,{{resourceId}},id,slideshow_id,{{slideshow_id}}'),

            Field::text('URI', 'uri')
                ->help('Exclude the "' . config('app.url') . config('nova.path') . '" portion of the URL.')
                ->hideFromIndex(),

            Field::number('Screen Time', 'screentime')
                ->min(0)
                ->help('Measured in minutes.'),

            Field::number('Order', 'order')
                ->min(0)
                ->sortable(),

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
