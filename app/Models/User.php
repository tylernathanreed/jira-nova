<?php

namespace App\Models;

use Jira;
use Cache;
use JiraRestApi\User\User as JiraUser;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email_address', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    //////////////////
    //* Scheduling *//
    //////////////////
    /**
     * Returns the schedule data for Nova.
     *
     * @return array
     */
    public function getSchedule()
    {
        return $this->schedule ?: Schedule::getDefaultSchedule();
    }

    ////////////
    //* Jira *//
    ////////////
    /**
     * Creates or updates the specified user from jira.
     *
     * @param  mixed  $jira
     * @param  array  $options
     *
     * @return static
     */
    public static function createOrUpdateFromJira($jira, $options = [])
    {
        // Try to find the existing user in the system
        if(!is_null($user = static::where('jira_id', '=', $jira->accountId)->first())) {

            // Update the user
            return $user->updateFromJira($jira, $options);

        }

        // Create the user
        return static::createFromJira($jira, $options);
    }

    /**
     * Creates and returns new user from the specified jira user.
     *
     * @param  mixed  $jira
     * @param  array  $options
     *
     * @return static
     */
    public static function createFromJira($jira, $options = [])
    {
        // Create a new user
        $user = new static;

        // Update the user from jira
        return $user->updateFromJira($jira, $options);
    }

    /**
     * Updates this user from jira.
     *
     * @param  mixed  $jira
     * @param  array  $options
     *
     * @return $this
     */
    public function updateFromJira($jira, $options = [])
    {
        // Update the jira attributes
        $this->jira_id = $jira->accountId;
        $this->jira_key = $jira->key;
        $this->display_name = $jira->displayName;
        $this->email_address = $jira->emailAddress;

        // If a password was provided, hasn and store it
        if(isset($options['password'])) {
            $this->password = bcrypt($options['password']);
        }

        // Save
        $this->save();

        // Allow chaining
        return $this;
    }

    /**
     * Finds and returns the specified jira user.
     *
     * @param  array  $attributes
     *
     * @return \JiraRestApi\User\User
     */
    public static function findJira($attributes = [])
    {
        // Return the result for a set interval
        return static::getJiraCache()->remember(static::class . ':' . json_encode($attributes), 15 * 60, function() use ($attributes) {

            // Check for an account id
            if(isset($attributes['accountId'])) {
                return Jira::users()->get(['accountId' => $attributes['accountId']]);
            }

            // Check for an email
            if(isset($attributes['email'])) {
                return Jira::users()->findUsers(['username' => $attributes['email']])[0];
            }

            // Just use find using the attributes
            return Jira::users()->findUsers($attributes)[0];

        });
    }

    /**
     * Returns the jira user for this user.
     *
     * @return \JiraRestApi\User\User
     */
    public function jira()
    {
        return static::findJira([
            'accountId' => $this->jira_id,
            'email' => $this->email_address
        ]);
    }

    /**
     * Returns the jira cache.
     *
     * @return \Illuminate\Cache\Repository
     */
    public static function getJiraCache()
    {
        return Cache::store('jira');
    }

    //////////////////////
    //* Authentication *//
    //////////////////////
    /**
     * Returns the attribute name of the identifier used for jira authentication.
     *
     * @return string
     */
    public function getAuthJiraIdentifierName()
    {
        return 'email_address';
    }

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the schedule associated to this user.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }
}
