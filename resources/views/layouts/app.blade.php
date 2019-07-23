@extends('layouts.web')

@section('head')

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet" type="text/css">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    @stack('styles')

@endsection

@section('body')

    <div id="app">
        @yield('app')
    </div>

    <div id="tail">

        <!-- Scripts -->
        <script src="{{ asset('js/app.js') }}"></script>

        @stack('scripts')

    </div>

@endsection