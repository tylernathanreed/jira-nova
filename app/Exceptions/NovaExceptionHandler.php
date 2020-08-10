<?php

namespace App\Exceptions;

use Closure;
use Laravel\Nova\Nova;
use Throwable;

class NovaExceptionHandler extends Handler
{
    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $e
     *
     * @return mixed
     *
     * @throws \Throwable
     */
    public function report(Throwable $e)
    {
        return with(Nova::$reportCallback, function ($handler) use ($e) {
            if (is_callable($handler) || $handler instanceof Closure) {
                return call_user_func($handler, $e);
            }

            return parent::report($e);
        });
    }
}
