@extends('dashboard')

@section('title', 'Access Rights')

@section('content')
    <div class="mx-auto max-w-6xl" x-data="accessRightsMatrix()">
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
                        class="text-green-600 hover:text-green-800">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        <div class="mt-5 mb-5">
            <h1 class="text-2xl font-bold text-gray-800">Access Rights</h1>
            <p class="mt-1 text-sm text-gray-500">Define what each role can see and do, per module and sub-module.</p>
        </div>

        <!-- Role selector -->
        <form method="GET" action="{{ route('admin.access') }}"
            class="mb-5 flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 bg-white p-4">
            <label class="text-xs font-medium text-gray-700">Role</label>
            <select name="role_id" onchange="this.form.submit()"
                class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs min-w-[220px]">
                @forelse ($roles as $role)
                    <option value="{{ $role->id }}" @selected($selectedRoleId == $role->id)>{{ $role->name }}</option>
                @empty
                    <option value="">No roles yet</option>
                @endforelse
            </select>
            <a href="{{ route('admin.roles.create') }}" class="text-xs text-blue-600 hover:underline">+ New Role</a>
        </form>

        @if ($roles->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center text-sm text-gray-500">
                No roles exist yet. <a href="{{ route('admin.roles.create') }}" class="text-blue-600 hover:underline">Create
                    one first</a>.
            </div>
        @else
            <form method="POST" action="{{ route('admin.access.update') }}" id="access-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="role_id" value="{{ $selectedRoleId }}">

                <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                        <p class="text-xs text-gray-500 flex-1">
                            Toggling a column header checks/unchecks that permission for every row currently shown.
                        </p>
                        <div class="flex gap-3 flex-shrink-0">
                            <button type="button" onclick="window.history.back()"
                                class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 transition-all duration-200">
                                Back
                            </button>
                            <button type="submit"
                                class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                                Save Changes
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead class="border-y border-gray-200 bg-gray-50 uppercase text-gray-500">
                                <tr>
                                    <th class="px-6 py-3 text-left">Module / Sub-Module</th>
                                    <th class="px-3 py-3 text-center w-20">
                                        <div class="flex flex-col items-center gap-1">
                                            <span>Create</span>
                                            <input type="checkbox"
                                                @change="toggleColumn('can_create', $event.target.checked)">
                                        </div>
                                    </th>
                                    <th class="px-3 py-3 text-center w-20">
                                        <div class="flex flex-col items-center gap-1">
                                            <span>Read</span>
                                            <input type="checkbox"
                                                @change="toggleColumn('can_read', $event.target.checked)">
                                        </div>
                                    </th>
                                    <th class="px-3 py-3 text-center w-20">
                                        <div class="flex flex-col items-center gap-1">
                                            <span>Update</span>
                                            <input type="checkbox"
                                                @change="toggleColumn('can_update', $event.target.checked)">
                                        </div>
                                    </th>
                                    <th class="px-3 py-3 text-center w-20">
                                        <div class="flex flex-col items-center gap-1">
                                            <span>Delete</span>
                                            <input type="checkbox"
                                                @change="toggleColumn('can_delete', $event.target.checked)">
                                        </div>
                                    </th>
                                    <th class="px-3 py-3 text-center w-16">All</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($modules as $module)
                                    @php
                                        $subModules = $module->subModules;
                                        $rowKeys = $subModules->isEmpty()
                                            ? [$module->id . '-null']
                                            : $subModules->map(fn($sm) => $module->id . '-' . $sm->id)->all();

                                        // Define modules that should be disabled
                                        $disabledModules = [
                                            'AR',
                                            'FA',
                                            'IA',
                                            'PUR',
                                            'SALES',
                                            'BUDGET',
                                            'COST',
                                            'MASTER',
                                        ];
                                        $isDisabled = in_array($module->code, $disabledModules);
                                    @endphp

                                    <!-- Module header row -->
                                    <tr class="bg-gray-50/70">
                                        <td colspan="5" class="px-6 py-2 font-semibold text-gray-800">
                                            {{ $module->name }}
                                            <span class="ml-2 font-normal text-gray-400">({{ $module->code }})</span>
                                            @if ($isDisabled)
                                                <span
                                                    class="ml-2 inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-500">
                                                    Features Not Applicable
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <input type="checkbox"
                                                @change="toggleModule('{{ implode(',', $rowKeys) }}', $event.target.checked)"
                                                {{ $isDisabled ? 'disabled' : '' }}>
                                        </td>
                                    </tr>

                                    @if ($subModules->isEmpty())
                                        @php
                                            $key = $module->id . '-null';
                                            $existing = $rightsMap->get($key);
                                        @endphp
                                        <tr class="hover:bg-gray-50" data-row-key="{{ $key }}">
                                            <td class="px-6 py-2 pl-10 text-gray-600">
                                                <span class="text-gray-400">(module-level access)</span>
                                            </td>
                                            @foreach (['can_create', 'can_read', 'can_update', 'can_delete'] as $flag)
                                                <td class="px-3 py-2 text-center">
                                                    <input type="hidden"
                                                        name="rights[{{ $module->id }}][null][{{ $flag }}]"
                                                        value="0">
                                                    <input type="checkbox"
                                                        name="rights[{{ $module->id }}][null][{{ $flag }}]"
                                                        value="1" data-flag="{{ $flag }}"
                                                        data-row="{{ $key }}" @checked($existing && $existing->$flag)
                                                        {{ $isDisabled ? 'disabled' : '' }}>
                                                </td>
                                            @endforeach
                                            <td class="px-3 py-2 text-center">
                                                <input type="checkbox" data-row-all="{{ $key }}"
                                                    @change="toggleRow('{{ $key }}', $event.target.checked)"
                                                    {{ $isDisabled ? 'disabled' : '' }}>
                                            </td>
                                        </tr>
                                    @else
                                        @foreach ($subModules as $subModule)
                                            @php
                                                $key = $module->id . '-' . $subModule->id;
                                                $existing = $rightsMap->get($key);
                                            @endphp
                                            <tr class="hover:bg-gray-50" data-row-key="{{ $key }}">
                                                <td class="px-6 py-2 pl-10 text-gray-700">{{ $subModule->name }}</td>
                                                @foreach (['can_create', 'can_read', 'can_update', 'can_delete'] as $flag)
                                                    <td class="px-3 py-2 text-center">
                                                        <input type="hidden"
                                                            name="rights[{{ $module->id }}][{{ $subModule->id }}][{{ $flag }}]"
                                                            value="0">
                                                        <input type="checkbox"
                                                            name="rights[{{ $module->id }}][{{ $subModule->id }}][{{ $flag }}]"
                                                            value="1" data-flag="{{ $flag }}"
                                                            data-row="{{ $key }}" @checked($existing && $existing->$flag)
                                                            {{ $isDisabled ? 'disabled' : '' }}>
                                                    </td>
                                                @endforeach
                                                <td class="px-3 py-2 text-center">
                                                    <input type="checkbox" data-row-all="{{ $key }}"
                                                        @change="toggleRow('{{ $key }}', $event.target.checked)"
                                                        {{ $isDisabled ? 'disabled' : '' }}>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                            No active modules found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-end border-t border-gray-200 px-6 py-4">
                        <button type="submit"
                            class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>

    <script>
        function accessRightsMatrix() {
            return {
                toggleColumn(flag, checked) {
                    document.querySelectorAll(`input[data-flag="${flag}"]:not([disabled])`).forEach(cb => {
                        cb.checked = checked;
                    });
                },

                toggleRow(rowKey, checked) {
                    document.querySelectorAll(`input[data-row="${rowKey}"]:not([disabled])`).forEach(cb => {
                        cb.checked = checked;
                    });
                    const rowAll = document.querySelector(`input[data-row-all="${rowKey}"]:not([disabled])`);
                    if (rowAll) rowAll.checked = checked;
                },

                toggleModule(rowKeysCsv, checked) {
                    const rowKeys = rowKeysCsv.split(',');
                    rowKeys.forEach(rowKey => {
                        document.querySelectorAll(`input[data-row="${rowKey}"]:not([disabled])`).forEach(cb => {
                            cb.checked = checked;
                        });
                        const rowAll = document.querySelector(`input[data-row-all="${rowKey}"]:not([disabled])`);
                        if (rowAll) rowAll.checked = checked;
                    });
                },
            };
        }
    </script>
@endsection
