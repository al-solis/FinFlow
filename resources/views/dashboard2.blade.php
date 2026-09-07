{{-- resources/views/layouts/dashboard.blade.php --}}
@php
    use Illuminate\Support\Facades\Auth;
    use App\Services\AuthorizationService;
    use App\Constants\Modules;
    $authService = new AuthorizationService(Auth::user());
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="py-0.5">
            <div class="flex flex-wrap justify-center gap-1.5 sm:gap-2">
                <!-- Dashboard Link -->
                <a href="{{ route('dashboard') }}" title="Real-time overview of agency assets and property"
                    class="py-1.5 px-2.5 flex flex-col items-center gap-x-1.5 text-sm text-gray-800 bg-gray-100 hover:text-cyan-700 rounded-lg focus:outline-hidden focus:text-cyan-700 hover:bg-gray-200">
                    <img width="32" height="32" src="{{ asset('icons/dashboard.png') }}" alt="Dashboard" />
                    <span class="text-xs mt-1">Dashboard</span>
                </a>

                <!-- Approvals -->
                @if ($authService->canRead(Modules::APPW, Modules::APPW_APPR))
                    <a href="{{ route('approvals.index') }}"
                        title="List of requests pending approval and their current status"
                        class="py-1.5 px-2.5 flex flex-col items-center gap-x-1.5 text-sm text-gray-800 bg-gray-100 hover:text-cyan-700 rounded-lg focus:outline-hidden focus:text-cyan-700 hover:bg-gray-200">
                        <img width="32" height="32" src="{{ asset('icons/approvals.png') }}" alt="Approvals" />
                        <span class="text-xs mt-1">Approvals</span>
                    </a>
                @endif

                <!-- Dynamic Modules -->
                @foreach ($modules as $module)
                    @php
                        // Check if module has any accessible sub-modules
                        $hasAccessibleSubModules = $module->subModules->contains(function ($sub) use (
                            $authService,
                            $module,
                        ) {
                            $permissions = $authService->getPermissions($module->code, $sub->code);
                            $hasAccess =
                                $permissions['can_read'] ||
                                $permissions['can_create'] ||
                                $permissions['can_update'] ||
                                $permissions['can_delete'];
                            return $hasAccess && Route::has($sub->route_name);
                        });
                    @endphp

                    @if ($hasAccessibleSubModules)
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <button type="button" @click="open = !open" title="{{ $module->description }}"
                                class="py-1.5 px-2.5 flex flex-col items-center gap-x-1.5 text-sm text-gray-800 bg-gray-100 hover:text-cyan-700 rounded-lg focus:outline-hidden focus:text-cyan-700 hover:bg-gray-200">
                                <img width="32" height="32" src="{{ asset('icons/' . $module->img) }}"
                                    alt="{{ $module->name }}" />
                                <span class="text-xs mt-1">{{ $module->name }}</span>
                            </button>

                            <div x-show="open" x-transition
                                class="absolute left-1/2 -translate-x-1/2 mt-1 w-56 bg-white rounded-lg shadow-lg border border-gray-200 z-50 py-1"
                                style="display: none;">
                                @foreach ($module->subModules->groupBy('group') as $groupName => $subs)
                                    @php
                                        // Check if any sub-module in this group is accessible
                                        $hasAccessibleInGroup = $subs->contains(function ($sub) use (
                                            $authService,
                                            $module,
                                        ) {
                                            $permissions = $authService->getPermissions($module->code, $sub->code);
                                            $hasAccess =
                                                $permissions['can_read'] ||
                                                $permissions['can_create'] ||
                                                $permissions['can_update'] ||
                                                $permissions['can_delete'];
                                            return $hasAccess && Route::has($sub->route_name);
                                        });
                                    @endphp

                                    @if ($hasAccessibleInGroup)
                                        @if (!$loop->first)
                                            <div class="my-1 border-t border-gray-200"></div>
                                        @endif

                                        @if (!blank($groupName))
                                            <div
                                                class="px-3 pt-2 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wide">
                                                {{ $groupName }}
                                            </div>
                                        @endif

                                        @foreach ($subs as $sub)
                                            @php
                                                $permissions = $authService->getPermissions($module->code, $sub->code);
                                                $hasAccess =
                                                    $permissions['can_read'] ||
                                                    $permissions['can_create'] ||
                                                    $permissions['can_update'] ||
                                                    $permissions['can_delete'];
                                            @endphp

                                            @if ($hasAccess && Route::has($sub->route_name))
                                                <a href="{{ route($sub->route_name) }}" title="{{ $sub->description }}"
                                                    class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-100 hover:text-cyan-700">
                                                    <img width="20" height="20"
                                                        src="{{ asset('icons/' . $sub->img) }}"
                                                        alt="{{ $sub->name }}" class="flex-shrink-0" />
                                                    <span>{{ $sub->name }}</span>
                                                    @if ($sub->can_create ?? $permissions['can_create'])
                                                        <span
                                                            class="ml-auto text-[8px] bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full">+</span>
                                                    @endif
                                                </a>
                                            @endif
                                        @endforeach
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach

                <!-- Reports -->
                @if ($authService->canRead(Modules::FIN))
                    <a href="" title="Financial Reports"
                        class="py-1.5 px-2.5 flex flex-col items-center gap-x-1.5 text-xs text-gray-800 bg-gray-100 hover:text-cyan-700 rounded-lg focus:outline-hidden focus:text-cyan-700 hover:bg-gray-200">
                        <img width="32" height="32" src="{{ asset('icons/reports.png') }}" alt="Reports" />
                        <span class="text-xs mt-1">Reports</span>
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <main>
        @yield('content')
    </main>
</x-app-layout>
