<?php

namespace App\Nova\Metrics\Concerns;

trait Nameable
{
    /**
     * The suffix of the displayable name.
     *
     * @var string|null
     */
    public $nameSuffix;

    /**
     * Sets the displayable name of the metric.
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
     * Alias of {@see $this->setName()}.
     *
     * @param  string  $name
     *
     * @return $this
     */
    public function setName($name)
    {
        return $this->label($name);
    }

    /**
     * Sets the suffix of the displayable name.
     *
     * @param  string  $name
     *
     * @return $this
     */
    public function labelSuffix($suffix)
    {
        $this->nameSuffix = $suffix;

        return $this;
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        if(is_null($this->name)) {
            return parent::name();
        }

        return $this->name . $this->nameSuffix;
    }
}