<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>

<body
    class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">

    <header class="flex items-center justify-end w-full mb-4">
        @if (route('login', absolute: false))
            <nav class="flex items-center justify-end gap-4">
                @auth
                    <a class="py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg 
                            bg-gray-900 text-white hover:bg-gray-800 focus:outline-none"
                        href="{{ url('/dashboard') }}">
                        Dashboard
                    </a>
                @else
                    <a class="py-3 px-4 w-[120px] inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg 
                                bg-gray-900 text-white hover:bg-gray-800 focus:outline-none"
                        href="{{ route('login') }}">
                        Login
                    </a>

                    @if (route(name: 'register', absolute: false))
                        <a class="py-3 px-4 w-[120px] inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg 
                                bg-gray-200 text-gray-800 hover:bg-gray-300 focus:outline-none"
                            href="{{ route('register') }}">
                            Register
                        </a>
                    @endif
                @endauth
            </nav>
        @endif
    </header>
    <div
        class="flex flex-col items-center justify-center w-full
                    transition-opacity opacity-100 duration-750 lg:grow
                    starting:opacity-0 bg-gray-600 p-6 rounded-lg">

        {{-- <img src="{{ asset('images/kesia_banner.webp') }}" alt="Logo" class="w-auto max-w-[280px] lg:max-w-sm"> --}}
        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-auto max-w-[280px] lg:max-w-sm">
        <br>
        <p class="mt-4 text-center text-4xl font-medium text-white tracking-wide">
            {{ env('APP_NAME', 'FinFlow') }}
        </p>
        <p class="mt-4 text-center text-2xl font-medium text-white tracking-wide">
            {{ env('APP_DESCRIPTION', 'Financial Workflow, Disbursement & Liquidation Management Platform') }}
        </p>

    </div>

    @if (route('login', absolute: false))
        <div class="h-14.5 hidden lg:block"></div>
    @endif
    <script src="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.js"></script>
</body>

</html>
