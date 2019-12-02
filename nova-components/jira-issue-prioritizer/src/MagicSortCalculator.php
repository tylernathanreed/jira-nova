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
    public static function calculate($issues)
    {
        // Convert the issues to a sorted collection
        $sorted = static::getSortedCollection($issues);

        // Return the issues by their key
        return $sorted->pluck('key')->toArray();
    }

    /**
     * Returns a sorted collection based on the given records.
     *
     * @param  array  $records
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getSortedCollection($records)
    {
        // Convert the list of records into a collection
        $records = (new Issue)->newCollection($records);

        // The first step to magic sort is to split the records into two
        // groups: one with commitments, and one without. We'll order
        // them the same way, and then eventually merge the lists.

        // Split the groups into commitments and backlog items
        $groups = $records->groupBy(function($record) {
            return !is_null($record->getDueDate()) ? 'commitments' : 'backlog';
        });

        // Now that the records have been grouped, we are going to sort
        // each group by a predefined set of criteria. This will not
        // account for due dates and such, as that comes later.

        // Sort the groups
        $groups = $groups->map(function($group) {
            return static::getSimpleSortedCollection($group);
        });

        // With the groups separated and sorted, then final challenge is
        // to zipper the groups together. We'll do this by calculating
        // estimates, and slotting in backlog items until delinquent.

        // Initialize the index
        $index = 0;

        // Initialize the list of sorted records
        $sorted = $groups['commitments']->map(function($record) use (&$index) {
            return [
                'key' => $record->key,
                'assignee' => $record->assignee_key,
                'remaining' => $record->estimate_remaining,
                'focus' => $record->focus,
                'due' => optional($record->getDueDate())->toDateString(),
                'order' => $index++
            ];
        });

        // Calculate the initial set of estimates
        $sorted = static::assignEstimates($sorted);

        // Intialize the list of unsorted records
        $unsorted = $groups['backlog']->map(function($record) {
            return [
                'key' => $record->key,
                'assignee' => $record->assignee_key,
                'remaining' => $record->estimate_remaining,
                'focus' => $record->focus,
                'due' => optional($record->getDueDate())->toDateString()
            ];
        });

        // The way we are going to zipper in the backlog records will
        // involve slotting in a new record before the first on-time
        // issue after the latest delinquency. Repeat until done.

        // Initialize the zipper index
        $index = 0;

        // Loop until we're out of backlog records
        for($i = 0; count($unsorted) > 0 && $i < count($unsorted); $i++) {

            // Determine the zipper index
            $index = static::getZipperIndex($sorted, $index);

            // Determine the next unsorted item to squeeze in
            $record = $unsorted[$i];

            // Temporarily add in the unsorted item
            $temporary = $sorted::make($sorted->all());
            $temporary->splice($index + 1, 0, [$record]);

            // Reapply the ordering
            $temporary = $temporary->mapWithKeys(function($record, $order) {
                $record['order'] = $order + 1;
                return [$order => $record];
            });

            // Assign new estimates
            $temporary = static::assignEstimates($temporary);

            // Determine the new zipper index
            $temporaryIndex = static::getZipperIndex($temporary);

            // If the zipper index moved by more than one, we can't put it there
            if($temporaryIndex - $index > 1) {

                // Force the zipper index forward
                $index++;

                // Try the same record again
                $i--;
                continue;

            }

            // Commit the change
            $sorted = $temporary;

            // Force the zipper index forward
            $index++;

        }

        // Return the sorted collection
        return $sorted;
    }

    /**
     * Returns the index to zipper in the next record.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $records
     * @param  integer                                   $start
     *
     * @return integer
     */
    public static function getZipperIndex($records, $start = 0)
    {
        // The first reference point that we need is the latest delinquency
        // in the list of records. We do not want to put anything before
        // it, as that will make the delinquency worse. Let's do it.

        // Make sure we can access the list by index
        $records = $records->values();

        // Initialize the delinquent index
        $delinquentIndex = null;

        // Search backwards through the list for the first delinquency
        for($i = count($records) - 1; $i >= $start; $i--) {

            // Determine the next potential delinquency
            $candidate = $records[$i];

            // In terms of delinquency, we're going to consider same-day
            // completions as delinquent, seeing as we wouldn't want
            // to put anything before it, as that would delay it.

            // If the candidate is not delinquent, skip it
            if($candidate['due'] > $candidate['estimate'] || is_null($candidate['due'])) {
                continue;
            }

            // Mark the delinquent index and bail
            $delinquentIndex = $i;
            break;

        }

        // If we found a delinquent index, start after it
        if(!is_null($delinquentIndex)) {
            return $delinquentIndex + 1;
        }

        // Otherwise, we can start at the beginning of the list
        return $start;
    }

    /**
     * Assigns an estimated completion date to each of the specified records.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $records
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected static function assignEstimates($records)
    {
        // Calculate the initial set of estimates
        $estimates = $records->groupBy('assignee')->mapWithKeys(function($group, $assignee) {
            return EstimateCalculator::calculate($assignee, $group->all());
        })->pluck('estimate', 'key');

        // Add the estimates to the list of sorted records
        return $records->map(function($record) use ($estimates) {

            $record['estimate'] = $estimates[$record['key']] ?? null;
            return $record;

        });
    }

    /**
     * Returns a simple sorted collection based on the given records.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $records
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getSimpleSortedCollection($records)
    {
        // Convert the records to a sortable collection
        $sortable = static::getSortableCollection($records);

        // Sort the collection
        $sorted = $sortable->sort(function($a, $b) {

            // If the first record blocks the second record, the first record goes first
            if(in_array($b['record']['key'], $a['blocks'])) {
                return -1;
            }

            // If the second record blocks the first record, the second record goes first
            if(in_array($a['record']['key'], $b['blocks'])) {
                return 1;
            }

            // Iterate through the properties
            foreach($a['properties'] as $key => $value) {

                // Determine the respective value
                $other = $b['properties'][$key];

                // If exclusively one value is null, that record yields
                if(is_null($value) != is_null($other)) {
                    return is_null($value) ? 1 : -1;
                }

                // If the values do not match, the lesser value wins
                if($value != $other) {
                    return $value <=> $other;
                }

            }

            // All values tied
            return 0;

        });

        // Return the sorted records
        return $sorted->pluck('record');
    }

    /**
     * Returns a sortable collection based on the given records.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $records
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getSortableCollection($records)
    {
        // Eager load relationships
        $records->load('labels');

        // Determine the block relations
        $blocks = Issue::getBlockRelationsFromJiraIssues($records)['blocks'];

        // Return the list of sortable properties for each record
        return $records->map(function($record) use ($blocks) {
            return [
                'record' => $record,
                'properties' => static::getSortableProperties($record),
                'blocks' => $blocks[$record['key']] ?? []
            ];
        });
    }

    /**
     * Returns the sortable properties for the specified records.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $record
     *
     * @return array
     */
    public static function getSortableProperties($record)
    {
        // Return the sortable properties in the order they should be sorted
        return [

            // Escalation Y/N
            'escalation' => -1 * ($record->priority_name == 'Highest' || $record->hasLabel('Executive')),

            // Past Due Date
            'past_due_date' => $record->isPastDue() ? $record->getDueDate()->toDateString() : null,

            // Testing Failed Y/N
            'is_testing_failed' => -1 * ($record->status_name == 'Testing Failed'),

            // In Development Y/N
            'is_in_development' => -1 * ($record->status_name == 'In Development'),

            // Due Date
            'due_date' => !is_null($date = $record->getDueDate()) ? $date->toDateString() : null,

            // Stack Rank
            'stack_rank' => $record->stack_rank,

            // Client Rank (TBD)
            'client_rank' => $record->client_stack_rank,

            // Has Epic Y/N
            'has_epic' => -1 * !is_null($record->epic_name),

            // Week Index
            'week_index' => $record->getWeekLabelIndex(),

            // Priority Index
            'priority_index' => $record->getPriorityIndex(),

            // Issue Type Index
            'issue_type_index' => $record->getTypeIndex(),

            // Entry Date
            'entry_date' => $record->entry_date->toDateString(),

            // Weekly Commitment
            'weekly_commitment' => !is_null($date = $record->getWeekCommitmentDueDate()) ? $date->toDateString() : null,

            // Primary Key
            'key' => $record->getKey()

        ];

    }
}