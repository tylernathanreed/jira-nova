<?php

namespace App\Providers;

use DB;
use App\Models\Epic;
use App\Models\Issue;
use App\Support\Jira\JiraService;
use App\Support\Jira\Query\Builder;
use App\Support\Jira\Query\Processor;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Query\Expression;

class JiraServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootJiraQueryMacros();
        $this->bootFieldMapping();
        $this->bootPostProcessor();
    }

    /**
     * Boots the jira query macros.
     *
     * @return void
     */
    protected function bootJiraQueryMacros()
    {
        /**
         * Returns the query results using the cache as a preference.
         *
         * @return \stdClass
         */
        Builder::macro('getUsingCache', function() {

            // Determine the issues resolveable from the cache
            $issues = $this->toEloquent()->get();

            // Determine the oldest issue
            $oldest = $this->toEloquent()->min('updated_at');

            // Only grab issues from jira that have been updated since the oldest date
            if(!is_null($oldest)) {
                $this->where('updated', '>=', carbon($oldest)->toDateTimeString());
            }

            // Return the jira results
            $results = $this->get();

            // Merge the results, favoring jira over cache
            $results->issues = $issues->keyBy('key')->toBase()->merge($results->issues->keyBy('key'))->values();

            // Update the count
            $results->count = count($results->issues);

            // Return the results
            return $results;

        });

        /**
         * Returns the eloquent query equivalent of this query.
         *
         * @return \Illuminate\Database\Eloquent\Builder
         */
        Builder::macro('toEloquent', function() {

            // Create a new query
            $query = (new Issue)->newQuery();

            // Apply the limit
            if(!is_null($this->limit)) {
                $query->limit($this->limit);
            }

            // Apply the offset
            if(!is_null($this->offset)) {
                $query->offset($this->offset);
            }

            // Determine the column mapping
            $map = function($column) {

                switch(strtolower($column)) {

                    case 'assignee': return 'assignee_key';
                    case 'issue category': return 'issue_category';
                    case 'priority': return 'priority_name';
                    case 'rank': return 'rank';
                    case 'reporter': return 'reporter_key';
                    case 'status': return 'status_name';

                    default: return $column;

                }

            };

            // Apply each where clause
            if(!empty($this->wheres)) {

                $query->getQuery()->wheres = array_map(function($where) use ($query, $map) {

                    if(isset($where['column'])) {
                        $where['column'] = $map($where['column']);
                    }

                    if(isset($where['value'])) {
                        $query->addBinding($where['value']);
                    }

                    if(isset($where['values'])) {
                        $query->addBinding($where['values'], 'where');
                    }

                    if(isset($where['query'])) {

                        $where['query'] = $where['query']->toEloquent()->getQuery();

                        $query->addBinding($where['query']->getRawBindings()['where'], 'where');

                    }

                    return $where;

                }, $this->wheres);

            }

            // Apply the order by clauses
            if(!empty($this->orders)) {

                $query->getQuery()->orders = array_map(function($order) use ($map) {

                    $order['column'] = $map($order['column']);

                    return $order;

                }, $this->orders);

            }

            // Return the query
            return $query;

        });
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

            // Determine the initial field mapping
            $result = [
                'url' => $host . '/browse/' . $issue->key,

                'summary' => data_get($fields, 'summary'),

                'priority_name' => $priority = data_get($fields, 'priority.name'),
                'priority_icon_url' => data_get($fields, 'priority.iconUrl'),

                'issue_category' => $category = data_get($fields, "{$mapping['issue_category']}.value", Issue::ISSUE_CATEGORY_DEV),

                'due_date' => $due = data_get($fields, 'duedate'),

                'estimate_remaining' => data_get($fields, 'timeestimate'),
                'estimate_date' => $est = data_get($fields, $mapping['estimated_completion_date']),
                'estimate_diff' => (is_null($due) || is_null($est)) ? null : carbon($est)->diffInDays(carbon($due), false),

                'type_name' => data_get($fields, 'issuetype.name'),
                'type_icon_url' => data_get($fields, 'issuetype.iconUrl'),

                'is_subtask' => $isSubtask = data_get($fields, 'issuetype.subtask', false),
                'parent_key' => $isSubtask ? ($parentKey = data_get($fields, 'parent.key')) : null,
                'parent_url' => $isSubtask ? $host . '/browse/' . $parentKey : null,

                'status_name' => data_get($fields, 'status.name'),
                'status_color' => data_get($fields, 'status.statuscategory.colorName'),

                'reporter_key' => data_get($fields, 'reporter.key'),
                'reporter_name' => data_get($fields, 'reporter.displayName'),
                'reporter_icon_url' => data_get($fields, 'reporter.avatarUrls.16x16'),

                'assignee_key' => data_get($fields, 'assignee.key'),
                'assignee_name' => data_get($fields, 'assignee.displayName'),
                'assignee_icon_url' => data_get($fields, 'assignee.avatarUrls.16x16'),

                'epic_id' => null,
                'epic_key' => $epicKey = data_get($fields, $mapping['epic_key']),
                'epic_url' => !is_null($epicKey) ? $host . '/browse/' . $epicKey : null,
                'epic_name' => data_get($fields, $mapping['epic_name']),
                'epic_color' => data_get($fields, $mapping['epic_color']),

                'labels' => $fields['labels'] ?? [],
                'fix_versions' => collect(data_get($fields, 'fixVersions', []))->pluck('name')->toArray(),

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

                'rank' => data_get($fields, $mapping['rank']),

                'entry_date' => data_get($fields, 'created')

            ];

            // Calculate the focus
            $result['focus'] = $priority == Issue::PRIORITY_HIGHEST ? Issue::FOCUS_OTHER : ($category == Issue::ISSUE_CATEGORY_DEV ? Issue::FOCUS_DEV : Issue::FOCUS_TICKET);

            // Return the result
            return $result;

        });
    }

    /**
     * Boots the post processor.
     *
     * @return void
     */
    protected function bootPostProcessor()
    {
        Processor::post(function($issues, $query) {

            // Assign the epic meta data
            $issues = $this->assignEpicMetaDataFromIssues($issues);

            return $issues;

        });
    }

    /**
     * Assigns the epic meta data from the specified issues.
     *
     * @param  array  $issues
     *
     * @return array
     */
    protected function assignEpicMetaDataFromIssues($issues)
    {
        // Determine the epic keys
        $epicKeys = array_values(array_unique(array_filter(array_pluck($issues, 'epic_key'))));

        // Determine the cached epics
        $cachedEpics = Epic::whereIn('key', $epicKeys)->get()->keyBy('key')->map(function($epic) {

            return [
                'id' => $epic->id,
                'name' => $epic->name,
                'color' => $epic->color
            ];

        });

        // Determine the missing epics
        $missingEpics = array_diff($epicKeys, $cachedEpics->keys()->toArray());

        // Fill the the missing epics
        $rawEpics = empty($missingEpics) ? collect() :
            $this->app->make(JiraService::class)->newQuery()->whereIn('issuekey', $missingEpics)->get()->issues->keyBy('key')->map(function($epic) {

                return [
                    'name' => $epic->epic_name,
                    'color' => $epic->epic_color,
                ];

            });

        // Determine the epic information
        $epics = array_merge(
            $cachedEpics->toArray(),
            $rawEpics->toArray()
        );

        // Check if any epics were found
        if(!empty($epics)) {

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
                $issue->epic_id = $epic['id'] ?? null;
                $issue->epic_name = $epic['name'];
                $issue->epic_color = $epic['color'];

                // Return the issue
                return $issue;

            }, $issues);

        }

        // Return the issues
        return $issues;
    }
}
