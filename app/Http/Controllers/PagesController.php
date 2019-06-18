<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    /**
     * Show the website landing page.
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
}
