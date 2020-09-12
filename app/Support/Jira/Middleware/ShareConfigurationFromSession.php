<?php

namespace App\Support\Jira\Middleware;

use Closure;
use App\Support\Jira\JiraService;

class ShareConfigurationFromSession
{
    /**
     * The jira service.
     *
     * @var \App\Support\Jira\JiraService
     */
    protected $jira;

    /**
     * Create a new session middleware.
     *
     * @param  \Illuminate\Contracts\Session\Session  $session
     * @param  \App\Support\Jira\JiraService          $jira
     *
     * @return void
     */
    public function __construct(JiraService $jira)
    {
        $this->jira = $jira;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure                  $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Make sure the request has a session
        if(!$request->hasSession()) {
            return $next($request);
        }

        // Determine the session
        $session = $request->session();

        // Make sure the session has jira configuration
        if(!$session->has('jira.username') || !$session->has('jira.password')) {
            return $next($request);
        }

        // Determine the jira configuration
        $config = $this->jira->getConfiguration();

        // Determine the session configuration
        $username = $session->get('jira.username');
        $password = $session->get('jira.password');

        // Update the jira configuration using the session configuration
        $config->setJiraUser($username);
        $config->setJiraPassword($password);

        // Handle the next middleware
        return $next($request);
    }
}
