<?php

namespace App\Models;

use Jira;
use Carbon\Carbon;
use App\Nova\Filters\Filter;
use JiraRestApi\Issue\IssueField;
use App\Support\Jira\RankingOperation;
use JiraRestApi\Issue\Issue as JiraIssue;
use Laravel\Nova\Http\Requests\NovaRequest;
use App\Nova\Resources\JiraIssue as IssueResource;

class Issue extends Model
{
    /////////////////
    //* Constants *//
    /////////////////
    /**
     * The priority constants.
     *
     * @var string
     */
    const PRIORITY_HIGHEST = 'Highest';
    const PRIORITY_HIGH = 'High';
    const PRIORITY_MEDIUM = 'Medium';
    const PRIORITY_LOW = 'Low';
    const PRIORITY_LOWEST = 'Lowest';

    /**
     * The focus constants.
     *
     * @var string
     */
    const FOCUS_DEV = 'Dev';
    const FOCUS_TICKET = 'Ticket';
    const FOCUS_OTHER = 'Other';

    /**
     * The issue category constants.
     *
     * @var string
     */
    const ISSUE_CATEGORY_DEV = 'Dev';
    const ISSUE_CATEGORY_TICKET = 'Ticket';
    const ISSUE_CATEGORY_DATA = 'Data';

    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'due_date',
        'estimate_date'
    ];

    ////////////
    //* Jira *//
    ////////////
    /**
     * Creates and returns a new jira query.
     *
     * @return \App\Support\Jira\Query\Builder
     */
    public function newJiraQuery()
    {
        return Jira::newQuery();
    }

    /**
     * Creates and returns a new jira query with the default column selection.
     *
     * @return \App\Support\Jira\Query\Builder
     */
    public function newJiraQueryWithDefaultSelection()
    {
        return $this->newJiraQuery()->select(
            $this->getDefaultColumns()
        );
    }

    /**
     * Returns the default columns to select.
     *
     * @return array
     */
    public function getDefaultColumns()
    {
        return array_merge(array_values(config('jira.fields')), [
            'assignee',
            'duedate',
            'issuelinks',
            'issuetype',
            'labels',
            'parent',
            'priority',
            'reporter',
            'status',
            'summary',
            'timeestimate'
        ]);
    }

    /**
     * Returns an array of raw issues from Jira.
     *
     * @param  array  $option
     *
     * @return array
     */
    public static function getIssuesFromJira($options = [])
    {
        // Create a new query from the options
        $query = (new static)->newJiraQueryFromOptions($options);

        // Determine the issues
        $issues = $query->get()->issues;

        // Key the issues by their jira key
        $issues = $issues->keyBy('key');

        // Check if we're not handling epics
        if(!($options['epics'] ?? false)) {

            // Determine the block map from the jira issues
            $blocks = static::getBlockMapFromJiraIssues($issues);

            // Assign the blocks to each issue
            foreach($issues as $key => &$issue) {
                $issue->blocks = $blocks[$key] ?? [];
            }

        }

        // Return the list of issues
        return $issues;
    }

    /**
     * Returns the issues specified by the given request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     *
     * @return array
     */
    public static function getIssuesFromRequest(NovaRequest $request)
    {
        // Determine the filters from the request
        $filters = Filter::getFiltersFromRequest($request, new IssueResource(new static));

        // Initialize the options
        $options = [
            'assignee' => [
                $request->user()->jira_key
            ],
            'groups' => [
                'dev' => true,
                'ticket' => false,
                'other' => true
            ]
        ];

        // Apply the filters to the options
        foreach($filters as $filter) {
            $filter->filter->applyToJiraOptions($options, $filter->value);
        }

        // Return the jira issues
        return static::getIssuesFromJira($options);
    }

    /**
     * Creates and returns a new jira query from the specified options.
     *
     * @param  array  $options
     *
     * @return \App\Support\Jira\Query\Builder
     */
    public function newJiraQueryFromOptions($options)
    {
        // Create a new query
        $query = $this->newJiraQueryWithDefaultSelection();

        // Determine the applicable focus groups
        $groups = $options['groups'] ?? [
            'dev' => true,
            'ticket' => true,
            'other' => true
        ];

        // Filter by assignee
        $query->whereIn('assignee', $options['assignee'] ?? ['tyler.reed']);

        // Ignore "Hold" priorities
        $query->whereNotIn('priority', ['Hold']);

        // Filter by status
        $query->whereIn('status', ['Assigned', 'Testing Failed', 'Dev Hold', 'In Development', 'In Design']);

        // If the "dev" focus group is disabled, exclude them
        if(!$groups['dev']) {

            $query->whereNot(function($query) {

                $query->where(function($query) {
                    $query->where('Issue Category', 'Dev');
                    $query->orWhereNull('Issue Category');
                });

                $query->where('priority', '!=', 'Highest');

            });

        }

        // If the "ticket" focus group is disabled, exclude them
        if(!$groups['ticket']) {

            $query->where(function($query) {

                $query->where(function($query) {
                    $query->whereNull('Issue Category');
                    $query->orWhereNotIn('Issue Category', ['Ticket', 'Data']);
                    $query->orWhere('priority', 'Highest');
                });

            });

        }

        // If the "other" focus group is disabled, exclude them
        if(!$groups['other']) {
            $query->where('priority', '!=', 'Highest');
        }

        // Order by rank
        $query->orderBy('rank');

        // Return the query
        return $query;
    }

    /**
     * Returns the block map for the specified jira issues.
     *
     * @param  array  $issues
     *
     * @return array
     */
    public static function getBlockMapFromJiraIssues($issues)
    {
        // Determine all of the block relations
        $relations = static::getAllBlockRelationsFromJiraIssues($issues);

        // Find the top-level issues
        $heads = array_values(array_diff(array_keys($relations['blocks']), array_keys($relations['blockedBy'])));

        // Sort the top-level issues
        sort($heads);

        // Initialize the chain depths
        $depths = [];

        // Iterate through the top-level issues
        foreach($heads as $index => $head) {

            // Add each head as a chain
            $depths[$head][] = [
                'chain' => $index,
                'depth' => 1
            ];

            // Determine the blocking issues
            $blocks = $relations['blocks'];

            // Initialize the current level
            $level = [$head];

            // Process the blocking issues until they're all gone
            for($i = 0; count($blocks) > 0 && $i < 10; $i++) {

                // Determine the next set of blocking issues to process
                $next = array_only($blocks, $level);

                // If we've run out of issues to process, then there's some
                // sort of cyclical chain of issues. We do not support a
                // relationship like this, so we will bail out early.

                // Stop if there aren't any nodes to process
                if(empty($next)) {
                    break;
                }

                // Iterate through each blocking set
                foreach($next as $parent => $children) {

                    // Iterate through each child
                    foreach($children as $child) {

                        // Make sure the child is not already in the chain
                        if(isset($depths[$child]) && !is_null(array_first($depths[$child], function($link) use ($index) { return $link['chain'] == $index; }))) {
                            continue;
                        }

                        // Add each child to the depth map
                        $depths[$child][] = [
                            'chain' => $index,
                            'depth' => $i + 2
                        ];

                    }

                }

                // Update the list of remaining blocking issues
                $blocks = array_except($blocks, array_keys($next));

                // Update the next level
                $level = array_collapse($next);

            }

        }

        // Return the depth map
        return $depths;
    }

    /**
     * Returns all of the block relations from the specified jira issues.
     *
     * @param  array  $issues
     *
     * @return array
     */
    protected static function getAllBlockRelationsFromJiraIssues($issues)
    {
        // Initialize the list of known issues
        $keys = $issues->pluck('key')->all();

        // Determine the block links between each issue
        $relations = static::getBlockRelationsFromJiraIssues($issues);

        // Find all of the related issues that we don't have
        $missing = array_values(array_diff(array_values(array_collapse(array_collapse($relations))), $keys));

        // Loop until no issues are missing
        for($i = 0; count($missing) > 0 && $i < 10; $i++) {

            // Find the links for the missing issues
            $results = (new static)->newJiraQuery()->whereIn('issuekey', $missing)->limit(count($missing))->select(['links'])->get();

            // Map the results into issues
            $issues = $results->issues->map(function($issue) {
                return (object) [
                    'key' => $issue->key,
                    'links' => $issue->links
                ];
            });

            // Determine the new keys
            $newKeys = $issues->pluck('key')->all();

            // Add the keys to the list of known issues
            $keys = array_merge($keys, $newKeys);

            // Determine the new set of relations
            $newRelations = static::getBlockRelationsFromJiraIssues($issues);

            // Add the new relations to the old relations
            $relations['blocks'] = array_merge($relations['blocks'], $newRelations['blocks']);
            $relations['blockedBy'] = array_merge($relations['blockedBy'], $newRelations['blockedBy']);

            // Update the list of missing issues
            $missing = array_values(array_diff(array_values(array_collapse(array_collapse($relations))), $keys));

        }

        // Return the relations
        return $relations;
    }

    /**
     * Returns the block relations from the specified jira issues.
     *
     * @param  array  $issues
     *
     * @return array
     */
    protected static function getBlockRelationsFromJiraIssues($issues)
    {
        return $issues->reduce(function($relations, $issue) {

            // Initialize the blocks and blocked-by lists
            $blocks = [];
            $blockedBy = [];

            // Determine the links
            $links = $issue->links;

            // Skip issues without links
            if(empty($links)) {
                return $relations;
            }

            // Find the block-type links
            $links = array_filter($links, function($link) {

                // Ignore non-block type links
                if($link['type'] != 'Blocks') {
                    return false;
                }

                // If the related issue is done or cancelled, then we don't care
                if(in_array($link['related']['status'], ['Done', 'Canceled'])) {
                    return false;
                }

                // Keep the link
                return true;

            });

            // Skip issues without block-type links
            if(empty($links)) {
                return $relations;
            }

            // Loop through the links again, this time categorizing them
            foreach($links as $link) {
                ${$link['direction'] == 'inward' ? 'blockedBy' : 'blocks'}[] = $link['related']['key'];
            }

            // Append the blocks to the relations
            if(!empty($blocks)) {
                $relations['blocks'][$issue->key] = $blocks;
            }

            // Append the blocked by to the relations
            if(!empty($blockedBy)) {
                $relations['blockedBy'][$issue->key] = $blockedBy;
            }

            // Return the relations
            return $relations;

        }, ['blocks' => [], 'blockedBy' => []]);
    }

    /**
     * Performs the ranking operations to sort the old list into the new list.
     *
     * @param  array  $oldOrder
     * @param  array  $newOrder
     * @param  array  $subtasks
     *
     * @return void
     */
    public static function updateOrderByRank($oldOrder, $newOrder, $subtasks = [])
    {
        RankingOperation::execute($oldOrder, $newOrder, $subtasks);
    }

    /**
     * Updates the estimated completion date from the given list of issues.
     *
     * @param  array  $estimates
     *
     * @return void
     */
    public static function updateEstimates($estimates)
    {
        // Iterate through each issue
        foreach($estimates as $key => $estimate) {

            // Create a new field set
            $fields = new IssueField(true);

            // Add the new estimated completion date
            $fields->addCustomField(config('jira.fields.estimated_completion_date'), $estimate);

            // Update the issue
            Jira::issues()->update($key, $fields);

        }
    }
}
