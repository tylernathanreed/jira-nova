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

    public function getJiraHost()                  { return $this->config->get('jira.host'); }
    public function getJiraUser()                  { return $this->config->get('jira.username'); }
    public function getJiraVersion()               { return $this->config->get('jira.version'); }
    public function getJiraPassword()              { return $this->config->get('jira.password'); }
    public function getJiraLogEnabled()            { return $this->config->get('jira.logs.enabled'); }
    public function getJiraLogFile()               { return $this->config->get('jira.logs.file'); }
    public function getJiraLogLevel()              { return $this->config->get('jira.logs.level'); }
    public function isCurlOptSslVerifyHost()       { return $this->config->get('jira.curl.verify_host'); }
    public function isCurlOptSslVerifyPeer()       { return $this->config->get('jira.curl.verify_peer'); }
    public function isCurlOptSslCert()             { return $this->config->get('jira.curl.cert'); }
    public function isCurlOptSslCertPassword()     { return $this->config->get('jira.curl.cert_password'); }
    public function isCurlOptSslKey()              { return $this->config->get('jira.curl.key'); }
    public function isCurlOptSslKeyPassword()      { return $this->config->get('jira.curl.key_password'); }
    public function isCurlOptVerbose()             { return $this->config->get('jira.curl.verbose'); }
    public function getCurlOptUserAgent()          { return $this->config->get('jira.curl.user_agent'); }
    public function getOAuthAccessToken()          { return $this->config->get('jira.oauth.token'); }
    public function isCookieAuthorizationEnabled() { return $this->config->get('jira.cookies.enabled'); }
    public function getCookieFile()                { return $this->config->get('jira.cookies.file'); }
    public function getProxyServer()               { return $this->config->get('jira.proxy.server'); }
    public function getProxyPort()                 { return $this->config->get('jira.proxy.port'); }
    public function getProxyUser()                 { return $this->config->get('jira.proxy.user'); }
    public function getProxyPassword()             { return $this->config->get('jira.proxy.password'); }
    public function getUseV3RestApi()              { return $this->config->get('jira.use_v3_rest_api'); }

    public function setJiraHost($value)                   { return $this->config->set('jira.host', $value); }
    public function setJiraUser($value)                   { return $this->config->set('jira.username', $value); }
    public function setJiraVersion($value)                { return $this->config->set('jira.version', $value); }
    public function setJiraPassword($value)               { return $this->config->set('jira.password', $value); }
    public function setJiraLogEnabled($value)             { return $this->config->set('jira.logs.enabled', $value); }
    public function setJiraLogFile($value)                { return $this->config->set('jira.logs.file', $value); }
    public function setJiraLogLevel($value)               { return $this->config->set('jira.logs.level', $value); }
    public function setCurlOptSslVerifyHost($value)       { return $this->config->set('jira.curl.verify_host', $value); }
    public function setCurlOptSslVerifyPeer($value)       { return $this->config->set('jira.curl.verify_peer', $value); }
    public function setCurlOptSslCert()                   { return $this->config->set('jira.curl.cert'); }
    public function setCurlOptSslCertPassword()           { return $this->config->set('jira.curl.cert_password'); }
    public function setCurlOptSslKey()                    { return $this->config->set('jira.curl.key'); }
    public function setCurlOptSslKeyPassword()            { return $this->config->set('jira.curl.key_password'); }
    public function setCurlOptVerbose($value)             { return $this->config->set('jira.curl.verbose', $value); }
    public function setCurlOptUserAgent($value)           { return $this->config->set('jira.curl.user_agent', $value); }
    public function setOAuthAccessToken($value)           { return $this->config->set('jira.oauth.token', $value); }
    public function setCookieAuthorizationEnabled($value) { return $this->config->set('jira.cookies.enabled', $value); }
    public function setCookieFile($value)                 { return $this->config->set('jira.cookies.file', $value); }
    public function setProxyServer($value)                { return $this->config->set('jira.proxy.server', $value); }
    public function setProxyPort($value)                  { return $this->config->set('jira.proxy.port', $value); }
    public function setProxyUser($value)                  { return $this->config->set('jira.proxy.user', $value); }
    public function setProxyPassword($value)              { return $this->config->set('jira.proxy.password', $value); }
    public function setUseV3RestApi($value)               { return $this->config->set('jira.use_v3_rest_api', $value); }
}
