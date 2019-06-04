<?php

namespace App\Console\Commands;

use Jira;
use Carbon\Carbon;
use Illuminate\Console\Command;
use JiraRestApi\Issue\IssueField;

class JiraEstimate extends Command
{
    /**
     * The field constants.
     *
     * @var string
     */
    const FIELD_DUE_DATE = 'duedate';
    const FIELD_REMAINING_ESTIMATE = 'timeestimate';
    const FIELD_PRIORITY = 'priority';
    const FIELD_ISSUE_CATEGORY = 'customfield_12005';
    const FIELD_ESTIMATED_COMPLETION_DATE = 'customfield_12011';
    const FIELD_RANK = 'customfield_10119';

    /**
     * The focus constants.
     *
     * @var string
     */
    const FOCUS_DEV = 'Dev';
    const FOCUS_TICKET = 'Ticket';
    const FOCUS_OTHER = 'Other';

    /**
     * The priority constants.
     *
     * @var string
     */
    const PRIORITY_HIGHEST = 'Highest';

    /**
     * The issue category constants.
     *
     * @var string
     */
    const ISSUE_CATEGORY_DEV = 'Dev';
    const ISSUE_CATEGORY_TICKET = 'Ticket';
    const ISSUE_CATEGORY_DATA = 'Data';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jira:estimate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns due dates based on time estimated and rank order.';

    /**
     * The general weekly schedule for allocating time.
     *
     * @var array
     */
    protected static $weeklySchedule = [
        Carbon::SUNDAY    => [self::FOCUS_DEV => 0,             self::FOCUS_TICKET => 0,             self::FOCUS_OTHER => 0],
        Carbon::MONDAY    => [self::FOCUS_DEV => 4.5 * 60 * 60, self::FOCUS_TICKET => 0,             self::FOCUS_OTHER => 3.5 * 60 * 60 * 0.5],
        Carbon::TUESDAY   => [self::FOCUS_DEV => 0,             self::FOCUS_TICKET => 5 * 60 * 60,   self::FOCUS_OTHER => 3 * 60 * 60 * 0.5],
        Carbon::WEDNESDAY => [self::FOCUS_DEV => 5 * 60 * 60,   self::FOCUS_TICKET => 0,             self::FOCUS_OTHER => 3 * 60 * 60 * 0.5],
        Carbon::THURSDAY  => [self::FOCUS_DEV => 0,             self::FOCUS_TICKET => 4.5 * 60 * 60, self::FOCUS_OTHER => 3.5 * 60 * 60 * 0.5],
        Carbon::FRIDAY    => [self::FOCUS_DEV => 5 * 60 * 60,   self::FOCUS_TICKET => 0,             self::FOCUS_OTHER => 3 * 60 * 60 * 0.5],
        Carbon::SATURDAY  => [self::FOCUS_DEV => 0,             self::FOCUS_TICKET => 0,             self::FOCUS_OTHER => 0],
    ];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Determine the jira issues
        $this->info('[1/3] Searching for Jira issues...');
        $benchmark = microtime(true);
        $issues = $this->getJiraIssues();
        $this->info('[1/3] -> Found [' . count($issues) . '] Jira issues in [' . round((microtime(true) - $benchmark), 2) . '] seconds.');

        // Assign estimated completion dates to the issues
        $this->info('[2/3] Assigning estimated completion dates...');
        $benchmark = microtime(true);
        $issues = $this->assignEstimatedCompletionDates($issues);
        $this->info('[2/3] -> Assigned estimated completion dates in [' . round((microtime(true) - $benchmark), 2) . '] seconds.');

        // Update the issues in jira
        $this->info('[3/3] Updating Jira issues...');
        $benchmark = microtime(true);
        $count = $this->updateJiraIssues($issues);
        $this->info('[3/3] -> Updated [' . $count . '] Jira issues in [' . round((microtime(true) - $benchmark), 2) . '] seconds.');
    }

    /**
     * Updates the specified issues in jira.
     *
     * @param  array  $issues
     *
     * @return integer
     */
    public function updateJiraIssues($issues)
    {
        // Initialize the count of updated jira issues
        $count = 0;

        // Iterate through each issue
        foreach($issues as $issue) {

            // If the issue doesn't need to be updated, skip it
            if($issue['new_estimated_completion_date'] == $issue['old_estimated_completion_date']) {
                continue;
            }

            // Update the issue
            Jira::issues()->update($issue['key'], (new IssueField(true))->addCustomField(static::FIELD_ESTIMATED_COMPLETION_DATE, $issue['new_estimated_completion_date']));

            // Increase the count
            $count++;

        }

        // Return the count of updated jira issues
        return $count;
    }

    /**
     * Assigns estimated complete dates to the issues.
     *
     * @param  array  $issues
     *
     * @return array
     */
    public function assignEstimatedCompletionDates($issues)
    {
        // Our schedule is broken down into focus times. Issues can be allocated
        // to one or more focuses, and these focus times are when we can work
        // on these issues. We ought to respect the focus in the schedule.

        // Initialize the dates for each focus
        $dates = [
            static::FOCUS_DEV => $this->getFirstAssignmentDate(static::FOCUS_DEV),
            static::FOCUS_TICKET => $this->getFirstAssignmentDate(static::FOCUS_TICKET),
            static::FOCUS_OTHER => $this->getFirstAssignmentDate(static::FOCUS_OTHER)
        ];

        // Iterate through each issue
        foreach($issues as $index => &$issue) {

            // Determine the issue focus
            $focuses = $issue['priority'] == static::PRIORITY_HIGHEST
                ? [static::FOCUS_DEV, static::FOCUS_TICKET, static::FOCUS_OTHER]
                : (
                    in_array($issue['issue_category'], [static::ISSUE_CATEGORY_TICKET, static::ISSUE_CATEGORY_DATA])
                        ? [static::FOCUS_TICKET]
                        : [static::FOCUS_DEV]
                );

            // Determine the remaining estimate
            $remaining = max($issue['time_estimate'] ?? 0, 1 * 60 * 60);

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
                $limit = static::$weeklySchedule[$date->dayOfWeek][$focus];

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
                while(static::$weeklySchedule[$date->dayOfWeek][$focus] <= 0) {
                    $date = $date->addDay();
                }

            }

            // Assign the estimated completion date
            $issue['new_estimated_completion_date'] = $date->toDateString();

        }

        // Return the issues
        return $issues;
    }

    /**
     * Returns the first assignment date for the schedule.
     *
     * @param  string  $focus
     *
     * @return \Carbon\Carbon
     */
    public function getFirstAssignmentDate($focus)
    {
        // Until we have a better scheduling concept, we're going to
        // base everything off of the default schedule, and probit
        // issues from being scheduled same-day after 11:00 AM.

        // Determine the soonest we can start scheduling
        $start = Carbon::now()->lte(Carbon::parse('11 AM')) // If it's prior to 11 AM
            ? Carbon::now()->startOfDay() // Start no sooner than today
            : Carbon::now()->addDays(1)->startOfDay(); // Otherwise, start no sooner than tomorrow

        // Determine the latest we can start scheduling
        $end = Carbon::now()->addDays(8)->startOfDay(); // Start no later than a week after tomorrow

        // Determine the first date where we can start assigning due dates
        $date = array_reduce(array_keys(static::$weeklySchedule), function($date, $key) use ($start, $focus) {
            return static::$weeklySchedule[$key][$focus] <= 0 ? $date : $date->min(($thisWeek = Carbon::now()->weekday($key)->startOfDay())->gte($start) ? $thisWeek : $thisWeek->addWeek());
        }, $end);

        // Return the date
        return $date;
    }

    /**
     * Returns the jira issues for the burndown.
     *
     * @return array
     */
    public function getJiraIssues()
    {
        // Determine the search query
        $jql = $this->newBurndownJiraIssuesExpression();

        // Initialize the list of issues
        $issues = [];

        // Initialize the pagination variables
        $page = 0;
        $count = 50;

        // Loop until we're out of results
        do {

            // Determine the search results
            $results = Jira::issues()->search($jql, $page * $count, $count, [
                static::FIELD_DUE_DATE,
                static::FIELD_REMAINING_ESTIMATE,
                static::FIELD_PRIORITY,
                static::FIELD_ISSUE_CATEGORY,
                static::FIELD_ESTIMATED_COMPLETION_DATE,
                static::FIELD_RANK
            ], [], false);

            // Remap the issues to reference what we need
            $results = array_map(function($issue) {
                return [
                    'key' => $issue->key,
                    'due_date' => $issue->fields->{static::FIELD_DUE_DATE},
                    'time_estimate' => $issue->fields->{static::FIELD_REMAINING_ESTIMATE},
                    'old_estimated_completion_date' => $issue->fields->{static::FIELD_ESTIMATED_COMPLETION_DATE} ?? null,
                    'priority' => optional($issue->fields->{static::FIELD_PRIORITY})->name,
                    'issue_category' => optional($issue->fields->{static::FIELD_ISSUE_CATEGORY} ?? null)->value ?? 'Dev',
                    'rank' => $issue->fields->{static::FIELD_RANK}
                ];
            }, $results->issues);

            // Determine the number of results
            $countResults = count($results);

            // If there aren't any results, stop here
            if($countResults == 0) {
                break;
            }

            // Append the results to the list of issues
            $issues = array_merge($issues, $results);

            // Forget the results
            unset($results);

            // Increase the page count
            $page++;

        } while ($countResults == $count);

        // Return the list of issues
        return $issues;
    }

    /**
     * Returns the jira expression that identifies the issues for the burndown.
     *
     * @return string
     */
    public function newBurndownJiraIssuesExpression()
    {
        return 'assignee in (tyler.reed) AND priority not in (Hold) AND status in (Assigned, "Testing Failed", "Dev Hold", "In Development") ORDER BY Rank ASC';
    }
}
