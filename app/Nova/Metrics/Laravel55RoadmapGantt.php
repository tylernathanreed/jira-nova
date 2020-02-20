<?php

namespace App\Nova\Metrics;

use App\Models\Issue;
use Illuminate\Http\Request;
use Reedware\NovaGanttMetric\Gantt;

class Laravel55RoadmapGantt extends Gantt
{
    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'gantt-metric';

    /**
     * The displayable name of the metric.
     *
     * @var string
     */
    public $name = 'Estimated Completion Date';

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $query = Issue::joinRelation('labels', function($join) {
            $join->whereIn('labels.name', ['Laravel5.2', 'Laravel5.3', 'Laravel5.4', 'Laravel5.5']);
        })->where('issues.epic_key', '=', 'UAS-3575');

        return $this->spreadByDays($request, $query, 'labels.name', 'estimate_date')->label(function($label) {
            return str_replace('5.', ' 5.', $label);
        });
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        return [
            90 => 'Next 90 Days',
            180 => 'Next 180 Days',
            270 => 'Next 270 Days'
        ];
    }
}
