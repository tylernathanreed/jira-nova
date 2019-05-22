<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;

class User extends Resource
{
    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'System';

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\User::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name', 'email',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            Field::id()->sortable(),

            Field::text('Account ID', function() {
                return $this->jira()->accountId;
            })->onlyOnDetail(),

            Field::text('Email Address', function() {
                return $this->jira()->emailAddress;
            }),

            Field::avatar('Avatar')->thumbnail(function() {
                return $this->jira()->avatarUrls->{"48x48"};
            })->maxWidth(48),

            Field::text('Display Name', function() {
                return $this->jira()->displayName;
            }),

            Field::boolean('Active', function() {
                return $this->jira()->active;
            }),

            Field::text('Time Zone', function() {
                return $this->jira()->timeZone;
            })->onlyOnDetail(),

            Field::text('Locale', function() {
                return $this->jira()->locale;
            })->onlyOnDetail()
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [
            new \App\Nova\Actions\UpdateFromJira
        ];
    }
}
