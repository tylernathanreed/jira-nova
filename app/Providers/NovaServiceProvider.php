<?php

namespace App\Providers;

use Laravel\Nova\Nova;
use App\Models\FocusGroup;
use Illuminate\Http\Request;
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

        // Provide the configuration variables to the front-end
        $this->provideNovaConfiguration();

        // Set the timezone
        $this->setUserTimezone();
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
                'name' => Nova::name(),
                'user' => $event->request->user()->toArray(),
                'schedule' => $event->request->user()->getSchedule()->toNovaData(),
                'focusGroups' => FocusGroup::all()->keyBy('system_name')->map->toNovaData(),
                'colors' => $this->app->make('config')->get('jira.colors')
            ]);

        });
    }

    /**
     * Sets the timezone resolver for the request user.
     *
     * @return void
     */
    protected function setUserTimezone()
    {
        Nova::userTimezone(function(Request $request) {
            return config('app.timezone');
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
            (new \App\Nova\Metrics\IssueCreatedByDateValue)->where('focus', 'Ticket')->setName('Ticket Entry'),
            (new \App\Nova\Metrics\IssueCreatedByDateTrend)->width('2/3'),
            (new \App\Nova\Metrics\IssueWeekStatusPartition)->setName('Last Week')->reference('-1 week'),
            (new \App\Nova\Metrics\IssueWeekStatusPartition)->setName('This Week'),
            (new \App\Nova\Metrics\IssueWeekStatusPartition)->setName('Next Week')->reference('+1 week'),
            (new \App\Nova\Metrics\IssueDelinquentByDueDateTrend),
            (new \App\Nova\Metrics\IssueDelinquentByEstimatedDateTrend),
            new \App\Nova\Metrics\IssueWeeklySatisfactionTrend,
            (new \App\Nova\Metrics\IssueWorkloadPartition)->groupByEpic(),
            (new \App\Nova\Metrics\IssueWorkloadPartition)->groupByFocus(),
            (new \App\Nova\Metrics\IssueWorkloadPartition)->groupByAssignee()
        ];
    }

    /**
     * Returns the extra dashboards that should be displayed on the Nova dashboard.
     *
     * @return array
     */
    protected function dashboards()
    {
        return [
            new \App\Nova\Dashboards\GroomingDashboard,
            new \App\Nova\Dashboards\DevelopmentDashboard,
            new \App\Nova\Dashboards\TestingDashboard,
            new \App\Nova\Dashboards\DefectsDashboard,
            new \App\Nova\Dashboards\WorklogDashboard
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
