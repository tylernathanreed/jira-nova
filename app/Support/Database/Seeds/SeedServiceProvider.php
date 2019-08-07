<?php

namespace App\Support\Database\Seeds;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

class SeedServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var boolean
     */
    protected $defer = true;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerGenerateSeedCommand();
    }

    /**
     * Registers the generate seed command.
     *
     * @return void
     */
    protected function registerGenerateSeedCommand()
    {
        $this->app->singleton('command.seed.generate', function ($app) {
            return new DatabaseSeedGenerateCommand($app['db'], $app['files']);
        });

        $this->commands([
            'command.seed.generate'
        ]);
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

    /**
     * Returns the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'command.seed.generate'
        ];
    }
}
