<?php

namespace App\Nova\Metrics;

use Auth;
use App\Models\User;
use App\Models\TimeOff;
use Illuminate\Http\Request;
use Reedware\NovaCalendarEventsMetric\CalendarEvents;

class TimeOffCalendarEvents extends CalendarEvents
{
    /**
     * The displayable name of the metric.
     *
     * @var string
     */
    public $name = 'Time Off';

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
    public $helpText = 'This metric shows upcoming time off and how many days they remove from the schedule.';

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
        $query = (new TimeOff)->newQuery();

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
                $query->where('user_id', '=', $id);

            }

            // Filters have not been specified
            else {

                // Filter to the authenticated user
                $query->where('user_id', '=', Auth::id());

            }

        }

        // Return the response
        return $this->list($request, $query, 'date', function($resource) {
            return $resource->date->format('D m/j/Y');
        }, function($resource) {
            return $resource->percent == 1 ? 'Full Day' : ($resource->percent == 0.5 ? 'Half Day' : 'Partial Day');
        }, function($resource) {
            return $resource->percent;
        })->suffix('days');
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