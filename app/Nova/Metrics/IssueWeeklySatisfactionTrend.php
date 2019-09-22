<?php

namespace App\Nova\Metrics;

use DB;
use App\Models\Issue;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Trend;
use Laravel\Nova\Metrics\TrendResult;

class IssueWeeklySatisfactionTrend extends Trend
{
    use Concerns\WeeklyLabels;

    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'trend-metric';

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        // Create a new query
        $query = (new Issue)->newQuery();

        // Make sure the issues have weekly labels
        $query->where('labels', 'like', '%"Week%');

        // Join into labels
        $query->joinRelation('labels', function($join) {
            $join->where('labels.name', 'like', 'Week%');
        });

        // Group by the label name
        $query->groupBy('labels.name');

        // Select the label and satisfaction
        $query->select([
            'labels.name as label',
            DB::raw('round(100.0 * sum(case when issues.resolution_date is not null then 1 else 0 end) / count(*)) as satisfaction')
        ]);

        // Determine the results
        $results = $query->getQuery()->get();

        // Determine the week number for each result
        $results->transform(function($week) {
            return tap($week, function($week) { $week->index = substr($week->label, 4); });
        });

        // Sort by the week number
        $results = $results->sortBy('index')->values();

        // Determine the current week index
        $current = $this->getWeekLabelIndex();

        // Remove non-numerical weeks and future weeks
        $weeks = $results->reject(function($week) use ($current) {
            return !is_numeric($week->index) || $week->index > $current;
        })->values();

        // Map the results into a list of satisfaction indices
        $satisfaction = $weeks->pluck('satisfaction', 'index')->sortKeys()->all();

        // Prefix the keys with "Week" verbiage
        $trend = array_combine(array_map(function($key) {
            return 'Week ' . $key;
        }, array_keys($satisfaction)), array_values($satisfaction));

        // Determine the average
        $average = array_sum($trend) / count($trend) / 100;

        // Return the result
        return (new TrendResult)->trend($trend)->result($average)->format([
            'output' => 'percent',
            'mantissa' => 0
        ]);
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Weekly Satisfaction (Percent)';
    }

}
