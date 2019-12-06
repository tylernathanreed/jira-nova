<?php

namespace NovaComponents\JiraIssuePrioritizer;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class SortedIssues extends Collection
{
    /**
     * Create a new sorted issues container.
     *
     * @param  \Illuminate\Support\Collection|array  $issues
     * @param  array                                 $criteria
     *
     * @return void
     */
    public function __construct($issues, $criteria = [])
    {
        // Convert the issues to an array
        if ($issues instanceof Collection) {
            $issues = $issues->all();
        }

        // Key the criteria for faster look up
        $criteria = (new Collection($criteria))->keyBy('key')->all();

        // Sort the issues
        $this->items = $this->sortIssues($issues, $criteria);
    }

    /**
     * Sorts the specified issues by the given criteria.
     *
     * @param  array  $issues
     * @param  array  $criteria
     *
     * @return array
     */
    protected function sortIssues($issues, $criteria)
    {
        // The first step in the sorting process is to order by the given
        // properties. This is essentially a "simple sort", and ignores
        // all quirky edge cases that we'll have to handle later on.

        // Sort the issues by their properties
        $issues = $this->sortIssuesByProperties($issues, $criteria);

        // Since blocking relations are conditional, we must handle those
        // separately. We are going to only touch blocked and blocking
        // records, and we'll keep everything else in the same order.

        // Sort the issues by their blocking relations
        $issues = $this->sortIssuesByBlockingRelations($issues, $criteria);

        // The third, and possibly most complete step, will be to order
        // the issues by their estimates, so that we can minimize any
        // delinquencies based off of sorted list of issues we have.

        // Sort the issues by their estimated delinquencies
        $issues = $this->sortIssuesByEstimatedDelinquencies($issues, $criteria);

        // Return the issues
        return $issues;
    }

    /**
     * Sorts the specified issues by their criteria properties.
     *
     * @param  array  $issues
     * @param  array  $criteria
     *
     * @return array
     */
    protected function sortIssuesByProperties($issues, $criteria)
    {
        // Sort the issues by their properties
        usort($issues, function($a, $b) use ($criteria) {

            // Determine the properties of the records
            $aP = $criteria[$a->key]['properties'] ?? [];
            $bP = $criteria[$b->key]['properties'] ?? [];

            // Iterate through the properties
            foreach($aP as $key => $value) {

                // Determine the respective value
                $other = $bP[$key] ?? null;

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

        // Return the issues
        return $issues;
    }

    /**
     * Sorts the specified issues by their blocking relations.
     *
     * @param  array  $issues
     * @param  array  $criteria
     *
     * @return array
     */
    protected function sortIssuesByBlockingRelations($issues, $criteria)
    {
        // Determine the blocking relations
        $blocks = array_filter(array_combine(array_keys($criteria), array_column($criteria, 'blocks')));

        // Determine the order of the issues
        $orders = array_flip(Arr::pluck($issues, 'key'));

        // Iterate through each blocking issue
        foreach($blocks as $blockedByKey => $links) {

            // Iterate through each blocked link
            foreach($links as $blockedKey) {

                // For each iteration of this loop, we have a blocked issue
                // that has been blocked by another issue. The goal here
                // is to order the blocked by issue higher if needed.

                // If an issue blocks another issue that isn't on our list, skip it
                if(!isset($orders[$blockedKey])) {
                    continue;
                }

                // Determine the orders
                $blockedOrder = $orders[$blockedKey];
                $blockedByOrder = $orders[$blockedByKey];

                // If the order already satisfies our requirement, skip it
                if($blockedOrder > $blockedByOrder) {
                    continue;
                }

                // We are going to be as conservative as possible when moving
                // blocking issues. Since they we are prioritized correctly
                // in advanced, we'll just slot it right above the issue.

                // Move the blocking issue above the blocked issue
                $issues = $this->moveIssue($issues, $blockedByOrder, $blockedOrder);

                // Rebuild the orders
                $orders = array_flip(Arr::pluck($issues, 'key'));

            }

        }

        // Return the issues
        return $issues;
    }

    /**
     * Sorts the specified issues by their estimated delinquencies.
     *
     * @param  array  $issues
     * @param  array  $criteria
     *
     * @return array
     */
    protected function sortIssuesByEstimatedDelinquencies($issues, $criteria)
    {
        // Estimated delinquencies require both an estimate and a due date.
        // We are given the due date from the criteria, but we will have
        // to calculate the estimate ourselves. Let's initialize it.

        // Initialize the estimate dates
        $estimates = $this->calculateEstimates($issues, $criteria);

        // Initialize the commitment dates
        $commitments = array_filter(Arr::pluck($criteria, 'due', 'key'));

        // One small quirk to account for are commitments that are blocked
        // by non-commitments. If a blocking issue does not have a date
        // assigned to it, we'll the first blocked commitment instead.

        // Determine the blocking relations
        $blocks = array_filter(array_combine(array_keys($criteria), array_column($criteria, 'blocks')));

        // Iterate through each blocking relation
        foreach($blocks as $blockedByKey => $links) {

            // Try to extract a commitment from the links
            $commitment = array_reduce($links, function($due, $blockedKey) use ($commitments) {

                // Determine the alternative commitment from the blocked key
                $alternative = $commitments[$blockedKey] ?? null;

                // If the current due date is null, use the alternative
                if(is_null($due)) {
                    return $alternative;
                }

                // Otherwise, use the minimum of the two
                return min($due, $alternative);

            }, $commitments[$blockedByKey] ?? null);

            // Assign the commitment, if present
            if(!is_null($commitment)) {
                $commitments[$blockedByKey] = $commitment;
            }

        }

        // The next step is to determine 

        // dump(compact('estimates', 'commitments', 'blocks'));

        // Return the issues
        return $issues;
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

        // Before we split the records into groups, we will want to order
        // them based on off a predefined set of criteria. While we may
        // violate this order later on, it is a good place to start.

        // Determine the initial list of sorted records
        $initial = static::getSimpleSortedCollection($records)->map(function($record) {
            return [
                'key' => $record->key,
                'assignee' => $record->assignee_key,
                'remaining' => $record->estimate_remaining,
                'focus' => $record->focus,
                'due' => optional($record->getDueDate())->toDateString()
            ];
        });

        // The second step to magic sort is to split the records into two
        // groups: one with commitments, and one without. We will have
        // to zipper them back together, which may not be the same.

        // Split the groups into commitments and backlog items
        $groups = $initial->groupBy(function($record) {
            return !is_null($record['due']) ? 'commitments' : 'backlog';
        });

        // In order to zipper the groups together, we will need to use
        // the commitments as our sorted list, and zipper in our non
        // commitments in a similar way, but without delinquencies.

        // Initialize the list of sorted records
        $sorted = $groups['commitments']->sortBy('order');

        // Calculate the initial set of estimates
        $sorted = static::assignEstimates($sorted);

        // Intialize the list of unsorted records
        $unsorted = $groups['backlog']->sortBy('order');

        // The way we are going to zipper in the backlog records will
        // involve slotting in a new record before the first on-time
        // issue after the latest delinquency. Repeat until done.

        // Initialize the zipper index
        $index = 0;

        // Loop until we're out of backlog records
        for($i = 0; count($unsorted) > 0 && $i < count($unsorted); $i++) {

            // Determine the next unsorted item to squeeze in
            $record = $unsorted[$i];

            // Determine the zipper index
            $index = static::getZipperIndex($sorted, $record['order'], $index);

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
            $temporaryIndex = static::getZipperIndex($temporary, $record['order']);

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
     * @param  integer                                   $order
     * @param  integer                                   $start
     *
     * @return integer
     */
    public static function getZipperIndex($records, $order, $start = 0)
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

            // Another restriction that we're going to enforce is that just
            // because we can make a commitment non-delinquent does not
            // mean that we can violate the initial ordering clause.

            // If the candidate would be ordered higher only because there's no due date, stop it
            if($order < $candidate['order']) {

                // Mark the delinquent index and bail
                $delinquentIndex = $i;
                break;

            }

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
     * Returns a key / value array of issues and their estimates.
     *
     * @param  array  $issues
     * @param  array  $criteria
     *
     * @return array
     */
    protected function calculateEstimates($issues, $criteria)
    {
        // Initialize the order
        $order = 0;

        // Assign an order to the respective criteria
        foreach($issues as $issue) {
            $criteria[$issue->key]['order'] = $order++;
        }

        // Calculate the estimates for each assignee, then collapse the results into a key / value array
        return (new Collection($criteria))->groupBy('assignee')->mapWithKeys(function($group, $assignee) {
            return [$assignee => EstimateCalculator::calculate($assignee, $group->all())];
        })->collapse()->pluck('estimate', 'key')->all();
    }

    /**
     * Splices the specified issue into a new position and remove the old entry.
     *
     * @param  array    $issues
     * @param  integer  $from
     * @param  integer  $to
     *
     * @return array
     */
    protected function moveIssue($issues, $from, $to)
    {
        // Insert the issue into the specified index
        array_splice($issues, $to, 0, [$issues[$from]]);

        // Remove the issue from the original index
        unset($issues[$from + 1]);

        // Return the issues
        return array_values($issues);
    }
}