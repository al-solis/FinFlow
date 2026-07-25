@extends('dashboard')
@section('content')
    <div class="p-6 space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Main GL Accounts</h1>
                <p class="text-sm text-gray-500">
                    Manage main GL accounts, account types, categories, and subcategories. <br>
                    {{-- Full account codes use 3 segments: <strong>Main Account</strong> - <strong>Department</strong> -
                    <strong>Cost Center</strong> <br>
                    Example: --}}
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
                    Add Account
                </button>

                <a href="{{ route('gl.chart.category.index') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-collection" viewBox="0 0 16 16">
                        <path
                            d="M2.5 3.5a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1zm2-2a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1zM0 13a1.5 1.5 0 0 0 1.5 1.5h13A1.5 1.5 0 0 0 16 13V6a1.5 1.5 0 0 0-1.5-1.5h-13A1.5 1.5 0 0 0 0 6zm1.5.5A.5.5 0 0 1 1 13V6a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5z" />
                    </svg>
                    Category
                </a>

                <a href="{{ route('gl.chart.subcategory.index') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-collection-fill" viewBox="0 0 16 16">
                        <path
                            d="M0 13a1.5 1.5 0 0 0 1.5 1.5h13A1.5 1.5 0 0 0 16 13V6a1.5 1.5 0 0 0-1.5-1.5h-13A1.5 1.5 0 0 0 0 6zM2 3a.5.5 0 0 0 .5.5h11a.5.5 0 0 0 0-1h-11A.5.5 0 0 0 2 3m2-2a.5.5 0 0 0 .5.5h7a.5.5 0 0 0 0-1h-7A.5.5 0 0 0 4 1" />
                    </svg>
                    Sub-category
                </a>
            </div>
        </div>

        {{-- Stats Cards --}}
        @php
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
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
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
        </div>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 pt-1">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @elseif (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 pt-1"
                data-success="true">
                {{ session('success') }}
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Clear form fields after successful submission
                    clearModalFields();
                });
            </script>
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
                    <select id="searchtype" name="searchtype"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                        onchange="this.form.submit()">
                        <option value="">All Types</option>
                        @foreach ($accountTypes as $type)
                            <option value="{{ $type->id }}" {{ request('searchtype') == $type->id ? 'selected' : '' }}>
                                {{ $type->description }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:w-1/3 w-full">
                    <select id="searchstatus" name="searchstatus"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                        onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="1" {{ request('searchstatus') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('searchstatus') === '0' ? 'selected' : '' }}>Inactive</option>
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
                        <th scope="col" class="px-4 py-3 text-left w-[100px]">Code</th>
                        <th scope="col" class="px-4 py-3 text-left w-[200px]">Description</th>
                        <th scope="col" class="px-4 py-3 text-left w-[150px]">Type</th>
                        <th scope="col" class="px-4 py-3 text-left w-[250px]">Category</th>
                        <th scope="col" class="px-4 py-3 text-left w-[350px]">Sub-category</th>
                        <th scope="col" class="px-4 py-3 text-left w-[150px]">Status</th>
                        <th scope="col" class="px-4 py-3 text-center w-[50px]">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    @forelse($mainAccounts as $mainAccount)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium w-[100px]">{{ $mainAccount->code }}</td>
                            <td class="px-4 py-3 w-[200px]">{{ $mainAccount->description }}</td>
                            <td class="px-4 py-3 w-[150px]">{{ $mainAccount->accountType->description ?? '' }}</td>
                            <td class="px-4 py-3 w-[250px]">{{ $mainAccount->accountCategory->description ?? '' }}</td>
                            <td class="px-4 py-3 w-[350px]">{{ $mainAccount->accountSubcategory->description ?? '' }}</td>
                            {{-- <td class="px-4 py-3 w-[150px]">
                                <a href="{{ route('location.sublocation.index', $mainAccount->id) }}"
                                    class="font-semibold text-gray-600 hover:underline">
                                    ({{ $mainAccount->sublocations_count }})
                                    sub-locations
                                </a>
                            </td> --}}
                            <td class="px-4 py-3 w-[150px] text-xs font-semibold">
                                @php
                                    $statuses = [
                                        0 => ['color' => 'bg-red-100 text-red-600', 'label' => 'Inactive'],
                                        1 => ['color' => 'bg-green-100 text-green-700', 'label' => 'Active'],
                                    ];
                                    $status = $statuses[$mainAccount->status] ?? [
                                        'color' => 'bg-gray-100 text-gray-600',
                                        'label' => 'Unknown',
                                    ];
                                @endphp

                                <span class="px-2 py-1 text-xs rounded-full {{ $status['color'] }}">
                                    {{ $status['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 w-[50px]">
                                <div class="flex items-center justify-center space-x-2">
                                    <button type="button" title="Edit account: {{ $mainAccount->description }}"
                                        data-modal-target="edit-modal" data-modal-toggle="edit-modal"
                                        data-id="{{ $mainAccount->id }}" data-code="{{ $mainAccount->code }}"
                                        data-description="{{ $mainAccount->description }}"
                                        data-type="{{ $mainAccount->account_type_id }}"
                                        data-category="{{ $mainAccount->account_category_id }}"
                                        data-subcategory="{{ $mainAccount->account_subcategory_id }}"
                                        data-status="{{ $mainAccount->status }}" onclick="openEditModal(this)"
                                        class="group flex space-x-1 text-gray-500 hover:text-blue-600 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                            <path
                                                d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z" />
                                            <path fill-rule="evenodd"
                                                d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z" />
                                        </svg>
                                        {{-- <span class="hidden group-hover:inline transition-opacity duration-200"></span> --}}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-gray-500">
                                No main accounts found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination Links -->
        <div
            class="w-full md:w-auto text-xs flex flex-col md:flex-row space-y-2 md:space-y-0 items-stretch md:items-center justify-end md:space-x-3 flex-shrink-0 mb-2">
            {{ $mainAccounts->links() }}
        </div>
    </div>


    <!-- Create account modal -->
    <div id="add-modal" tabindex="-1" aria-hidden="true"
        class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-modal md:h-full">
        <div class="relative p-4 w-full max-w-md h-full md:h-auto">
            <!-- Modal content -->
            <div class="relative p-4 bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5">
                <!-- Modal header -->
                <div class="flex justify-between items-center pb-4 mb-2 rounded-t border-b sm:mb-5 dark:border-gray-600">
                    <h3 class="text-md font-semibold text-gray-900 dark:text-white">
                        Add New Account
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
                    <form action="{{ route('gl.chart.store') }}" method="POST">
                        @csrf
                        <div class="grid ml-1 mr-1 gap-2 mb-4 sm:grid-cols-2">
                            <div class="md:col-span-1">
                                <label for="account_code"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Account
                                    Code*</label>
                                <input type="text" name="account_code" id="account_code"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    placeholder="e.g. 1000" required>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="description"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Description*</label>
                                <textarea type="text" name="description" id="description" rows="3"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    placeholder="e.g. Cash on Hand" required></textarea>
                            </div>

                            <div class="md:col-span-1">
                                <label for="type"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Type*</label>
                                <select id="type" name="type"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    required>
                                    {{-- <option selected="">Select product type</option> --}}
                                    @foreach ($accountTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->description }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="md:col-span-1">
                                <label for="category" class="block text-xs font-medium text-gray-900">
                                    Category*
                                </label>

                                <select id="category" name="category"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-full p-2.5"
                                    required>
                                    <option value="">Select Category</option>
                                </select>
                            </div>

                            <div class="md:col-span-1">
                                <label for="subcategory" class="block text-xs font-medium text-gray-900">
                                    Sub-category*
                                </label>

                                <select id="subcategory" name="subcategory"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-full p-2.5"
                                    required>
                                    <option value="">Select Sub-category</option>
                                </select>
                            </div>

                            <div class="md:col-span-1">
                                <label for="status"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Status*</label>
                                <select id="status" name="status"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    required>
                                    {{-- <option selected="">Select product type</option> --}}
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
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
                            Add Account
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End create account modal -->

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
                        Update Account
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
                                <label for="edit_account_code"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Account
                                    Code*</label>
                                <input type="text" name="edit_account_code" id="edit_account_code"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    placeholder="e.g. 1000" required>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="edit_description"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Description*</label>
                                <textarea type="text" name="edit_description" id="edit_description" rows="3"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    placeholder="e.g. Cash on Hand" required></textarea>
                            </div>

                            <div class="md:col-span-1">
                                <label for="edit_type"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Type*</label>
                                <select id="edit_type" name="edit_type"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    required>
                                    {{-- <option selected="">Select product type</option> --}}
                                    @foreach ($accountTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->description }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="md:col-span-1">
                                <label for="edit_category" class="block text-xs font-medium text-gray-900">
                                    Category*
                                </label>

                                <select id="edit_category" name="edit_category"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-full p-2.5"
                                    required>
                                    <option value="">Select Category</option>
                                </select>
                            </div>

                            <div class="md:col-span-1">
                                <label for="edit_subcategory" class="block text-xs font-medium text-gray-900">
                                    Sub-category*
                                </label>

                                <select id="edit_subcategory" name="edit_subcategory"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-full p-2.5"
                                    required>
                                    <option value="">Select Sub-category</option>
                                </select>
                            </div>

                            <div class="md:col-span-1">
                                <label for="edit_status"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Status*</label>
                                <select id="edit_status" name="edit_status"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    required>
                                    {{-- <option selected="">Select product type</option> --}}
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" @if (Auth::user()->role == 2) disabled @endif
                            class="btn-Update mt-2 text-white inline-flex items-center bg-gray-700 hover:bg-gray-800 focus:ring-4 focus:outline-none focus:ring-gray-300 font-medium rounded-md text-xs px-5 py-2.5 text-center dark:bg-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-800 {{ Auth::user()->role == 2 ? ' cursor-not-allowed' : '' }}">
                            {{-- <svg class="me-1 -ms-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path></svg> --}}
                            Update Account
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End edit modal -->

    <script>
        const accountCategories = @json($accountCategories);
        const accountSubcategories = @json($accountSubcategories);
    </script>

    <script>
        function clearModalFields() {
            // Clear all form fields
            const form = document.querySelector('form');
            form.reset();

            // Remove any success messages after a delay
            setTimeout(() => {
                const successMessage = document.querySelector('[data-success]');
                if (successMessage) {
                    successMessage.remove();
                }
            }, 3000);
        }

        document.addEventListener('DOMContentLoaded', function() {

            const typeSelect = document.getElementById('type');
            const categorySelect = document.getElementById('category');
            const subcategorySelect = document.getElementById('subcategory');

            // Load Categories
            function loadCategories(typeId, selectedCategory = null) {

                categorySelect.innerHTML = '<option value="">Select Category</option>';

                // Clear subcategory whenever type changes
                if (subcategorySelect) {
                    subcategorySelect.innerHTML = '<option value="">Select Sub-category</option>';
                }

                accountCategories
                    .filter(category => category.account_type_id == typeId)
                    .forEach(category => {

                        let option = document.createElement('option');
                        option.value = category.id;
                        option.textContent = category.description;

                        if (selectedCategory && selectedCategory == category.id) {
                            option.selected = true;
                        }

                        categorySelect.appendChild(option);
                    });

                if (selectedCategory && subcategorySelect) {
                    loadSubcategories(selectedCategory);
                }
            }

            // Load Subcategories
            function loadSubcategories(categoryId, selectedSubcategory = null) {

                if (!subcategorySelect) return;

                subcategorySelect.innerHTML = '<option value="">Select Sub-category</option>';

                accountSubcategories
                    .filter(subcategory => subcategory.account_category_id == categoryId)
                    .forEach(subcategory => {

                        let option = document.createElement('option');
                        option.value = subcategory.id;
                        option.textContent = subcategory.description;

                        if (selectedSubcategory && selectedSubcategory == subcategory.id) {
                            option.selected = true;
                        }

                        subcategorySelect.appendChild(option);
                    });
            }

            // When Account Type changes
            typeSelect.addEventListener('change', function() {
                loadCategories(this.value);
            });

            // When Category changes
            categorySelect.addEventListener('change', function() {
                loadSubcategories(this.value);
            });

            // Initial Load
            if (typeSelect.value) {
                loadCategories(typeSelect.value);
            }

        });

        function openEditModal(button) {

            const id = button.dataset.id;
            const typeId = button.dataset.type;
            const categoryId = button.dataset.category;
            const subcategoryId = button.dataset.subcategory;

            document.getElementById('edit_id').value = id;
            document.getElementById('edit_account_code').value = button.dataset.code;
            document.getElementById('edit_description').value = button.dataset.description;
            document.getElementById('edit_type').value = typeId;
            document.getElementById('edit_status').value = button.dataset.status;

            // Load Categories
            const editCategory = document.getElementById('edit_category');
            editCategory.innerHTML = '<option value="">Select Category</option>';

            accountCategories
                .filter(category => category.account_type_id == typeId)
                .forEach(category => {

                    let option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.description;

                    if (category.id == categoryId) {
                        option.selected = true;
                    }

                    editCategory.appendChild(option);

                });

            // Load Subcategories
            const editSubcategory = document.getElementById('edit_subcategory');
            editSubcategory.innerHTML = '<option value="">Select Sub-category</option>';

            accountSubcategories
                .filter(subcategory => subcategory.account_category_id == categoryId)
                .forEach(subcategory => {

                    let option = document.createElement('option');
                    option.value = subcategory.id;
                    option.textContent = subcategory.description;

                    if (subcategory.id == subcategoryId) {
                        option.selected = true;
                    }

                    editSubcategory.appendChild(option);

                });

            document.getElementById('editForm').action = `/gl/chart/${id}`;
        }

        const editType = document.getElementById('edit_type');
        const editCategory = document.getElementById('edit_category');
        const editSubcategory = document.getElementById('edit_subcategory');

        editType.addEventListener('change', function() {

            editCategory.innerHTML = '<option value="">Select Category</option>';
            editSubcategory.innerHTML = '<option value="">Select Sub-category</option>';

            accountCategories
                .filter(category => category.account_type_id == this.value)
                .forEach(category => {

                    let option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.description;

                    editCategory.appendChild(option);

                });

        });

        editCategory.addEventListener('change', function() {

            editSubcategory.innerHTML = '<option value="">Select Sub-category</option>';

            accountSubcategories
                .filter(subcategory => subcategory.account_category_id == this.value)
                .forEach(subcategory => {

                    let option = document.createElement('option');
                    option.value = subcategory.id;
                    option.textContent = subcategory.description;

                    editSubcategory.appendChild(option);

                });

        });
    </script>
@endsection
