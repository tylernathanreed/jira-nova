<?php

namespace App\Nova\Actions;

use Jira;
use App\Models\User;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Priority;
use App\Models\IssueType;
use App\Models\IssueStatusType;
use Illuminate\Support\Collection;
use Laravel\Nova\Fields\ActionFields;

class SyncIssueFromJira extends Action
{
    /**
     * Creates a new action instance.
     *
     * @param  array  $options
     *
     * @return $this
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->options = [];
    }

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection     $models
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        // Initialize all related entities
        $allUsers = User::all()->keyBy('jira_id')->all();
        $allTypes = IssueType::all()->keyBy('id')->all();
        $allStatuses = IssueStatusType::all()->keyBy('jira_id')->all();
        $allPriorities = Priority::all()->keyBy('jira_id')->all();
        $allProjects = Project::all()->keyBy('jira_id')->all();

        // Determine the jira issues
        $jiras = $models->first() instanceof Issue
            ? collect(Jira::issues()->search('issuekey in (' . $models->pluck('jira_key')->implode(', ') . ')')->issues)->keyBy('id')
            : $models;

        // Iterate through each issue
        foreach($models as $issue) {

            // Determine the jira instance for this issue
            $jira = $jiras[$issue->jira_id] ?? null;

            // Skip issues that couldn't be found
            if(is_null($jira)) {
                continue;
            }

            // Determine the reporter
            $reporter = (!is_null($reporter = $jira->fields->reporter) && !empty($reporter->accountId))
                ? $allUsers[$reporter->accountId] ?? ($allUsers[$reporter->accountId] = User::createOrUpdateFromJira($reporter))
                : null;

            // Determine the creator
            $creator = !is_null($creator = $jira->fields->creator)
                ? $allUsers[$creator->accountId] ?? ($allUsers[$creator->accountId] = User::createOrUpdateFromJira($creator))
                : null;

            // Determine the assignee
            $assignee = !is_null($assignee = $jira->fields->assignee)
                ? $allUsers[$assignee->accountId] ?? ($allUsers[$assignee->accountId] = User::createOrUpdateFromJira($assignee))
                : null;

            // Determine the issue type
            $type = !is_null($type = $jira->fields->issuetype)
                ? $allTypes[$type->id] ?? ($allTypes[$type->id] = IssueType::createOrUpdateFromJira($type))
                : null;

            // Determine the issue status type
            $status = !is_null($status = $jira->fields->status)
                ? $allStatuses[$status->id] ?? ($allStatuses[$status->id] = IssueStatusType::createOrUpdateFromJira($status, [
                    'project' => $this,
                    'category' => IssueStatusCategory::createOrUpdateFromJira($status->statuscategory, [
                        'project' => $this
                    ])
                ]))
                : null;

            // Determine the issue priority
            $priority = !is_null($priority = $jira->fields->priority)
                ? $allPriorities[$priority->id] ?? ($allPriorities[$priority->id] = Priority::createOrUpdateFromJira($priority))
                : null;

            // Determine the issue project
            $project = $this->options['project'] ?? (
                !is_null($project = $jira->fields->project)
                    ? $allProjects[$project->id] ?? ($allPriorities[$project->id] = Project::where('jira_id', '=', $project->id)->first())
                    : null
            );

            // Create or update each issue
            Issue::createOrUpdateFromJira($jira, [
                'project' => $project,
                'reporter' => $reporter,
                'creator' => $creator,
                'assignee' => $assignee,
                'type' => $type,
                'status' => $status,
                'priority' => $priority
            ]);

        }
    }

    /**
     * Returns the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [];
    }
}
