<?php

namespace App\Nova\Metrics;

use DB;
use App\Models\Issue;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Trend;

class IssueDelinquentByDiff extends Trend
{
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

        // Filter to delinquent issues
        $query->where('estimate_diff', '<=', -1);

        // Group by the the estimate diff
        $query->groupBy('estimate_diff');

        // Select the estimate diff and count
        $query->select([
            DB::raw('-estimate_diff as estimate_diff'),
            DB::raw('count(*) as count')
        ]);

        // Order by the estimate diff
        $query->orderBy('estimate_diff', 'desc');

        // Determine the query results
        $values = $query->getQuery()->get()->keyBy('estimate_diff');

        // Determine the maximum entry
        $max = min($values->max('estimate_diff'), 100);

        // Merge counts greater than 99 to one entry
        $values[100] = (object) [
            'estimate_diff' => 100,
            'count' => $values->where('estimate_diff', '>=', 100)->sum('count')
        ];

        // Remove entries greater than 99
        $values = $values->filter(function($value) {
            return $value->estimate_diff <= 100;
        });

        // Add in missing days
        for($i = 1; $i < $max; $i++) {
            $values[$i] = ['estimated_diff' => $i, 'count' => $values[$i]->count ?? 0];
        }

        dump(compact('values'));

        // Convert the results into a key / value array
        $values = $values->pluck('count', 'estimate_diff')->toArray();


        // Determine the result
        // $result = $this->countByDays($request, $query, 'estimate_date');

        // Return the result
        return $this->result($values);
        /*
        // Condense 100+ delinquencies into the same bucket
        $result->value

        // Determine the isssues
        $issues = collect(json_decode($request->resourceData, true));

        // Estimate the difference for each issue
        $issues->transform(function($issue) {

            $issue['offset'] = (is_null($issue['est']) || is_null($issue['due'])) ? null : Carbon::parse($issue['est'])->diffInDays($issue['due'], false);

            return $issue;

        });

        // Initialize the result
        $result = array_combine(array_map(function($v) {
            return $v . ($v == 1 ? ' day' : ' days');
        }, array_merge(range(1, 99), ['100+'])), array_fill(0, 100, 0));

        // Convert the issues into delinquency groups
        $data = $issues->where('offset', '<', 0)->groupBy(function($issue) {
            return $issue['offset'] <= -100 ? '100+' : -$issue['offset'];
        })->map->count()->all();

        // Fill in the data
        foreach($data as $key => $value) {
            $result[$key == 1 ? '1 day' : "{$key} days"] = $value;
        }

        // Determine the max days
        $max = min(-$issues->where('offset', '<', 0)->min('offset'), 100);

        // Remove trailing entries
        $result = array_filter($result, function($key) use ($max) {
            return (int) $key <= $max;
        }, ARRAY_FILTER_USE_KEY);

        // Return the trend result
        return (new TrendResult)->trend($result)->suffix('issues')->result(array_sum($result));
        */
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
            90 => '90 Days',
            365 => '1 Year'
        ];
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Delinquencies (By Days)';
    }
}
