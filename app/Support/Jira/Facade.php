<?php

namespace App\Support\Jira;

use Illuminate\Support\Facades\Facade as BaseFacade;

class Facade extends BaseFacade
{
    /**
     * Returns the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return JiraService::class;
    }
}