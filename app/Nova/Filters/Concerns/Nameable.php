<?php

namespace App\Nova\Filters\Concerns;

trait Nameable
{
    /**
     * Sets the name of this lens.
     *
     * @param  string  $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}