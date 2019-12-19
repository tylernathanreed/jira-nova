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

        // The next step is to walk through each delinquency to see if
        // there are any non-delinquencies that could be pulled down
        // and stay non-delinquent. We'll call this grab and pull.

        // Iterate through the estimates
        foreach($estimates as $key => $estimate) {

            // If the issue does not have a commitment, we can skip it
            if(is_null($commitment = ($commitments[$key] ?? null))) {
                continue;
            }

            // If the estimate is not delinquent, we can skip it
            if($estimate < $commitment) {
                continue;
            }

            // At this point, we know that we have a delinquency. The next
            // step is to see if there are any issues ahead of this one
            // that we can pull down without making things any worse.

            // Pull down any issues that won't be made delinquent by doing so
            $issues = $this->pullIssues($issues, $commitments, $estimates, $key, $criteria);

            // With the issues changed, the estimates will be different. We
            // will need to recalculate the estimates, such that the next
            // iteration will have a fresh set of data to operate with.

            // Recalculate the estimates
            $estimates = $this->calculateEstimates($issues, $criteria);

        }

        // Return the issues
        return $issues;
    }

    /**
     * Pulls the issues above the specified key that are non-delinquent in hopes to make the specified issue non-delinquent.
     *
     * @param  array   $issues
     * @param  array   $commitments
     * @param  array   $estimates
     * @param  string  $key
     * @param  array   $criteria
     *
     * @return array
     */
    protected function pullIssues($issues, $commitments, $estimates, $key, $criteria)
    {
        // The first piece of information that we'll need is an index.
        // We'll search for the issue with the given key to find it.
        // Our index will tell us what the algorithm is thinking.

        // Determine the index of the given issue
        $index = array_search($key, Arr::pluck($issues, 'key'));

        // If for whatever reason, we failed to find the index, instead
        // of failing, we'll just return the original set of issues.
        // No clue what happened, but at least we won't explode.

        // Make sure an index was found
        if($index === false) {
            return $issues;
        }

        // With the overhead out of the way, the next step is to utilize
        // the initial set of estimates. We'll walk backwards through
        // these and pull down any issues that we're allowed to do.

        // Convert the commitments and estimates into a seqential array
        $candidates = array_map(function($key) use ($estimates, $commitments) {
            return [
                'key' => $key,
                'estimate' => $estimates[$key],
                'due' => $commitments[$key] ?? null,
                'moveable' => is_null($commitments[$key] ?? null) || $commitments[$key] > $estimates[$key]
            ];
        }, array_keys($estimates));

        // Iterate backwards through the candidates
        for($i = $index - 1; $i >= 0; $i--) {

            // Determine the current candidate
            $candidate = $candidates[$i];

            // If the candidate cannot be moved, stop here
            if(!$candidate['moveable']) {
                break;
            }

            // At this point, we know that our issue is delinquent, and there's
            // a non-delinquent issue above it that we can move. If by moving
            // the issue we'd make it delinquent, then we shouldn't move it.

            // Temporarily move the candidate
            $temporary = $this->moveIssue($issues, $i, $index + 1);

            // Rebuild the estimates
            $newEstimates = $this->calculateEstimates($temporary, $criteria);

            // Determine the commitment and estimate
            $commitment = $candidate['due'];
            $estimate = $newEstimates[$candidate['key']];

            // If the change would make the moved issue delinquent, don't do it
            if(!is_null($commitment) && $commitment < $estimate) {
                break;
            }

            // Commit the change
            $issues = $temporary;
            $index--;

            // If the issue itself is no longer delinquent, we can stop
            if($newEstimates[$key] <= $commitments[$key]) {
                break;
            }

        }

        // Return the issues
        return $issues;
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
        unset($issues[$from + ($from > $to ? 1 : 0)]);

        // Return the issues
        return array_values($issues);
    }
}