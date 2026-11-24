@extends('dashboard')

@section('content')
    <div class="p-6">
        <div class="max-w-lg mx-auto bg-white border rounded-xl p-6 text-center">
            <h1 class="text-2xl font-semibold text-gray-900">Record not found</h1>
            <p class="mt-2 text-sm text-gray-500">
                The page or record you are looking for does not exist, or you no longer have access to it.
            </p>
            <a href="{{ url()->previous() }}"
                class="mt-4 inline-flex items-center gap-2 px-4 py-2 text-xs font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                Go back
            </a>
        </div>
    </div>
@endsection
