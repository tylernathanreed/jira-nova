<?php

namespace App\Support;

use BeyondCode\DumpServer\RequestContextProvider;
use ReflectionClass;
use Symfony\Component\VarDumper\Dumper\ContextProvider\SourceContextProvider;
use Symfony\Component\VarDumper\Server\Connection;

class DumpServerChecker
{
    /**
     * The dump server connection.
     *
     * @var \Symfony\Component\VarDumper\Server\Connection
     */
    protected static $connection;

    /**
     * The connection socket.
     *
     * @var {stream resource}
     */
    protected static $socket;

    /**
     * Returns whether or not the dump server is online.
     *
     * @return boolean
     */
    public static function isOnline()
    {
        set_error_handler([self::class, 'nullErrorHandler']);

        $socket = static::getConnectionSocket();

        try {

            if(static::sendCheckStatus()) {
                return true;
            }

            static::rebootConnectionSocket();

            if(static::sendCheckStatus()) {
                return true;
            }

        }

        finally {
            restore_error_handler();
        }

        return false;
    }

    /**
     * Does nothing.
     *
     * @return void
     */
    public static function nullErrorHandler()
    {
        // no-op
    }

    /**
     * Sends the check status to the socket.
     *
     * @return booleam
     */
    protected static function sendCheckStatus()
    {
        $socket = static::getConnectionSocket();

        return stream_socket_sendto($socket, 'checkAlive') !== -1;
    }

    /**
     * Returns the socket for the connection.
     *
     * @return {stream resource}
     */
    public static function getConnectionSocket()
    {
        return !is_null(static::$socket)
            ? static::$socket
            : (static::$socket = static::resolveConnectionSocket());
    }

    /**
     * Resolves the socket for the connection.
     *
     * @return {stream resource}
     */
    protected static function resolveConnectionSocket()
    {
        $connection = static::getConnection();

        return tap((new ReflectionClass($connection))->getMethod('createSocket'), function($m) {
            $m->setAccessible(true);
        })->invoke($connection);
    }

    /**
     * Resolves the socket for the connection.
     *
     * @return {stream resource}
     */
    protected static function rebootConnectionSocket()
    {
        if(!is_null($socket = static::$socket)) {

            stream_socket_shutdown($socket, STREAM_SHUT_RDWR);
            fclose($socket);

        }

        return static::$socket = static::resolveConnectionSocket();
    }


    /**
     * Returns the dump server connection.
     *
     * @return \Symfony\Component\VarDumper\Server\Connection
     */
    public static function getConnection()
    {
        return !is_null(static::$connection)
            ? static::$connection
            : (static::$connection = static::resolveConnection());
    }

    /**
     * Resolves the dump server connection.
     *
     * @return \Symfony\Component\VarDumper\Server\Connection
     */
    protected static function resolveConnection()
    {
        $host = config('debug-server.host');

        return new Connection($host, [
            'request' => new RequestContextProvider(request()),
            'source' => new SourceContextProvider('utf-8', base_path()),
        ]);
    }
}