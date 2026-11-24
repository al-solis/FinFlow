@extends('dashboard')

@section('title', 'Link Expired')

@section('content')
    <div class="min-h-[calc(100vh-180px)] flex items-center justify-center px-4 py-8">

        <div
            class="w-full max-w-xl bg-yellow-50 border border-yellow-300 rounded-lg shadow-sm p-8 flex flex-col sm:flex-row items-start gap-5">

            <div class="text-yellow-500 text-4xl flex-shrink-0 mt-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor"
                    class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
                    <path
                        d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.15.15 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.2.2 0 0 1-.054.06.1.1 0 0 1-.066.017H1.146a.1.1 0 0 1-.066-.017.2.2 0 0 1-.054-.06.18.18 0 0 1 .002-.183L7.884 2.073a.15.15 0 0 1 .054-.057m1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767z" />
                    <path
                        d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z" />
                </svg>
            </div>

            <div class="flex-1">
                <h2 class="text-xl font-semibold text-yellow-900 mb-3">
                    This link has expired
                </h2>

                <p class="text-yellow-800 mb-4 text-base leading-relaxed">
                    For your security, edit and view links are only valid for a few minutes.
                    This link has expired or the URL has been modified.
                </p>

                <p class="text-yellow-800 mb-6 text-base leading-relaxed">
                    Please go back to the list and click <strong>Edit</strong> again to get a fresh link.
                </p>

                <a href="{{ url('/dashboard') }}"
                    class="inline-block px-5 py-2.5 bg-yellow-600 hover:bg-yellow-700 text-yellow-800 font-medium rounded-md shadow transition duration-150 ease-in-out">
                    Go back
                </a>
            </div>

        </div>
    </div>
@endsection
