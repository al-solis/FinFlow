@extends('dashboard')
@section('title', 'Unauthorized Access')
@section('content')
    <div class="min-h-[70vh] flex items-center justify-center px-4">
        <div class="max-w-md w-full text-center">
            {{-- Icon badge --}}
            <div class="mx-auto mb-6 flex items-center justify-center w-24 h-24 rounded-full bg-red-50 dark:bg-red-500/10">
                <svg class="w-12 h-12 text-red-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                    height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 14v3m-3-6V7a3 3 0 1 1 6 0v4M5 11h14a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-7a1 1 0 0 1 1-1Z" />
                </svg>
            </div>

            {{-- Code --}}
            <p class="text-7xl font-extrabold tracking-tight text-gray-900 dark:text-white">403</p>

            {{-- Title --}}
            <h1 class="mt-2 text-xl font-semibold text-gray-900 dark:text-white">Access Denied</h1>

            {{-- Body --}}
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                You don't have permission to access this page. If you believe this is a mistake,
                please contact your administrator.
            </p>

            {{-- Diagnostic reason (only shown when the exception handler provides one) --}}
            @if (!empty($message) && auth()->check() && auth()->user()->isAdmin())
                <p class="mt-3 text-xs text-gray-400 dark:text-gray-500 font-mono break-words">
                    {{ $message }}
                </p>
            @endif

            {{-- Actions --}}
            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ url()->previous() }}"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-700 focus:outline-none">
                    <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                        fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 12h14M5 12l4-4m-4 4 4 4" />
                    </svg>
                    Go back
                </a>
                <a href="{{ url('/dashboard') }}"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700 dark:hover:text-white rounded-lg focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 focus:outline-none">
                    <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                        fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m4 12 8-8 8 8M6 10.5V19a1 1 0 0 0 1 1h3v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h3a1 1 0 0 0 1-1v-8.5" />
                    </svg>
                    Home
                </a>
            </div>
        </div>
    </div>
@endsection
