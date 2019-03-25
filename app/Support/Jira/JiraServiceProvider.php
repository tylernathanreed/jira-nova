<?php

namespace App\Support\Jira;

use Illuminate\Support\ServiceProvider;

class JiraServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerJiraService();
    }

    /**
     * Registers the Jira service.
     *
     * @return void
     */
    protected function registerJiraService()
    {
        $this->app->singleton(JiraService::class, function($app) {
            return new JiraService;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
