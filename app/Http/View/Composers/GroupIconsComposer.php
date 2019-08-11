<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;
use Illuminate\Contracts\Config\Repository;

class GroupIconsComposer
{
    /**
     * The configuration repository.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Create a new profile composer.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     *
     * @return void
     */
    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     *
     * @return void
     */
    public function compose(View $view)
    {
        $components = $this->config->get('nova.resource-group-icons');

        $icons = collect($components)->map(function($component) {
            return "<{$component} class=\"sidebar-icon\"></{$component}>";
        });

        $view->with('groupIcons', $icons);
    }
}