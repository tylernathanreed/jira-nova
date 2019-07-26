<?php

namespace App\Support\Jira;

use Jira;
use RuntimeException;
use App\Support\Astar\Algorithm;
use JiraAgileRestApi\IssueRank\IssueRank;

class RankingOperation
{
    /////////////////
    //* Constants *//
    /////////////////
    /**
     * The maximum group size that is allowed to be moved.
     *
     * @var integer
     */
    const MAXIMUM_OPERATION_SIZE = 50;

    /**
     * The relative relation constants.
     *
     * @var string
     */
    const RELATION_BEFORE = 'before';
    const RELATION_AFTER = 'after';

    /**
     * The cost multipliers.
     *
     * @var float
     */
    const COST_BASIS = 1;
    const COST_PER_ISSUE = 1;

    /**
     * The field constants.
     *
     * @var string
     */
    const FIELD_RANK = 10119;

    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The current state of the issue groups.
     *
     * @var array
     */
    public $groups;

    /**
     * The group index being moved.
     *
     * @var integer
     */
    public $moveIndex;

    /**
     * The direction in relation to the adjacent index the group is being moved.
     *
     * @var string
     */
    public $relation;

    /**
     * The adjacent index the group is being moved next to.
     *
     * @var integer|null
     */
    public $adjacentIndex;

    //////////////////
    //* Constuctor *//
    //////////////////
    /**
     * Creates and returns a new ranking operation.
     *
     * @param  array         $groups
     * @param  integer       $moveIndex
     * @param  string        $relation
     * @param  integer|null  $adjacentIndex
     *
     * @return $this
     */
    public function __construct($groups, $moveIndex, $relation, $adjacentIndex)
    {
        $this->groups = $groups;
        $this->moveIndex = $moveIndex;
        $this->relation = $relation;
        $this->adjacentIndex = $adjacentIndex;
    }

    /////////////////
    //* Accessors *//
    /////////////////
    /**
     * Returns the unique identifier for this move.
     *
     * @return string
     */
    public function getKey()
    {
        return static::getGroupArrangementIdentifier($this->groups) . ';' . $this->moveIndex . ';' . (
            $this->relation == static::RELATION_BEFORE
                ? $this->moveIndex . '=>' . (is_null($this->adjacentIndex) ? 'NULL' : $this->adjacentIndex)
                : $this->moveIndex . '<=' . (is_null($this->adjacentIndex) ? 'NULL' : $this->adjacentIndex)
        );
    }

    /**
     * Returns the move cost of this operation.
     *
     * @return integer|float
     */
    public function getAlgorithmMoveCost()
    {
        return static::COST_BASIS + count($this->groups[$this->moveIndex]['issues']) * static::COST_PER_ISSUE;
    }

    /**
     * Returns the simulated group arrangement after performing this operation.
     *
     * @return array
     */
    public function getSimulatedResult()
    {
        // Initialize the result
        $result = [];

        // Iterate through each group
        foreach($this->groups as $index => $group) {

            // Skip the move index
            if($index == $this->moveIndex) {
                continue;
            }

            // Check for the adjacent index
            if($index == $this->adjacentIndex) {

                // If the move index goes before, add it now
                if($this->relation == static::RELATION_BEFORE) {
                    $result[] = $this->groups[$this->moveIndex];
                }

                // Add the adjacent group to the result
                $result[] = $group;

                // If the move index goes after, add it now
                if($this->relation == static::RELATION_AFTER) {
                    $result[] = $this->groups[$this->moveIndex];
                }

            }

            // Otherwise, just add the group
            else {
                $result[] = $group;
            }

        }

        // Merge any adjacent groups
        $result = static::mergeAdjacentGroups($groups);

        // Return the result
        return $result;
    }

    //////////////////
    //* Operations *//
    //////////////////
    /**
     * Performs this operation.
     *
     * @return void
     */
    public function perform()
    {
        // Create a new issue rank
        $rank = $this->newIssueRank();

        // Perform the ranking operation
        return Jira::issueRanks()->update($rank);
    }

    /**
     * Creates and returns a new issue rank instance.
     *
     * @link https://developer.atlassian.com/cloud/jira/software/rest/#api-rest-agile-1-0-issue-rank-put
     *
     * @return \JiraAgileRestApi\IssueRank\IssueRank
     */
    public function newIssueRank()
    {
        // Create the issue rank
        $rank = new IssueRank;

        // Set the issues
        $rank->issues = $this->groups[$this->moveIndex]['issues'];

        // Set the custom rank field id
        $rank->rankCustomFieldId = static::FIELD_RANK;

        // Check if the issues come before the adjacent issue
        if($this->relation == static::RELATION_BEFORE) {

            // Set the rank before issue
            $rank->rankBeforeIssue = $this->groups[$this->adjacentIndex]['head']['key'];

        }

        // The issues come after the adjacent issue
        else {

            // Set the rank after issue
            $rank->rankAfterIssue = $this->groups[$this->adjacentIndex]['tail']['key'];

        }

        // Return the issue rank
        return $rank;
    }

    /////////////////////////
    //* Static Operations *//
    /////////////////////////
    /**
     * Executes the ranking operations to sort the old list into the new list.
     *
     * @param  array  $oldOrder
     * @param  array  $newOrder
     * @param  array  $subtasks
     *
     * @return void
     */
    public static function execute($oldOrder, $newOrder, $subtasks = [])
    {
        // Calculate the ranking operations
        $operations = static::calculate($oldOrder, $newOrder, $subtasks);

        // Perform each operation
        foreach($operations as $operation) {
            $operation->perform();
        }
    }

    /**
     * Creates and returns the ranking operations to sort the old list into the new list.
     *
     * @param  array  $oldOrder
     * @param  array  $newOrder
     * @param  array  $subtasks
     *
     * @return array
     */
    public static function calculate($oldOrder, $newOrder, $subtasks = [])
    {
        // We can simplify this problem by grouping the old order into
        // groups of issues that are already in the new order. We'll
        // use relative order, as this will act as a linked list.

        // Determine the issue groups
        $groups = static::getRankingGroups($oldOrder, $newOrder, $subtasks);

        // Calculate the ranking operations for the groups
        return static::calculateForGroups($groups, $subtasks);
    }

    /**
     * Creates and returns the ranking operations to sort the specified groups.
     *
     * @param  array  $groups
     * @param  array  $subtasks
     *
     * @return array
     *
     * @throws \RuntimeException
     */
    public static function calculateForGroups($groups, $subtasks = [])
    {
        // Create a new search algorithm
        $algorithm = static::newSearchAlgorithm([
            'move' => null,
            'groups' => $groups
        ], $subtasks);

        // Solve the algorithm
        $solution = $algorithm->solve();

        // If the algorithm failed, throw an exception
        if($solution === false) {
            throw new RuntimeException("Unable to calculate ranking operations. An invalid ranking order was likely used.");
        }

        // Determine the ranking operations
        $operations = array_column($solution, 'move');

        // Return the operations
        return $operations;
    }

    /**
     * Creates and returns the state search algorithm.
     *
     * @param  array  $initial
     * @param  array  $subtasks
     *
     * @return \App\Support\Astar\Algorithm
     */
    public static function newSearchAlgorithm($initial, $subtasks = [])
    {
        // Create a new search algorithm
        $algorithm = new Algorithm;

        // Set the key resolver
        $algorithm->setKeyResolver(function($state) {
            return static::getGroupArrangementIdentifier($state['groups']);
        });

        // Set the cost resolver
        $algorithm->setEstimatedCostResolver(function($state) {
            return static::getGroupArrangementHeuristic($state['groups']);
        });

        // Determine the total number of issues
        $total = array_reduce($initial['groups'], function($total, $group) {
            return $total + count($group['issues']);
        }, 0);

        // Set the move resolver
        $algorithm->setMoveResolver(function($state, $depth) use ($subtasks, $total) {

            // Determine the available operations
            $moves = $depth <= $total
                ? static::getGroupArrangementAvailableOperations($state['groups'], $subtasks)
                : [];

            // Cast each operation into a move
            return array_map(function($move) {
                return [
                    'cost' => $move->getAlgorithmMoveCost(),
                    'state' => [
                        'move' => $move,
                        'groups' => $move->getSimulatedResult()
                    ]
                ];
            }, $moves);

        });

        // Set the initial state
        $algorithm->setInitialState($initial);

        // Return the algorithm
        return $algorithm;
    }

    /**
     * Returns the group arrangement identifier for the specified groups.
     *
     * @param  array  $groups
     *
     * @return string
     */
    public static function getGroupArrangementIdentifier($groups)
    {
        // Reduce the groups to an identifier
        return json_encode(array_reduce($groups, function($identifier, $group) {

            // Determine the individual identifier for the group
            $individual = $group['order'] . ';' . $group['head']['key'] . ':' . $group['tail']['key'] . ';' . count($group['issues']);

            // Add the individual identifier to the list
            $identifier[] = $individual;

            // Return the new identifier
            return $identifier;

        }, []));
    }

    /**
     * Returns the available ranking operations that could be used on the specified groups.
     *
     * @param  array  $groups
     * @param  array  $subtasks
     *
     * @return array
     */
    public static function getGroupArrangementAvailableOperations($groups, $subtasks = [])
    {
        // Determine the movable groups
        $movable = static::getGroupArrangementMoveableIndexes($groups, $subtasks);

        // If there are no movable groups, then there are no available operations
        if(empty($movable)) {
            return [];
        }

        // From the set of movable groups, we can generate the total set
        // of moves. This will essentially be moving any group to be
        // adjacent to any other group. The search space is huge.

        // Determine the lowest group that can be ordered, and set that as the minimum
        $minimum = min($movable);

        // Determine the greatest group that can be ordered, and set that as the maximum
        $maximum = max($movable);

        // Initialize the set of moves
        $moves = [];

        // Iterate through each moveable index
        foreach($movable as $index) {

            // Determine the intended order
            $order = $groups[$index]['order'];

            // Each out of order group has two places where it can go,
            // being after its correct previous group or before its
            // correct subsequent group. No global positioning.

            // Intialize the previous and subsequent group indexes
            $previous = null;
            $next = null;

            // Iterate through each group
            foreach($groups as $position => $group) {

                // Check for the previous group
                if(is_null($previous) && $group['order'] == $order - 1) {
                    $previous = $position;
                }

                // Check for the next group
                else if(is_null($next) && $group['order'] == $order + 1) {
                    $next = $position;
                }

                // If both positions have been found, we can stop searching
                if(!is_null($previous) && !is_null($next)) {
                    break;
                }

            }

            // Due to how subtasks are unusually ranked, they cannot be used
            // as adjacent issues. If the previous or next issue ends up
            // being a subtask, we'll have to find another way around.

            // Clear the previous and/or next issues if they're subtasks
            $previous = !is_null($previous) ? (!in_array($groups[$previous]['tail']['key'], $subtasks) ? $previous : null) : null;
            $next = !is_null($next) ? (!in_array($groups[$next]['head']['key'], $subtasks) ? $next : null) : null;

            // Create the "before" and "after" ranking operations
            $before = !is_null($next) ? new static($groups, $index, static::RELATION_BEFORE, $next) : null;
            $after = !is_null($previous) ? new static($groups, $index, static::RELATION_AFTER, $previous) : null;

            // Add the "before" operation to the move list
            if(!is_null($before)) {
                $moves[$before->getKey()] = $before;
            }

            // Since both operations will have the same cost, if we're able to
            // do one, we do not need to include the other. This means that
            // we if included the "before" operation, we'll skip "after".

            // Add the "after" operation to the move list
            if(!is_null($after) && is_null($before)) {
                $moves[$after->getKey()] = $after;
            }

        }

        // Return the list of moves
        return $moves;
    }

    /**
     * Returns the groups that can be moved by an operation.
     *
     * @param  array  $groups
     * @param  array  $subtasks
     *
     * @return array
     */
    public static function getGroupArrangementMoveableIndexes($groups, $subtasks = [])
    {
        // The available operations will include moving groups that are
        // not in the correct order, but won't include groups whose
        // issue count exceeds the maximum for moving. Easy-ish.

        // Initialize the list of moveable groups
        $movable = [];

        // Determine the count of groups
        $count = count($groups);

        // Iterate through each group
        foreach($groups as $index => $group) {

            // If the group exceeds the maximum operation size, it cannot be moved
            if(count($group['issues']) > static::MAXIMUM_OPERATION_SIZE) {
                continue;
            }

            // Check for the first group
            if($index == 0) {

                // If the first group is meant to be first, it shouldn't be moved
                if($group['order'] == 0) {
                    continue;
                }

            }

            // Check for the last group
            else if($index == $count - 1) {

                // If the last group is meant to be last, it shouldn't be moved
                if($group['order'] == $count - 1) {
                    continue;
                }

            }

            // The group is somewhere in the middle
            else {

                // Determine the neighboring groups
                $previous = $groups[$index - 1];
                $next = $groups[$index + 1];

                // If the group is already in order (in relation to the groups around it), it shouldn't be moved
                if($previous['order'] == $group['order'] - 1 && $next['order'] == $group['order'] + 1) {
                    continue;
                }

            }

            // The group can be moved
            $movable[] = $index;

        }

        // Unfortunately, subtasks behave too strangely to be able to
        // move them around. Any group that contains a subtask is
        // one that we'll have to keep still and dance around.

        // Remove indexes that contain subtasks (unless they're all subtasks)
        $movable = array_filter($movable, function($index) use ($groups, $subtasks) {
            return ($count = count(array_intersect($groups[$index]['issues'], $subtasks))) == 0 || $count == count($groups[$index]['issues']);
        });

        // Return the movable group indexes
        return $movable;
    }

    /**
     * Returns the heuristic cost of the specified group arrangement.
     *
     * @param  array  $groups
     *
     * @return float
     */
    public static function getGroupArrangementHeuristic($groups)
    {
        // Determine the movable group indexes
        $movable = static::getGroupArrangementMoveableIndexes($groups);

        // Initialize the cost
        $cost = 0;

        // Add the group cost for each group that can be moved
        foreach($movable as $index) {
            $cost += static::COST_BASIS + count($groups[$index]['issues']) * static::COST_PER_ISSUE;
        }

        // Return the cost
        return $cost;
    }

    /**
     * Returns the ranking groups that need to be sorted based on the specified old and new order.
     *
     * @param  array  $oldOrder
     * @param  array  $newOrder
     * @param  array  $subtasks
     *
     * @return array
     */
    public static function getRankingGroups($oldOrder, $newOrder, $subtasks = [])
    {
        // We're going to be forming a linked list of linked lists. The
        // first thing that we need is to know the previous and next
        // issue for each individual issue, so that we can chain.

        // Determine the before and after for each order
        $oldOrder = static::getBeforeAndAfter($oldOrder);
        $newOrder = static::getBeforeAndAfter($newOrder);

        // Initialize the list of groups
        $groups = [];

        // Initialize the first group
        $group = [];

        // Iterate through the old order
        foreach($oldOrder as $key => $issue) {

            // The current issue and new issue will have the same key, but
            // may have different links. We're going to walk through the
            // old list, and create chained groups from the new order.

            // Determine the new issue
            $newIssue = $newOrder[$issue['key']];

            // Check if the current group doesn't have a head
            if(!isset($group['head'])) {

                // Initialize the group with a single issue
                $group['head'] = $newIssue;
                $group['issues'] = [$key];
                $group['tail'] = $newIssue;
                $group['is_subtasks'] = in_array($key, $subtasks);

                // Skip to the next issue
                continue;

            }

            // Determine the tail issue
            $tail = $group['tail'];

            // If the next issue links to the tail, we can expand the group.
            // However, we want to also group subtasks together, so if we
            // started with a subtask, the next issue must also be one.

            // Determine whether or not the next issue links to the tail
            $linkable = $newOrder[$tail['key']]['after'] == $key;

            // Determine whether or not the next issue is a subtask
            $isSubtask = in_array($key, $subtasks);

            // If the next issue can be linked, expand the group
            if($linkable && $group['is_subtasks'] == $isSubtask) {

                // Add the issue to the group and move the tail
                $group['issues'][] = $key;
                $group['tail'] = $newIssue;

            }

            // Otherwise, we need to start a new group
            else {

                // Add the group to the list of groups
                $groups[] = $group;

                // Initialize the next group
                $group = [
                    'head' => $newIssue,
                    'issues' => [$key],
                    'tail' => $newIssue,
                    'is_subtasks' => in_array($key, $subtasks)
                ];

            }

        }

        // Add the final group to the list of groups
        $groups[] = $group;

        // If an issue is moved out of a group, since we only tracked
        // one group at a time, the group would technically be split
        // in two. We should find connecting groups and link them.

        // Link connecting groups
        $groups = static::mergeAdjacentGroups($groups);

        // Determine the group order based on the head index
        $ordered = collect($groups)->sortBy('head.index')->values()->all();

        // Assign the intended order to each group
        foreach($ordered as $index => $order) {

            // Iterate through each group
            foreach($groups as &$group) {

                // Make sure the head matches
                if($group['head']['key'] != $order['head']['key']) {
                    continue;
                }

                // Assign the group order
                $group['order'] = $index;

            }

        }

        // Return the groups
        return $groups;
    }

    /**
     * Merges groups that are adjacent to each other.
     *
     * @param  array  $groups
     *
     * @return array
     */
    public static function mergeAdjacentGroups($groups)
    {
        // Link connecting groups
        return array_reduce($groups, function($groups, $group) {

            // If we don't have any groups yet, add one
            if(empty($groups)) {
                return [$group];
            }

            // Determine the last group
            $last = array_pop($groups);

            // Check if the next group should be linked
            if($last['tail']['after'] == $group['head']['key'] && $group['is_subtasks'] == $last['is_subtasks']) {

                // Merge the two groups
                $group = [
                    'head' => $last['head'],
                    'issues' => array_merge($last['issues'], $group['issues']),
                    'tail' => $group['tail'],
                    'is_subtasks' => $group['is_subtasks']
                ];

                // Add the merged group in
                $groups[] = $group;

            }

            // Otherwise, the next group should be separate
            else {

                // Add both groups separately
                $groups[] = $last;
                $groups[] = $group;

            }

            // Return the list of groups
            return $groups;

        }, []);
    }

    /**
     * Returns the before and after pairing for each order.
     *
     * @param  array  $order
     *
     * @return array
     */
    public static function getBeforeAndAfter($order)
    {
        // Expand each element into a key/value pair
        $issues = array_map(function($key) {
            return compact('key');
        }, $order);

        // Add the before and after entries
        foreach($issues as $index => &$issue) {

            // Add the "before" entry
            $issue['before'] = isset($issues[$index - 1])
                ? $issues[$index - 1]['key']
                : null;

            // Add the "after" entry
            $issue['after'] = isset($issues[$index + 1])
                ? $issues[$index + 1]['key']
                : null;

            // Add the "index" entry
            $issue['index'] = $index;

        }

        // Key the issues by their key
        $issues = array_combine(array_column($issues, 'key'), array_values($issues));

        // Return the issues
        return $issues;
    }
}