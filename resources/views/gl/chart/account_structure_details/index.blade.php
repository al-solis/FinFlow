@extends('dashboard')
@section('content')
    <div class="p-6 space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">
                    Configured Segments ({{ $details->count() }})
                </h1>
                <p class="text-sm text-gray-500">{{ $accountStructure->name }} - {{ $accountStructure->description }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('gl.structure') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium text-gray border border-gray-300 bg-gray-100 rounded-lg hover:bg-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back
                </a>

                <form method="POST" action="{{ route('gl.structure.sync', $accountStructure->id) }}"
                    onsubmit="return confirm('Synchronize Chart of Accounts? Only missing accounts will be generated.')">
                    @csrf

                    <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                        @if ($accountStructure->last_synced_at)
                            Synchronize Chart of Accounts
                        @else
                            Generate Chart of Accounts
                        @endif
                    </button>
                </form>

                <button {{ isset($accountStructure->last_synced_at) ? 'disabled' : '' }}
                    data-modal-target="add-detail-modal" data-modal-toggle="add-detail-modal"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 
                    {{ isset($accountStructure->last_synced_at) ? 'opacity-50 cursor-not-allowed' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Segment
                </button>
            </div>
        </div>

        {{-- Preview --}}
        @php
            $exampleMainAccount = \App\Models\main_account::first();
            $previewParts = $details->map(function ($d) use ($exampleMainAccount) {
                return $d->source_type === 'main_account'
                    ? $exampleMainAccount->code ?? '1000'
                    : str_repeat('0', $d->segment->length ?? 2);
            });
        @endphp
        <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-5 space-y-4">
            <h3 class="font-semibold text-indigo-900">Account Code Format Preview</h3>

            <div class="flex items-center gap-2">
                @foreach ($details as $i => $detail)
                    <span class="px-3 py-2 bg-indigo-50 text-indigo-700 font-mono rounded-lg border border-indigo-200">
                        {{ $previewParts[$i] }}
                    </span>
                    @if (!$loop->last)
                        <span class="text-gray-400">{{ $detail->separator }}</span>
                    @endif
                @endforeach
            </div>

            <div class="bg-gray-50 border border-indigo-200 rounded-lg p-4 font-mono text-xs text-gray-600 space-y-1">
                <div class="font-semibold text-gray-800">{{ $previewParts->implode(' - ') }}</div>
                @foreach ($details->reverse() as $detail)
                    <div>└── {{ $detail->displayName }}{{ $detail->source_type === 'main_account' ? ' ★' : '' }}</div>
                @endforeach
                <div class="pt-2 text-gray-400">★ GL Account segment (Chart of Accounts)</div>
            </div>
        </div>

        @if ($errors->any())
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
        @endif

        {{-- Table --}}
        <div class="bg-white border rounded-xl overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead class="bg-gray-200 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left w-[70px]">Order</th>
                        <th class="px-4 py-3 text-left w-[200px]">Segment Name</th>
                        <th class="px-4 py-3 text-left w-[100px]">Label</th>
                        <th class="px-4 py-3 text-left w-[100px]">Code Length</th>
                        <th class="px-4 py-3 text-left w-[120px]">Type</th>
                        <th class="px-4 py-3 text-left w-[90px]">Status</th>
                        @if (!$accountStructure->last_synced_at)
                            <th class="px-4 py-3 text-center w-[140px]">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($details as $index => $detail)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-medium">{{ $detail->displayName }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="px-2 py-1 bg-blue-50 text-blue-700 rounded font-mono">{{ $detail->label }}</span>
                            </td>
                            <td class="px-4 py-3">{{ $detail->codeLengthLabel }}</td>
                            <td class="px-4 py-3">
                                @if ($detail->source_type === 'main_account')
                                    <span class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded-full">GL Account</span>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full">Dimension</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($detail->isActive)
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full">Active</span>
                                @else
                                    <span class="px-2 py-1 bg-red-100 text-red-600 rounded-full">Inactive</span>
                                @endif
                            </td>
                            @if (!$accountStructure->last_synced_at)
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- @if ($detail->source_type !== 'main_account') --}}
                                        @if (!$loop->first)
                                            <form method="POST"
                                                action="{{ route('gl.chart.account_structure_details.move-up', [$accountStructure->id, $detail->id]) }}">
                                                @csrf @method('PUT')
                                                <button type="submit" title="Move up"
                                                    class="text-gray-500 hover:text-gray-900">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        fill="currentColor" class="bi bi-arrow-up-short"
                                                        viewBox="0 0 16 16">
                                                        <path fill-rule="evenodd"
                                                            d="M8 12a.5.5 0 0 0 .5-.5V5.707l2.146 2.147a.5.5 0 0 0 .708-.708l-3-3a.5.5 0 0 0-.708 0l-3 3a.5.5 0 1 0 .708.708L7.5 5.707V11.5a.5.5 0 0 0 .5.5" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif

                                        @if (!$loop->last)
                                            <form method="POST"
                                                action="{{ route('gl.chart.account_structure_details.move-down', [$accountStructure->id, $detail->id]) }}">
                                                @csrf @method('PUT')
                                                <button type="submit" title="Move down"
                                                    class="text-gray-500 hover:text-gray-900">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        fill="currentColor" class="bi bi-arrow-down-short"
                                                        viewBox="0 0 16 16">
                                                        <path fill-rule="evenodd"
                                                            d="M8 4a.5.5 0 0 1 .5.5v5.793l2.146-2.147a.5.5 0 0 1 .708.708l-3 3a.5.5 0 0 1-.708 0l-3-3a.5.5 0 1 1 .708-.708L7.5 10.293V4.5A.5.5 0 0 1 8 4" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif

                                        @if ($detail->source_type != 'main_account')
                                            <form method="POST"
                                                action="{{ route('gl.chart.account_structure_details.destroy', [$accountStructure->id, $detail->id]) }}"
                                                onsubmit="return confirm('Remove this segment from the structure?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" title="Remove"
                                                    class="text-red-500 hover:text-red-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                                        <path
                                                            d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z" />
                                                        <path
                                                            d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                        {{-- @else
                                        <span class="text-gray-300" title="GL Account is always first">—</span>
                                    @endif --}}
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500">No segments configured.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Audit -->
        <div>
            <h3 class="mb-3 text-lg font-semibold text-gray-800 flex items-center gap-2">
                <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Audit Information
            </h3>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-2">
                <div>
                    <label class="mb-2 block text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Last Synced By
                    </label>
                    <input type="text" readonly
                        value="{{ isset($accountStructure) ? optional($accountStructure->lastSyncedBy)->first_name . ' ' . optional($accountStructure->lastSyncedBy)->last_name : '' }}"
                        class="w-full rounded-lg border border-gray-200 bg-gray-100 px-4 py-2.5 text-xs text-gray-600 cursor-not-allowed">
                </div>

                <div>
                    <label class="mb-2 block text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Last Synced At
                    </label>
                    <input type="text" readonly
                        value="{{ isset($accountStructure) && $accountStructure->last_synced_at ? $accountStructure->last_synced_at : '' }}"
                        class="w-full rounded-lg border border-gray-200 bg-gray-100 px-4 py-2.5 text-xs text-gray-600 cursor-not-allowed">
                </div>
            </div>
        </div>
    </div>

    {{-- Add Segment Modal --}}
    <div id="add-detail-modal" tabindex="-1" aria-hidden="true"
        class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-modal md:h-full">
        <div class="relative p-4 w-full max-w-md h-full md:h-auto">
            <div class="relative p-4 bg-white rounded-lg shadow sm:p-5">
                <div class="flex justify-between items-center pb-4 mb-2 border-b">
                    <h3 class="text-md font-semibold text-gray-900">Add Segment</h3>
                    <button type="button" data-modal-toggle="add-detail-modal"
                        class="text-gray-400 hover:text-gray-900">✕</button>
                </div>
                <form action="{{ route('gl.chart.account_structure_details.store', $accountStructure->id) }}"
                    method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-900 mb-1">Segment*</label>
                        <select name="segment_id"
                            class="bg-gray-50 border border-gray-300 text-xs rounded-lg block w-full p-2.5" required>
                            <option value="">Select a segment</option>
                            @foreach ($availableSegments as $segment)
                                <option value="{{ $segment->id }}">{{ $segment->description }} ({{ $segment->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-900 mb-1">Separator after this segment</label>
                        <input type="text" name="separator" maxlength="2" value="-"
                            class="bg-gray-50 border border-gray-300 text-xs rounded-lg block w-full p-2.5">
                    </div>
                    <button type="submit"
                        class="text-white bg-gray-700 hover:bg-gray-800 font-medium rounded-md text-xs px-5 py-2.5">
                        Add Segment
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.getElementById('alert-message');

            if (alert) {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';

                    setTimeout(() => {
                        alert.remove();
                    }, 500);
                }, 3000);
            }
        });
    </script>
@endsection
