<?php

namespace NovaComponents\SlideshowPlayer;

use Laravel\Nova\ResourceTool;

class SlideshowPlayer extends ResourceTool
{
    /**
     * Get the displayable name of the resource tool.
     *
     * @return string
     */
    public function name()
    {
        return 'Slideshow Player';
    }

    /**
     * Get the component name for the resource tool.
     *
     * @return string
     */
    public function component()
    {
        return 'slideshow-player';
    }
}
