<?php

namespace App\Http\Controllers\Nova;

use Illuminate\Http\Request;
use App\Support\Jira\JiraService;
use Laravel\Nova\Http\Controllers\LoginController as NovaLoginController;

class LoginController extends NovaLoginController
{
    /**
     * The jira service instance.
     *
     * @var \App\Support\Jira\JiraService
     */
    protected $jira;

    /**
     * Create a new controller instance.
     *
     * @param  \App\Support\Jira\JiraService  $jira
     *
     * @return void
     */
    public function __construct(JiraService $jira)
    {
        // Call the parent constructor
        parent::__construct();

        // Assign the attributes
        $this->jira = $jira;
    }

    /**
     * Called when the user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed                     $user
     *
     * @return void
     */
    protected function authenticated(Request $request, $user)
    {
        // Update the jira configuration
        $this->updateJiraConfiguration($request, $user);
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        // Clear the jira configuration
        $this->clearJiraConfiguration($request);

        // Call the parent method
        return parent::logout($request);
    }

    /**
     * Called when the user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed                     $user
     *
     * @return void
     */
    protected function updateJiraConfiguration(Request $request, $user)
    {
        // Determine the jira configuration
        $config = $this->jira->getConfiguration();

        // Update the configuration
        $config->setJiraUser($user->email_address);
        $config->setJiraPassword($request->password);

        // Remember the configuration for subsequent requests
        $request->session()->put('jira.username', $user->email_address);
        $request->session()->put('jira.password', $request->password);
    }

    /**
     * Clears the jira configuration.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return void
     */
    protected function clearJiraConfiguration(Request $request)
    {
        // Determine the jira configuration
        $config = $this->jira->getConfiguration();

        // Update the configuration
        $config->setJiraUser(null);
        $config->setJiraPassword(null);
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'email_address';
    }
}
