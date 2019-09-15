<?php

namespace App\Providers;

use Jira;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Query\Builder as Query;
use Illuminate\Database\Eloquent\Builder as Eloquent;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerLaravelTelescope();

        View::composer(
            'nova::resources.navigation', \App\Http\View\Composers\GroupIconsComposer::class
        );

        $this->registerQueryMacros();
        $this->registerEloquentMacros();
    }

    /**
     * Registers laravel telescope.
     *
     * @return void
     */
    protected function registerLaravelTelescope()
    {
        // Make sure telescope is enabled
        if(!$this->app->config->get('telescope.enabled')) {
            return;
        }

        // Register the laravel service provider
        $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);

        // Register the application service provider
        $this->app->register(\App\Providers\TelescopeServiceProvider::class);
    }

    /**
     * Registers the macros for the query builder.
     *
     * @return void
     */
    protected function registerQueryMacros()
    {
        Query::macro('toRealSql', function() {

            return Str::replaceArray('?', array_map(function($binding) {
                return is_numeric($binding) ? $binding : "'{$binding}'";
            }, $this->getBindings()), $this->toSql());

        });
    }

    /**
     * Registers the macros for the eloquent builder.
     *
     * @return void
     */
    protected function registerEloquentMacros()
    {
        $passthrough = ['toRealSql'];

        foreach($passthrough as $method) {
            Eloquent::macro($method, function(...$parameters) use ($method) { return $this->getQuery()->{$method}(...$parameters); });
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Log into jira when running in console
        if($this->app->runningInConsole()) {
            $this->loginToJira();
        }
    }

    /**
     * Logs into jira.
     *
     * @return void
     */
    protected function loginToJira()
    {
        $config = Jira::getConfiguration();

        $config->setJiraUser(config('jira.cli.username'));
        $config->setJiraPassword(config('jira.cli.password'));
    }
}
