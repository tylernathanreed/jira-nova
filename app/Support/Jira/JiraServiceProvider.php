<?php

namespace App\Support\Jira;

use Illuminate\Support\ServiceProvider;
use App\Support\Jira\Auth\JiraUserProvider;
use App\Support\Jira\Config\SharedConfiguration;
use Reedware\LaravelApi\Connection as ApiConnection;
use JiraRestApi\Configuration\ConfigurationInterface;
use App\Support\Jira\Api\Connection as JiraConnection;

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
        $this->registerJiraConnection();
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
            return new JiraService($app, $app->make(SharedConfiguration::class));
        });
    }

    protected function registerJiraAuthProvider()
    {
        $this->app->auth->provider('jira', function($app, $config) {
            return new JiraUserProvider($this->app->make(JiraService::class), $this->app['hash'], $config['model']);
        });
    }

    /**
     * Registers the jira connection.
     *
     * @return void
     */
    protected function registerJiraConnection()
    {
        ApiConnection::resolverFor('jira', function($connection, $config) {
            return new JiraConnection($connection, $config);
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
