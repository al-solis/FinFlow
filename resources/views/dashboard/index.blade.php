@extends('dashboard')

@php
    use App\Constants\Modules;
@endphp

@section('content')
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Welcome Section -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Welcome back, {{ Auth::user()->first_name }}!</h1>
                <p class="text-sm text-gray-500">Here's what's happening with your financial tasks</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                @if (isset($stats['pending_approvals']))
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Pending Approvals</p>
                                <p class="text-2xl font-bold text-gray-900">{{ $stats['pending_approvals'] }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if (isset($stats['my_rfds']) || isset($stats['total_rfds']))
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">RFDs</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    {{ isset($stats['my_rfds']) ? $stats['my_rfds'] : $stats['total_rfds'] }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @if (isset($stats['my_cash_advances']) || isset($stats['total_cash_advances']))
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Cash Advances</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    {{ isset($stats['my_cash_advances']) ? $stats['my_cash_advances'] : $stats['total_cash_advances'] }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @if (isset($stats['my_journals']) || isset($stats['total_journals']))
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Journal Entries</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    {{ isset($stats['my_journals']) ? $stats['my_journals'] : $stats['total_journals'] }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">Recent Activity</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($recentActivity as $activity)
                        <div class="px-6 py-3 hover:bg-gray-50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <span class="text-xs font-medium text-gray-500">{{ $activity['type'] }}</span>
                                    <span class="text-xs text-gray-700">{{ $activity['reference'] }}</span>
                                    <span class="text-xs text-gray-500">{{ $activity['description'] }}</span>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <span class="text-xs font-medium text-gray-900">
                                        {{ number_format($activity['amount'], 2) }}
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        {{ $activity['created_at']->diffForHumans() }}
                                    </span>
                                    <a href="{{ $activity['url'] }}" class="text-xs text-blue-600 hover:underline">View</a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-gray-500 text-sm">
                            No recent activity found.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Reports Section -->
            @if (isset($authService) && $authService->canRead(Modules::FIN))
                <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Quick Reports</h3>

                    <div class="grid grid-cols-2 md:grid-cols-6 gap-3">

                        @if ($authService->canRead(Modules::GL, Modules::GL_TRIAL))
                            <a href="{{ route('gl.trial') }}"
                                class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 rounded-lg border border-gray-200 transition-colors">

                                <!-- Trial Balance -->
                                <svg class="w-4 h-4 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v12a2 2 0 01-2 2z" />
                                </svg>

                                <span>Trial Balance</span>
                            </a>
                        @endif


                        @if ($authService->canRead(Modules::FIN, Modules::FIN_BS))
                            <a href="{{ route('fin.bs') }}"
                                class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 rounded-lg border border-gray-200 transition-colors">

                                <!-- Balance Sheet -->
                                <svg class="w-4 h-4 text-indigo-600 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 7h18M3 12h18M3 17h18M5 4h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5a1 1 0 011-1z" />
                                </svg>

                                <span>Balance Sheet</span>
                            </a>
                        @endif


                        @if ($authService->canRead(Modules::FIN, Modules::FIN_IS))
                            <a href="{{ route('fin.is') }}"
                                class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 rounded-lg border border-gray-200 transition-colors">

                                <!-- Income Statement -->
                                <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 17l6-6 4 4 8-8M14 7h7v7" />
                                </svg>

                                <span>Income Statement</span>
                            </a>
                        @endif


                        @if ($authService->canRead(Modules::FIN, Modules::FIN_CF))
                            <a href="{{ route('fin.cf') }}"
                                class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 rounded-lg border border-gray-200 transition-colors">

                                <!-- Cash Flow -->
                                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 3v18m0-18l-4 4m4-4l4 4M5 12h14" />
                                </svg>

                                <span>Cash Flow</span>
                            </a>
                        @endif


                        @if ($authService->canRead(Modules::FIN, Modules::FIN_TAXR))
                            <a href="{{ route('reports.index') }}?report_type=tax_report"
                                class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 rounded-lg border border-gray-200 transition-colors">

                                <!-- Tax Report -->
                                <svg class="w-4 h-4 text-orange-600 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 14l2 2 4-4m5-2V7a2 2 0 00-2-2h-3l-2-2-2 2H8a2 2 0 00-2 2v3m12 0v9a2 2 0 01-2 2H8a2 2 0 01-2-2v-9" />
                                </svg>

                                <span>Tax Report</span>
                            </a>
                        @endif


                        <!-- All Reports -->
                        <a href="{{ route('reports.index') }}"
                            class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-blue-600 hover:bg-blue-50 rounded-lg border border-blue-200 transition-colors">

                            <!-- Folder -->
                            <svg class="w-4 h-4 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                            </svg>

                            <span>All Reports →</span>
                        </a>

                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection
