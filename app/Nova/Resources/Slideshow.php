<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;

class Slideshow extends Resource
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
    public static $model = \App\Models\Slideshow::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'display_name';

    /**
     * The relationship counts that should be eager loaded when performing an index query.
     *
     * @var array
     */
    public static $withCount = [
        'pages'
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

            Field::text('Display Name', 'display_name')->sortable(),

            Field::text('System Name', 'system_name')->sortable(),

            Field::select('Theme', 'theme')->options([
                'default' => 'Default',
                'dark' => 'Dark'
            ]),

            Field::textarea('Description', 'description')->hideFromIndex(),

            Field::number('Total Pages', 'pages_count')->onlyOnIndex()->sortable(),

            Field::hasMany('Pages', 'pages', SlideshowPage::class)

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
