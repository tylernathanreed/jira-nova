<?php

namespace App\Providers;

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
        // Register the field macros
        $this->registerFieldMacros($fields);
    }

    /**
     * Registers the field macros.
     *
     * @param  Reedware\NovaFieldManager\NovaFieldManager  $fields
     *
     * @return void
     */
    protected function registerFieldMacros(NovaFieldManager $fields)
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
    }
}
