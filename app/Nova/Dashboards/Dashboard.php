<?php

namespace App\Nova\Dashboards;

use Illuminate\Support\Str;
use Laravel\Nova\Dashboard as NovaDashboard;

abstract class Dashboard extends NovaDashboard
{
    /**
     * The displayable name for this dashboard.
     *
     * @var string
     */
    protected static $label;

    /**
     * Returns the displayable name for this dashboard.
     *
     * @return string
     */
    public static function label()
    {
        return static::$label ?? Str::singular(Str::title(Str::snake(class_basename(get_called_class()), ' ')));
    }
}
