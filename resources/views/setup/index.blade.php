@extends('dashboard')
@section('content')
    @php
        use Illuminate\Support\Facades\Auth;
    @endphp
    <div class="py-5">
        <div class="max-w-7xl mx-auto px-2 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Chart --}}
            <div
                class="p-3 hover:bg-blue-100 focus:outline-hidden bg-white border border-gray-200 rounded-2xl shadow hover:shadow-md dark:bg-gray-800 dark:border-gray-700 transition">
                <div class="flex flex-col items-center text-center">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full mb-4">
                        {{-- <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-blue-600 dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg> --}}
                        <i class="bi bi-diagram-3-fill text-blue-600 dark:text-blue-300 text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Main GL Accounts</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                        Setup and manage main GL accounts.
                    </p>
                    <a href="{{ route('setup.chart.index') }}"
                        class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-700 transition">
                        Open Main GL Accounts
                    </a>
                </div>
            </div>

            {{-- Dimension --}}
            <div
                class="p-3 hover:bg-blue-100 focus:outline-hidden bg-white border border-gray-200 rounded-2xl shadow hover:shadow-md dark:bg-gray-800 dark:border-gray-700 transition">
                <div class="flex flex-col items-center text-center">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full mb-4">
                        {{-- <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-blue-600 dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg> --}}
                        <i class="bi bi-diagram-3-fill text-blue-600 dark:text-blue-300 text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Segments</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                        Setup and manage account segments.
                    </p>
                    <a href="{{ route('setup.chart.segment.index') }}"
                        class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-700 transition">
                        Open Segments
                    </a>
                </div>
            </div>

            {{-- Account Structures --}}
            <div
                class="p-3 hover:bg-blue-100 focus:outline-hidden bg-white border border-gray-200 rounded-2xl shadow hover:shadow-md dark:bg-gray-800 dark:border-gray-700 transition">
                <div class="flex flex-col items-center text-center">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full mb-4">
                        {{-- <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-blue-600 dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg> --}}
                        <i class="bi bi-diagram-3-fill text-blue-600 dark:text-blue-300 text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Account Structures</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                        Define GL account code segments.
                    </p>
                    <a href="{{ route('setup.chart.account_structures.index') }}"
                        class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-700 transition">
                        Open Structures
                    </a>
                </div>
            </div>

            {{-- Approval --}}
            <div
                class="p-3 hover:bg-blue-100 focus:outline-hidden bg-white border border-gray-200 rounded-2xl shadow hover:shadow-md dark:bg-gray-800 dark:border-gray-700 transition">
                <div class="flex flex-col items-center text-center">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full mb-4">
                        {{-- <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-blue-600 dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg> --}}
                        <i class="bi bi-diagram-3-fill text-blue-600 dark:text-blue-300 text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Approval</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                        Setup and manage approval workflow.
                    </p>
                    <a href="" class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-700 transition">
                        Open Approval
                    </a>
                </div>
            </div>

            {{-- User --}}
            <div
                class="p-3 hover:bg-blue-100 focus:outline-hidden bg-white border border-gray-200 rounded-2xl shadow hover:shadow-md dark:bg-gray-800 dark:border-gray-700 transition">
                <div class="flex flex-col items-center text-center">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full mb-4">
                        {{-- <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-blue-600 dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg> --}}
                        <i class="bi bi-diagram-3-fill text-blue-600 dark:text-blue-300 text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">User</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                        Setup and manage user accounts.
                    </p>
                    <a href="" class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-700 transition">
                        Open User
                    </a>
                </div>
            </div>

            {{-- Roles --}}
            <div
                class="p-3 hover:bg-blue-100 focus:outline-hidden bg-white border border-gray-200 rounded-2xl shadow hover:shadow-md dark:bg-gray-800 dark:border-gray-700 transition">
                <div class="flex flex-col items-center text-center">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full mb-4">
                        {{-- <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-blue-600 dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg> --}}
                        <i class="bi bi-diagram-3-fill text-blue-600 dark:text-blue-300 text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Roles & Permissions</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                        Setup and manage user roles and permissions.
                    </p>
                    <a href="" class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-700 transition">
                        Open Roles
                    </a>
                </div>
            </div>

        </div>
    </div>
@endsection
