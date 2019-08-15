<?php

namespace App\Providers;

use Laravel\Nova\Nova;
use App\Models\FocusGroup;
use Laravel\Nova\Cards\Help;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Events\ServingNova;
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
        $this->provideNovaConfiguration();
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
     * Adds configuration settings to the Nova front-end.
     *
     * @return void
     */
    protected function provideNovaConfiguration()
    {
        Nova::serving(function(ServingNova $event) {

            Nova::provideToScript([
                'user' => $event->request->user()->toArray(),
                'schedule' => $event->request->user()->getScheduleForNova(),
                'focusGroups' => FocusGroup::all()->keyBy('system_name')->map->toNovaData()
            ]);

        });
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
            (new \App\Nova\Metrics\IssueCreatedByDateTrend)->width('2/3'),
            (new \App\Nova\Metrics\IssueWeekStatusPartition)->label('Last Week')->reference('-1 week'),
            (new \App\Nova\Metrics\IssueWeekStatusPartition)->label('This Week'),
            (new \App\Nova\Metrics\IssueWeekStatusPartition)->label('Next Week')->reference('+1 week'),
            (new \App\Nova\Metrics\IssueDelinquentByDueDateTrend),
            (new \App\Nova\Metrics\IssueDelinquentByEstimatedDateTrend),
            new \App\Nova\Metrics\IssueWeeklySatisfactionTrend,
            new \App\Nova\Metrics\IssueWorkloadByEpicPartition,
            new \App\Nova\Metrics\IssueWorkloadByFocusPartition,
            new \App\Nova\Metrics\IssueWorkloadByAssigneePartition
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
            new \NovaComponents\JiraIssuePrioritizer\JiraIssuePrioritizer,
            new \NovaComponents\CacheManager\CacheManager,
            new \MadWeb\NovaTelescopeLink\TelescopeLink('Telescope', 'blank'),
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
