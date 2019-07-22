<?php

namespace App\Support\Jira\Middleware;

use Closure;
use App\Support\Jira\JiraService;
use Illuminate\Contracts\Session\Session;

class ShareConfigurationFromSession
{
    /**
     * The session store.
     *
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

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
    public function __construct(Session $session, JiraService $jira)
    {
        $this->session = $session;
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
        // Make sure the session has jira configuration
        if(!$this->session->has('jira.username') || !$this->session->has('jira.password')) {
            return $next($request);
        }

        // Determine the jira configuration
        $config = $this->jira->getConfiguration();

        // Determine the session configuration
        $username = $this->session->get('jira.username');
        $password = $this->session->get('jira.password');

        // Update the jira configuration using the session configuration
        $config->setJiraUser($username);
        $config->setJiraPassword($password);

        // Handle the next middleware
        return $next($request);
    }
}
