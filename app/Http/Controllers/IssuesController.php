<?php

namespace App\Http\Controllers;

use App\Models\Issue;
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
        // Determine the issues from the request
        $issues = Issue::getIssuesFromRequest($request);

        // Return the json response
        return response()->json([
            'label' => 'Issue',
            'resources' => collect($issues)->values()->mapInto(new JiraIssue(new Issue))->map->serializeForIndex($request),
        ]);

    }
}
