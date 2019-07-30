<?php

namespace App\Nova\Metrics;

use DB;
use Carbon\Carbon;
use App\Models\Issue;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Trend;
use Laravel\Nova\Metrics\TrendResult;

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

        // Apply the range filter
        $query->where('due_date', '<=', Carbon::parse("+{$request->range} days"));

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
            $values[$i] = (object) ['estimate_diff' => $i, 'count' => $values[$i]->count ?? 0];
        }

        // Order by estimated diff
        $values = $values->sortBy('estimate_diff');

        // Convert the results into a key / value array
        $values = array_combine(
            $values->keys()->map(function($key) {
                return $key == 100 ? '100+ Days Delinquent' : ($key == 1 ? '1 Day Delinquent' : "{$key} Days Delinquent");
            })->toArray(),
            $values->values()->map(function($value) {
                return (int) $value->count;
            })->toArray()
        );

        // Return the result
        return (new TrendResult)->trend($values)->suffix('issue')->result(array_sum($values));
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        return [
            30 => 'Due within 30 Days',
            60 => 'Due within 60 Days',
            90 => 'Due within 90 Days',
            365 => 'Due within 1 Year'
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
