<?php

namespace NovaComponents\BadgeUrl;

use Laravel\Nova\Fields\Field;

class BadgeUrl extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'badge-url';

    /**
     * The callback to be used to resolve the field's background.
     *
     * @var \Closure
     */
    public $backgroundCallback;

    /**
     * The callback to be used to resolve the field's foreground.
     *
     * @var \Closure
     */
    public $foregroundCallback;

    /**
     * The callback to be used to resolve the field's link.
     *
     * @var \Closure
     */
    public $linkCallback;

    /**
     * The callback to be used to resolve the field's router link.
     *
     * @var \Closure
     */
    public $toCallback;

    /**
     * The callback to be used to resolve the field's style.
     *
     * @var \Closure
     */
    public $styleCallback;

    /**
     * The background color to use.
     *
     * @param  string  $background
     *
     * @return $this
     */
    public function background($background)
    {
        return $this->withMeta(compact('background'));
    }

    /**
     * Define the callback that should be used to resolve the field's background.
     *
     * @param  callable  $backgroundCallback
     *
     * @return $this
     */
    public function backgroundUsing(callable $backgroundCallback)
    {
        $this->backgroundCallback = $backgroundCallback;

        return $this;
    }

    /**
     * The foreground color to use.
     *
     * @param  string  $foreground
     *
     * @return $this
     */
    public function foreground($foreground)
    {
        return $this->withMeta(compact('foreground'));
    }

    /**
     * Define the callback that should be used to resolve the field's foreground.
     *
     * @param  callable  $foregroundCallback
     *
     * @return $this
     */
    public function foregroundUsing(callable $foregroundCallback)
    {
        $this->foregroundCallback = $foregroundCallback;

        return $this;
    }

    /**
     * The link to use.
     *
     * @param  string  $link
     *
     * @return $this
     */
    public function link($link)
    {
        return $this->withMeta(compact('link'));
    }

    /**
     * Define the callback that should be used to resolve the field's link.
     *
     * @param  callable  $linkCallback
     *
     * @return $this
     */
    public function linkUsing(callable $linkCallback)
    {
        $this->linkCallback = $linkCallback;

        return $this;
    }


    /**
     * The router link to use.
     *
     * @param  string  $link
     *
     * @return $this
     */
    public function to($to)
    {
        return $this->withMeta(compact('to'));
    }

    /**
     * Define the callback that should be used to resolve the field's router link.
     *
     * @param  callable  $toCallback
     *
     * @return $this
     */
    public function toUsing(callable $toCallback)
    {
        $this->toCallback = $toCallback;

        return $this;
    }

    /**
     * The additional styles to apply.
     *
     * @param  array  $style
     *
     * @return $this
     */
    public function style($style)
    {
        return $this->withMeta(compact('style'));
    }

    /**
     * Define the callback that should be used to resolve the field's style.
     *
     * @param  callable  $styleCallback
     *
     * @return $this
     */
    public function styleUsing(callable $styleCallback)
    {
        $this->styleCallback = $styleCallback;

        return $this;
    }

    /**
     * Resolve the field's value for display.
     *
     * @param  mixed        $resource
     * @param  string|null  $attribute
     *
     * @return void
     */
    public function resolveForDisplay($resource, $attribute = null)
    {
        // Call the parent method
        parent::resolveForDisplay($resource, $attribute);

        // Resolve the background callback
        if(is_callable($this->backgroundCallback)) {
            $this->background(call_user_func($this->backgroundCallback, $this->value, $resource));
        }

        // Resolve the foreground callback
        if(is_callable($this->foregroundCallback)) {
            $this->foreground(call_user_func($this->foregroundCallback, $this->value, $resource));
        }

        // Resolve the link callback
        if(is_callable($this->linkCallback)) {
            $this->link(call_user_func($this->linkCallback, $this->value, $resource));
        }

        // Resolve the to callback
        if(is_callable($this->toCallback)) {
            $this->to(call_user_func($this->toCallback, $this->value, $resource));
        }

        // Resolve the style callback
        if(is_callable($this->styleCallback)) {
            $this->style(call_user_func($this->styleCallback, $this->value, $resource));
        }
    }
}
