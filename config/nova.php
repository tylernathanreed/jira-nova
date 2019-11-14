<?php

use Laravel\Nova\Http\Middleware\Authorize;
use Laravel\Nova\Http\Middleware\BootTools;
use Laravel\Nova\Http\Middleware\Authenticate;
use Laravel\Nova\Http\Middleware\DispatchServingNovaEvent;

return [

    /*
    |--------------------------------------------------------------------------
    | Nova App Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to display the name of the application within the UI
    | or in other locations. Of course, you're free to change the value.
    |
    */

    'name' => 'Jinora',

    /*
    |--------------------------------------------------------------------------
    | Nova App URL
    |--------------------------------------------------------------------------
    |
    | This URL is where users will be directed when clicking the application
    | name in the Nova navigation bar. You are free to change this URL to
    | any location you wish depending on the needs of your application.
    |
    */

    'url' => env('APP_URL', '/'),

    /*
    |--------------------------------------------------------------------------
    | Nova Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Nova will be accessible from. Feel free to
    | change this path to anything you like. Note that this URI will not
    | affect Nova's internal API routes which aren't exposed to users.
    |
    */

    'path' => '/nova',

    /*
    |--------------------------------------------------------------------------
    | Nova Authentication Guard
    |--------------------------------------------------------------------------
    |
    | This configuration option defines the authentication guard that will
    | be used to protect your Nova routes. This option should match one
    | of the authentication guards defined in the "auth" config file.
    |
    */

    'guard' => env('NOVA_GUARD', null),

    /*
    |--------------------------------------------------------------------------
    | Nova Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Nova route, giving you the
    | chance to add your own middleware to this stack or override any of
    | the existing middleware. Or, you can just stick with this stack.
    |
    */

    'middleware' => [
        'web',
        Authenticate::class,
        DispatchServingNovaEvent::class,
        BootTools::class,
        Authorize::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Nova Pagination Type
    |--------------------------------------------------------------------------
    |
    | This option defines the pagination visual style used by Resources. You
    | may choose between two types: "simple" and "links". Feel free to set
    | this option to the visual style you like for your application.
    |
    */

    'pagination' => 'simple',

    /*
    |--------------------------------------------------------------------------
    | Nova Action Resource Class
    |--------------------------------------------------------------------------
    |
    | This configuration option allows you to specify a custom resource class
    | to use instead of the one that ships with Nova. You may use this to
    | define any extra form fields or other custom behavior you need.
    |
    */

    'actions' => [
        'resource' => ActionResource::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Nova Tool Priority
    |--------------------------------------------------------------------------
    |
    | Out of the box, Nova offers two tools: dashboard and resources. If you
    | wanted to rearrange these, such as placing a custom tool in between
    | them, you wouldn't be able to. This configuration will fix that.
    |
    */

    'tool-priority' => [
        \Laravel\Nova\Tools\Dashboard::class,
        \NovaComponents\JiraIssuePrioritizer\JiraIssuePrioritizer::class,
        \Laravel\Nova\Tools\ResourceManager::class,
        \MadWeb\NovaTelescopeLink\TelescopeLink::class
    ],


    /*
    |--------------------------------------------------------------------------
    | Nova Resource Group Icons
    |--------------------------------------------------------------------------
    |
    | Groups by themselves don't typically have icons, but since they're so
    | visually appealing, we've added icons to all groups. If a component
    | icon isn't specified here, we'll use something generic instead.
    |
    */

    'resource-group-icons' => [
        \App\Nova\Resources\Issue::class => 'icon-tasks',
        'Management' => 'icon-cubes',
        'Meta' => 'icon-code',
        'Scheduling' => 'icon-calendar',
        'System' => 'icon-cogs',
        'Workflows' => 'icon-project-diagram'
    ]

];
