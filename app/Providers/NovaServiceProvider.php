<?php

namespace App\Providers;

use Laravel\Nova\Nova;
use Laravel\Nova\Cards\Help;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\NovaApplicationServiceProvider;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Call the parent method
        parent::boot();

        // Alias the login controller
        $this->aliasLoginController();
    }

    /**
     * Aliases the login controller to use a custom one.
     *
     * @return void
     */
    protected function aliasLoginController()
    {
        $this->app->alias(
            \App\Http\Controllers\Nova\LoginController::class,
            \Laravel\Nova\Http\Controllers\LoginController::class
        );
    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes()
    {
        Nova::routes()
                ->withAuthenticationRoutes()
                ->withPasswordResetRoutes()
                ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewNova', function ($user) {
            return in_array($user->email, [
                //
            ]);
        });
    }

    /**
     * Get the cards that should be displayed on the Nova dashboard.
     *
     * @return array
     */
    protected function cards()
    {
        return [
            (new \App\Nova\Metrics\IssueTicketCreatedByDateValue),
            (new \App\Nova\Metrics\IssueCreatedByDate)->width('2/3'),
            (new \App\Nova\Metrics\IssueWeekStatus)->label('Last Week')->reference('-1 week'),
            (new \App\Nova\Metrics\IssueWeekStatus)->label('This Week'),
            (new \App\Nova\Metrics\IssueWeekStatus)->label('Next Week')->reference('+1 week'),
            (new \App\Nova\Metrics\IssueDelinquentByDiff)->width('2/3'),
            new \App\Nova\Metrics\IssueWorkloadByFocus,
            new \App\Nova\Metrics\IssueWorkloadByEpic,
        ];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools()
    {
        return [
            new \NovaComponents\JiraIssueManager\JiraIssueManager,
            new \MadWeb\NovaTelescopeLink\TelescopeLink('Telescope', 'blank')
        ];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }
}
