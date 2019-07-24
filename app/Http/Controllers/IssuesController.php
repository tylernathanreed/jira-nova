<?php

namespace App\Http\Controllers;

use Nova;
use App\Models\Issue;
use Illuminate\Http\Request;
use App\Nova\Resources\JiraIssue;
use Laravel\Nova\Http\Requests\NovaRequest;

class IssuesController extends Controller
{
    /**
     * Displays the listing of issues.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(NovaRequest $request)
    {
    	// Determine the jira issues
    	$issues = Issue::getIssuesFromJira([
    		'groups' => [
    			'dev' => true,
    			'ticket' => false,
    			'other' => true
    		]
    	]);

        // Check for ajax requests
        if($request->ajax() || $request->wantsJson()) {

            // Return the json response
            return response()->json([
                'label' => 'Issue',
                'resources' => collect($issues)->values()->mapInto(new JiraIssue(new Issue))->map->serializeForIndex($request),
            ]);

        }

    	// Return the response
        return view('models.issues.index', compact('issues'));
    }

    /**
     * Updates the issues specified within the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function submit(Request $request)
    {
        // Determine the issues
        $issues = collect($request->issues);

        // Determine the old and new orders
        $oldOrder = $issues->sortBy('original.order')->pluck('key')->toArray();
        $newOrder = $issues->sortBy('order')->pluck('key')->toArray();

        // Determine the subtasks
        $subtasks = $issues->where('is_subtask', '=', 1)->pluck('key')->toArray();

        // Determine the issues with new estimates
        $estimates = $issues->filter(function($issue) {
            return $issue['est'] != $issue['original']['est'];
        })->pluck('est', 'key');

        // Perform the ranking operations to sort the old list into the new list
        Issue::updateOrderByRank($oldOrder, $newOrder, $subtasks);

        // Update the estimated completion dates
        Issue::updateEstimates($estimates);

        // Redirect back to the index page
        return redirect()->route('issues.index');
    }
}
