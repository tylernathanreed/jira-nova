<?php

namespace App\Support\Jira;

use App\Support\Jira\Api\ApiManager;
use Illuminate\Support\ServiceProvider;
use App\Support\Jira\Auth\JiraUserProvider;
use App\Support\Jira\Config\SharedConfiguration;
use JiraRestApi\Configuration\ConfigurationInterface;

class JiraServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // $this->registerJiraClient();
        $this->registerJiraApiManager();
        $this->registerJiraService();
        // $this->registerJiraConnection();
        $this->registerJiraAuthProvider();
    }

    /**
     * Registers the Jira service.
     *
     * @return void
     */
    protected function registerJiraApiManager()
    {
        $this->app->singleton(ApiManager::class);
    }

    /**
     * Registers the Jira service.
     *
     * @return void
     */
    protected function registerJiraClient()
    {
        $this->app->singleton(JiraClient::class, function($app) {
            return new JiraClient($app, $app->make(SharedConfiguration::class));
        });
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
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
