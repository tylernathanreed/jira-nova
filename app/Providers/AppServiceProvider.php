<?php

namespace App\Providers;

use Jira;
use Illuminate\Support\Facades\View;
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
        $this->registerLaravelTelescope();

        View::composer(
            'nova::resources.navigation', \App\Http\View\Composers\GroupIconsComposer::class
        );
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
