<?php

namespace App\Models;

use Jira;
use JiraRestApi\Issue\IssueField;
use App\Support\Jira\RankingOperation;
use JiraRestApi\Issue\Issue as JiraIssue;

class Issue extends Model
{
    /////////////////
    //* Constants *//
    /////////////////
    /**
     * The field constants.
     *
     * @var string
     */
    const FIELD_ISSUE_TYPE = 'issuetype';
    const FIELD_STATUS = 'status';
    const FIELD_SUMMARY = 'summary';
    const FIELD_DUE_DATE = 'duedate';
    const FIELD_REMAINING_ESTIMATE = 'timeestimate';
    const FIELD_PRIORITY = 'priority';
    const FIELD_REPORTER = 'reporter';
    const FIELD_ASSIGNEE = 'assignee';
    const FIELD_ISSUE_CATEGORY = 'customfield_12005';
    const FIELD_ESTIMATED_COMPLETION_DATE = 'customfield_12011';
    const FIELD_EPIC_KEY = 'customfield_12000';
    const FIELD_EPIC_NAME = 'customfield_10002';
    const FIELD_EPIC_COLOR = 'customfield_10004';
    const FIELD_LINKS = 'issuelinks';
    const FIELD_RANK = 'customfield_10119';

    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'jira_id', 'jira_key', 'project_id'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'due_date'
    ];

    ////////////
    //* Jira *//
    ////////////
    /**
     * Returns an array of raw issues from Jira.
     *
     * @param  array  $option
     *
     * @return array
     */
    public static function getIssuesFromJira($options = [])
    {
        // Determine the search query
        $jql = static::newIssuesFromJiraExpression($options);

        // Initialize the list of issues
        $issues = [];

        // Initialize the pagination variables
        $page = 0;
        $count = 50;

        // Loop until we're out of results
        do {

            // Determine the search results
            $results = Jira::issues()->search($jql, $page * $count, $count, [
                static::FIELD_ISSUE_TYPE,
                static::FIELD_STATUS,
                static::FIELD_SUMMARY,
                static::FIELD_DUE_DATE,
                static::FIELD_REMAINING_ESTIMATE,
                static::FIELD_PRIORITY,
                static::FIELD_REPORTER,
                static::FIELD_ASSIGNEE,
                static::FIELD_ISSUE_CATEGORY,
                static::FIELD_ESTIMATED_COMPLETION_DATE,
                static::FIELD_EPIC_KEY,
                static::FIELD_EPIC_NAME,
                static::FIELD_EPIC_COLOR,
                static::FIELD_LINKS,
                static::FIELD_RANK
            ], [], false);

            // Remap the issues to reference what we need
            $results = array_map(function($issue) {
                return [
                    'key' => $issue->key,
                    'url' => rtrim(config('services.jira.host'), '/') . '/browse/' . $issue->key,
                    'type' => $issue->fields->{static::FIELD_ISSUE_TYPE}->name,
                    'type_icon_url' => $issue->fields->{static::FIELD_ISSUE_TYPE}->iconUrl,
                    'is_subtask' => $issue->fields->{static::FIELD_ISSUE_TYPE}->subtask,
                    'status' => $issue->fields->{static::FIELD_STATUS}->name,
                    'status_color' => $issue->fields->{static::FIELD_STATUS}->statuscategory->colorName,
                    'summary' => $issue->fields->{static::FIELD_SUMMARY},
                    'due_date' => $issue->fields->{static::FIELD_DUE_DATE},
                    'time_estimate' => $issue->fields->{static::FIELD_REMAINING_ESTIMATE},
                    'old_estimated_completion_date' => $issue->fields->{static::FIELD_ESTIMATED_COMPLETION_DATE} ?? null,
                    'priority' => optional($issue->fields->{static::FIELD_PRIORITY})->name,
                    'priority_icon_url' => optional($issue->fields->{static::FIELD_PRIORITY})->iconUrl,
                    'reporter_name' => optional($issue->fields->{static::FIELD_REPORTER})->displayName,
                    'reporter_icon_url' => optional($issue->fields->{static::FIELD_REPORTER})->avatarUrls['16x16'] ?? null,
                    'assignee_name' => optional($issue->fields->{static::FIELD_ASSIGNEE})->displayName,
                    'assignee_icon_url' => optional($issue->fields->{static::FIELD_ASSIGNEE})->avatarUrls['16x16'] ?? null,
                    'issue_category' => optional($issue->fields->{static::FIELD_ISSUE_CATEGORY} ?? null)->value ?? 'Dev',
                    'epic_key' => $epicKey = ($issue->fields->{static::FIELD_EPIC_KEY} ?? null),
                    'epic_url' => !is_null($epicKey) ? rtrim(config('services.jira.host'), '/') . '/browse/' . $epicKey : null,
                    'epic_name' => $issue->fields->{static::FIELD_EPIC_NAME} ?? null,
                    'epic_color' => $issue->fields->{static::FIELD_EPIC_COLOR} ?? null,
                    'links' => $issue->fields->{static::FIELD_LINKS} ?? [],
                    'blocks' => [],
                    'rank' => $issue->fields->{static::FIELD_RANK}
                ];
            }, $results->issues);

            // Determine the number of results
            $countResults = count($results);

            // If there aren't any results, stop here
            if($countResults == 0) {
                break;
            }

            // Append the results to the list of issues
            $issues = array_merge($issues, $results);

            // Forget the results
            unset($results);

            // Increase the page count
            $page++;

        } while ($countResults == $count);

        // Kep the issues by their jira key
        $issues = array_combine(array_column($issues, 'key'), $issues);

        // Determine the epic keys
        $epics = array_values(array_unique(array_filter(array_column($issues, 'epic_key'))));

        // Check if any epics were found
        if(!empty($epics)) {

            // Map the epics into issues
            $epics = static::getIssuesFromJira([
                'keys' => $epics,
                'epics' => true
            ]);

            // Fill in the epic details for the non-epic issues
            $issues = array_map(function($issue) use ($epics) {

                // If the issue does not have an epic key, return it as-is
                if(is_null($issue['epic_key'])) {
                    return $issue;
                }

                // Determine the associated epic
                $epic = $epics[$issue['epic_key']] ?? null;

                // If the epic couldn't be found, return it as-is
                if(is_null($epic)) {
                    return $issue;
                }

                // Fill in the epic information
                $issue['epic_name'] = $epic['epic_name'];
                $issue['epic_color'] = $epic['epic_color'];

                // Return the issue
                return $issue;

            }, $issues);

        }

        // Check if we're not handling epics
        if(!($options['epics'] ?? false)) {

            // Determine the block map from the jira issues
            $blocks = static::getBlockMapFromJiraIssues($issues);

            // Assign the blocks to each issue
            foreach($issues as $key => &$issue) {
                $issue['blocks'] = $blocks[$key] ?? [];
            }

        }

        // Return the list of issues
        return $issues;
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
        $keys = array_column($issues, 'key');

        // Determine the block links between each issue
        $relations = static::getBlockRelationsFromJiraIssues($issues);

        // Find all of the related issues that we don't have
        $missing = array_values(array_diff(array_values(array_collapse(array_collapse($relations))), $keys));

        // Loop until no issues are missing
        for($i = 0; count($missing) > 0 && $i < 10; $i++) {

            // Find the links for the missing issues
            $results = Jira::issues()->search('issuekey in (' . implode(', ', $missing) . ')', 0, count($missing), [
                static::FIELD_LINKS
            ], [], false);

            // Map the results into issues
            $issues = array_map(function($issue) {
                return [
                    'key' => $issue->key,
                    'links' => $issue->fields->{static::FIELD_LINKS}
                ];
            }, $results->issues);

            // Determine the new keys
            $newKeys = array_column($issues, 'key');

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
        return array_reduce($issues, function($relations, $issue) {

            // Initialize the blocks and blocked-by lists
            $blocks = [];
            $blockedBy = [];

            // Determine the links
            $links = $issue['links'];

            // Skip issues without links
            if(empty($links)) {
                return $relations;
            }

            // Find the block-type links
            $links = array_filter($links, function($link) {

                // Ignore non-block type links
                if($link->type->name != 'Blocks') {
                    return false;
                }

                // Determine the related issue
                $related = $link->inwardIssue ?? $link->outwardIssue;

                // If the related issue is done or cancelled, then we don't care
                if(in_array($related->fields->{static::FIELD_STATUS}->name, ['Done', 'Canceled'])) {
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
                ${isset($link->inwardIssue) ? 'blockedBy' : 'blocks'}[] = ($link->inwardIssue ?? $link->outwardIssue)->key;
            }

            // Append the blocks to the relations
            if(!empty($blocks)) {
                $relations['blocks'][$issue['key']] = $blocks;
            }

            // Append the blocked by to the relations
            if(!empty($blockedBy)) {
                $relations['blockedBy'][$issue['key']] = $blockedBy;
            }

            // Return the relations
            return $relations;

        }, ['blocks' => [], 'blockedBy' => []]);
    }

    /**
     * Returns the jira expression that identifies issues.
     *
     * @param  array  $options
     *
     * @return string
     */
    public static function newIssuesFromJiraExpression($options = [])
    {
        // Check for a list of issues
        if(!empty($keys = ($options['keys'] ?? []))) {
            return 'issuekey in (' . implode(', ', $keys) . ')';
        }

        // Determine the applicable focus groups
        $groups = $options['groups'] ?? [
            'dev' => true,
            'ticket' => true,
            'other' => true
        ];

        // Determine the base expression
        $expression = 'assignee in (tyler.reed) AND priority not in (Hold) AND status in (Assigned, "Testing Failed", "Dev Hold", "In Development", "In Design")';

        // If the "dev" focus group is disabled, exclude them
        if(!$groups['dev']) {
            $expression .= ' AND NOT (("Issue Category" = "Dev" or "Issue Category" is empty) AND priority != Highest)';
        }

        // If the "ticket" focus group is disabled, exclude them
        if(!$groups['ticket']) {
            $expression .= ' AND ("Issue Category" is empty or "Issue Category" not in ("Ticket", "Data"))';
        }

        // If the "other" focus group is disabled, exclude them
        if(!$groups['other']) {
            $expression .= ' AND priority != Highest';
        }

        // Add the order by clause
        $expression .= ' ORDER BY Rank ASC';

        // Return the expression
        return $expression;
    }

    /**
     * Creates or updates the specified issue type from jira.
     *
     * @param  \JiraRestApi\Issue\Issue  $jira
     * @param  array                     $options
     *
     * @return static
     */
    public static function createOrUpdateFromJira(JiraIssue $jira, $options = [])
    {
        // Try to find the existing issue type in our system
        if(!is_null($issue = static::where('jira_id', '=', $jira->id)->first())) {

            // Update the issue type
            return $issue->updateFromJira($jira, $options);

        }

        // Create the issue type
        return static::createFromJira($jira, $options);
    }

    /**
     * Creates a new issue type from the specified jira issue type.
     *
     * @param  \JiraRestApi\Issue\Issue  $jira
     * @param  array                     $options
     *
     * @return static
     */
    public static function createFromJira(JiraIssue $jira, $options = [])
    {
        // Create a new issue type
        $issue = new static;

        // Update the issue type from jira
        return $issue->updateFromJira($jira, $options);
    }

    /**
     * Syncs this model from jira.
     *
     * @param  \JiraRestApi\Issue\Issue  $jira
     * @param  array                     $options
     *
     * @return $this
     */
    public function updateFromJira(JiraIssue $jira, $options = [])
    {
        // Perform all actions within a transaction
        return $this->getConnection()->transaction(function() use ($jira, $options) {

            // If a project was provided, associate it
            if(!is_null($project = ($options['project'] ?? null))) {
                $this->project()->associate($project);
            }

            // If a parent was provided, associate it
            if(!is_null($parent = ($options['parent'] ?? null))) {
                $this->parent()->associate($parent);
            }

            // If an issue type was provided, associate it
            if(!is_null($type = ($options['type'] ?? null))) {
                $this->type()->associate($type);
            }

            // If an issue status was provided, associate it
            if(!is_null($status = ($options['status'] ?? null))) {
                $this->status()->associate($status);
            }

            // If an issue priority was provided, associate it
            if(!is_null($priority = ($options['priority'] ?? null))) {
                $this->priority()->associate($priority);
            }

            // If a reporter was provided, associate it
            if(!is_null($reporter = ($options['reporter'] ?? null))) {
                $this->reporter()->associate($reporter);
            }

            // If an assignee was provided, associate it
            if(!is_null($assignee = ($options['assignee'] ?? null))) {
                $this->assignee()->associate($assignee);
            }

            // If a creator was provided, associate it
            if(!is_null($creator = ($options['creator'] ?? null))) {
                $this->createdBy()->associate($creator);
            }

            // Assign the attributes
            $this->jira_id        = $jira->id;
            $this->jira_key       = $jira->key;
            $this->summary        = $jira->fields->summary;
            $this->description    = $jira->fields->description;
            $this->due_date       = $jira->fields->duedate;
            $this->time_estimated = optional($jira->fields->timeoriginalestimate)->scalar;
            $this->time_spent     = $jira->fields->timespent ?? null;
            $this->time_remaining = $jira->fields->timeestimate ?? null;
            $this->last_viewed_at = optional($jira->fields->lastViewed)->scalar;

            // Save
            $this->save();

            // Allow chaining
            return $this;

        });
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
            $fields->addCustomField(static::FIELD_ESTIMATED_COMPLETION_DATE, $estimate);

            // Update the issue
            Jira::issues()->update($key, $fields);

        }
    }

    /**
     * Returns the jira project for this project.
     *
     * @return \JiraRestApi\Issue\Issue
     */
    public function jira()
    {
        return Jira::issues()->get($this->jira_key);
    }

    /////////////////
    //* Relations *//
    /////////////////
    /** 
     * Returns the project that this issue belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Returns the parent to this issue.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /**
     * Returns the issue type that this issue belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type()
    {
        return $this->belongsTo(IssueType::class, 'issue_type_id');
    }

    /**
     * Returns the status type of this issue.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function status()
    {
        return $this->belongsTo(IssueStatusType::class, 'status_id');
    }

    /**
     * Returns the priority of this issue.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function priority()
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }

    /**
     * Returns the user that reported this issue.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Returns the user currently assigned to this issue.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Returns the user that created this issue.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
