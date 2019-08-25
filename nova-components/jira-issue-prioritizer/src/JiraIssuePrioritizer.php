<?php

namespace NovaComponents\JiraIssuePrioritizer;

use Laravel\Nova\Nova;
use Laravel\Nova\Tool;

class JiraIssuePrioritizer extends Tool
{
    /**
     * Perform any tasks that need to happen when the tool is booted.
     *
     * @return void
     */
    public function boot()
    {
        Nova::script('jira-priorities', __DIR__.'/../dist/js/component.js');
        Nova::style('jira-priorities', __DIR__.'/../dist/css/component.css');
    }

    /**
     * Build the view that renders the navigation links for the tool.
     *
     * @return \Illuminate\View\View
     */
    public function renderNavigation()
    {
        return view('jira-priorities::navigation');
    }
}
