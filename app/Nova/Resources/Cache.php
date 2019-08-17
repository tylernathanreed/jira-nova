<?php

namespace App\Nova\Resources;

use Nova;
use Field;
use Laravel\Nova\Panel;
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

            Field::text('Status', 'status')->exceptOnForms(),
            Field::text('Progress', function() {

                return $this->update_record_total ? number_format($this->update_record_count / $this->update_record_total * 100, 2) . '%' : (
                    $this->build_record_total ? number_format($this->build_record_count / $this->build_record_total * 100, 2) . '%' : null
                );

            })->onlyOnIndex(),

            new Panel('Build Details', [
                Field::dateTime('Started At', 'build_started_at')->onlyOnDetail(),
                Field::dateTime('Completed At', 'build_completed_at')->onlyOnDetail(),
                Field::number('Record Count', 'build_record_count')->onlyOnDetail(),
                Field::number('Record Total', 'build_record_total')->onlyOnDetail(),
                Field::text('Progress', function() {
                    return $this->build_record_total ? number_format($this->build_record_count / $this->build_record_total * 100, 2) . '%' : null;
                })->onlyOnDetail(),
            ]),

            new Panel('Update Details', [
                Field::dateTime('Started At', 'update_started_at')->onlyOnDetail(),
                Field::dateTime('Completed At', 'update_completed_at')->onlyOnDetail(),
                Field::number('Record Count', 'update_record_count')->onlyOnDetail(),
                Field::number('Record Total', 'update_record_total')->onlyOnDetail(),
                Field::text('Progress', function() {
                    return $this->update_record_total ? number_format($this->update_record_count / $this->update_record_total * 100, 2) . '%' : null;
                })->onlyOnDetail(),
                // Field::number('Updates Since Build', 'updates_since_build')->exceptOnForms(),
            ]),

            Field::dateTime('Created At', 'created_at')->onlyOnDetail(),
            Field::dateTime('Updated At', 'updated_at')->onlyOnDetail(),

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
