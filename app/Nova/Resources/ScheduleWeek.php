<?php

namespace App\Nova\Resources;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Datetime;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\BelongsTo;

class ScheduleWeek extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\ScheduleWeek::class;

    /**
     * Indicates if the resoruce should be globally searchable.
     *
     * @var bool
     */
    public static $globallySearchable = false;

    /**
     * Returns the value that should be displayed to represent the resource.
     *
     * @return string
     */
    public function title()
    {
        return $this->schedule->display_name . ': Week ' . $this->week_number;
    }

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
            ID::make()
                ->sortable()
                ->onlyOnDetail(),

            BelongsTo::make('Schedule', 'schedule', Schedule::class)
                ->rules('required'),

            Number::make('Week Number', 'week_number')
                ->rules('required')
                ->min(1)
                ->step(1),
                // ->creationRules('unique:schedule_weeks,week_number,NULL,id,schedule_id,{{schedule_id}}')
                // ->updateRules('unique:schedule_weeks,week_number,{{resourceId}},id,schedule_id,{{schedule_id}}'),

            BelongsTo::make('Template', 'template', ScheduleWeekTemplate::class)
                ->rules('required'),

            Text::make('Template System Name', 'week_template_system_name')
                ->onlyOnDetail(),

            Date::make('Start Date', 'start_date'),

            Date::make('Due Date', 'due_date'),

            Select::make('Allocation Type', 'allocation_type')
                ->rules('required')
                ->options([
                    'weekly' => 'Weekly',
                    'daily' => 'Daily'
                ]),

            Datetime::make('Created At', 'created_at')->onlyOnDetail(),

            Datetime::make('Updated At', 'updated_at')->onlyOnDetail()

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
