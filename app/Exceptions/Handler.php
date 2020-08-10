<?php

namespace App\Exceptions;

use Exception;
use Throwable;
use App\Support\DumpServerChecker;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     *
     * @return void
     *
     * @throws \Exception
     */
    public function report(Exception $exception)
    {
        parent::report($exception);

        if ($this->shouldntReport($exception)) {
            return;
        }

        $this->dump($exception);
    }

    /**
     * Dumps the specified exception.
     *
     * @return boolean
     */
    public function dump(Throwable $exception)
    {
        if(!DumpServerChecker::isOnline()) {
            return false;
        }

        $trace = preg_replace(
            '/^#\d+ ~\\\\vendor\\\\[^\n+]+\n/m',
            '',
            $this->removeGlobalPaths($exception->getTraceAsString())
        );

        dump([
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $this->removeGlobalPaths($exception->getFile()),
            'line' => $exception->getLine(),
            'trace' => $trace
        ]);

        return true;
    }

    /**
     * Removes the global paths from the specified message.
     *
     * @param  string  $message
     *
     * @return string
     */
    protected function removeGlobalPaths($message)
    {
        return str_replace(base_path(), '~', $message);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        return parent::render($request, $exception);
    }
}
