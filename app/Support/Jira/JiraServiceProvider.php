<?php

namespace App\Support\Jira;

use Illuminate\Support\ServiceProvider;
use App\Support\Jira\Auth\JiraUserProvider;

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
        $this->registerJiraAuthProvider();
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

    protected function registerJiraAuthProvider()
    {
        $this->app->auth->provider('jira', function($app, $config) {
            return new JiraUserProvider($this->app->make(JiraService::class), $this->app['hash'], $config['model']);
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
