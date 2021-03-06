<?php

namespace App\Models;

use DB;
use Jira;
use Nova;
use Closure;
use Carbon\Carbon;
use App\Nova\Filters\Filter;
use JiraRestApi\Issue\IssueField;
use App\Support\Contracts\Cacheable;
use App\Support\Jira\RankingOperation;
use JiraRestApi\Issue\Issue as JiraIssue;
use Laravel\Nova\Http\Requests\NovaRequest;
use App\Nova\Resources\JiraIssue as IssueResource;

class Issue extends Model implements Cacheable
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
     * The attributes that should be casted.
     *
     * @var array
     */
    protected $casts = [
        'labels' => 'array',
        'fix_versions' => 'array',
        'links' => 'json'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'due_date',
        'estimate_date',
        'entry_date',
        'changelogs_updated_at',
        'worklogs_updated_at'
    ];

    /////////////////
    //* Accessors *//
    /////////////////
    /**
     * Returns the internal url to this epic in Nova.
     *
     * @return string
     */
    public function getInternalUrl()
    {
        return static::getInternalUrlForId($this->id);
    }

    /**
     * Returns the internal url to the specified epic in Nova.
     *
     * @param  integer  $id
     *
     * @return string
     */
    public static function getInternalUrlForId($id)
    {
        return url(Nova::path() . '/resources/issues/' . $id);
    }

    /**
     * Returns the external url to this epic in Jira.
     *
     * @return string
     */
    public function getExternalUrl()
    {
        return static::getExternalUrlForKey($this->key);
    }

    /**
     * Returns the external url to the specified epic in Nova.
     *
     * @param  string  $key
     *
     * @return string
     */
    public static function getExternalUrlForKey($key)
    {
        return rtrim(config('jira.host'), '/') . '/browse/' . $key;
    }

    /**
     * Returns whether or not this issue has the specified label.
     *
     * @param  string  $label
     *
     * @return boolean
     */
    public function hasLabel($label)
    {
        return !is_null(collect($this->labels)->first(function($compare) use ($label) {
            return $compare == $label;
        }));
    }

    /**
     * Returns the due date of this issue.
     *
     * @return \Carbon\Carbon|null
     */
    public function getDueDate()
    {
        // Determine the applicable due dates
        $dates = $this->getApplicableDueDates();

        // If there aren't any dates, then it cannot be due
        if(count($dates) == 0) {
            return null;
        }

        // Return the oldest date
        return min($dates);
    }

    /**
     * Returns the applicable due dates.
     *
     * @return array
     */
    public function getApplicableDueDates()
    {
        return array_filter([
            $this->getProductionDueDate(),
            $this->getWeekCommitmentDueDate()
        ]);
    }

    /**
     * Returns the production due date of this issue.
     *
     * @return \Carbon\Carbon|null
     */
    public function getProductionDueDate()
    {
        return $this->due_date;
    }

    /**
     * Returns the week commitment due date of this issue.
     *
     * @return \Carbon\Carbon|null
     */
    public function getWeekCommitmentDueDate()
    {
        // Determine the week label index from the label names
        $index = $this->getWeekLabelIndex();

        // If no index was found, no commitment has been made
        if(is_null($index)) {
            return null;
        }

        // Return the end date of the week
        return Label::getWeekRange($index)[1];
    }

    /**
     * Returns the week label index of this issue.
     *
     * @return integer|null
     */
    public function getWeekLabelIndex()
    {
        // Return the week label index from the label names
        return Label::getWeekLabelIndexFromLabelNames($this->labels);
    }

    /**
     * Returns whether or not this issue is past due.
     *
     * @return boolean
     */
    public function isPastDue()
    {
        return !is_null($due = $this->getDueDate()) && $due->isPast() && !$due->isToday();
    }

    /**
     * Returns the priority index of this issue.
     *
     * @return integer|null
     */
    public function getPriorityIndex()
    {
        switch($this->priority_name) {

            case 'Highest':
                return 1;

            case 'High':
                return 2;

            case 'Medium':
                return 3;

            case 'Low':
                return 4;

            case 'Lowest':
                return 5;

            default:
                return null;

        }
    }

    /**
     * Returns the type index of this issue.
     *
     * @return integer|null
     */
    public function getTypeIndex()
    {
        switch($this->type_name) {

            case 'Bug': case 'Error': case 'Problem':
                return 1;

            case 'Data Update': case 'Meta Update':
                return 2;

            case 'Task': case 'Sub-Task': case 'Epic':
                return 3;

            case 'Service Request': case 'Training':
                return 4;

            case 'Improvement':
                return 5;

            case 'Research':
                return 6;

            case 'New Feature':
                return 7;

            case 'Fit and Finish': case 'Consistency': case 'Verbiage':
                return 8;

            case 'Recommendation':
                return 9;

            default:
                return null;

        }
    }

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
            'timeestimate',
            'created'
        ]);
    }

    /**
     * Returns an array of raw issues from Jira.
     *
     * @param  array  $option
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getIssuesFromJira($options = [])
    {
        // Create a new query from the options
        $query = (new static)->newJiraQueryFromOptions($options);

        // Determine the issues
        $issues = $query->get()->issues;

        // Key the issues by their jira key
        $issues = $issues->keyBy('key');

        // Determine the block map from the jira issues
        // $blocks = static::getBlockMapFromJiraIssues($issues);

        // Assign the blocks to each issue
        foreach($issues as $key => &$issue) {
            $issue->blocks = $blocks[$key] ?? [];
        }

        // Return the list of issues
        return $issues;
    }

    /**
     * Returns the issues specified by the given request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getIssuesFromRequest(NovaRequest $request)
    {
        // Determine the filters from the request
        $filters = Filter::getFiltersFromRequest($request, new IssueResource(new static));

        // Initialize the options
        $options = [
            'assignee' => User::pluck('display_name', 'jira_key')->sort()->flip()->toArray(),
            'groups' => [
                'dev' => true,
                'ticket' => true,
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

        // Ignore "Hold" priorities
        $query->whereNotIn('priority', ['Hold']);

        // Filter by assignee
        if(!empty($options['assignee'])) {

            // Check for traditional assignee
            $query->where(function($query) use ($options) {

                $query->whereIn('assignee', $options['assignee']);
                $query->whereIn('status', ['Assigned', 'Testing Failed', 'Dev Hold', 'In Development', 'In Design', 'Can\'t Test']);

            });

            // Check for Designated QA
            $query->orWhere(function($query) use ($options) {

                $query->whereIn('Designated QA', $options['assignee']);
                $query->whereIn('status', ['Ready to Test [Test]']);

            });

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
     * @param  \Illuminate\Support\Collection  $issues
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
            $links = array_filter(is_array($links) ? $links : json_decode($links, true), function($link) {

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

    /**
     * Returns the epic colors.
     *
     * @return array
     */
    public static function getEpicColors()
    {
        // Determine the epic colors
        $colors = (new static)->select(['epic_name', 'epic_color'])->whereNotNull('epic_name')->distinct()->getQuery()->get()->pluck('epic_color', 'epic_name');

        // Determine the epic color hex map
        $map = static::getEpicColorHexMap();

        // Map the colors into hex values
        $colors->transform(function($color) use ($map) {
            return $map[$color ?? 'ghx-label-0'] ?? '#000';
        });

        // Add the "Other" color
        $colors['Other'] = '#777';

        // Return the colors
        return $colors->all();
    }

    /**
     * Returns the epic color hex map.
     *
     * @return array
     */
    public static function getEpicColorHexMap()
    {
        return [
            'ghx-label-0' => '#ccc',
            'ghx-label-2' => '#ffc400',
            'ghx-label-4' => '#2684ff',
            'ghx-label-5' => '#00c7ef',
            'ghx-label-6' => '#abf5d1',
            'ghx-label-7' => '#8777d9',
            'ghx-label-8' => '#998dd9',
            'ghx-label-9' => '#ff7452',
            'ghx-label-11' => '#79e2f2',
            'ghx-label-12' => '#7a869a',
            'ghx-label-13' => '#57d9a3',
            'ghx-label-14' => '#ff8f73',
        ];
    }

    /**
     * Returns the color mapping for priorities.
     *
     * @return array
     */
    public static function getPriorityColors()
    {
        return [
            'Highest' => 'firebrick',
            'High' => '#f44',
            'Medium' => 'silver',
            'Low' => 'mediumseagreen',
            'Lowest' => 'green'
        ];
    }

    /////////////
    //* Cache *//
    /////////////
    /**
     * Caches the issues.
     *
     * @param  \Closure             $callback
     * @param  \Carbon\Carbon|null  $since
     *
     * @return array
     */
    public static function runCacheHandler(Closure $callback, Carbon $since = null)
    {
        // Iterate through the pages to cache
        static::newCacheQuery($since)->chunk(100, function($chunk, $page) use ($callback) {

            // Determine the issues
            $issues = $chunk->issues->keyBy('key')->map(function($issue) {

                $issue = array_except((array) $issue, [
                    'url',
                    'parent_url'
                ]);

                return $issue;

            });

            // Enable mass assignment
            static::unguarded(function() use ($issues) {

                // Update or create each issue
                $issues->each(function($issue, $key) {
                    static::updateOrCreate(compact('key'), $issue);
                });

            });

            // Invoke the handler
            $callback($page * 100, $chunk->count);

        });

        // Update issue attributes that are aggregated from jira attributes
        static::updateIssueAggregates();
    }

    /**
     * Returns the number of records that need to be cached.
     *
     * @param  \Carbon\Carbon|null  $since
     *
     * @return integer
     */
    public static function getCacheRecordCount(Carbon $since = null)
    {
        return static::newCacheQuery($since)->count();
    }

    /**
     * Creates and returns a new cache query.
     *
     * @param  \Carbon\Carbon|null  $since
     *
     * @return \App\Support\Jira\Query\Builder
     */
    public static function newCacheQuery(Carbon $since = null)
    {
        // Create a new query
        $query = Jira::newQuery();

        // Enforce an order by clause
        $query->orderBy('issuekey');

        // If we've never cached before, return the query as-is
        if(is_null($since)) {
            return $query;
        }

        // Exclude issues that we've already updated
        $query->where('updated', '>=', $since->toDateString());

        // Return the query
        return $query;
    }

    /**
     * Updates the issue attributes that are aggregated from jira attributes.
     *
     * @return void
     */
    public static function updateIssueAggregates()
    {
        // Update the rank index
        if(DB::connection()->getDriverName() == 'mysql') {
            static::updateMySQLIssueRankIndex();
        }
    }

    /**
     * Updates the issue rank index for all issues.
     *
     * @return void
     */
    public static function updateMySQLIssueRankIndex()
    {
        (new static)->newQuery()->whereRaw('0 = (@rownum := 0)')->orderBy('rank')->update([
            'rank_index' => DB::raw('@rownum := 1 + @rownum')
        ]);
    }

    ///////////////
    //* Queries *//
    ///////////////
    /**
     * Creates and returns a new remaining workload query.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function newRemainingWorkloadQuery()
    {
        // Create a new query
        $query = $this->newQuery();

        // Ignore completed issues
        $query->incomplete();

        // Make sure the remaining estimate is capped to be a one hour minimum
        $query->select([
            'id',
            DB::raw('case when estimate_remaining is null then 3600 when estimate_remaining < 3600 then 3600 else estimate_remaining end as estimate_remaining'),
            'focus',
            'epic_id',
            'epic_name',
            'status_name',
            'assignee_name',
            'priority_name',
            'due_date',
            'project_id',
            'labels',
            'fix_versions',
            'type_name',
            'issue_category',
            'estimate_date'
        ]);

        // Wrap the query into a subquery
        $query->fromSub($query, 'issues');

        // Return the query
        return $query;
    }

    /**
     * Creates and returns a new rank index query.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function newRankIndexQuery()
    {
        // Create a new query
        $query = $this->newQuery();

        // Only look at issues that have ranks
        $query->whereNotNull('rank');

        // Select the issue id and rank index
        $query->select([
            'id',
            DB::raw('row_number() over(order by rank asc) as rank_index')
        ]);

        // Return the query
        return $query;
    }

    ////////////////////
    //* Query Scopes *//
    ////////////////////
    /**
     * Filters out completed issues.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     *
     * @return void
     */
    public function scopeIncomplete($query)
    {
        $query->whereNotIn('status_name', [
            'Done',
            'Canceled',
            'Testing Passed [Test]',
            'Testing passed [UAT]'
        ]);
    }

    /**
     * Filters out completed issues.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     *
     * @return void
     */
    public function scopeComplete($query)
    {
        $query->whereIn('status_name', [
            'Done',
            'Canceled',
            'Testing Passed [Test]',
            'Testing passed [UAT]'
        ]);
    }

    /**
     * Filters to issues that are actively delinquent.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed                                  $when
     *
     * @return void
     */
    public function scopeDelinquent($query, $when = 'now')
    {
        $query->incomplete()->where('due_date', '<=', carbon($when));
    }

    /**
     * Filters to issues that will become delinquent.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed                                  $when
     *
     * @return void
     */
    public function scopeWillBeDelinquent($query, $when = 'now')
    {
        $query->incomplete()->whereColumn('due_date', '<=', 'estimate_date');
    }

    /**
     * Filters to issues that have the specified label name.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string                                 $name
     *
     * @return void
     */
    public function scopeHasLabel($query, $name)
    {
        $query->whereHas('labels', function($query) use ($name) {
            $query->where('labels.name', '=', $name);
        });
    }

    /**
     * Filters to issues that have a label like the specified name.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string                                 $name
     *
     * @return void
     */
    public function scopeHasLabelLike($query, $name)
    {
        $query->whereHas('labels', function($query) use ($name) {
            $query->where('labels.name', 'like', $name);
        });
    }

    /**
     * Filters to issues that have been assigned.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     *
     * @return void
     */
    public function scopeAssigned($query)
    {
        $query->whereNotNull('assignee_name');
    }

    /**
     * Filters to issues that have not been assigned.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     *
     * @return void
     */
    public function scopeUnassigned($query)
    {
        $query->whereNull('assignee_name');
    }

    /**
     * Filters to issues that are classified as features.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     *
     * @return void
     */
    public function scopeFeatures($query)
    {
        // All issues that are not defects are considered features
        $query->whereNotIn('issues.id', $this->newQuery()->defects()->select('id'));
    }

    /**
     * Filters to issues that are classified as defects.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     *
     * @return void
     */
    public function scopeDefects($query)
    {
        // Use a nested "where" clause
        $query->where(function($query) {

            // Exclude issue types that are not defects
            $query->whereNotIn('type_name', [
                'Consistency',
                'Epic',
                'Fit and Finish',
                'Improvement',
                'New Feature',
                'Recommendation',
                'Research',
                'Sub-task',
                'Subtask'
            ]);

            // Use a nested "where" clause for each issue category
            $query->where(function($query) {

                // All data issues are defects
                $query->where('issue_category', '=', 'Data');

                // All tickets are defects
                $query->orWhere('issue_category', '=', 'Ticket');

                // Some dev items are defects
                $query->orWhere(function($query) {

                    // Check for dev item
                    $query->where(function($query) {

                        // Issue category can be "Dev" or blank
                        $query->where('issue_category', '=', 'Dev');
                        $query->orWhereNull('issue_category');

                    });

                    // Dev tasks are not defects
                    $query->whereNotIn('type_name', [
                        'Task'
                    ]);

                });

                // All highest priorities are defects
                $query->orWhere('priority_name', '=', 'Highest');

                // All executive labelled issues are defects
                $query->orWhere('labels', 'like', '%"Executive"%');

            });

        });
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
     * Returns the labels associated to this issue.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function labels()
    {
        return $this->belongsToMany(Label::class, 'issues_labels');
    }

    /**
     * Returns the versions associated to this issue.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function versions()
    {
        return $this->belongsToMany(Version::class, 'issues_fix_versions');
    }

    /**
     * Returns the changelogs associated to this issue.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function changelogs()
    {
        return $this->hasMany(IssueChangelog::class, 'issue_id');
    }

    /**
     * Returns the worklogs associated to this issue.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function worklogs()
    {
        return $this->hasMany(IssueWorklog::class, 'issue_id');
    }

    /**
     * Returns the status that this issue belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function status()
    {
        return $this->belongsTo(WorkflowStatusType::class, 'status_name', 'name');
    }
}
