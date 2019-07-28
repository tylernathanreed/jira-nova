<?php

return [

    'host' => env('JIRA_HOST'),

    'cli' => [
        'username' => env('JIRA_CLI_USER'),
        'password' => env('JIRA_CLI_PASS'),
    ],

    'fields' => [
        'epic_color' => env('JIRA_FIELD_EPIC_COLOR', 'customfield_10004'),
        'epic_key' => env('JIRA_FIELD_EPIC_KEY', 'customfield_12000'),
        'epic_name' => env('JIRA_FIELD_EPIC_NAME', 'customfield_10002'),
        'estimated_completion_date' => env('JIRA_FIELD_ESTIMATED_COMPLETION_DATE', 'customfield_12011'),
        'issue_category' => env('JIRA_FIELD_ISSUE_CATEGORY', 'customfield_12005'),
        'rank' => env('JIRA_FIELD_RANK', 'customfield_10119')
    ],

    'version' => env('JIRA_VERSION', '7.9.2'),
    'use_v3_rest_api' => env('JIRA_REST_API_V3'),

    'oauth' => [
        'token' => env('JIRA_OAUTH_ACCESS_TOKEN')
    ],

    'cookies' => [
        'enabled' => env('JIRA_COOKIE_AUTH_ENABLED', false),
        'file' => env('JIRA_COOKIE_AUTH_FILE', 'jira-cookie.txt')
    ],

    'logs' => [
        'enabled' => env('JIRA_LOG_ENABLED', true),
        'level' => env('JIRA_LOG_LEVEL', 'WARNING'),
        'file' => env('JIRA_LOG_FILE', 'jira-rest-client.log')
    ],

    'curl' => [
        'verify_host' => env('JIRA_CURLOPT_SSL_VERIFYHOST', false),
        'verify_peer' => env('JIRA_CURLOPT_SSL_VERIFYPEER', false),
        'user_agent' => env('JIRA_CURLOPT_USERAGENT', sprintf('curl/%s (%s)', ($curl = curl_version())['version'], $curl['host'])),
        'verbose' => env('JIRA_CURLOPT_VERBOSE', false)
    ],

    'proxy' => [
        'server' => env('JIRA_PROXY_SERVER'),
        'port' => env('JIRA_PROXY_PORT'),
        'user' => env('JIRA_PROXY_USER'),
        'password' => env('JIRA_PROXY_PASSWORD'),
    ]

];