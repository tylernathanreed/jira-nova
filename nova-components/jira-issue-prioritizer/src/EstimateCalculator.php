<?php

namespace NovaComponents\JiraIssuePrioritizer;

use App\Models\User;
use App\Models\Issue;
use App\Models\Schedule;
use App\Models\HolidayInstance;

/*
|--------------------------------------------------------------------------
| Estimate Calculator
|--------------------------------------------------------------------------
|
| The estimate calculation will calculate the estimated completion dates
| of the provided issues based on information about the issue. This is
| useful for determining estimated delinquencies and early warnings.
|
| The following fields are required per issue to calculate an estimate:
|
|  - {string}   "key"        The unique string identifier for the issue.
|  - {integer}  "order"      The order in which the issues need to be completed.
|  - {string}   "focus"      The name of the focus group the issue is in.
|  - {integer}  "remaining"  The estimated remaining work time (in seconds) to complete the issue.
|
*/
class EstimateCalculator
{
    /**
     * Calcuates the estimated completion dates for the specified issues.
     *
     * @param  string  $assignee
     * @param  array   $issues
     *
     * @return array
     */
    public static function calculate($assignee, $issues)
    {
        // Determine the schedule for the assignee
        $schedule = static::getScheduleForAssignee($assignee);

        // Determine the schedule adjustments for the assignee
        $adjustments = static::getScheduleAdjustmentsForAssignee($assignee);

        // Determine the estimated cmopletion dates
        $estimates = static::getEstimatedCompletionDates($issues, $schedule, $adjustments);

        // Return the completion dates
        return array_map(function($issue) {
            return [
                'key' => $issue['key'],
                'estimate' => carbon($issue['new_estimated_completion_date'])->toDateString()
            ];
        }, $estimates);
    }

    /**
     * Returns the schedule for the specified assignee.
     *
     * @param  string  $assignee
     *
     * @return \App\Models\Schedule
     */
    public static function getScheduleForAssignee($assignee)
    {
        // If the assignee can be mapped to a user, return their schedule
        if(!is_null($user = User::where('jira_key', '=', $assignee)->first())) {
            return $user->getSchedule();
        }

        // Otherwise, return the default schedule
        return Schedule::getDefaultSchedule();
    }

    /**
     * Returns the schedule adjustments for the specified assignee.
     *
     * @return array
     */
    public static function getScheduleAdjustmentsForAssignee($assignee)
    {
        // Determine the user
        $user = User::where('jira_key', '=', $assignee)->first();

        // Determine the schedule for the assignee
        $schedule = static::getScheduleForAssignee($assignee);

        // Initialize the adjustments
        $adjustments = [];

        // The first adjustment that can happen is the user can take time
        // off. The time off module keeps track of the day and percent
        // of time off being used. We'll need to flip the percent.

        // Determine the time off by date
        $timeoffs = !is_null($user) ? $user->timeoffs()->where('date', '>=', carbon()->toDateString())->pluck('percent', 'date') : [];

        // Add each time off as an adjustment
        foreach($timeoffs as $date => $percent) {
            $adjustments[carbon($date)->toDateString()] = 1 - max($percent, 0);
        }

        // The second adjustment that can happen is a company holiday can
        // occur. On these dates, all users have the date off, and are
        // not expected to work. We'll adjust the schedule to do so.

        // Determine the holidays by date
        $holidays = HolidayInstance::where('observed_date', '>=', carbon()->toDateString())->pluck('observed_date');

        // Add each holiday as an adjustment
        foreach($holidays as $date) {
            $adjustments[carbon($date)->toDateString()] = 0;
        }

        // The third adjustment that we'll make is for meetings. We assume
        // that meetings aren't logged as issues, and thus we'll have to
        // consider the time commitments for each participant in them.

        // Determine the meetings by date
        $meetings = !is_null($user)
            ? $user->meetings->where('effective_date', '>=', carbon()->toDateString())->groupBy(function($meeting) {
                return $meeting->effective_date->toDateString();
            })->map(function($meetings) {
                return $meetings->sum->length_in_seconds;
            })->toArray()
            : [];

        // Add each meeting as an adjustment
        foreach($meetings as $date => $length) {

            // Determine the daily limit
            $limit = $schedule->getAllocationLimit(carbon($date)->dayOfWeek);

            // If the limit is already zero, don't bother
            if($limit <= 0) {
                continue;
            }

            // If the current allocation is already zero, don't bother
            if(($adjustments[$date] ?? 1) <= 0) {
                continue;
            }

            // Assign the adjustment
            $adjustments[$date] = max(($adjustments[$date] ?? 1) - ($length / $limit), 0);

        }

        // Return the adjustments
        return $adjustments;
    }

    /**
     * Returns the estimated completion dates for the specified issues.
     *
     * @param  array  $issues
     * @param  mixed  $schedule
     * @param  array  $adjustments
     *
     * @return array
     */
    public static function getEstimatedCompletionDates($issues, $schedule, $adjustments = [])
    {
        // Our schedule is broken down into focus times. Issues can be allocated
        // to one or more focuses, and these focus times are when we can work
        // on these issues. We ought to respect the focus in the schedule.

        // Order the issues
        $issues = collect($issues)->sortBy('order')->all();

        // Initialize the dates for each focus
        $dates = $schedule->getFirstAssignmentDatesByFocus();

        // Initialize the allocations
        $allocations = [];

        // Iterate through each issue
        foreach($issues as $index => &$issue) {

            // Determine the issue focus
            $focuses = $schedule->type == Schedule::TYPE_SIMPLE
                ? ['all']
                : (
                    $issue['focus'] == Issue::FOCUS_OTHER
                        ? [Issue::FOCUS_DEV, Issue::FOCUS_TICKET, Issue::FOCUS_OTHER]
                        : [$issue['focus']]
                );

            // Determine the remaining estimate
            $remaining = max($issue['remaining'] ?? 0, 1 * 60 * 60);

            // Since an issue on its own can take longer than a day to complete,
            // we essentially have to chip away at the remaining estimate so
            // that we can correctly spread the work effort into many days.

            // Allocate the remaining estimate in a time loop until its all gone
            while($remaining > 0) {

                // Determine the applicable focus dates
                $focusDates = array_only($dates, $focuses);

                // Determine the earliest focus date
                $date = array_reduce($focusDates, function($date, $focusDate) {
                    return is_null($date) ? $focusDate : $date->min($focusDate);
                }, null);

                // Determine the focus with that date
                $focus = array_last($focuses, function($focus) use ($focusDates, $date) {
                    return $focusDates[$focus]->eq($date);
                });

                // Determine how much time as already been allocated for the day
                $allocated = ($date->hour * 60 + $date->minute) * 60 + $date->second;

                // Determine the daily focus limit
                $limit = $schedule->getAllocationLimit($date->dayOfWeek, $focus);

                // Determine the adjustment for the date
                $adjustment = min(max($adjustments[$date->toDateString()] ?? 1, 0), 1);

                // Adjust the limit
                $limit *= $adjustment;

                // If the previous issue ended cleanly on the exact amount of allocatable
                // time, we wanted it to end on that date. However, we have to advance
                // to the next day for the next issue, otherwise we'll loop forever.

                // Check if we've run out of time for the day
                if(round($limit - $allocated) <= 0) {

                    // Advance to the next day
                    $date = $date->addDay()->startOfDay();

                    // Try again
                    continue;

                }

                // Determine how much time we can allocate for today
                $allocatable = round(min($remaining, $limit - $allocated));

                // Allocate the time
                $date = $date->addSeconds($allocatable);

                // Add the allocation to the tracking array
                $allocations[$date->toDateString()][$issue['key']] = round($allocatable / 3600, 2);

                // Reduce the remaining time by how much was allocated
                $remaining -= $allocatable;

                // If we have exceeded the daily limit, advance to the next day
                if($allocated + $allocatable > $limit) {
                    $date = $date->addDay()->startOfDay();
                }

                // Skip dates that have no allocatable time
                while($schedule->getAllocationLimit($date->dayOfWeek, $focus) <= 0 || ($adjustments[$date->toDateString()] ?? 1) <= 0) {
                    $date = $date->addDay();
                }

                // Update the tracking date
                $dates[$focus] = $date;

            }

            // Assign the estimated completion date
            $issue['new_estimated_completion_date'] = $date->toDateString();

        }

        // Dump the allocations for debugging
        // dump($allocations);

        // Return the issues
        return $issues;
    }
}