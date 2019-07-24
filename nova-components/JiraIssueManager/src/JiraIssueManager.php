<?php

namespace NovaComponents\JiraIssueManager;

use Laravel\Nova\Nova;
use Laravel\Nova\Tool;

class JiraIssueManager extends Tool
{
    /**
     * Perform any tasks that need to happen when the tool is booted.
     *
     * @return void
     */
    public function boot()
    {
        Nova::script('jira-issue-manager', __DIR__.'/../dist/js/component.js');
        Nova::style('jira-issue-manager', __DIR__.'/../dist/css/component.css');
    }

    /**
     * Build the view that renders the navigation links for the tool.
     *
     * @return \Illuminate\View\View
     */
    public function renderNavigation()
    {
        return view('jira-issue-manager::navigation');
    }
}
