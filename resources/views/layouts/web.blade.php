<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @yield('head')
    </head>

    <body{!! isset($body['class']) ? ' class="' . $body['class'] . '"' : '' !!}>
        @yield('body')
    </body>
</html>
