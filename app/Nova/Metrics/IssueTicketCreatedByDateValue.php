<?php

namespace App\Nova\Metrics;

use App\Models\Issue;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Value;

class IssueTicketCreatedByDateValue extends Value
{
    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'value-metric';

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $query = (new Issue)->newQuery();

        $query->where('focus', 'Ticket');

        return $this->count($request, $query, null, 'entry_date');
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        return [
            30 => '30 Days',
            60 => '60 Days',
            365 => '365 Days',
            'MTD' => 'Month To Date',
            'QTD' => 'Quarter To Date',
            'YTD' => 'Year To Date',
        ];
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Ticket Entry';
    }

}
