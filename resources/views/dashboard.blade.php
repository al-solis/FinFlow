@php
    use Illuminate\Support\Facades\Auth;
@endphp
<x-app-layout>
    <x-slot name="header">
        {{-- <div class="flex items-center gap-3">
            <div class="flex-shrink-0 mt-0">
                <x-application-logo class="h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
            </div>

            <h2 class="ml-3 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight mt-0">
                {{ env('APP_NAME', 'Asset and Workforce Management System') }}
            </h2>
        </div> --}}

        <div class="py-0.5">
            <div class="flex flex-wrap justify-center gap-1.5 sm:gap-2">
                <a href="" title="Real-time overview of agency assets and property"
                    class="py-1.5 px-2.5 flex flex-col items-center gap-x-1.5 text-sm text-gray-800 bg-gray-100 hover:text-cyan-700 rounded-lg focus:outline-hidden focus:text-cyan-700 hover:bg-gray-200">
                    {{-- <i class="bi bi-speedometer"></i> --}}
                    <img width="32" height="32" src="https://img.icons8.com/color/48/dashboard-layout.png"
                        alt="dashboard-layout" />
                    <span class="text-xs mt-1">Dashboard</span>
                </a>
                <a href="" title ="List of created requests and their current status"
                    class="py-1.5 px-2.5 flex flex-col items-center gap-x-1.5 text-sm text-gray-800 bg-gray-100 hover:text-cyan-700 rounded-lg focus:outline-hidden focus:text-cyan-700  hover:bg-gray-200">
                    {{-- <img src="{{ asset('images/to-do.gif') }}" alt="My Requests" class="w-12 h-12 object-contain"> --}}
                    <img width="32" height="32"
                        src="https://img.icons8.com/external-beshi-glyph-kerismaker/48/external-Approved-start-up-beshi-glyph-kerismaker.png"
                        alt="external-Approved-start-up-beshi-glyph-kerismaker" />
                    <span class="text-xs mt-1">My Requests</span>
                </a>
                @foreach ($modules as $module)
                    <a href="" title ="{{ $module->description }}"
                        class="py-1.5 px-2.5 flex flex-col items-center gap-x-1.5 text-sm text-gray-800 bg-gray-100 hover:text-cyan-700 rounded-lg focus:outline-hidden focus:text-cyan-700  hover:bg-gray-200">
                        {{-- <img src="{{ asset('images/to-do.gif') }}" alt="My Requests" class="w-12 h-12 object-contain"> --}}
                        <i class={{ $module->icon }}></i>
                        <img width="32" height="32" src="{{ asset('icons/' . $module->path) }}"
                            alt="{{ $module->name }}" />
                        <span class="text-xs mt-1">{{ $module->name }}</span>
                    </a>
                @endforeach
                {{-- <a href="" title="Request for disbursement"
                    class="py-1.5 px-2.5 flex flex-col items-center gap-x-1.5 text-sm text-gray-800 bg-gray-100 hover:text-cyan-700 rounded-lg focus:outline-hidden focus:text-cyan-700  hover:bg-gray-200">
                    <img width="48" height="48" src="https://img.icons8.com/color/48/budget.png"
                        alt="budget" />
                    <span class="text-xs mt-1">RFD</span>
                </a> --}}
                {{-- <a href="" title="Cash advance"
                    class="py-1.5 px-2.5 flex flex-col items-center gap-x-1.5 text-sm text-gray-800 bg-gray-100 hover:text-cyan-700 rounded-lg focus:outline-hidden focus:text-cyan-700  hover:bg-gray-200">
                    <img width="48" height="48" src="https://img.icons8.com/color/48/cash-in-hand.png"
                        alt="cash-in-hand" />
                    <span class="text-xs mt-1">Cash Advance</span>
                </a> --}}
                {{-- <a href="" title="Cash Advance Liquidation"
                    class="py-1.5 px-2.5 flex flex-col items-center gap-x-1.5 text-sm text-gray-800 bg-gray-100 hover:text-cyan-700 rounded-lg focus:outline-hidden focus:text-cyan-700  hover:bg-gray-200">
                    <img width="48" height="48"
                        src="https://img.icons8.com/external-soft-fill-juicy-fish/60/external-refund-banking-soft-fill-soft-fill-juicy-fish.png"
                        alt="external-refund-banking-soft-fill-soft-fill-juicy-fish" />
                    Liquidation
                </a> --}}
                {{-- <a href="" title="Approval of disbursement and cash advance"
                    class="py-1.5 px-2.5 flex flex-col items-center gap-x-1.5 text-sm text-gray-800 bg-gray-100 hover:text-cyan-700 rounded-lg focus:outline-hidden focus:text-cyan-700  hover:bg-gray-200">
                    
                    <img width="48" height="48" src="https://img.icons8.com/fluency/48/verified-account--v1.png"
                        alt="verified-account--v1" />
                    Approval
                </a> --}}
                {{-- <a href="" title="Refunds"
                    class="py-1.5 px-2.5 flex flex-col items-center gap-x-1.5 text-sm text-gray-800 bg-gray-100 hover:text-cyan-700 rounded-lg focus:outline-hidden focus:text-cyan-700  hover:bg-gray-200">
                    <img width="48" height="48"
                        src="https://img.icons8.com/external-others-inmotus-design/67/external-Down-round-icons-others-inmotus-design-25.png"
                        alt="external-Down-round-icons-others-inmotus-design-25" />
                    Refunds
                </a> --}}
                {{-- <a href="" title="Reimbursement"
                    class="py-1.5 px-2.5 flex flex-col items-center gap-x-1.5 text-sm text-gray-800 bg-gray-100 hover:text-cyan-700 rounded-lg focus:outline-hidden focus:text-cyan-700  hover:bg-gray-200">
                    <img width="48" height="48"
                        src="https://img.icons8.com/external-others-inmotus-design/67/external-Up-round-icons-others-inmotus-design-28.png"
                        alt="external-Up-round-icons-others-inmotus-design-28" />
                    Reimbursement
                </a> --}}
                <a class="py-1.5 px-2.5 flex flex-col items-center gap-x-1.5 text-sm text-gray-800 bg-gray-100 hover:text-cyan-700 rounded-lg focus:outline-hidden focus:text-cyan-700  hover:bg-gray-200"
                    href="">
                    <img width="32" height="32"
                        src="https://img.icons8.com/fluency/48/pie-chart-report-script.png"
                        alt="pie-chart-report-script" />
                    Reports
                </a>
                @if (Auth::user()->role_id == '1')
                    <a href="{{ route('setup.index') }}"
                        class="py-1.5 px-2.5 flex flex-col items-center gap-x-1.5 text-sm text-gray-800 bg-gray-100 hover:text-cyan-700 rounded-lg focus:outline-hidden focus:text-cyan-700  hover:bg-gray-200">
                        <img width="32" height="32" src="https://img.icons8.com/bubbles/100/settings.png"
                            alt="settings" />
                        Setup
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <main>
        @yield('content')
    </main>

</x-app-layout>
