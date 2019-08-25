<?php

namespace NovaComponents\JiraIssuePrioritizer;

use App\Models\User;
use App\Models\Issue;
use App\Models\Schedule;

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

        // Determine the estimated cmopletion dates
        $estimates = static::getEstimatedCompletionDates($issues, $schedule);

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
     * Returns the estimated completion dates for the specified issues.
     *
     * @param  array  $issues
     * @param  mixed  $schedule
     *
     * @return array
     */
    public static function getEstimatedCompletionDates($issues, $schedule)
    {
        // Our schedule is broken down into focus times. Issues can be allocated
        // to one or more focuses, and these focus times are when we can work
        // on these issues. We ought to respect the focus in the schedule.

        // Order the issues
        $issues = collect($issues)->sortBy('order')->all();

        // Initialize the dates for each focus
        $dates = $schedule->getFirstAssignmentDatesByFocus();

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

                // If the previous issue ended cleanly on the exact amount of allocatable
                // time, we wanted it to end on that date. However, we have to advance
                // to the next day for the next issue, otherwise we'll loop forever.

                // Check if we've run out of time for the day
                if($allocated >= $limit) {

                    // Advance to the next day
                    $date = $date->addDay()->startOfDay();

                    // Try again
                    continue;

                }

                // Determine how much time we can allocate for today
                $allocatable = min($remaining, $limit - $allocated);

                // Allocate the time
                $date = $date->addSeconds($allocatable);

                // Reduce the remaining time by how much was allocated
                $remaining -= $allocatable;

                // If we have exceeded the daily limit, advance to the next day
                if($allocated + $allocatable > $limit) {
                    $date = $date->addDay()->startOfDay();
                }

                // Skip dates that have no allocatable time
                while($schedule->getAllocationLimit($date->dayOfWeek, $focus) <= 0) {
                    $date = $date->addDay();
                }

                // Update the tracking date
                $dates[$focus] = $date;

            }

            // Assign the estimated completion date
            $issue['new_estimated_completion_date'] = $date->toDateString();

        }

        // Return the issues
        return $issues;
    }
}