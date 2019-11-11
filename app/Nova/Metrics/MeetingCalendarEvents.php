<?php

namespace App\Nova\Metrics;

use Auth;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\MeetingInstance;
use Reedware\NovaCalendarEventsMetric\CalendarEvents;

class MeetingCalendarEvents extends CalendarEvents
{
    /**
     * The displayable name of the metric.
     *
     * @var string
     */
    public $name = 'Meetings';

    /**
     * Whether or not the date range is futuristic.
     *
     * @var boolean
     */
    public $futuristic = true;

    /**
     * The help text for the metric.
     *
     * @var string
     */
    public $helpText = 'This metric shows upcoming meetings and the amount of time they reduce from the schedule.';

    /**
     * Indicated whether the metric should be refreshed when filters are changed.
     *
     * @var boolean
     */
    public $refreshWhenFilterChanged = true;

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        // Initialize the query
        $query = (new MeetingInstance)->newQuery();

        // Check for the jira priorities tool
        if($request->referrerName == 'jira-priorities') {

            // Determine the filters for the jira issues
            $filters = !empty($request->referrerQuery)
                ? json_decode($request->referrerQuery, true)['jira-issues_filter'] ?? null
                : null;

            // Check if filters have been specified
            if(!empty($filters)) {

                // Decode the filters
                $filters = collect(json_decode(base64_decode($filters)));

                // Determine the assignee
                $assignee = optional($filters->where('class', \App\Nova\Filters\Assignee::class)->first())->value;

                // Determine the user id
                $id = optional(User::where('jira_key', '=', $assignee)->first())->id;

                // Filter to the specified user
                $query->whereHas('participants', function($query) use ($id) {
                    $query->where('users.id', '=', $id);
                });

            }

            // Filters have not been specified
            else {

                // Filter to the authenticated user
                $query->whereHas('participants', function($query) {
                    $query->where('users.id', '=', Auth::id());
                });

            }

        }

        // Return the response
        return $this->list($request, $query, 'effective_date', 'name', function($resource) {
            return $resource->effective_date->format('D m/j/Y') . ' ' . $resource->starts_at->format('g:i A') . ' - ' . $resource->ends_at->format('g:i A');
        }, function($resource) {
            return round($resource->length_in_seconds / 3600, 2);
        })->suffix('hours');
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        $adjective = $this->futuristic ? 'Next' : 'Past';

        return [
            30 => $adjective . ' 30 Days',
            60 => $adjective . ' 60 Days',
            90 => $adjective . ' 90 Days',
            365 => $adjective . ' 1 Year'
        ];
    }
}