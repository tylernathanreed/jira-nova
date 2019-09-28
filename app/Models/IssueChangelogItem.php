<?php

namespace App\Models;

use DB;

class IssueChangelogItem extends Model
{
    /////////////////
    //* Constants *//
    /////////////////
    /**
     * The item field name constants.
     *
     * @var string
     */
    const FIELD_STATUS = 'status';
    const FIELD_ORIGINAL_ESTIMATE = 'timeoriginalestimate';

    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    ///////////////
    //* Queries *//
    ///////////////
    /**
     * Creates and returns a new status transition query.
     *
     * @param  array  $options
     *
     * @option  {string|array}  "only_to"
     * @option  {string|array}  "except_to"
     * @option  {string|array}  "only_from"
     * @option  {string|array}  "except_from"
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newStatusTransitionQuery($options = [])
    {
        // Create a new query
        $query = (new static)->newQuery();

        // Only look at status transitions
        $query->where('item_field_name', '=', static::FIELD_STATUS);

        // Filter using the "only_to" option, if provided
        if(!empty($onlyTo = (array) ($options['only_to'] ?? []))) {
            $query->whereIn('item_to', $onlyTo);
        }

        // Filter using the "except_to" option, if provided
        if(!empty($exceptTo = (array) ($options['except_to'] ?? []))) {
            $query->whereNotIn('item_to', $exceptTo);
        }

        // Filter using the "only_from" option, if provided
        if(!empty($onlyFrom = (array) ($options['only_from'] ?? []))) {
            $query->whereIn('item_from', $onlyFrom);
        }

        // Filter using the "except_from" option, if provided
        if(!empty($exceptFrom = (array) ($options['except_from'] ?? []))) {
            $query->whereNotIn('item_from', $exceptFrom);
        }

        // Return the query
        return $query;
    }

    /**
     * Creates and returns a new status commitments query.
     *
     * @param  string|array  $statuses
     * @param  array         $options
     *
     * @option  {array}         "range"
     * @option  {boolean|null}  "kept"
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newStatusCommitmentsQuery($statuses, $options = [])
    {
        // Make sure the statuses variable is an array
        $statuses = (array) $statuses;

        // Determine the range
        $range = $options['range'] ?? [carbon('2016-01-01'), carbon()];

        // Create a new issue query
        $query = (new Issue)->newQuery();

        // Make sure the issue has a commitment
        $query->whereNotNull('issues.due_date');

        // Make sure the commitment was set within the time range
        $query->whereBetween('issues.due_date', $range);

        // Use a nested "where" clause
        $query->where(function($query) use ($statuses, $range) {

            // We are going to be looking for issues that or either in
            // the given status, or has been in the specified status
            // within the specified timeframe (if one was given).

            // If the issue is in the given status, then the commitment is in scope
            $query->whereIn('issues.status_name', $statuses);

            // If the issue was in the given status within the specified range, the commitment is in scope
            $query->orWhereHas('changelogs', function($query) use ($statuses, $range) {

                // Make sure the change occurred within the time range
                $query->whereBetween('issue_changelogs.created_at', $range);

                // Make sure the change was to transition from one of the given statuses
                $query->whereHas('items', function($query) use ($statuses) {

                    // Only look at status transitions
                    $query->where('issue_changelog_items.item_field_name', '=', static::FIELD_STATUS);

                    // Filter by the given statuses
                    $query->whereIn('issue_changelog_items.item_from', $statuses);

                });

            });

        });

        // Check if the commitment needs to be kept
        if($options['kept'] ?? null) {

            // If the commitment needs to be kept, then the issue cannot
            // be in any of the given statuses, it must have left the
            // status prior to the due date, and not have reentered.

            // Make sure the issue is no longer in any of the given statuses
            $query->whereNotIn('issues.status_name', $statuses);

            // Make sure the issue transitioned out of the status prior to the due date
            $query->whereHas('changelogs', function($query) use ($statuses) {

                // Make sure the change was on or prior to the due date
                $query->whereColumn('issue_changelogs.created_at', '<=', 'issues.due_date');

                // Make sure the change was a status transition out of the given status
                $query->whereHas('items', function($query) use ($statuses) {

                    // Only look at status transitions
                    $query->where('issue_changelog_items.item_field_name', '=', static::FIELD_STATUS);

                    // Make sure the issue left the status
                    $query->whereIn('issue_changelog_items.item_from', $statuses);

                    // Make sure the issue status didn't just move around in the same group
                    $query->whereNotIn('issue_changelog_items.item_to', $statuses);

                });

            });

            // Make sure the issue didn't reenter any of the given statuses after the due date
            $query->whereDoesntHave('changelogs', function($query) use ($statuses) {

                // Filter to changes made after the due date
                $query->whereColumn('issue_changelogs.created_at', '>', 'issues.due_date');

                // Filter to changes that were status transitions back into the given statuses
                $query->whereHas('items', function($query) use ($statuses) {

                    // Only look at status transitions
                    $query->where('issue_changelog_items.item_field_name', '=', static::FIELD_STATUS);

                    // Filter to changes back into the given statuses
                    $query->whereIn('issue_changelog_items.item_to', $statuses);

                });

            });

        }

        // Return the query
        return $query;
    }

    /**
     * Creates and returns a new resolution date query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newResolutionDateQuery()
    {
        // Create a new issue query
        $query = (new Issue)->newQuery();

        // Create a new resolution date subquery
        $subquery = $this->newResolutionDateSubquery();

        // Join into the subquery
        $query->joinSub($subquery, 'resolutions', function($join) {
            $join->on('resolutions.issue_id', '=', 'issues.id');
        });

        // Select the issue columns and the resolution date
        $query->select([
            'issues.*',
            'resolutions.resolved_at'
        ]);

        // Return the query
        return $query;
    }

    /**
     * Creates and returns a new resolution date subquery.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newResolutionDateSubquery()
    {
        // Create a new issue query
        $query = (new Issue)->newQuery();

        // Only look at completed issues
        $query->complete();

        // Join into issue changelog items
        $query->joinRelation('changelogs.items', function($join) {

            // Only look at status changes
            $join->where('issue_changelog_items.item_field_name', '=', static::FIELD_STATUS);

            // Only look at transitions to the current status
            $join->whereColumn('issue_changelog_items.item_to', '=', 'issues.status_name');

        });

        // Group by the issue id
        $query->groupBy('issues.id');

        // Select the issue and the most recent status transition date
        $query->select([
            'issues.id as issue_id',
            DB::raw('max(issue_changelogs.created_at) as resolved_at')
        ]);

        // Return the query
        return $query;
    }

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the issue that this changelog belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function changelog()
    {
        return $this->belongsTo(IssueChangelog::class, 'issue_changelog_id');
    }
}
