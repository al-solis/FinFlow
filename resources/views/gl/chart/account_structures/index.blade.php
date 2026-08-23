@extends('dashboard')
@section('content')
    @php
        use Carbon\Carbon;
    @endphp
    <div class="p-6 space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Account Structures</h1>
                <p class="text-sm text-gray-500">
                    Manage the account structure for the General Ledger module.<br>
                </p>
            </div>
            <div class="flex items-center gap-2 mt-0">
                {{-- <a href="{{ route('setup.index') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium text-gray border border-gray-300 bg-gray-100 rounded-lg hover:bg-gray-200 ">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back
                </a> --}}

                <button data-modal-target="add-modal" data-modal-toggle="add-modal"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Account Structure
                </button>
            </div>
        </div>

        {{-- Stats Cards --}}
        {{-- @php
            $cards = [
                [
                    'title' => 'Total Accounts',
                    'value' => $totalAccounts,
                    'color' => 'blue',
                    'icon' => '
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-card-text text-blue-600" viewBox="0 0 16 16">
                        <path d="M14.5 3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5zm-13-1A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2z"/>
                        <path d="M3 5.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5M3 8a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 8m0 2.5a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5"/>
                        </svg>',
                ],
                [
                    'title' => 'Active Accounts',
                    'value' => $activeAccounts,
                    'color' => 'green',
                    'icon' => '
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M5 13l4 4L19 7" />
                        </svg>',
                ],
                [
                    'title' => 'Inactive Accounts',
                    'value' => $inactiveAccounts,
                    'color' => 'red',
                    'icon' => '
           <svg xmlns="http://www.w3.org/2000/svg"  width="16" height="16" fill="currentColor" class="bi bi-x-lg text-red-600" viewBox="0 0 16 16">
            <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"/>
            </svg>',
                ],
            ];
        @endphp --}}

        {{-- <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($cards as $card)
                <div class="bg-white border rounded-xl p-4 flex items-center gap-4">
                    <div
                        class="w-10 h-10 rounded-lg bg-{{ $card['color'] }}-100 
                                flex items-center justify-center">
                        {!! $card['icon'] !!}
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">{{ $card['title'] }}</p>
                        <p class="text-xl font-semibold text-gray-900">
                            {{ $card['value'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div> --}}

        {{-- @if ($errors->any())
            <div id="alert-message"
                class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 pt-1">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @elseif (session('success'))
            <div id="alert-message"
                class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 pt-1"
                data-success="true">
                {{ session('success') }}
            </div>
        @elseif(session('error'))
            <div id="alert-message" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif --}}
        @if (session('success'))
            <div id="success-alert"
                class="mt-3 mb-3 rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-800 shadow-sm transition-all duration-500">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-4 w-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ session('success') }}
                    </div>

                    <button type="button" onclick="this.closest('[id$=-alert]').style.display='none'"
                        class="text-green-600 hover:text-green-800 transition-colors duration-200">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        {{-- Filters --}}
        <form action="" method="GET">
            <div class="flex flex-col md:flex-row gap-2 text-xs md:text-sm">
                <div class="md:w-2/3 w-full">
                    <input type="text" id="search" name="search"
                        placeholder="Search by name, code, or description..."
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                        value = "{{ request()->query('search') }}" oninput="this.form.submit()">
                </div>

                <div class="md:w-1/3 w-full">
                    <select id="searchstatus" name="searchstatus"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                        onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="0" {{ request('searchstatus') === '0' ? 'selected' : '' }}>Draft</option>
                        <option value="1" {{ request('searchstatus') === '1' ? 'selected' : '' }}>Generated</option>
                        <option value="2" {{ request('searchstatus') === '2' ? 'selected' : '' }}>Active</option>
                        <option value="3" {{ request('searchstatus') === '3' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>
            </div>
            <button type="submit"
                class="hidden mt-4 w-full shrink-0 rounded-lg bg-gray-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-4 focus:ring-gray-300 dark:bg-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-800 sm:mt-0 sm:w-auto">Search</button>
        </form>

        {{-- Table --}}
        <div class="bg-white border rounded-xl overflow-x-auto md:overflow-visible scroll-smooth">
            <table class="min-w-full text-xs">
                <thead class="bg-gray-200 text-gray-600">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left w-[70px]">Code</th>
                        <th scope="col" class="px-4 py-3 text-left w-[120px]">Name</th>
                        <th scope="col" class="px-4 py-3 text-left w-[200px]">Description</th>
                        <th scope="col" class="px-4 py-3 text-left w-[100px]">Start</th>
                        <th scope="col" class="px-4 py-3 text-left w-[100px]">End</th>
                        <th scope="col" class="px-4 py-3 text-left w-[80px]">Default</th>
                        <th scope="col" class="px-4 py-3 text-left w-[50px]">Status</th>
                        <th scope="col" class="px-4 py-3 text-center w-[120px]">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    @forelse($accountStructures as $accountStructure)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium w-[70px]">{{ $accountStructure->id }}</td>
                            <td class="px-4 py-3 font-medium w-[120px]">{{ $accountStructure->name }}</td>
                            <td class="px-4 py-3 w-[200px]">{{ $accountStructure->description }}</td>
                            <td class="px-4 py-3 w-[100px]">
                                {{ Carbon::parse($accountStructure->start_date)->format('Y-m-d') }}
                            </td>
                            <td class="px-4 py-3 w-[100px]">
                                {{ Carbon::parse($accountStructure->end_date)->format('Y-m-d') }}
                            </td>
                            <td class="px-4 py-3 w-[80px]">
                                @if ($accountStructure->is_default)
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">Yes</span>
                                @else
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">No</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 w-[50px] text-xs font-semibold">
                                @php
                                    $statuses = [
                                        0 => ['color' => 'bg-gray-100 text-gray-600', 'label' => 'Draft'],
                                        1 => ['color' => 'bg-blue-100 text-blue-700', 'label' => 'Generated'],
                                        2 => ['color' => 'bg-green-100 text-green-700', 'label' => 'Active'],
                                        3 => ['color' => 'bg-orange-100 text-orange-600', 'label' => 'Closed'],
                                    ];
                                    $status = $statuses[$accountStructure->status] ?? [
                                        'color' => 'bg-gray-100 text-gray-600',
                                        'label' => 'Unknown',
                                    ];
                                @endphp

                                <span class="px-2 py-1 text-xs rounded-full {{ $status['color'] }}">
                                    {{ $status['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-3">

                                    {{-- Configure Account Structure --}}
                                    <a href="{{ route('gl.chart.account_structure_details.index', $accountStructure->id) }}"
                                        title="Configure Account Structure"
                                        class="text-indigo-600 hover:text-indigo-800 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                            fill="currentColor" class="bi bi-diagram-3" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd"
                                                d="M0 1.5A1.5 1.5 0 0 1 1.5 0h2A1.5 1.5 0 0 1 5 1.5v1A1.5 1.5 0 0 1 3.5 4H3v2h10V4h-.5A1.5 1.5 0 0 1 11 2.5v-1A1.5 1.5 0 0 1 12.5 0h2A1.5 1.5 0 0 1 16 1.5v1A1.5 1.5 0 0 1 14.5 4H14v3.5A1.5 1.5 0 0 1 12.5 9H9v2h.5A1.5 1.5 0 0 1 11 12.5v1A1.5 1.5 0 0 1 9.5 15h-3A1.5 1.5 0 0 1 5 13.5v-1A1.5 1.5 0 0 1 6.5 11H7V9H3.5A1.5 1.5 0 0 1 2 7.5V4h-.5A1.5 1.5 0 0 1 0 2.5v-1z" />
                                        </svg>
                                    </a>

                                    <a href="{{ route('gl.chart_of_accounts.index', $accountStructure) }}"
                                        title="View Chart of Accounts for {{ $accountStructure->name }}"
                                        class="text-green-600 hover:text-green-800 transition-colors">
                                        <svg width="18" height="18" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </a>

                                    {{-- Edit --}}
                                    <button type="button"
                                        title="Edit account structure: {{ $accountStructure->description }}"
                                        data-modal-target="edit-modal" data-modal-toggle="edit-modal"
                                        data-id="{{ $accountStructure->id }}" data-name="{{ $accountStructure->name }}"
                                        data-description="{{ $accountStructure->description }}"
                                        data-start_date="{{ $accountStructure->start_date }}"
                                        data-end_date="{{ $accountStructure->end_date }}"
                                        data-status="{{ $accountStructure->status }}"
                                        data-default="{{ $accountStructure->is_default ? '1' : '0' }}"
                                        onclick="openEditModal(this)"
                                        class="text-gray-500 hover:text-blue-600 transition-colors">

                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                            <path
                                                d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z" />
                                            <path fill-rule="evenodd"
                                                d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z" />
                                        </svg>
                                    </button>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                                <img src="{{ asset('images/main-account.svg') }}" alt="No data"
                                    class="mx-auto mb-4 w-24 h-28">
                                <p>No account structures found.</p>
                                <span>Click here to <a href="#" data-modal-target="add-modal"
                                        data-modal-toggle="add-modal" class="text-blue-600 hover:underline">add a new
                                        account structure</a>.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination Links -->
        <div
            class="w-full md:w-auto text-xs flex flex-col md:flex-row space-y-2 md:space-y-0 items-stretch md:items-center justify-end md:space-x-3 flex-shrink-0 mb-2">
            {{ $accountStructures->links() }}
        </div>
    </div>


    <!-- Create account structure modal -->
    <div id="add-modal" tabindex="-1" aria-hidden="true"
        class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-modal md:h-full">
        <div class="relative p-4 w-full max-w-md h-full md:h-auto">
            <!-- Modal content -->
            <div class="relative p-4 bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5">
                <!-- Modal header -->
                <div class="flex justify-between items-center pb-4 mb-2 rounded-t border-b sm:mb-5 dark:border-gray-600">
                    <h3 class="text-md font-semibold text-gray-900 dark:text-white">
                        Add New Account Structure
                    </h3>
                    <button type="button"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white"
                        data-modal-toggle="add-modal">
                        <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <!-- Modal body -->
                <div class="overflow-y-auto max-h-[70vh]">
                    <form action="{{ route('gl.structure.store') }}" method="POST">
                        @csrf
                        <div class="grid ml-1 mr-1 gap-2 mb-4 sm:grid-cols-2">
                            <div class="md:col-span-1">
                                <label for="name"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Name*</label>
                                <input type="text" name="name" id="name" maxlength="60"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    placeholder="e.g. COA 2026" required>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="description"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Description*</label>
                                <textarea type="text" name="description" id="description" rows="3" maxlength="120"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    placeholder="e.g. Account structure for the Fiscal Year " required></textarea>
                            </div>

                            <div class="md:col-span-1">
                                <label for="start_date"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Start Date*</label>
                                <input type="date" name="start_date" id="start_date"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    required>
                            </div>

                            <div class="md:col-span-1">
                                <label for="end_date" class="block text-xs font-medium text-gray-900 dark:text-white">End
                                    Date*</label>
                                <input type="date" name="end_date" id="end_date"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    required>
                            </div>

                            {{-- <div class="md:col-span-1">
                                <label for="status"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Status*</label>
                                <select id="status" name="status"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    required>
                                    
                                    <option value="0">Draft</option>
                                    <option value="1">Generated</option>
                                    <option value="2">Active</option>
                                    <option value="3">Closed</option>
                                </select>
                            </div> --}}
                            <div class="flex items-center md:col-span-1">
                                <input checked id="default" name="default" type="checkbox" value=""
                                    class="w-4 h-4 border border-default-medium rounded-xs bg-neutral-secondary-medium focus:ring-2 focus:ring-brand-soft">
                                <label for="default" class="select-none ms-2 text-sm font-medium text-heading">set as
                                    default</label>
                            </div>
                        </div>
                        <button type="submit"
                            class="text-white inline-flex items-center bg-gray-700 hover:bg-gray-800 focus:ring-4 focus:outline-none focus:ring-gray-300 font-medium rounded-md text-xs px-5 py-2.5 text-center dark:bg-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-800">
                            <svg class="mr-1 -ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                    d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            Add Account Structure
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End create segment modal -->

    <!-- Modal  Edit-->
    <div id="edit-modal" tabindex="-1" aria-hidden="true"
        class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <!-- Modal content -->
            <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
                <!-- Modal header -->
                <div
                    class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600 border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Update Segment
                    </h3>
                    <button type="button"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
                        data-modal-toggle="edit-modal">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                        </svg>
                        <span class="sr-only">Close</span>
                    </button>
                </div>
                <!-- Modal body -->
                <div class="overflow-y-auto max-h-[70vh]">
                    <form id="editForm" class="p-4 md:p-5" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="edit_id" id="edit_id">
                        <div class="grid ml-1 mr-1 gap-2 mb-4 sm:grid-cols-2">
                            <div class="md:col-span-1">
                                <label for="edit_name"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Name*</label>
                                <input type="text" name="edit_name" id="edit_name" maxlength="60"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    placeholder="e.g. COA 2026" required>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="edit_description"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Description*</label>
                                <textarea type="text" name="edit_description" id="edit_description" rows="3" maxlength="120"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    placeholder="e.g. Account structure for the Fiscal Year " required></textarea>
                            </div>

                            <div class="md:col-span-1">
                                <label for="edit_start_date"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Start Date*</label>
                                <input type="date" name="edit_start_date" id="edit_start_date"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    required>
                            </div>

                            <div class="md:col-span-1">
                                <label for="edit_end_date"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">End
                                    Date*</label>
                                <input type="date" name="edit_end_date" id="edit_end_date"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    required>
                            </div>

                            {{-- <div class="md:col-span-1">
                                <label for="edit_status"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Status*</label>
                                <select id="edit_status" name="edit_status"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    required>
                                    <option value="0">Draft</option>
                                    <option value="1">Generated</option>
                                    <option value="2">Active</option>
                                    <option value="3">Closed</option>
                                </select>
                            </div> --}}
                            <div class="flex items-center md:col-span-1">
                                <input checked id="edit_default" name="edit_default" type="checkbox"
                                    class="w-4 h-4 border border-default-medium rounded-xs bg-neutral-secondary-medium focus:ring-2 focus:ring-brand-soft">
                                <label for="edit_default" class="select-none ms-2 text-sm font-medium text-heading">set as
                                    default</label>
                            </div>
                        </div>

                        <button type="submit" @if (Auth::user()->role == '2') disabled @endif
                            class="btn-Update mt-2 text-white inline-flex items-center bg-gray-700 hover:bg-gray-800 focus:ring-4 focus:outline-none focus:ring-gray-300 font-medium rounded-md text-xs px-5 py-2.5 text-center dark:bg-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-800 {{ Auth::user()->role == '2' ? ' cursor-not-allowed' : '' }}">
                            {{-- <svg class="me-1 -ms-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path></svg> --}}
                            Update Segment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End edit modal -->

    <script>
        // document.addEventListener('DOMContentLoaded', function() {
        //     const alert = document.getElementById('alert-message');

        //     if (alert) {
        //         setTimeout(() => {
        //             alert.style.transition = 'opacity 0.5s ease';
        //             alert.style.opacity = '0';

        //             setTimeout(() => {
        //                 alert.remove();
        //             }, 500);
        //         }, 3000);
        //     }
        // });

        function formatDateForInput(dateString) {
            if (!dateString) return '';

            const date = new Date(dateString);
            if (isNaN(date)) return '';

            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        }

        function openEditModal(button) {

            const id = button.dataset.id;

            startDate = formatDateForInput(button.dataset.start_date);
            endDate = formatDateForInput(button.dataset.end_date);

            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = button.dataset.name;
            document.getElementById('edit_description').value = button.dataset.description;
            document.getElementById('edit_start_date').value = startDate;
            document.getElementById('edit_end_date').value = endDate;
            document.getElementById('edit_default').checked = Boolean(Number(button.dataset.default));

            document.getElementById('editForm').action = `/gl/chart/account_structures/${id}`;
        }
    </script>
@endsection
