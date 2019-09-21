<?php

namespace App\Nova\Metrics\Concerns;

trait Nameable
{
    /**
     * Sets the displayable name of the metric.
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

    /**
     * Alias of {@see $this->setName()}.
     *
     * @param  string  $name
     *
     * @return $this
     */
    public function label($name)
    {
        return $this->setName($name);
    }
}