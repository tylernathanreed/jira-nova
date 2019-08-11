@if(count(Nova::availableResources(request())))
    @foreach($navigation as $group => $resources)
        <treeview tag="div" toggle-tag="h4" toggle-class="sidebar-item text-base p-4">
            <template slot="label">
                <span class="flex items-center flex-1">
                    @if(isset($groupIcons[$group]))
                        {!! $groupIcons[$group] !!}
                    @else
                        <svg class="sidebar-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <path fill="var(--sidebar-icon)" d="M3 1h4c1.1045695 0 2 .8954305 2 2v4c0 1.1045695-.8954305 2-2 2H3c-1.1045695 0-2-.8954305-2-2V3c0-1.1045695.8954305-2 2-2zm0 2v4h4V3H3zm10-2h4c1.1045695 0 2 .8954305 2 2v4c0 1.1045695-.8954305 2-2 2h-4c-1.1045695 0-2-.8954305-2-2V3c0-1.1045695.8954305-2 2-2zm0 2v4h4V3h-4zM3 11h4c1.1045695 0 2 .8954305 2 2v4c0 1.1045695-.8954305 2-2 2H3c-1.1045695 0-2-.8954305-2-2v-4c0-1.1045695.8954305-2 2-2zm0 2v4h4v-4H3zm10-2h4c1.1045695 0 2 .8954305 2 2v4c0 1.1045695-.8954305 2-2 2h-4c-1.1045695 0-2-.8954305-2-2v-4c0-1.1045695.8954305-2 2-2zm0 2v4h4v-4h-4z"/>
                        </svg>
                    @endif
                    <span class="sidebar-label">{{ $group }}</span>
                </span>
            </template>

            <template slot="menu">
                <ul class="list-reset bg-90-quarter">
                    @foreach($resources as $resource)
                        <li class="sidebar-item text-sm">
                            <router-link :to="{
                                name: 'index',
                                params: {
                                    resourceName: '{{ $resource::uriKey() }}'
                                }
                            }" class="text-white text-justify no-underline ml-8 p-4 w-full">
                                {{ $resource::label() }}
                            </router-link>
                        </li>
                    @endforeach
                </ul>
            </template>
        </treeview>

    @endforeach
@endif