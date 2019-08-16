<?php

namespace App\Nova\Resources;

use Nova;
use Field;
use Illuminate\Http\Request;
use App\Support\Contracts\Cacheable;

class Cache extends Resource
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
    public static $model = \App\Models\Cache::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'model_class';

    /**
     * Indicates if the resoruce should be globally searchable.
     *
     * @var bool
     */
    public static $globallySearchable = false;

    /**
     * Indicates if the resource should be displayed in the sidebar.
     *
     * @var bool
     */
    public static $displayInNavigation = true;

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        $resources = array_filter(Nova::$resources, function($r) {
            return $r::$model == static::$model ? false : $r::newModel() instanceof Cacheable;
        });

        $models = array_combine(
            array_map(function($r) {
                return $r::$model;
            }, $resources),
            array_map(function($r) {
                return $r::label();
            }, $resources)
        );

        return [

            Field::id()->onlyOnDetail(),

            Field::select('Model', 'model_class', function() {
                return Nova::resourceForModel($this->model_class)::label();
            })->options($models),

            Field::dateTime('Updated', 'updated_at')->exceptOnForms(),

            Field::dateTime('Built', 'built_at')->exceptOnForms()

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
