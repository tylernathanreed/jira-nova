<?php

namespace App\Support\Jira\Auth;

use Throwable;
use Illuminate\Support\Str;
use App\Support\Jira\JiraService;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class JiraUserProvider extends EloquentUserProvider
{
    /**
     * Create a new database user provider.
     *
     * @param  \App\Support\Jira\JiraService         $jira
     * @param  \Illuminate\Contracts\Hashing\Hasher  $hasher
     * @param  string                                $model
     *
     * @return void
     */
    public function __construct(JiraService $jira, HasherContract $hasher, $model)
    {
        $this->jira = $jira;

        parent::__construct($hasher, $model);
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        $model = $this->createModel();

        return $this->newModelQuery($model)
                    ->where($model->getAuthIdentifierName(), $identifier)
                    ->first();
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        // Check if the user an be retrieved by eloquent
        if(!is_null($user = parent::retrieveByCredentials($credentials))) {
            return $user;
        }

        // Determine the jira configuration
        $config = $this->jira->getConfiguration();

        // Remember the original username and password
        $original = [
            'username' => $config->getJiraUser(),
            'password' => $config->getJiraPassword()
        ];

        // Try to find the user with their credentials
        try {

            // Change the api credentials to the one provided
            $credentials = [
                'username' => $credentials[$this->getAuthIdentifierName()],
                'password' => $credentials['password']
            ];

            $config->setJiraUser($original['username']);
            $config->setJiraPassword($original['password']);

            // Determine the jira user
            $user = $this->jira->users()->findUsers(['username' => $credentials['username']])[0] ?? null;

        }

        // Suppress all exceptions
        catch(Throwable $ex) {

            // Nothing to do

        }

        finally {

            // No matter what happens, reset the configuration
            $config->setJiraUser($original['username']);
            $config->setJiraPassword($original['password']);

        }

        // If a jira user couldn't be found, return null
        if(!isset($user)) {
            return null;
        }

        // At this point, we've found the user within jira. We need to
        // create or update our local version of the user so that we
        // don't have to reach out to jira for every single login.

        // Create/update and return the user model
        return $this->createModel()::createOrUpdateFromJira($user, $credentials);
    }

    /**
     * Returns the attribute name for authentication.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return $this->createModel()->getAuthJiraIdentifierName();
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array                                       $credentials
     *
     * @return bool
     */
    public function validateCredentials(UserContract $user, array $credentials)
    {
        $plain = $credentials['password'];

        return $this->hasher->check($plain, $user->getAuthPassword());
    }
}
