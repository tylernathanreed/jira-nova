<?php

namespace NovaComponents\JiraIssueManager;

use Laravel\Nova\Nova;
use Laravel\Nova\Events\ServingNova;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use NovaComponents\JiraIssueManager\Http\Middleware\Authorize;

class ToolServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'jira-issue-manager');

        $this->app->booted(function () {
            $this->routes();
        });

        Nova::serving(function(ServingNova $event) {

            Nova::provideToScript([
                'user' => $event->request->user()->toArray()
            ]);

        });
    }

    /**
     * Register the tool's routes.
     *
     * @return void
     */
    protected function routes()
    {
        // If routes are cached, skip this step
        if($this->app->routesAreCached()) {
            return;
        }

        // Register the tool routes
        Route::middleware(['nova', Authorize::class])
                ->prefix('nova-vendor/jira-issue-manager')
                ->group(__DIR__ . '/../routes/api.php');

        // Register the nova api routes
        Route::group($this->novaRouteConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/nova-api.php');
        });
    }

    /**
     * Returns the Nova route group configuration array.
     *
     * @return array
     */
    protected function novaRouteConfiguration()
    {
        return [
            'namespace' => 'Laravel\Nova\Http\Controllers',
            'domain' => config('nova.domain', null),
            'as' => 'nova.api.',
            'prefix' => 'nova-api',
            'middleware' => 'nova',
        ];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
