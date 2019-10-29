<?php

namespace App\Nova\Filters\Concerns;

trait Nameable
{
    /**
     * Sets the name of this filter.
     *
     * @param  string  $name
     *
     * @return $this
     */
    public function label($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Alias of {@see $this->label()}.
     *
     * @param  string  $name
     *
     * @return $this
     */
    public function setName($name)
    {
        return $this->label($name);
    }
}