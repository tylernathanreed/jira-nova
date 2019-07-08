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

            // If the current group doesn't have a head, give it one
            if(!isset($group['head'])) {
                $group['head'] = $key;
            }

            // 

        }

        dd(compact('oldOrder', 'newOrder'));

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

        }

        // Key the issues by their key
        $issues = array_combine(array_column($issues, 'key'), array_values($issues));

        // Return the issues
        return $issues;
    }
}