<?php

namespace App\Support\Jira\Config;

use Illuminate\Contracts\Config\Repository;
use JiraRestApi\Configuration\ConfigurationInterface as RestInterface;
use JiraAgileRestApi\Configuration\ConfigurationInterface as AgileInterface;

class SharedConfiguration implements RestInterface, AgileInterface
{
    /**
     * The configuration repository.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Creates and returns a new shared configuration instance.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     *
     * @return $this
     */
    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    public function getJiraHost()                  { return $this->config->get('services.jira.host'); }
    public function getJiraUser()                  { return $this->config->get('services.jira.username'); }
    public function getJiraVersion()               { return $this->config->get('services.jira.version'); }
    public function getJiraPassword()              { return $this->config->get('services.jira.password'); }
    public function getJiraLogEnabled()            { return $this->config->get('services.jira.logs.enabled'); }
    public function getJiraLogFile()               { return $this->config->get('services.jira.logs.file'); }
    public function getJiraLogLevel()              { return $this->config->get('services.jira.logs.level'); }
    public function isCurlOptSslVerifyHost()       { return $this->config->get('services.jira.curl.verify_host'); }
    public function isCurlOptSslVerifyPeer()       { return $this->config->get('services.jira.curl.verify_peer'); }
    public function isCurlOptVerbose()             { return $this->config->get('services.jira.curl.verbose'); }
    public function getCurlOptUserAgent()          { return $this->config->get('services.jira.curl.user_agent'); }
    public function getOAuthAccessToken()          { return $this->config->get('services.jira.oauth.token'); }
    public function isCookieAuthorizationEnabled() { return $this->config->get('services.jira.cookies.enabled'); }
    public function getCookieFile()                { return $this->config->get('services.jira.cookies.file'); }
    public function getProxyServer()               { return $this->config->get('services.jira.proxy.server'); }
    public function getProxyPort()                 { return $this->config->get('services.jira.proxy.port'); }
    public function getProxyUser()                 { return $this->config->get('services.jira.proxy.user'); }
    public function getProxyPassword()             { return $this->config->get('services.jira.proxy.password'); }
    public function getUseV3RestApi()              { return $this->config->get('services.jira.use_v3_rest_api'); }

    public function setJiraHost($value)                   { return $this->config->set('services.jira.host', $value); }
    public function setJiraUser($value)                   { return $this->config->set('services.jira.username', $value); }
    public function setJiraVersion($value)                { return $this->config->set('services.jira.version', $value); }
    public function setJiraPassword($value)               { return $this->config->set('services.jira.password', $value); }
    public function setJiraLogEnabled($value)             { return $this->config->set('services.jira.logs.enabled', $value); }
    public function setJiraLogFile($value)                { return $this->config->set('services.jira.logs.file', $value); }
    public function setJiraLogLevel($value)               { return $this->config->set('services.jira.logs.level', $value); }
    public function setCurlOptSslVerifyHost($value)       { return $this->config->set('services.jira.curl.verify_host', $value); }
    public function setCurlOptSslVerifyPeer($value)       { return $this->config->set('services.jira.curl.verify_peer', $value); }
    public function setCurlOptVerbose($value)             { return $this->config->set('services.jira.curl.verbose', $value); }
    public function setCurlOptUserAgent($value)           { return $this->config->set('services.jira.curl.user_agent', $value); }
    public function setOAuthAccessToken($value)           { return $this->config->set('services.jira.oauth.token', $value); }
    public function setCookieAuthorizationEnabled($value) { return $this->config->set('services.jira.cookies.enabled', $value); }
    public function setCookieFile($value)                 { return $this->config->set('services.jira.cookies.file', $value); }
    public function setProxyServer($value)                { return $this->config->set('services.jira.proxy.server', $value); }
    public function setProxyPort($value)                  { return $this->config->set('services.jira.proxy.port', $value); }
    public function setProxyUser($value)                  { return $this->config->set('services.jira.proxy.user', $value); }
    public function setProxyPassword($value)              { return $this->config->set('services.jira.proxy.password', $value); }
    public function setUseV3RestApi($value)               { return $this->config->set('services.jira.use_v3_rest_api', $value); }
}
