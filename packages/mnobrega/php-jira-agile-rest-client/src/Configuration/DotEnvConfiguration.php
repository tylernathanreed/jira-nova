<?php

namespace JiraAgileRestApi\Configuration;

use JiraAgileRestApi\JiraException;

/**
 * Class DotEnvConfiguration.
 */
class DotEnvConfiguration extends AbstractConfiguration
{
    /**
     * @param string $path
     */
    public function __construct($path = '.')
    {
        $this->loadDotEnv($path);

        $this->jiraHost = $this->env('JIRA_HOST');
        $this->jiraUser = $this->env('JIRA_USER');
        $this->jiraVersion = $this->env('JIRA_VERSION');
        $this->jiraPassword = $this->env('JIRA_PASS');
        $this->jiraLogFile = $this->env('JIRA_LOG_FILE', 'jira-rest-client.log');
        $this->jiraLogLevel = $this->env('JIRA_LOG_LEVEL', 'WARNING');
        $this->curlOptSslVerifyHost = $this->env('CURLOPT_SSL_VERIFYHOST', false);
        $this->curlOptSslVerifyPeer = $this->env('CURLOPT_SSL_VERIFYPEER', false);
        $this->curlOptVerbose = $this->env('CURLOPT_VERBOSE', false);
    }

    /**
     * Gets the value of an environment variable. Supports boolean, empty and null.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    private function env($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;

            case 'false':
            case '(false)':
                return false;

            case 'empty':
            case '(empty)':
                return '';

            case 'null':
            case '(null)':
                return;
        }

        if ($this->startsWith($value, '"') && $this->endsWith($value, '"')) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    /**
     * Determine if a given string starts with a given substring.
     *
     * @param string       $haystack
     * @param string|array $needles
     *
     * @return bool
     */
    public function startsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && strpos($haystack, $needle) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a given string ends with a given substring.
     *
     * @param string       $haystack
     * @param string|array $needles
     *
     * @return bool
     */
    public function endsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ((string) $needle === substr($haystack, -strlen($needle))) {
                return true;
            }
        }

        return false;
    }

    /**
     * load dotenv.
     *
     * @param $path
     *
     * @throws JiraException
     */
    private function loadDotEnv($path)
    {
        $requireParam = [
            'JIRA_HOST', 'JIRA_USER', 'JIRA_PASS',
        ];

        // support for dotenv 1.x and 2.x. see also https://github.com/lesstif/php-jira-rest-client/issues/102
        if (class_exists('\Dotenv\Dotenv')) {

            // dirty solution for check whether dotenv v3 or v2
            try {
                $method = new \ReflectionMethod('\Dotenv\Dotenv', 'create');

                $dotenv = \Dotenv\Dotenv::create($path);

                $dotenv->safeLoad();
                $dotenv->required($requireParam);
            } catch (\ReflectionException $re) {
                // dotenv v2 doesn't have create method.
                $dotenv = new \Dotenv\Dotenv($path);

                $dotenv->load();

                $dotenv->required($requireParam);
            }
        } elseif (class_exists('\Dotenv')) {    // DotEnv v1
            \Dotenv::load($path);
            \Dotenv::required($requireParam);
        } else {
            throw new JiraException('can not load PHP dotenv class.!');
        }
    }
}
