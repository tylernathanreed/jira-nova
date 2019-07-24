<?php

namespace App\Providers;

use Jira;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
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

        $config->setJiraUser(env('_JIRA_USER'));
        $config->setJiraPassword(env('_JIRA_PASS'));
    }
}
