<?php

namespace App\Providers;

use Carbon\Carbon;
use App\Models\Issue;
use App\Support\Jira\JiraService;
use App\Support\Jira\Query\Processor;
use Illuminate\Support\ServiceProvider;

class JiraServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootFieldMapping();
        $this->bootPostProcessor();
    }


    /**
     * Boots the field mapping.
     *
     * @return void
     */
    protected function bootFieldMapping()
    {
        // Determine the configuration
        $config = $this->app->config;

        // Determine the host endpoint
        $host = rtrim($config->get('jira.host'), '/');

        // Determine the custom field mapping
        $mapping = [
            'issue_category' => 'customfield_12005',
            'estimated_completion_date' => 'customfield_12011',
            'epic_key' => 'customfield_12000',
            'epic_name' => 'customfield_10002',
            'epic_color' => 'customfield_10004',
            'rank' => 'customfield_10119'
        ];

        Processor::map(function($fields, $issue) use ($host, $mapping) {

            // Return the field mapping
            return [
                'url' => $host . '/browse/' . $issue->key,

                'summary' => data_get($fields, 'summary'),

                'priority_name' => $priority = data_get($fields, 'priority.name'),
                'priority_icon_url' => data_get($fields, 'priority.iconUrl'),

                'issue_category' => $category = data_get($fields, "{$mapping['issue_category']}.value", Issue::ISSUE_CATEGORY_DEV),
                'focus' => $priority == Issue::PRIORITY_HIGHEST ? Issue::FOCUS_OTHER : ($category == Issue::ISSUE_CATEGORY_DEV ? Issue::FOCUS_DEV : Issue::FOCUS_TICKET),

                'due_date' => $due = data_get($fields, 'duedate'),

                'estimate_remaining' => data_get($fields, 'timeestimate'),
                'estimate_date' => $est = data_get($fields, $mapping['estimated_completion_date']),
                'estimate_diff' => (is_null($due) || is_null($est)) ? null : Carbon::parse($est)->diffInDays(Carbon::parse($due), false),

                'type_name' => data_get($fields, 'issuetype.name'),
                'type_icon_url' => data_get($fields, 'issuetype.iconUrl'),

                'is_subtask' => $isSubtask = data_get($fields, 'issuetype.subtask', false),
                'parent_key' => $isSubtask ? ($parentKey = data_get($fields, 'parent.key')) : null,
                'parent_url' => $isSubtask ? $host . '/browse/' . $parentKey : null,

                'status_name' => data_get($fields, 'status.name'),
                'status_color' => data_get($fields, 'status.statuscategory.colorName'),

                'reporter_name' => data_get($fields, 'reporter.displayName'),
                'reporter_icon_url' => data_get($fields, 'reporter.avatarUrls.16x16'),

                'assignee_name' => data_get($fields, 'assignee.displayName'),
                'assignee_icon_url' => data_get($fields, 'assignee.avatarUrls.16x16'),

                'epic_key' => $epicKey = data_get($fields, $mapping['epic_key']),
                'epic_url' => !is_null($epicKey) ? $host . '/browse/' . $epicKey : null,
                'epic_name' => data_get($fields, $mapping['epic_name']),
                'epic_color' => data_get($fields, $mapping['epic_color']),

                'labels' => json_encode($fields['labels'] ?? []),

                'links' => array_map(function($link) {

                    $related = $link->inwardIssue ?? $link->outwardIssue;

                    return [
                        'type' => $link->type->name,
                        'direction' => isset($link->inwardIssue) ? 'inward' : 'outward',
                        'related' => [
                            'key' => $related->key,
                            'status' => $related->fields->status->name,
                        ]
                    ];

                }, $fields['issuelinks'] ?? []),

                'rank' => data_get($fields, $mapping['rank'])
            ];

        });
    }

    /**
     * Boots the post processor.
     *
     * @return void
     */
    protected function bootPostProcessor()
    {
        Processor::post(function($issues) {

            // Determine the epic keys
            $epics = array_values(array_unique(array_filter(array_pluck($issues, 'epic_key'))));

            // Check if any epics were found
            if(!empty($epics)) {

                // Map the epics into issues
                $epics = $this->app->make(JiraService::class)->newQuery()->whereIn('issuekey', $epics)->get()->issues->keyBy('key');

                // Fill in the epic details for the non-epic issues
                $issues = array_map(function($issue) use ($epics) {

                    // If the issue does not have an epic key, return it as-is
                    if(is_null($issue->epic_key)) {
                        return $issue;
                    }

                    // Determine the associated epic
                    $epic = $epics[$issue->epic_key] ?? null;

                    // If the epic couldn't be found, return it as-is
                    if(is_null($epic)) {
                        return $issue;
                    }

                    // Fill in the epic information
                    $issue->epic_name = $epic->epic_name;
                    $issue->epic_color = $epic->epic_color;

                    // Return the issue
                    return $issue;

                }, $issues);

            }

            // Return the issues
            return $issues;

        });
    }

}
