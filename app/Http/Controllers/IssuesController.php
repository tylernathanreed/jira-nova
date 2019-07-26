<?php

namespace App\Http\Controllers;

use Nova;
use App\Models\Issue;
use Illuminate\Http\Request;
use App\Nova\Resources\JiraIssue;
use Laravel\Nova\Query\ApplyFilter;
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
        // Determine the filters from the request
        $filters = $this->filters($request);

        // Initialize the options
        $options = [
            'assignee' => [
                $request->user()->jira_key
            ],
            'groups' => [
                'dev' => true,
                'ticket' => false,
                'other' => true
            ]
        ];

        // Apply the filters to the options
        foreach($filters as $filter) {
            $filter->filter->applyToJiraOptions($options, $filter->value);
        }

    	// Determine the jira issues
    	$issues = Issue::getIssuesFromJira($options);

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
     * Returns the filters encoded in the specified request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     *
     * @return \Illuminate\Support\Collection
     */
    protected function filters(NovaRequest $request)
    {
        // Decode the filters
        if(empty($filters = $this->decodedFilters($request))) {
            return collect();
        }

        // Determine the available filters
        $availableFilters = $this->availableFilters($request);

        // Map the filters into available filters
        $appliedFilters = collect($filters)->map(function($filter) use ($availableFilters) {

            // Determine the first matching filter
            $matchingFilter = $availableFilters->first(function($availableFilter) use ($filter) {
                return $filter['class'] === $availableFilter->key();
            });

            // If we found a matching filter, convert it to a class / value pair
            if($matchingFilter) {
                return ['filter' => $matchingFilter, 'value' => $filter['value']];
            }

        });

        // Remove empty filters
        $populatedFilters = $appliedFilters->reject(function($filter) {

            // If the value is an array, make sure there's at least one entry
            if(is_array($filter['value'])) {
                return count($filter['value']) < 1;
            }

            // Otherwise, if the value is a string, make sure it's not empty
            else if(is_string($filter['value'])) {
                return trim($filter['value']) === '';
            }

            // Otherwise, make sure the value isn't null
            return is_null($filter['value']);

        });

        // Map the filter pairings into objects
        $applyFilters = $populatedFilters->map(function ($filter) {
            return new ApplyFilter($filter['filter'], $filter['value']);
        })->values();

        // Return the filters to be applied
        return $applyFilters;
    }

    /**
     * Returns the decoded filters encoded in the specified request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     *
     * @return array
     */
    protected function decodedFilters(NovaRequest $request)
    {
        if(empty($request->filters)) {
            return [];
        }

        $filters = json_decode(base64_decode($request->filters), true);

        return is_array($filters) ? $filters : [];
    }

    /**
     * Returns all of the possibly available filters for the request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     *
     * @return \Illuminate\Support\Collection
     */
    protected function availableFilters(NovaRequest $request)
    {
        return (new JiraIssue(new Issue))->availableFilters($request);
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
        // Issue::updateEstimates($estimates);

        // Redirect back to the index page
        return redirect()->route('issues.index');
    }
}
