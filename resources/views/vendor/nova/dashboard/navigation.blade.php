<treeview tag="div" toggle-tag="h4" toggle-class="sidebar-item text-base p-4">
    <template slot="label">
        <span class="flex items-center flex-1">
            <svg class="sidebar-icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 20 20">
                <defs>
                    <path id="b" d="M11 18v-5H9v5c0 1.1045695-.8954305 2-2 2H4c-1.1045695 0-2-.8954305-2-2v-7.5857864l-.29289322.2928932c-.39052429.3905243-1.02368927.3905243-1.41421356 0-.3905243-.3905243-.3905243-1.02368929 0-1.41421358l9-9C9.48815536.09763107 9.74407768 0 10 0c.2559223 0 .5118446.09763107.7071068.29289322l9 9c.3905243.39052429.3905243 1.02368928 0 1.41421358-.3905243.3905243-1.0236893.3905243-1.4142136 0L18 10.4142136V18c0 1.1045695-.8954305 2-2 2h-3c-1.1045695 0-2-.8954305-2-2zm5 0V8.41421356l-6-6-6 6V18h3v-5c0-1.1045695.8954305-2 2-2h2c1.1045695 0 2 .8954305 2 2v5h3z"/>
                    <filter id="a" width="135%" height="135%" x="-17.5%" y="-12.5%" filterUnits="objectBoundingBox">
                        <feOffset dy="1" in="SourceAlpha" result="shadowOffsetOuter1"/>
                        <feGaussianBlur in="shadowOffsetOuter1" result="shadowBlurOuter1" stdDeviation="1"/>
                        <feColorMatrix in="shadowBlurOuter1" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.166610054 0"/>
                    </filter>
                </defs>
                <g fill="none" fill-rule="evenodd">
                    <use fill="#000" filter="url(#a)" xlink:href="#b"/>
                    <use fill="var(--sidebar-icon)" xlink:href="#b"/>
                </g>
            </svg>
            <span class="sidebar-label" style="margin-top: 2px">{{ __('Dashboards') }}</span>
        </span>
    </template>

    <template slot="menu">
        <ul class="list-reset bg-90-quarter">
            <li class="sidebar-item text-sm">
                <router-link exact to="/" class="text-white text-justify no-underline ml-8 p-4 w-full">
                    {{ __('Home') }}
                </router-link>
            </li>

            @foreach(Laravel\Nova\Nova::availableDashboards(request()) as $dashboard)
                <li class="sidebar-item text-sm">
                    <router-link :to="{
                        name: 'dashboard.custom',
                        params: {
                            name: '{{ $dashboard::uriKey() }}',
                        }
                    }" class="text-white text-justify no-underline ml-8 p-4 w-full">
                        {{ $dashboard::label() }}
                    </router-link>
                </li>
            @endforeach
        </ul>
    </template>
</treeview>
