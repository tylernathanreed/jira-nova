<?php

namespace App\Providers;

use Closure;
use Carbon\Carbon;
use Laravel\Nova\Fields\Field;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Validation\Rule;
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

        /**
         * Fills this field normally, but also fills additional content using the specified callback.
         *
         * @param  \Closure  $callback
         *
         * @return $this
         */
        Field::macro('fillExtraUsing', function(Closure $callback) {

            // Override the fill callback
            return $this->fillUsing(function($request, $model, $attribute, $requestAttribute) use ($callback) {

                // Fill the original attribute from the request
                $response = $this->fillAttributeFromRequest($request, $requestAttribute, $model, $attribute);

                // Invoke the callback
                $callback($request, $model, $attribute, $requestAttribute);

                // Return the response
                return $response;

            });

        });

        /**
         * Adds the specified rules to the field's validation rules.
         *
         * @param  callable|array|string  $rules
         *
         * @return $this
         */
        Field::macro('addRules', function($rules) {

            // Determine the current rules
            $currentRules = $this->rules;

            // Normalize the new rules
            $newRules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

            // Merge the rules
            $this->rules = array_merge($currentRules, $newRules);

            // Allow chaining
            return $this;

        });

        /**
         * Validates that the relation field is a sibling of another field within the request.
         *
         * @param  string       $sibling
         * @param  string       $requestAttribute
         * @param  string       $siblingAttribute
         * @param  string|null  $localAttribute
         *
         * @return $this
         */
        Field::macro('siblingTo', function($sibling, $requestAttribute, $siblingAttribute, $localAttribute = null) {

            // If a local attribute wasn't provided, use the sibling attribute
            if(is_null($localAttribute)) {
                $localAttribute = $siblingAttribute;
            }

            // Determine the request
            $request = request();

            // When the user is providing values for both this field and the
            // sibling field, the underlying parent must be the same. If
            // they are different, then the siblings aren't siblings.

            // Make sure the request attribute has been provided
            if(!$request->has($requestAttribute)) {
                return $this;
            }

            // Since validation has not occurred yet, the referenced sibling
            // may not actually exist. If any assumptions fail, we won't
            // the additional rule here, as a prequisite rule failed.

            // Create a new sibling query
            $siblingQuery = $sibling::newModel()->newQuery();

            // Respect soft deletes
            if($request->input("{$requestAttribute}_trashed")) {
                $siblingQuery->withTrashed();
            }

            // Determine the expected parent key
            $expectedParentKey = $siblingQuery->whereKey($request->input($requestAttribute))->value($siblingAttribute);

            // Make sure an expected parent key was found
            if(is_null($expectedParentKey)) {
                return $this;
            }

            // At this point, we know the expected parent key, but we have not
            // yet figured out the actual parent key. This requires knowing
            // the possible list of siblings using the expected parent.

            // Determine the key name of the resource
            $keyName = ($this->resourceClass)::newModel()->getKeyName();

            // Create a new resource query
            $resourceQuery = ($this->resourceClass)::newModel()->newQuery();

            // Respect soft deletes
            if($request->input("{$this->attribute}_trashed")) {
                $resourceQuery->withTrashed();
            }

            // Determine the allowed keys
            $allowedKeys = $resourceQuery->where($localAttribute, $expectedParentKey)->pluck($keyName);

            // Require the selected resource key to be in the list of allowed keys
            $this->addRules(['in: ' . $allowedKeys->implode(',')]);

            // Allow chaining
            return $this;

        });

    }
}
