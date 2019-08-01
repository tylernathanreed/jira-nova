<?php

namespace App\Nova\Metrics;

use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class JiraIssueWorkloadByFocusPartition extends Partition
{
    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'resource-partition-metric';

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        // Determine the isssues
        $issues = collect(json_decode($request->resourceData, true));

        // Determine the workload by focus
        $counts = $issues->groupBy('focus')->map->sum(function($i) {
            return max($i['remaining'], 3600);
        })->map(function($v) {
            return (float) number_format($v / 3600, 2);
        })->all();

        // Determine the results
        $results = [
            'Dev' => $counts['Dev'] ?? 0,
            'Ticket' => $counts['Ticket'] ?? 0,
            'Other' => $counts['Other'] ?? 0
        ];

        // Return the partition result
        return $this->result($results)->colors([
            'Dev' => '#5b9bd5',
            'Ticket' => '#ffc000',
            'Other' => '#cc0000'
        ]);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'jira-issue-workload-by-focus';
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Workload (By Focus)';
    }

}
