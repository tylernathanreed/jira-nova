<?php

namespace App\Nova\Metrics;

use Illuminate\Http\Request;
use App\Models\HolidayInstance;
use Reedware\NovaCalendarEventsMetric\CalendarEvents;

class HolidayCalendarEvents extends CalendarEvents
{
    /**
     * The displayable name of the metric.
     *
     * @var string
     */
    public $name = 'Holidays';

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
    public $helpText = 'This metric shows upcoming holidays which are removed from the schedule.';

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
        $query = (new HolidayInstance)->newQuery();

        // Return the response
        return $this->list($request, $query, 'observed_date', 'name', function($resource) {
            return $resource->effective_date != $resource->observed_date ? 'Effective ' . $resource->effective_date->format('D m/j/Y') : null;
        });
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