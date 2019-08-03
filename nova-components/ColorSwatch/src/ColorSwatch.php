<?php

namespace NovaComponents\ColorSwatch;

use Laravel\Nova\Fields\Code as CodeField;

class ColorSwatch extends CodeField
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'color-swatch';

    /**
     * Indicates if the element should be shown on the index view.
     *
     * @var bool
     */
    public $showOnIndex = true;

    /**
     * Create a new field.
     *
     * @param  string                $name
     * @param  string|callable|null  $attribute
     * @param  callable|null         $resolveCallback
     *
     * @return void
     */
    public function __construct($name, $attribute = null, callable $resolveCallback = null)
    {
    	parent::__construct($name, $attribute, $resolveCallback);

    	$this->json();
    }
}
