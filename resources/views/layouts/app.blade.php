<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    use Illuminate\Support\Facades\Auth;
@endphp

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'FinFlow') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('assets/css/select2.min.css') }}">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/flowbite.min.js', 'resources/js/select2.min.js', 'resources/js/chart.js', 'resources/js/apexcharts.min.js', 'resources/js/preline.js'])
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        {{-- @include('layouts.navigation') --}}

        <!-- Page Heading -->
        @isset($header)
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="max-w-7xl mx-auto py-2 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>

    <div data-dial-init class="fixed bottom-6 start-6 group">
        <div id="speed-dial-menu-bottom-left" class="flex flex-col items-center hidden mt-4 space-y-2">
            <a href="{{ route('logout') }}"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();" type="button"
                data-tooltip-target="tooltip-exit" data-tooltip-placement="left"
                class="flex justify-center items-center w-[40px] h-[40px] text-body hover:text-heading bg-neutral-blue-soft rounded-full border border-gray shadow-xs hover:bg-neutral-gray-medium hover:border-gray-medium focus:ring-4 focus:ring-neutral-gray-soft focus:outline-none">
                {{-- <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13V4M7 14H5a1 1 0 0 0-1 1v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4a1 1 0 0 0-1-1h-2m-1-5-4 5-4-5m9 8h.01"/></svg> --}}
                <i class="bi bi-box-arrow-right"></i>
                <span class="sr-only">Sign out</span>
            </a>
            <div id="tooltip-exit" role="tooltip"
                class="absolute z-10 invisible inline-block px-3 py-2 text-xs font-medium 
            text-white transition-opacity duration-300 
            bg-gray-900 rounded-lg shadow-xs opacity-0 whitespace-nowrap tooltip">
                Sign out ({{ Auth::user()->first_name }} {{ Auth::user()->last_name }})
                <div class="tooltip-arrow" data-popper-arrow></div>
            </div>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                @csrf
            </form>
        </div>
        <button type="button" data-dial-toggle="speed-dial-menu-bottom-left"
            aria-controls="speed-dial-menu-bottom-left" aria-expanded="false"
            class="flex bg-gray-500 items-center justify-center text-white bg-brand rounded-full w-10 h-10 hover:bg-brand-strong focus:ring-4 focus:ring-brand-medium focus:outline-none">
            <svg class="w-5 h-5 transition-transform group-hover:rotate-45" aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M5 12h14m-7 7V5" />
            </svg>
            <span class="sr-only">Open actions menu</span>
        </button>
    </div>

    {{-- <script src="{{ asset('assets/js/preline.js') }}"></script> --}}
    <script src="{{ asset('assets/js/flowbite.min.js') }}"></script>
    <script src="{{ asset('assets/js/select2.min.js') }}"></script>

    @stack('scripts')

</body>

<!-- Footer with Data Privacy Notice -->
<footer class="py-4 border-t border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row justify-left items-center text-xs text-gray-500">
            <div class="flex items-center">
                <svg class="h-4 w-4 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                        clip-rule="evenodd" />
                </svg>
                <span>Personal data displayed is masked based on your access rights in compliance with the Data
                    Privacy Act of 2012 (Republic Act No. 10173)</span>
            </div>
            {{-- <div class="mt-2 md:mt-0">
                @piiAccessBadge
            </div> --}}
        </div>
    </div>
</footer>

</html>
