<?php

namespace App\Console\Commands;

use Jira;
use Carbon\Carbon;
use App\Support\CsvWriter;
use JiraRestApi\JiraException;
use Illuminate\Console\Command;
use JiraRestApi\Issue\IssueField;

class JiraEstimate extends Command
{
    /**
     * The field constants.
     *
     * @var string
     */
    const FIELD_SUMMARY = 'summary';
    const FIELD_DUE_DATE = 'duedate';
    const FIELD_STATUS = 'status';
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
     * @option  {string}   only       The explicit focus groups to estimate.
     * @option  {string}   except     The focus groups to ignore.
     * @option  {boolean}  pretend    Whether or not to skip jira updates.
     * @option  {boolean}  commit     Whether or not to commit the estimated date as due dates.
     * @option  {boolean}  complain   Whether or not to report issues that are estimated to be completed after their due dates.
     * @option  {integer}  threshold  The number of days past due an estimate must be before complaining.
     *
     * @var string
     */
    protected $signature = 'jira:estimate {--only=} {--except=} {--complain} {--threshold=7} {--pretend} {--commit}';

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
        // Estimate the jira issues
        $issues = $this->estimateJiraIssues();

        // Complain about delinquent estimates
        $this->complain($issues);
    }

    /**
     * Estimates the jira issues.
     *
     * @return array
     */
    public function estimateJiraIssues()
    {
        // Determine the jira issues
        $this->info('[1/4] Searching for Jira issues...');
        $benchmark = microtime(true);
        $issues = $this->getJiraIssues();
        $this->info('[1/4] -> Found [' . count($issues) . '] Jira issues in [' . round((microtime(true) - $benchmark), 2) . '] seconds.');

        // Assign estimated completion dates to the issues
        $this->info('[2/4] Assigning estimated completion dates...');
        $benchmark = microtime(true);
        $issues = $this->assignEstimatedCompletionDates($issues);
        $this->info('[2/4] -> Assigned estimated completion dates in [' . round((microtime(true) - $benchmark), 2) . '] seconds.');

        // Update the issues in jira
        $this->info('[3/4] Updating Jira issues...');
        $benchmark = microtime(true);
        $count = $this->updateJiraIssues($issues);
        $this->info('[3/4] -> Updated [' . $count . '] Jira issues in [' . round((microtime(true) - $benchmark), 2) . '] seconds.');

        if($this->options('commit')) {
            $this->info('[3/4] -> Committed estimated dates as due dates.');
        }

        // Return the updated jira issues
        return $issues;
    }

    /**
     * Complains about delinquent estimates.
     *
     * @param  array  $issues
     *
     * @return void
     */
    public function complain($issues)
    {
        $this->info('[4/4] Complaining about Jira issues...');

        // Determine the threshold
        $threshold = $this->option('threshold');

        // Determine the issues to complain about
        $issues = array_filter($issues, function($issue) use ($threshold) {

            // Ignore issues that are missing either date
            if(is_null($due = $issue['due_date']) || is_null($est = $issue['new_estimated_completion_date'])) {
                return false;
            }

            // Determine the diff in days
            $delta = Carbon::parse($due)->diffInDays(Carbon::parse($est), false);

            // If the delta meets or exceeds the threshold, complain
            return $delta >= $threshold;

        });

        // Complain about the issue count
        $this->info('[4/4] -> ' . count($issues) . ' are estimated to be completed ' . $threshold . ' days or later than their due date!');

        // If complaining is enabled, create a report of the issues that exceed the threshold
        if($this->option('complain')) {

            // Determine the name of the output file
            $filename = "Issues Estimated At Least {$threshold} Days Deliquent " . date('Y-m-d') . '.csv';

            // Write the issues to an output file
            $this->info("[4/4] -> Writing issues to [{$filename}].");
            $writer = (new CsvWriter($filename))->map($issues, function($issue) {

                // Map each issue into fields
                return [
                    'Key' => $issue['key'],
                    'Priority' => $issue['priority'],
                    'Category' => $issue['issue_category'],
                    'Summary' => $issue['summary'],
                    'Status' => $issue['status'],
                    'Due Date' => Carbon::parse($issue['due_date'])->toDateString(),
                    'Est Date' => Carbon::parse($issue['new_estimated_completion_date'])->toDateString(),
                    'Rem Hours' => round($issue['time_estimate'] / 60 / 60, 2),
                    'Delta' => Carbon::parse($issue['due_date'])->diffInDays(Carbon::parse($issue['new_estimated_completion_date']), false)
                ];

            }, true, true);

        }
    }

    /**
     * Updates the specified issues in jira.
     *
     * @param  array  $issues
     *
     * @return integer
     *
     * @throws \JiraRestApi\JiraException
     */
    public function updateJiraIssues($issues)
    {
        // Initialize the count of updated jira issues
        $count = 0;

        // Iterate through each issue
        foreach($issues as $issue) {

            // If the issue doesn't need to be updated, skip it
            if(!$this->isIssueDirty($issue)) {
                continue;
            }

            // Make sure pretend is not enabled
            if(!$this->option('pretend')) {

                // Create a new field set
                $fields = new IssueField(true);

                // Add the new estimated completion date
                $fields->addCustomField(static::FIELD_ESTIMATED_COMPLETION_DATE, $issue['new_estimated_completion_date']);

                // If we're committing, include the due date
                if($this->option('commit')) {
                    $fields->setDueDate($issue['new_estimated_completion_date']);
                }

                // Update the issue
                try {
                    Jira::issues()->update($issue['key'], $fields);
                }

                // Catch jira exceptions
                catch(JiraException $ex) {

                    // Log the exception
                    $this->info("[3/4] -> Failed to update issue [{$issue['key']}].");
                    throw $ex;

                }

            }

            // Increase the count
            $count++;

        }

        // Return the count of updated jira issues
        return $count;
    }

    /**
     * Returns whether or not the issue needs to be updated.
     *
     * @param  array  $issue
     *
     * @return boolean
     */
    protected function isIssueDirty($issue)
    {
        // If we're committing, then the check the due date
        if($this->option('commit') && $issue['due_date'] != $issue['new_estimated_completion_date']) {
            return true;
        }

        // Otherwise, check the estimated date
        if($issue['new_estimated_completion_date'] != $issue['old_estimated_completion_date']) {
            return true;
        }

        // Issue is not dirty
        return false;
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
                static::FIELD_SUMMARY,
                static::FIELD_DUE_DATE,
                static::FIELD_STATUS,
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
                    'summary' => $issue->fields->{static::FIELD_SUMMARY},
                    'due_date' => $issue->fields->{static::FIELD_DUE_DATE},
                    'status' => $issue->fields->{static::FIELD_STATUS}->name,
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
        // Determine the applicable focus groups
        $groups = $this->getApplicableFocusGroups();

        // Determine the base expression
        $expression = 'assignee in (tyler.reed) AND priority not in (Hold) AND status in (Assigned, "Testing Failed", "Dev Hold", "In Development")';

        // If the "dev" focus group is disabled, exclude them
        if(!$groups['dev']) {
            $expression .= ' AND NOT (("Issue Category" = "Dev" or "Issue Category" is empty) AND priority != Highest)';
        }

        // If the "ticket" focus group is disabled, exclude them
        if(!$groups['ticket']) {
            $expression .= ' AND NOT ("Issue Category" in ("Ticket", "Data") AND priority != Highest)';
        }

        // If the "other" focus group is disabled, exclude them
        if(!$groups['other']) {
            $expression .= ' AND priority != Highest';
        }

        // Add the order by clause
        $expression .= ' ORDER BY Rank ASC';

        // Return the expression
        return $expression;
    }

    /**
     * Returns the focus groups that are being estimated.
     *
     * @return array
     */
    public function getApplicableFocusGroups()
    {
        // Initialize the focus groups
        $groups = [
            'dev' => null,
            'ticket' => null,
            'other' => null,
        ];

        // Determine the "only" option
        $only = !is_null($this->option('only'))
            ? explode(',', strtolower($this->option('only')))
            : null;

        // If the option wasn't provided, assume all focus groups
        if(empty($only)) {
            $only = array_keys($groups);
        }

        // Otherwise, map the specific focus groups
        else {
            $only = array_values(array_intersect(array_keys($groups), $only));
        }

        // Populate the focus groups using the "only" option
        foreach($groups as $group => &$value) {
            $value = in_array($group, $only);
        }

        // Determine the "except" option
        $except = !is_null($this->option('except'))
            ? explode(',', strtolower($this->option('except')))
            : null;

        // If the option wasn't provided, assume no focus groups
        if(empty($except)) {
            $except = [];
        }

        // Otherwise, map the specific focus groups
        else {
            $except = array_values(array_intersect(array_keys($groups), $except));
        }

        // Disable focus groups using the "except" option
        foreach($except as $group) {
            $groups[$group] = false;
        }

        // Return the focus groups
        return $groups;
    }
}
