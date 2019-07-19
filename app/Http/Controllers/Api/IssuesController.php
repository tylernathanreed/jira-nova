<?php

namespace App\Http\Controllers\Api;

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

        dd($issues);
    }
}
