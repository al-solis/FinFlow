@extends('dashboard')
@section('content')
    <div class="p-6 space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Tax Formulas</h1>
                <p class="text-sm text-gray-500">
                    Specifies how the tax is computed for the Tax Management module.
                </p>
            </div>
            <div class="flex items-center gap-2 mt-0">
                <a href=""
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium text-gray border border-gray-300 bg-gray-100 rounded-lg hover:bg-gray-200 ">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back
                </a>

                <button data-modal-target="add-modal" data-modal-toggle="add-modal"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Tax Formula
                </button>
            </div>
        </div>

        {{-- Stats Cards --}}
        {{-- @php
            $cards = [
                [
                    'title' => 'Total Categories',
                    'value' => $totalCategories,
                    'color' => 'blue',
                    'icon' => '
                        <svg xmlns="http://www.w3.org/2000/svg" class = "w-5 h-5 text-blue-600" width="16" height="16" fill="currentColor" class="bi bi-grid-3x3-gap" viewBox="0 0 16 16">
                        <path d="M4 2v2H2V2zm1 12v-2a1 1 0 0 0-1-1H2a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1m0-5V7a1 1 0 0 0-1-1H2a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1m0-5V2a1 1 0 0 0-1-1H2a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1m5 10v-2a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1m0-5V7a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1m0-5V2a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1M9 2v2H7V2zm5 0v2h-2V2zM4 7v2H2V7zm5 0v2H7V7zm5 0h-2v2h2zM4 12v2H2v-2zm5 0v2H7v-2zm5 0v2h-2v-2zM12 1a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zm-1 6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1zm1 4a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1z"/>
                        </svg>',
                ],
                [
                    'title' => 'Active',
                    'value' => $activeCategories,
                    'color' => 'green',
                    'icon' => '
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M5 13l4 4L19 7" />
                        </svg>',
                ],
                [
                    'title' => 'Inactive',
                    'value' => $inactiveCategories,
                    'color' => 'red',
                    'icon' => '
                        <svg class="w-5 h-5 text-red-600" width="16" height="16" fill="currentColor" class="bi bi-x-lg" viewBox="0 0 16 16">
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
        </div> --}}

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
                    <input type="text" id="simple-search" name="search"
                        placeholder="Search by code, name or description..."
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                        value = "{{ request()->query('search') }}" oninput="this.form.submit()">
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
                        <th scope="col" class="px-4 py-3 text-left w-[80px]">Code</th>
                        <th scope="col" class="px-4 py-3 text-left w-[150px]">Name</th>
                        <th scope="col" class="px-4 py-3 text-left w-[80px]">Type</th>
                        <th scope="col" class="px-4 py-3 text-left w-[80px]">Basis</th>
                        <th scope="col" class="px-4 py-3 text-left w-[150px]">Expression</th>
                        <th scope="col" class="px-4 py-3 text-left w-[100px]">Status</th>
                        <th scope="col" class="px-4 py-3 text-center w-[50px]">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    @forelse($taxFormulas as $taxFormula)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 w-[80px]">{{ $taxFormula->code }}</td>
                            <td class="px-4 py-3 w-[150px]">{{ $taxFormula->name }}</td>
                            <td class="px-4 py-3 w-[80px]">{{ $taxFormula->type }}</td>
                            <td class="px-4 py-3 w-[80px]">{{ $taxFormula->basis }}</td>
                            <td class="px-4 py-3 w-[150px]">{{ $taxFormula->expression }}</td>
                            <td class="px-4 py-3 w-[100px] text-xs font-semibold">
                                @php
                                    $statuses = [
                                        0 => ['color' => 'bg-red-100 text-red-600', 'label' => 'Inactive'],
                                        1 => ['color' => 'bg-green-100 text-green-700', 'label' => 'Active'],
                                    ];
                                    $status = $statuses[$taxFormula->status] ?? [
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
                                    <button type="button" title="Edit tax type {{ $taxFormula->name }}"
                                        data-modal-target="edit-modal" data-modal-toggle="edit-modal"
                                        data-id="{{ $taxFormula->id }}" data-name="{{ $taxFormula->name }}"
                                        data-code="{{ $taxFormula->code }}" data-type="{{ $taxFormula->type }}"
                                        data-basis="{{ $taxFormula->basis }}"
                                        data-operation="{{ $taxFormula->operation }}"
                                        data-expression="{{ $taxFormula->expression }}"
                                        data-rounding="{{ $taxFormula->rounding }}"
                                        data-decimal_places="{{ $taxFormula->decimal_places }}"
                                        data-status="{{ $taxFormula->status }}" onclick="openEditModal(this)"
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
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                                <img src="{{ asset('images/tax-formula.svg') }}" alt="No data"
                                    class="mx-auto mb-4 w-24 h-28">
                                No tax formula found. Click here to <a href="#" data-modal-target="add-modal"
                                    data-modal-toggle="add-modal" class="text-blue-600 hover:underline">add a new
                                    tax formula</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination Links -->
        <div
            class="w-full md:w-auto text-xs flex flex-col md:flex-row space-y-2 md:space-y-0 items-stretch md:items-center justify-end md:space-x-3 flex-shrink-0 mb-2">
            {{ $taxFormulas->links() }}
        </div>
    </div>

    <!-- Create tax type modal -->
    <div id="add-modal" tabindex="-1" aria-hidden="true"
        class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-modal md:h-full">
        <div class="relative p-4 w-full max-w-xl h-full md:h-auto">
            <!-- Modal content -->
            <div class="relative p-4 bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5">
                <!-- Modal header -->
                <div class="flex justify-between items-center pb-4 mb-2 rounded-t border-b sm:mb-5 dark:border-gray-600">
                    <h3 class="text-md font-semibold text-gray-900 dark:text-white">
                        Add New Tax Formula
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
                    <form action="{{ route('tax.tf.store') }}" method="POST">
                        @csrf
                        <div class="grid ml-1 mr-1 gap-2 mb-4 sm:grid-cols-2">
                            <div class="sm:col-span-1">
                                <label for="code"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Code*</label>
                                <input type="text" name="code" id="code" maxLength="20"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    placeholder="e.g. VAT" required>
                            </div>

                            <div class="md:col-span-1">
                                <label for="type"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Type*</label>
                                <select id="type" name="type"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    required>
                                    {{-- <option selected="">Select product type</option> --}}
                                    <option value="Percentage">Percentage</option>
                                    <option value="FixedAmount">FixedAmount</option>
                                    <option value="Formula">Formula</option>
                                </select>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="name"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Name*</label>
                                <input type="text" name="name" id="name" maxlength="100"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    placeholder="e.g. Value Added Tax" required>
                            </div>

                            <div class="md:col-span-1">
                                <label for="basis"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Basis*</label>
                                <select id="basis" name="basis"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    required>
                                    {{-- <option selected="">Select product type</option> --}}
                                    <option value="Gross">Gross</option>
                                    <option value="Net">Net</option>
                                    <option value="Taxable">Taxable</option>
                                    <option value="VAT">VAT</option>
                                </select>
                            </div>

                            <div class="md:col-span-1">
                                <label for="operation"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Operation*</label>
                                <select id="operation" name="operation"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    required>
                                    {{-- <option selected="">Select product type</option> --}}
                                    <option value="Add">Add</option>
                                    <option value="Deduct">Deduct</option>
                                </select>
                            </div>

                            <div id="expression-section" style="display:none;" class="md:col-span-2">
                                <div class="mb-2">
                                    <label class="block text-xs font-medium">
                                        Formula Expression
                                    </label>

                                    <textarea id="expression" name="expression" rows="3" class="w-full rounded border-gray-300 text-xs">{{ old('expression') }}</textarea>

                                    @error('expression')
                                        <div class="text-red-600 text-sm mt-1">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <h6 class="font-semibold text-xs mb-2">Variables</h6>

                                    <div class="flex flex-wrap gap-2">

                                        @php
                                            $variables = [
                                                'Gross',
                                                'Net',
                                                'Taxable',
                                                'VAT',
                                                'PreviousTax',
                                                'Rate',
                                                'Quantity',
                                                'UnitPrice',
                                            ];
                                        @endphp

                                        @foreach ($variables as $var)
                                            <button type="button"
                                                class="px-3 py-1 rounded text-xs bg-blue-100 hover:bg-blue-200"
                                                onclick="insertToken('expression', '{{ $var }}')">
                                                {{ $var }}
                                            </button>
                                        @endforeach
                                    </div>

                                    <div class="mb-3 mt-2">
                                        <h6 class="font-semibold text-xs mb-2">
                                            Operators
                                        </h6>

                                        <div class="flex flex-wrap gap-2">
                                            @foreach (['+', '-', '*', '/', '(', ')'] as $op)
                                                <button type="button"
                                                    class="px-3 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200"
                                                    onclick="insertToken('expression', '{{ $op }}')">
                                                    {{ $op }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="md:col-span-1">
                                <label for="rounding"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Rounding*</label>
                                <select id="rounding" name="rounding"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    required>
                                    <option value="Round">Round</option>
                                    <option value="Floor">Floor</option>
                                    <option value="Ceiling">Ceiling</option>
                                </select>
                            </div>

                            <div class="sm:col-span-1">
                                <label for="decimal_places"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Decimal Places*</label>
                                <input type="number" name="decimal_places" id="decimal_places" min="0"
                                    max="10"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    placeholder="e.g. 2" required>
                            </div>

                            <div class="md:col-span-2">
                                <label for="status"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Status*</label>
                                <select id="status" name="status"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    required>
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
                            Add Tax Type
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End create tax formula modal -->

    <!-- Modal  Edit-->
    <div id="edit-modal" tabindex="-1" aria-hidden="true"
        class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-xl max-h-full">
            <!-- Modal content -->
            <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
                <!-- Modal header -->
                <div
                    class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600 border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Update Tax Formula
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
                            <div class="sm:col-span-1">
                                <label for="edit_code"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Code*</label>
                                <input type="text" name="edit_code" id="edit_code" maxLength="20"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    placeholder="e.g. VAT_EXCLUSIVE" required>
                            </div>

                            <div class="md:col-span-1">
                                <label for="edit_type"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Type*</label>
                                <select id="edit_type" name="edit_type"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    required>
                                    <option value="Percentage">Percentage</option>
                                    <option value="FixedAmount">FixedAmount</option>
                                    <option value="Formula">Formula</option>
                                </select>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="edit_name"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Name*</label>
                                <input type="text" name="edit_name" id="edit_name" maxlength="100"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    placeholder="e.g. VAT Exclusive" required>
                            </div>

                            <div class="md:col-span-1">
                                <label for="edit_basis"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Basis*</label>
                                <select id="edit_basis" name="edit_basis"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    required>
                                    {{-- <option selected="">Select product type</option> --}}
                                    <option value="Gross">Gross</option>
                                    <option value="Net">Net</option>
                                    <option value="Taxable">Taxable</option>
                                    <option value="VAT">VAT</option>
                                </select>
                            </div>

                            <div class="md:col-span-1">
                                <label for="edit_operation"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Operation*</label>
                                <select id="edit_operation" name="edit_operation"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    required>
                                    <option value="Add">Add</option>
                                    <option value="Deduct">Deduct</option>
                                </select>
                            </div>

                            <div id="edit-expression-section" style="display:none;" class="md:col-span-2">
                                <div class="mb-2">
                                    <label class="block text-xs font-medium">
                                        Formula Expression
                                    </label>

                                    <textarea id="edit_expression" name="edit_expression" rows="3" class="w-full rounded border-gray-300 text-xs">{{ old('expression') }}</textarea>

                                    @error('expression')
                                        <div class="text-red-600 text-sm mt-1">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <h6 class="font-semibold text-xs mb-2">Variables</h6>

                                    <div class="flex flex-wrap gap-2">

                                        @php
                                            $variables = [
                                                'Gross',
                                                'Net',
                                                'Taxable',
                                                'VAT',
                                                'PreviousTax',
                                                'Rate',
                                                'Quantity',
                                                'UnitPrice',
                                            ];
                                        @endphp

                                        @foreach ($variables as $var)
                                            <button type="button"
                                                class="px-3 py-1 rounded text-xs bg-blue-100 hover:bg-blue-200"
                                                onclick="insertToken('edit_expression', '{{ $var }}')">{{ $var }}</button>
                                        @endforeach
                                    </div>

                                    <div class="mb-3 mt-2">
                                        <h6 class="font-semibold text-xs mb-2">
                                            Operators
                                        </h6>

                                        <div class="flex flex-wrap gap-2">
                                            @foreach (['+', '-', '*', '/', '(', ')'] as $op)
                                                <button type="button"
                                                    class="px-3 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200"
                                                    onclick="insertToken('edit_expression', '{{ $op }}')">{{ $op }}</button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="md:col-span-1">
                                <label for="edit_rounding"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Rounding*</label>
                                <select id="edit_rounding" name="edit_rounding"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    required>
                                    <option value="Round">Round</option>
                                    <option value="Floor">Floor</option>
                                    <option value="Ceiling">Ceiling</option>
                                </select>
                            </div>

                            <div class="sm:col-span-1">
                                <label for="edit_decimal_places"
                                    class="block text-xs font-medium text-gray-900 dark:text-white">Decimal Places*</label>
                                <input type="number" name="edit_decimal_places" id="edit_decimal_places" min="0"
                                    max="10"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    placeholder="e.g. 2" required>
                            </div>


                            <div class="md:col-span-2">
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

                        <button type="submit"
                            class="mt-2 text-white inline-flex items-center bg-gray-700 hover:bg-gray-800 focus:ring-4 focus:outline-none focus:ring-gray-300 font-medium rounded-md text-xs px-5 py-2.5 text-center dark:bg-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-800">
                            {{-- <svg class="me-1 -ms-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path></svg> --}}
                            Update Tax Formula
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End edit modal -->

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

        function openEditModal(button) {
            const id = button.getAttribute('data-id');
            document.getElementById('edit_id').value = button.getAttribute('data-id');
            document.getElementById('edit_code').value = button.getAttribute('data-code');
            document.getElementById('edit_name').value = button.getAttribute('data-name');
            document.getElementById('edit_type').value = button.getAttribute('data-type');
            document.getElementById('edit_basis').value = button.getAttribute('data-basis');
            document.getElementById('edit_operation').value = button.getAttribute('data-operation');
            document.getElementById('edit_expression').value = button.getAttribute('data-expression');
            document.getElementById('edit_rounding').value = button.getAttribute('data-rounding');
            document.getElementById('edit_decimal_places').value = button.getAttribute('data-decimal_places');
            document.getElementById('edit_status').value = button.getAttribute('data-status');

            toggleEditExpression();

            const form = document.getElementById('editForm');
            form.action = `tax_formula/${id}`;
        }

        const type = document.getElementById('type');
        const section = document.getElementById('expression-section');

        function toggleExpression() {
            section.style.display =
                type.value === 'Formula' ? 'block' : 'none';
        }

        type.addEventListener('change', toggleExpression);
        toggleExpression();

        const editType = document.getElementById('edit_type');
        const editSection = document.getElementById('edit-expression-section');

        function toggleEditExpression() {
            editSection.style.display = editType.value === 'Formula' ? 'block' : 'none';
        }
        editType.addEventListener('change', toggleEditExpression);

        ['expression', 'edit_expression'].forEach(id => {
            document.getElementById(id).addEventListener('keydown', function(e) {
                const allowed = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
                    'Tab', 'Home', 'End', ' '
                ];
                if (allowed.includes(e.key) || e.ctrlKey || e.metaKey) return;
                e.preventDefault();
            });
        });

        function insertToken(targetId, token) {
            let input = document.getElementById(targetId);
            let start = input.selectionStart;
            let end = input.selectionEnd;
            input.setRangeText(token, start, end, 'end');
            input.focus();
        }
    </script>
@endsection
