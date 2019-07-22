<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Support\Jira\JiraService;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

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
        $this->middleware('guest')->except('logout');

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
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'email_address';
    }
}
