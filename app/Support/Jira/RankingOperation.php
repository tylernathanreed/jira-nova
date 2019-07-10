<?php

namespace App\Support\Jira;

class RankingOperation
{
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
        dd(compact('groups'));
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