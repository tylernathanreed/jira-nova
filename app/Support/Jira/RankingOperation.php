<?php

namespace App\Support\Jira;

use App\Support\Astar\Algorithm;

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

        // Return the result
        return $result;
    }

    /////////////////////////
    //* Static Operations *//
    /////////////////////////
    /**
     * Executes the ranking operations to sort the old list into the new list.
     *
     * @param  array  $oldOrder
     * @param  array  $newOrder
     *
     * @return void
     */
    public static function execute($oldOrder, $newOrder)
    {
        // Calculate the ranking operations
        $operations = static::calculate($oldOrder, $newOrder);

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
     *
     * @return array
     */
    public static function calculate($oldOrder, $newOrder)
    {
        // We can simplify this problem by grouping the old order into
        // groups of issues that are already in the new order. We'll
        // use relative order, as this will act as a linked list.

        // Determine the issue groups
        $groups = static::getRankingGroups($oldOrder, $newOrder);

        // Calculate the ranking operations for the groups
        return static::calculateForGroups($groups);
    }

    /**
     * Creates and returns the ranking operations to sort the specified groups.
     *
     * @param  array  $groups
     *
     * @return array
     */
    public static function calculateForGroups($groups)
    {
        // Create a new search algorithm
        $algorithm = static::newSearchAlgorithm([
            'move' => null,
            'groups' => $groups
        ]);

        // Solve the algorithm
        $solution = $algorithm->solve();

        dd(compact('solution'));

    }

    /**
     * Creates and returns the state search algorithm.
     *
     * @param  array  $initial
     *
     * @return \App\Support\Astar\Algorithm
     */
    public static function newSearchAlgorithm($initial)
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

        // Set the move resolver
        $algorithm->setMoveResolver(function($state) {

            // Determine the available operations
            $moves = static::getGroupArrangementAvailableOperations($state['groups']);

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
     *
     * @return array
     */
    public static function getGroupArrangementAvailableOperations($groups)
    {
        // Determine the movable groups
        $movable = static::getGroupArrangementMoveableIndexes($groups);

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

            // Create the "before" and "after" ranking operations
            $before = new static($groups, $index, static::RELATION_BEFORE, $next);
            $after = new static($groups, $index, static::RELATION_AFTER, $previous);

            // Add the moves to the list
            $moves[$before->getKey()] = $before;
            $moves[$after->getKey()] = $after;

        }

        // Return the list of moves
        return $moves;
    }

    /**
     * Returns the groups that can be moved by an operation.
     *
     * @param  array  $groups
     *
     * @return array
     */
    public static function getGroupArrangementMoveableIndexes($groups)
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
     *
     * @return array
     */
    public static function getRankingGroups($oldOrder, $newOrder)
    {
        // Determine the before and after for each order
        $oldOrder = static::getBeforeAndAfter($oldOrder);
        $newOrder = static::getBeforeAndAfter($newOrder);

        // Initialize the list of groups
        $groups = [];

        // Initialize the first group
        $group = [];

        // Iterate through the old order
        foreach($oldOrder as $key => $issue) {

            // Determine the new issue
            $newIssue = $newOrder[$issue['key']];

            // Check if the current group doesn't have a head
            if(!isset($group['head'])) {

                // Initialize the group with a single issue
                $group['head'] = $newIssue;
                $group['issues'] = [$key];
                $group['tail'] = $newIssue;

                // Skip to the next issue
                continue;

            }

            // Determine the tail issue
            $tail = $group['tail'];

            // If the next issue links to the tail, then expand the group
            if($newOrder[$tail['key']]['after'] == $key) {

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
                    'tail' => $newIssue
                ];

            }

        }

        // Add the final group to the list of groups
        $groups[] = $group;

        // If an issue is moved out of a group, since we only tracked
        // one group at a time, the group would technically be split
        // in two. We should find connecting groups and link them.

        // Link connecting groups
        $groups = array_reduce($groups, function($groups, $group) {

            // If we don't have any groups yet, add one
            if(empty($groups)) {
                return [$group];
            }

            // Determine the last group
            $last = array_pop($groups);

            // Check if the next group should be linked
            if($last['tail']['after'] == $group['head']['key']) {

                // Merge the two groups
                $group = [
                    'head' => $last['head'],
                    'issues' => array_merge($last['issues'], $group['issues']),
                    'tail' => $group['tail']
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