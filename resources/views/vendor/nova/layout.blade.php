<!DOCTYPE html>

<?php $fullscreen = request()->fullscreen ? 'enabled' : 'disabled'; ?>
<?php $theme = request()->theme ?? 'default'; ?>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ implode(' ', array_filter([
    'h-full',
    'font-sans',
    'antialiased',
    "nova-{$theme}-theme",
    "nova-fullscreen-{$fullscreen}"
]))}}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=1280">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @if(!empty($path = Str::title(str_replace('-', ' ', str_replace('/', ' :: ', ltrim(request()->path(), Nova::path()))))))
        <title>{{ Nova::name() }} :: {{ $path }}</title>
    @else
        <title>{{ Nova::name() }}</title>
    @endif

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,800,800i,900,900i" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">

    <!-- Tool Styles -->
    @foreach(Nova::availableStyles(request()) as $name => $path)
        <link rel="stylesheet" href="/nova-api/styles/{{ $name }}">
    @endforeach
</head>
<body class="min-w-site bg-40 text-black min-h-full">
    <div id="nova">
        <div v-cloak class="flex min-h-screen">
            <!-- Sidebar -->
            <div class="min-h-screen flex-none min-h-screen w-sidebar bg-grad-sidebar fullscreen:hidden">
                <a href="{{ Nova::path() }}" class="no-underline">
                    <div class="bg-logo flex items-center w-sidebar h-header px-6 text-white">
                       @include('nova::partials.logo')
                    </div>
                </a>

                <div>
                    <?php $tools = collect(Nova::availableTools(request()))->sortBy(function($tool) {
                        return array_search(get_class($tool), config('nova.tool-priority'));
                    }); ?>

                    @foreach($tools as $tool)
                        {!! $tool->renderNavigation() !!}
                    @endforeach
                </div>
            </div>

            <!-- Content -->
            <div class="content">
                <div class="flex items-center relative shadow h-header bg-white z-20 px-6 fullscreen:hidden">
                    @if(count(Nova::globallySearchableResources(request())) > 0)
                        <global-search></global-search>
                    @endif

                    <dropdown class="ml-auto h-9 flex items-center dropdown-right">
                        @include('nova::partials.user')
                    </dropdown>
                </div>

                <div class="flex items-center h-header z-20 px-6 pt-search normalscreen:hidden slideshow:hidden">
                    <div class="px-6">
                        @include('nova::partials.logo')
                    </div>
                    <div class="flex-1"></div>
                    <div class="px-6">
                        Week {{ \App\Models\Label::getWeekLabelIndex() }}
                    </div>
                </div>

                <div data-testid="content" class="px-view py-view mx-auto slideshow:p-0">
                    @yield('content')

                    @include('nova::partials.footer')
                </div>
            </div>
        </div>
    </div>

    <script>
        window.config = @json(Nova::jsonVariables(request()));
    </script>

    <!-- Scripts -->
    <script src="{{ mix('manifest.js', 'vendor/nova') }}"></script>
    <script src="{{ mix('vendor.js', 'vendor/nova') }}"></script>
    <script src="{{ mix('app.js', 'vendor/nova') }}"></script>

    <!-- Build Nova Instance -->
    <script>
        window.Nova = new CreateNova(config)
    </script>

    <!-- Tool Scripts -->
    @foreach(Nova::availableScripts(request()) as $name => $path)
        @if(\Illuminate\Support\Str::startsWith($path, ['http://', 'https://']))
            <script src="{!! $path !!}"></script>
        @else
            <script src="/nova-api/scripts/{{ $name }}"></script>
        @endif
    @endforeach

    <!-- Before Liftoff -->
    <script src="{{ url('js/before-liftoff.js') }}"></script>

    <!-- Start Nova -->
    <script>
        Nova.liftOff()
    </script>
</body>
</html>
