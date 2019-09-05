<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default API Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the web api connections below you wish
    | to use as your default connection for all web api work. Of course
    | you may use many connections at once using the web api service.
    |
    */

    'default' => env('API_CONNECTION', 'jira'),

    /*
    |--------------------------------------------------------------------------
    | API Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the web api connections setup for your application.
    | There are some basic examples of different api types to help you
    | get started, but you likely will not be needing all of them.
    |
    */

    'connections' => [

        'jira' => [
            'driver' => 'jira',
            'version' => '3',
            'host' => env('JIRA_HOST'),
            'username' => env('JIRA_CLI_USER'),
            'password' => env('JIRA_CLI_PASS'),
            'options' => [
                'json' => true,
                'expects_json' => true
            ]
        ]

    ]

];