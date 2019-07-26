<?php

namespace App\Nova\Metrics;

use App\Models\Issue;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Trend;
use Laravel\Nova\Metrics\TrendResult;
use Laravel\Nova\Http\Requests\NovaRequest;

class DelinquentIssuesByDiff extends Trend
{
    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'resource-trend-metric';

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        // Determine the issues
        $issues = Issue::getIssuesFromRequest(app()->make(NovaRequest::class));

        // Initialize the result
        $result = array_combine(array_map(function($v) {
            return $v . ($v == 1 ? ' day' : ' days');
        }, array_merge(range(1, 99), ['100+'])), array_fill(0, 100, 0));

        // Convert the issues into delinquency groups
        $data = collect($issues)->where('estimate_diff', '<', 0)->groupBy(function($issue) {
            return $issue['estimate_diff'] <= -100 ? '100+' : -$issue['estimate_diff'];
        })->map->count()->all();

        // Fill in the data
        foreach($data as $key => $value) {
            $result[$key == 1 ? '1 day' : "{$key} days"] = $value;
        }

        // Determine the max days
        $max = min(-min(array_filter(array_column($issues, 'estimate_diff'))), 100);

        // Remove trailing entries
        $result = array_filter($result, function($key) use ($max) {
            dump(compact('key', 'max'));
            return (int) $key <= $max;
        }, ARRAY_FILTER_USE_KEY);

        // Return the trend result
        return (new TrendResult)->trend($result)->suffix('issues')->result(array_sum($data));
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        return [];
    }

    /**
     * Determine for how many minutes the metric should be cached.
     *
     * @return  \DateTimeInterface|\DateInterval|float|int
     */
    public function cacheFor()
    {
        // return now()->addMinutes(5);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'delinquent-issues-by-diff';
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
