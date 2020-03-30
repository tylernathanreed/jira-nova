<?php

namespace App\Providers;

use Carbon\Carbon;
use Laravel\Nova\Fields\Field;
use Illuminate\Support\ServiceProvider;
use Reedware\NovaFieldManager\NovaFieldManager;

class FieldServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @param  \Reedware\NovaFieldManager\NovaFieldManager  $fields
     *
     * @return void
     */
    public function boot(NovaFieldManager $fields)
    {
        // Register the field manager macros
        $this->registerFieldManagerMacros($fields);

        // Register the field instance macros
        $this->registerFieldInstanceMacros();
    }

    /**
     * Registers the field manager macros.
     *
     * @param  Reedware\NovaFieldManager\NovaFieldManager  $fields
     *
     * @return void
     */
    protected function registerFieldManagerMacros(NovaFieldManager $fields)
    {
        /**
         * Creates and returns a new display name field.
         *
         * @return \Laravel\Nova\Fields\Text
         */
        $fields->macro('displayName', function() use ($fields) {

            return $fields->text('Display Name', 'display_name')
                ->sortable()
                ->rules('required', 'string', 'max:50');

        });

        /**
         * Creates and returns a new allocation field.
         *
         * @return \Laravel\Nova\Fields\Text
         */
        $fields->macro('allocation', function($label, $attribute = null) use ($fields) {

            return $fields->number($label, $attribute)
                ->min(0)
                ->max(86400)
                ->step(1)
                ->rules('required', 'min:0', 'max:86400')
                ->displayUsing(function($value) {
                    return number_format($value / 3600, 2);
                })
                ->help(
                    'This value is displayed in hours on other screens, but updated here using seconds.'
                );

        });

        /**
         * Creates and returns a new icon url field.
         *
         * @return \Laravel\Nova\Fields\Avatar
         */
        $fields->macro('iconUrl', function($label, $url) use ($fields) {

            return $fields->avatar($label, $url)->thumbnail(function($value) {
                return $value;
            })->preview(function($value) {
                return $value;
            })->disableDownload();

        });

        /**
         * Creates and returns a new percent field.
         *
         * @return \Laravel\Nova\Fields\Avatar
         */
        $fields->macro('percent', function($label, $attribute) use ($fields) {

            return $fields->number($label, $attribute)
                ->min(1)
                ->step(1)
                ->max(100)
                ->displayUsing(function($value) {
                    return ($value * 100) . '%';
                })
                ->resolveUsing(function($value) {
                    return $value * 100;
                })
                ->fillUsing(function($request, $model, $attribute, $requestAttribute) {
                    return $model->setAttribute($attribute, $request->{$requestAttribute} / 100);
                });

        });

        /**
         * Overrides the "Date" field with a default format.
         *
         * @link https://momentjs.com/docs/#/parsing/string-format/ for {@see $date->format()}.
         * @link https://flatpickr.js.org/formatting/ for {@see $date->pickerFormat()}.
         *
         * @return \Laravel\Nova\Fields\Date
         */
        $fields->macro('date', function($name, $attribute = null, callable $resolveCallback = null) use ($fields) {

            return $fields->dateField($name, $attribute, $resolveCallback)
                ->format('MMM Do, YYYY')
                ->pickerFormat('Y-m-d');

        });

        /**
         * Overrides the "Time" field to use twelve-hour format and 15 minute increment.
         *
         * @return \Laraning\NovaTimeField\TimeField
         */
        $fields->macro('time', function($name, $attribute = null, callable $resolveCallback = null) use ($fields) {

            // Create the field
            $field = $fields->timeField($name, $attribute, $resolveCallback);

            // Enforce a twelve-hour format
            $field->withTwelveHourTime();

            // Enforce a 15 minute increment
            if(method_exists($field, 'minuteIncrement')) {
                $field->minuteIncrement(15);
            }

            // Return the field
            return $field;

        });
    }

    /**
     * Registers the field instance macros.
     *
     * @return void
     */
    protected function registerFieldInstanceMacros()
    {
        /**
         * Fills this field after the underlying resource has been created.
         *
         * @return $this
         */
        Field::macro('fillAfterCreate', function() {
            return $this->fillUsing(function($request, $model, $attribute, $requestAttribute) {
                return function() use ($request, $model, $attribute, $requestAttribute) {
                    return $this->fillAttributeFromRequest($request, $requestAttribute, $model, $attribute);
                };
            });
        });
    }
}
