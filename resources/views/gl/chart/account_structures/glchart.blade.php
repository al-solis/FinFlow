{{-- resources/views/gl/chart_of_accounts/index.blade.php --}}

@extends('dashboard')
@section('content')
    <div class="p-6 space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">
                    Chart of Accounts
                </h1>
                <p class="text-sm text-gray-500">
                    {{ $accountStructure->name }} - {{ $accountStructure->description }}
                    <span class="ml-2 text-xs bg-gray-100 px-2 py-1 rounded-full">
                        {{ number_format($stats['total']) }} accounts
                    </span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('gl.structure') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium text-gray border border-gray-300 bg-gray-100 rounded-lg hover:bg-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back
                </a>

                <a href="{{ route('gl.chart_of_accounts.export', $accountStructure) }}"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export CSV
                </a>
            </div>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            @php
                $statCards = [
                    ['title' => 'Total Accounts', 'value' => $stats['total'], 'color' => 'blue'],
                    ['title' => 'Active', 'value' => $stats['active'], 'color' => 'green'],
                    ['title' => 'Inactive', 'value' => $stats['inactive'], 'color' => 'red'],
                    ['title' => 'Posting Accounts', 'value' => $stats['posting'], 'color' => 'purple'],
                    ['title' => 'Non-Posting', 'value' => $stats['non_posting'], 'color' => 'orange'],
                ];
            @endphp

            @foreach ($statCards as $card)
                <div class="bg-white border rounded-xl p-4">
                    <p class="text-xs text-gray-500">{{ $card['title'] }}</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($card['value']) }}</p>
                </div>
            @endforeach
        </div>

        {{-- Alerts --}}
        @if ($errors->any())
            <div id="alert-message" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @elseif (session('success'))
            <div id="alert-message"
                class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                {{ session('success') }}
            </div>
        @elseif(session('error'))
            <div id="alert-message" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        {{-- Filters --}}
        <form method="GET" action="{{ route('gl.chart_of_accounts.index', $accountStructure) }}" id="filterForm">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-2 text-xs md:text-sm">
                <div class="md:col-span-2">
                    <input type="text" name="search" placeholder="Search by code or name..."
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5"
                        value="{{ request()->query('search') }}" oninput="document.getElementById('filterForm').submit()">
                </div>

                <div>
                    <select name="account_type_id"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5"
                        onchange="document.getElementById('filterForm').submit()">
                        <option value="">All Types</option>
                        @foreach ($accountTypes as $type)
                            <option value="{{ $type->id }}"
                                {{ request('account_type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->description }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <select name="account_category_id"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5"
                        onchange="document.getElementById('filterForm').submit()">
                        <option value="">All Categories</option>
                        @foreach ($accountCategories as $category)
                            <option value="{{ $category->id }}"
                                {{ request('account_category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->description }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <select name="status"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5"
                        onchange="document.getElementById('filterForm').submit()">
                        <option value="">All Status</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div>
                    <select name="is_posting"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5"
                        onchange="document.getElementById('filterForm').submit()">
                        <option value="">All Types</option>
                        <option value="1" {{ request('is_posting') === '1' ? 'selected' : '' }}>Posting</option>
                        <option value="0" {{ request('is_posting') === '0' ? 'selected' : '' }}>Non-Posting</option>
                    </select>
                </div>

                <div>
                    <a href="{{ route('gl.chart_of_accounts.index', $accountStructure) }}"
                        class="inline-flex items-center justify-center w-full px-4 py-2 text-xs font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200">
                        Clear Filters
                    </a>
                </div>
            </div>
        </form>

        {{-- Bulk Actions --}}
        <div class="flex items-center gap-2 bg-gray-50 p-3 rounded-lg border">
            <span class="text-xs text-gray-600">Bulk Actions:</span>
            <button id="bulkActivateBtn"
                class="px-3 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                disabled>
                Activate Selected
            </button>
            <button id="bulkDeactivateBtn"
                class="px-3 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                disabled>
                Deactivate Selected
            </button>
            <span id="selectedCount" class="text-xs text-gray-500 ml-2">0 selected</span>
        </div>

        {{-- Table --}}
        <div class="bg-white border rounded-xl overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead class="bg-gray-200 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left w-[30px]">
                            <input type="checkbox" id="selectAll" class="rounded border-gray-300">
                        </th>
                        <th class="px-4 py-3 text-left w-[150px]">Account Code</th>
                        <th class="px-4 py-3 text-left">Account Name</th>
                        <th class="px-4 py-3 text-left w-[140px]">Type</th>
                        <th class="px-4 py-3 text-left w-[160px]">Category</th>
                        <th class="px-4 py-3 text-left w-[60px]">Posting</th>
                        <th class="px-4 py-3 text-left w-[80px]">Status</th>
                        <th class="px-4 py-3 text-center w-[120px]">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    @forelse($chartAccounts as $account)
                        <tr class="hover:bg-gray-50" data-account-id="{{ $account->id }}">
                            <td class="px-4 py-3">
                                <input type="checkbox" class="account-checkbox rounded border-gray-300"
                                    value="{{ $account->id }}">
                            </td>
                            <td class="px-4 py-3 font-mono font-medium text-gray-900">
                                {{ $account->account_code }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="account-name-display">{{ $account->account_name }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs">
                                    {{ $account->accountType->description ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $account->accountCategory->description ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($account->is_posting)
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Yes</span>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs">No</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="status-badge px-2 py-1 rounded-full text-xs 
                            {{ $account->status ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                                    {{ $account->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button" title="Edit account" data-modal-target="edit-modal"
                                        data-modal-toggle="edit-modal" data-id="{{ $account->id }}"
                                        data-code="{{ $account->account_code }}"
                                        data-name="{{ $account->account_name }}"
                                        data-status="{{ $account->status ? '1' : '0' }}" onclick="openEditModal(this)"
                                        class="text-blue-600 hover:text-blue-800 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    <button type="button" title="Toggle status"
                                        onclick="toggleStatus({{ $account->id }}, {{ $account->status ? 0 : 1 }})"
                                        class="text-gray-500 hover:text-gray-700 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-gray-500">
                                <svg class="mx-auto mb-4 w-16 h-16 text-gray-300" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-lg font-medium text-gray-600">No accounts found</p>
                                <p class="text-sm">Try adjusting your filters or generate the chart of accounts first.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">
                Showing {{ $chartAccounts->firstItem() ?? 0 }} to {{ $chartAccounts->lastItem() ?? 0 }} of
                {{ $chartAccounts->total() }} accounts
            </div>
            <div>
                {{ $chartAccounts->appends(request()->query())->links() }}
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div id="edit-modal" tabindex="-1" aria-hidden="true"
        class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
                <!-- Modal header -->
                <div
                    class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600 border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Edit Account
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

                        <div class="mb-4">
                            <label class="block text-xs font-medium text-gray-900 dark:text-white mb-1">Account
                                Code</label>
                            <input type="text" id="edit_code" disabled
                                class="bg-gray-100 border border-gray-300 text-gray-500 text-xs rounded-lg block w-full p-2.5 cursor-not-allowed">
                        </div>

                        <div class="mb-4">
                            <label for="edit_name"
                                class="block text-xs font-medium text-gray-900 dark:text-white mb-1">Account Name *</label>
                            <input type="text" name="account_name" id="edit_name" maxlength="255" required
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5">
                        </div>

                        <div class="mb-4">
                            <label for="edit_status"
                                class="block text-xs font-medium text-gray-900 dark:text-white mb-1">Status</label>
                            <select name="status" id="edit_status"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <button type="submit"
                            class="text-white inline-flex items-center bg-gray-700 hover:bg-gray-800 focus:ring-4 focus:outline-none focus:ring-gray-300 font-medium rounded-md text-xs px-5 py-2.5 text-center dark:bg-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-800">
                            Update Account
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk Status Form --}}
    <form id="bulkStatusForm" method="POST" action="{{ route('gl.chart_of_accounts.bulk_status', $accountStructure) }}"
        style="display:none;">
        @csrf
        <input type="hidden" name="account_ids" id="bulkAccountIds">
        <input type="hidden" name="status" id="bulkStatus">
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-dismiss alerts
            const alert = document.getElementById('alert-message');
            if (alert) {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 3000);
            }

            // Select all functionality
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.account-checkbox');

            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateBulkButtons();
            });

            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateBulkButtons);
            });

            function updateBulkButtons() {
                const checked = document.querySelectorAll('.account-checkbox:checked');
                const count = checked.length;

                document.getElementById('selectedCount').textContent = count + ' selected';
                document.getElementById('bulkActivateBtn').disabled = count === 0;
                document.getElementById('bulkDeactivateBtn').disabled = count === 0;
            }

            // Bulk actions
            document.getElementById('bulkActivateBtn').addEventListener('click', function() {
                if (confirm('Activate all selected accounts?')) {
                    submitBulkStatus(1);
                }
            });

            document.getElementById('bulkDeactivateBtn').addEventListener('click', function() {
                if (confirm('Deactivate all selected accounts?')) {
                    submitBulkStatus(0);
                }
            });

            function submitBulkStatus(status) {
                const checked = document.querySelectorAll('.account-checkbox:checked');
                const ids = Array.from(checked).map(cb => cb.value);

                document.getElementById('bulkAccountIds').value = JSON.stringify(ids);
                document.getElementById('bulkStatus').value = status;
                document.getElementById('bulkStatusForm').submit();
            }
        });

        // Edit modal functions
        function openEditModal(button) {
            const id = button.dataset.id;
            const code = button.dataset.code;
            const name = button.dataset.name;
            const status = button.dataset.status;

            document.getElementById('edit_id').value = id;
            document.getElementById('edit_code').value = code;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_status').value = status;

            // Get the account structure ID from the page
            const structureId = '{{ $accountStructure->id }}';

            // Set the form action
            document.getElementById('editForm').action =
                `/gl/chart/account_structures/${structureId}/chart-of-accounts/${id}`;
        }

        // Toggle status function
        function toggleStatus(accountId, newStatus) {
            const statusText = newStatus === 1 ? 'activate' : 'deactivate';
            if (!confirm(`Are you sure you want to ${statusText} this account?`)) return;

            // Get the account row
            const row = document.querySelector(`tr[data-account-id="${accountId}"]`);
            if (!row) return;

            // Get current name from the row
            const nameSpan = row.querySelector('.account-name-display');
            const accountName = nameSpan ? nameSpan.textContent.trim() : 'Account';

            // Get the structure ID
            const structureId = '{{ $accountStructure->id }}';

            // Submit via form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/gl/chart/account_structures/${structureId}/chart-of-accounts/${accountId}`;

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);

            const method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = 'PUT';
            form.appendChild(method);

            const nameInput = document.createElement('input');
            nameInput.type = 'hidden';
            nameInput.name = 'account_name';
            nameInput.value = accountName;
            form.appendChild(nameInput);

            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = newStatus;
            form.appendChild(statusInput);

            document.body.appendChild(form);
            form.submit();
        }

        // Bulk status form
        document.getElementById('bulkActivateBtn')?.addEventListener('click', function() {
            if (confirm('Activate all selected accounts?')) {
                submitBulkStatus(1);
            }
        });

        document.getElementById('bulkDeactivateBtn')?.addEventListener('click', function() {
            if (confirm('Deactivate all selected accounts?')) {
                submitBulkStatus(0);
            }
        });

        function submitBulkStatus(status) {
            const checked = document.querySelectorAll('.account-checkbox:checked');
            const ids = Array.from(checked).map(cb => cb.value);

            document.getElementById('bulkAccountIds').value = JSON.stringify(ids);
            document.getElementById('bulkStatus').value = status;
            document.getElementById('bulkStatusForm').submit();
        }
    </script>

@endsection
