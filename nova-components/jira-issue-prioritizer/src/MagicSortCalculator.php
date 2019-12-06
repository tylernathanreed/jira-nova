<?php

namespace NovaComponents\JiraIssuePrioritizer;

use App\Models\Issue;

class MagicSortCalculator
{
    /**
     * Calcuates the magic ordering of the specified issues.
     *
     * @param  array  $issues
     *
     * @return array
     */
    public static function calculate(array $issues)
    {
        // Determine the sorting criteria
        $criteria = static::getSortingCriteria($issues);

        // Create a new sorted issues collection
        $sorted = (new SortedIssues($issues, $criteria))->toBase();

        // $sorted->pluck('key')->dump();

        // Return the issues by their key
        return $sorted->pluck('key')->toArray();
    }

    /**
     * Returns the sorting criteria for the specified issues.
     *
     * @param  array  $issues
     *
     * @return array
     */
    public static function getSortingCriteria(array $issues)
    {
        // Convert the issues to an eloquent collection
        $issues = (new Issue)->newCollection($issues);

        // Eager load relationships
        $issues->load('labels');

        // Determine the block relations
        $blocks = Issue::getBlockRelationsFromJiraIssues($issues)['blocks'];

        // Return the list of sortable properties for each issue
        return $issues->map(function($issue) use ($blocks) {
            return [
                'key' => $issue->key,
                'assignee' => $issue->assignee_key,
                'remaining' => $issue->estimate_remaining,
                'focus' => $issue->focus,
                'due' => optional($issue->getDueDate())->toDateString(),
                'properties' => static::getSortableProperties($issue),
                'blocks' => $blocks[$issue['key']] ?? []
            ];
        })->all();
    }

    /**
     * Returns the sortable properties for the specified issue.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $issue
     *
     * @return array
     */
    public static function getSortableProperties($issue)
    {
        // Return the sortable properties in the order they should be sorted
        return [

            // Escalation Y/N
            'escalation' => -1 * ($issue->priority_name == 'Highest' || $issue->hasLabel('Executive')),

            // Past Due Date
            'past_due_date' => $issue->isPastDue() ? $issue->getDueDate()->toDateString() : null,

            // Testing Failed Y/N
            'is_testing_failed' => -1 * ($issue->status_name == 'Testing Failed'),

            // In Development Y/N
            'is_in_development' => -1 * ($issue->status_name == 'In Development'),

            // Stack Rank
            'stack_rank' => $issue->stack_rank,

            // Client Rank (TBD)
            'client_rank' => $issue->client_stack_rank,

            // Has Epic Y/N
            'has_epic' => -1 * !is_null($issue->epic_name),

            // Week Index
            'week_index' => $issue->getWeekLabelIndex(),

            // Priority Index
            'priority_index' => $issue->getPriorityIndex(),

            // Issue Type Index
            'issue_type_index' => $issue->getTypeIndex(),

            // Due Date
            'due_date' => !is_null($date = $issue->getDueDate()) ? $date->toDateString() : null,

            // Entry Date
            'entry_date' => $issue->entry_date->toDateString(),

            // Jira Key
            'key' => $issue->key

        ];

    }
}