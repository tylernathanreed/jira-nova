<?php

namespace JiraAgileRestApi;

use Illuminate\Support\ServiceProvider;
use JiraAgileRestApi\Configuration\ConfigurationInterface;
use JiraAgileRestApi\Configuration\DotEnvConfiguration;

class JiraAgileRestApiServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
    }

    /**
     * Register bindings in the container.
     */
    public function register()
    {
        $this->app->bind(ConfigurationInterface::class, function () {
            return new DotEnvConfiguration(base_path());
        });
    }
}
