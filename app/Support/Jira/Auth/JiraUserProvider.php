<?php

namespace App\Support\Jira\Auth;

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

        // Try to find the jira user
        $user = $this->jira->users()->findUsers(['username' => $credentials[$this->getAuthIdentifierName()]])[0] ?? null;

        // At this point, we have confirmed that the user exists within Jira,
        // but we haven't confirmed that the password is correct. The next
        // step is to confirm that the password can be used to log in.

        /**
         * @todo Verify
         */
        dd(compact('credentials', 'user'));
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
