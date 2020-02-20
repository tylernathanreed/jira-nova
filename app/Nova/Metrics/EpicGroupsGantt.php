<?php

namespace App\Nova\Metrics;

use DB;
use App\Models\Epic;
use Illuminate\Http\Request;
use Reedware\NovaGanttMetric\Gantt;

class EpicGroupsGantt extends Gantt
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
    public $name = 'Epic Timeline';

    /**
     * The help text for the metric.
     *
     * @var string
     */
    public $helpText = 'This metric shows the timeline of estimated completion for the shown epics.';

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $subquery = Epic::joinRelation('issues', function($join) {

            $join->where('issues.project_key', '=', 'UAS');
            $join->whereNotNull('issues.estimate_date');
            $join->incomplete();

        })->where('epics.name', 'not like', '%Phase 2')->select([
            DB::raw(preg_replace('/\s\s+/', ' ', "
                case
                    when epics.name like '%CASL%'
                        then 'CASL'
                    when epics.name like '%BND%'
                        then 'BND'
                    when epics.name like '%CUSC%'
                        then 'CUSC'
                    when epics.name in ('NSLDS', 'Clearinghouse', 'CHESLA Boarding')
                        then 'eUAS'
                    when epics.name like '%eUAS%'
                        then 'eUAS'
                    when epics.name = 'Laravel 5.5'
                        then 'Laravel 5.5'
                    else null
                end as `group`
            ")),
            'epics.name',
            DB::raw('min(issues.estimate_date) as start_date'),
            DB::raw('max(issues.estimate_date) as end_date'),
            DB::raw('max(issues.due_date) as due_date')
        ])->groupBy('epics.name');

        $query = Epic::query()
            ->fromSub($subquery, 'epic_timelines')
            ->whereNotNull('group')
            ->orderBy('due_date');

        return $this->spreadByDays($request, $query, 'group', ['start_date', 'end_date']);
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

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'epic-timelines';
    }
}
