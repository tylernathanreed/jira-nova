<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use Illuminate\Http\Request;

class IssuesController extends Controller
{
    /**
     * Displays the listing of issues.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
    	// Determine the jira issues
    	$issues = Issue::getIssuesFromJira([
    		'groups' => [
    			'dev' => true,
    			'ticket' => false,
    			'other' => true
    		]
    	]);

    	// Return the response
        return view('pages.index', compact('issues'));
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
        $oldOrder = $issues->sortBy('index')->pluck('key')->toArray();
        $newOrder = $issues->sortBy('order')->pluck('key')->toArray();

        // Perform the ranking operations to sort the old list into the new list
        Issue::updateOrderByRank($oldOrder, $newOrder);

        dd($request);
    }
}
