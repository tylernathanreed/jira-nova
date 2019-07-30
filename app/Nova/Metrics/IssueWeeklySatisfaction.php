<?php

namespace App\Nova\Metrics;

use App\Models\Issue;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Trend;
use Laravel\Nova\Metrics\TrendResult;

class IssueWeeklySatisfaction extends Trend
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

        // Select the labels and status
        $query->select([
            'labels',
            'status_name'
        ]);

        // Determine the results
        $results = $query->getQuery()->get();

        // Determine the counts for each week index
        $weeks = $results->groupBy(function($issue) {

            // Return the max week per issue
            return array_reduce(json_decode(json_decode($issue->labels)), function($week, $label) {

                // Determine the week index
                $index = strpos($label, 'Week') === 0 ? substr($label, 4) : null;

                // If the index isn't valid, don't change the week
                if(is_null($index) || !is_numeric($index)) {
                    return $week;
                }

                // Return the max week
                return is_null($week) ? $index : max($week, $index);

            }, null);

        });

        // Determine the current week index
        $current = $this->getWeekLabelIndex();

        // Determine the future week indexes
        $future = array_filter($weeks->keys()->all(), function($week) use ($current) {
            return $week > $current;
        });

        // Remove the future week indexes
        foreach($future as $index) {
            unset($weeks[$index]);
        }

        // Convert each week into a done vs not done metric
        $satisfaction = $weeks->map(function($week) {

            return number_format($week->whereIn('status_name', [
                'Done',
                'Canceled',
                'Testing Passed [Test]'
            ])->count() / $week->count() * 100, 2);

        });

        // Sort the results
        $results = $satisfaction->sortKeys()->all();

        // Prefix the keys with "Week" verbiage
        $results = array_combine(array_map(function($key) {
            return 'Week ' . $key;
        }, array_keys($results)), array_values($results));

        // Determine the average
        $average = array_sum($results) / count($results);

        // Return the result
        return (new TrendResult)->trend($results)->result($average);
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
