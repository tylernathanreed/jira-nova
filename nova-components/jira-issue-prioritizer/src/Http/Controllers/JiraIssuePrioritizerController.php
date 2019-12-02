<?php

namespace NovaComponents\JiraIssuePrioritizer\Http\Controllers;

use App\Models\Issue;
use Illuminate\Http\Request;
use NovaComponents\JiraIssuePrioritizer\EstimateCalculator;
use NovaComponents\JiraIssuePrioritizer\MagicSortCalculator;

class JiraIssuePrioritizerController extends Controller
{
    /**
     * Calculates the estimated completion dates for the specified issues.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function estimate(Request $request)
    {
        // Determine the issues
        $issues = json_decode($request->issues, true);

        // Group the issues by assignee
        $groups = collect($issues)->groupBy('assignee');

        // Calculate the estimates for each group
        $groups->transform(function($issues, $assignee) {
            return EstimateCalculator::calculate($assignee, $issues->all());
        });

        // Collapse the groups into estimates
        $estimates = $groups->collapse()->toArray();

        // Return the estimates
        return response()->json(compact('estimates'));
    }

    /**
     * Sorts the issues in the specified request by a predefined criteria.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function magicSort(Request $request)
    {
        // Determine the issues
        $issues = json_decode($request->issues);

        // Map the issues to models
        $issues = Issue::whereIn('key', $issues)->get()->all();

        // Determine the new order
        $orders = MagicSortCalculator::calculate($issues);

        // Return the estimates
        return response()->json(compact('orders'));
    }
}