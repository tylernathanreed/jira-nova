<?php

namespace App\Http\Middleware;

use Auth;
use Jira;
use Nova;
use Closure;
use App\Http\Controllers\Nova\LoginController;

class LoginUsingRequest
{
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
        if(!$request->has('login')) {
            return $next($request);
        }

        // Attempt to log in
        app()->make(LoginController::class)->login($request);

        // Redirect to the dashboard
        return redirect()->to($request->url() . '?' . http_build_query([
            'fullscreen' => $request->fullscreen,
            'theme' => $request->theme
        ]));
    }
}
