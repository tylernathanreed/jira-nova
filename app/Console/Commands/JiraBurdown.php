<?php

namespace App\Console\Commands;

use Jira;
use Carbon\Carbon;
use Illuminate\Console\Command;
use JiraRestApi\Issue\IssueField;

class JiraBurdown extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jira:burndown';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns due dates based on time estimated and rank order.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Determine the jira issues
        $this->info('Searching for Jira issues...');
        $benchmark = microtime(true);
        $issues = $this->getJiraIssues();
        $this->info('Found [' . count($issues) . '] Jira issues in [' . ceil((microtime(true) - $benchmark) / 1000) . '] second(s).');

        // Assign estimated completion dates to the issues
        $this->info('Assigning estimated completion dates...');
        $benchmark = microtime(true);
        $issues = $this->assignEstimatedCompletionDates($issues);
        $this->info('Assigned estimated completion dates in [' . ceil(((microtime(true) - $benchmark) / 1000)) . '] second(s).');

        // Update the issues in jira
        $this->info('Updating Jira issues...');
        $benchmark = microtime(true);
        $count = $this->updateJiraIssues($issues);
        $this->info('Updated [' . $count . '] Jira issues in [' . ceil((microtime(true) - $benchmark) / 1000) . '] second(s).');
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
            if($issue['estimated_completion_date'] == $issue['original_due_date']) {
                continue;
            }

            // Update the issue
            Jira::issues()->update($issue['key'], (new IssueField(true))->setDueDate($issue['estimated_completion_date']));

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
        // Determine the allocatable seconds for each day of the week
        $weeklySchedule = [
            Carbon::SUNDAY => 0,
            Carbon::MONDAY => 4.5 * 60 * 60,
            Carbon::TUESDAY => 0,
            Carbon::WEDNESDAY => 5 * 60 * 60,
            Carbon::THURSDAY => 0,
            Carbon::FRIDAY => 5 * 60 * 60,
            Carbon::SATURDAY => 0,
        ];

        // Determine the start date range
        $start = Carbon::now()->addDays(1)->startOfDay(); // Start no sooner than tomorrow
        $end = Carbon::now()->addDays(8)->startOfDay(); // Start no later than a week after tomorrow

        // Determine the first date where we can start assigning due dates
        $date = array_reduce(array_keys($weeklySchedule), function($date, $key) use ($weeklySchedule, $start) {
            return $weeklySchedule[$key] <= 0 ? $date : $date->min(($thisWeek = Carbon::now()->weekday($key)->startOfDay())->gt($start) ? $thisWeek : $thisWeek->addWeek());
        }, $end);

        // Iterate through each issue
        foreach($issues as &$issue) {

            // Determine the remaining estimate
            $remaining = $issue['remaining_estimate'] ?? 1 * 60 * 60;

            // Since an issue on its own can take longer than a day to complete,
            // we essentially have to chip away at the remaining estimate so
            // that we can correctly spread the work effort into many days.

            // Allocate the remaining estimate in a time loop until its all gone
            while($remaining > 0) {

                // Determine how much time as already been allocated for the day
                $allocated = ($date->hour * 60 + $date->minute) * 60 + $date->second;

                // If the previous issue ended cleanly on the exact amount of allocatable
                // time, we wanted it to end on that date. However, we have to advance
                // to the next day for the next issue, otherwise we'll loop forever.

                // Check if we've run out of time for the day
                if($allocated >= $weeklySchedule[$date->dayOfWeek]) {

                    // Advance to the next day
                    $date = $date->addDay()->startOfDay();

                    // Try again
                    continue;

                }

                // Determine how much time we can allocate for today
                $allocatable = min($remaining, $weeklySchedule[$date->dayOfWeek] - $allocated);

                // Allocate the time
                $date = $date->addSeconds($allocatable);

                // Reduce the remaining time by how much was allocated
                $remaining -= $allocatable;

                // If we have exceeded the daily limit, advance to the next day
                if($allocated + $allocatable > $weeklySchedule[$date->dayOfWeek]) {
                    $date = $date->addDay()->startOfDay();
                }

                // Skip dates that have no allocatable time
                while($weeklySchedule[$date->dayOfWeek] <= 0) {
                    $date = $date->addDay();
                }

            }

            // Assign the estimated completion date
            $issue['estimated_completion_date'] = $date->toDateString();

        }

        // Return the issues
        return $issues;
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
                'duedate',
                'timeestimate',
                'customfield_10119'
            ], [], false);

            // Remap the issues to reference what we need
            $results = array_map(function($issue) {
                return [
                    'key' => $issue->key,
                    'original_due_date' => $issue->fields->duedate,
                    'remaining_estimate' => $issue->fields->timeestimate,
                    'rank' => $issue->fields->customfield_10119
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
        return 'assignee in (tyler.reed) AND priority in (Medium, High) AND status in (Assigned, "Testing Failed", "Dev Hold", "In Development") AND ("Issue Category" = Dev OR "Issue Category" is EMPTY) ORDER BY Rank ASC';
    }
}
