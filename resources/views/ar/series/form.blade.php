@extends('dashboard')

@section('title', $series->exists ? 'Edit Invoice Series' : 'New Invoice Series')

@section('content')
    @php
        $isEdit = $series->exists;
        $formAction = $isEdit ? route('ar.series.update', $series->id) : route('ar.series.store');
        $input =
            'mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs disabled:bg-gray-100 disabled:text-gray-500';
        $label = 'block text-xs font-medium text-gray-900';
        $section = 'mb-4 mt-6 border-b border-gray-200 pb-2 text-sm font-semibold text-gray-700';
        $dateVal = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('Y-m-d') : '';

        // FIX: the "Default" checkbox is disabled on the very first series of a
        // document type — store() forces is_default = true in that case.
        $isFirstForType = $isEdit
            ? false
            : !in_array(old('document_type', 'sales_invoice'), $docTypesWithSeries ?? [], true);
        $defaultChecked = $isFirstForType ? true : (bool) old('is_default', $series->is_default);
    @endphp

    <div class="mx-auto max-w-3xl">
        @if ($errors->any())
            <div class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 shadow-sm">
                <div class="font-semibold text-red-700 text-sm">Please correct the following errors:</div>
                <ul class="mt-1 list-disc list-inside text-xs text-red-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $formAction }}" x-data="seriesForm({
            prefix: @js((string) old('prefix', $series->prefix)),
            start: @js((int) old('series_start', $series->series_start ?: 1)),
            end: @js(old('series_end', $series->series_end) === null || old('series_end', $series->series_end) === '' ? '' : (int) old('series_end', $series->series_end)),
            padding: @js((int) old('padding', $series->padding ?: 6))
        })">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="mt-5 mb-5 bg-white rounded-2xl shadow-lg border border-gray-200/80 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-white">
                                {{ $isEdit ? 'Edit Invoice Series' : 'New Invoice Series' }}</h1>
                            <p class="mt-1 text-sm text-blue-100">Register the number range printed or authorized by the
                                BIR.</p>
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('ar.ser') }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20 border border-white/20">Back</a>
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50 shadow-lg">
                                {{ $isEdit ? 'Update Series' : 'Save Series' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="px-8 py-6">
                    @if ($locked)
                        <div class="mb-4 rounded-lg border border-yellow-300 bg-yellow-50 p-3 text-xs text-yellow-800">
                            Numbers have already been issued from this series
                            (next number: <strong>{{ $series->nextLabel() }}</strong>), so the document type, code,
                            prefix, start number, and padding are locked. You can still extend the end of the range,
                            update the BIR authority details, and activate or deactivate the series.
                        </div>
                    @endif

                    <h2 class="{{ $section }} !mt-0">Series</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="{{ $label }}">Document Type <span class="text-red-500">*</span></label>
                            <select name="document_type" required @disabled($locked) class="{{ $input }}">
                                @foreach ($types as $value => $text)
                                    <option value="{{ $value }}" @selected(old('document_type', $series->document_type) === $value)>
                                        {{ $text }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Series Code <span class="text-red-500">*</span></label>
                            <input type="text" name="code" maxlength="20" required @disabled($locked)
                                value="{{ old('code', $series->code) }}" placeholder="e.g., SI-HO"
                                class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Branch Code</label>
                            <input type="text" name="tax_branch_code" maxlength="10"
                                value="{{ old('tax_branch_code', $series->tax_branch_code) }}"
                                class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Prefix</label>
                            <input type="text" name="prefix" maxlength="20" x-model="prefix"
                                @disabled($locked) placeholder="e.g., SI-" class="{{ $input }}">
                            <p class="mt-1 text-[11px] text-gray-400">
                                Used by the overlap check. Two series with the same prefix must not share a number range.
                            </p>
                        </div>
                        <div>
                            <label class="{{ $label }}">Start Number <span class="text-red-500">*</span></label>
                            <input type="number" name="series_start" min="1" required x-model.number="start"
                                @disabled($locked) class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">End Number</label>
                            <input type="number" name="series_end" min="1" x-model.number="end"
                                placeholder="blank = no end" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Digits (zero padding) <span
                                    class="text-red-500">*</span></label>
                            <input type="number" name="padding" min="1" max="12" required
                                x-model.number="padding" @disabled($locked) class="{{ $input }}">
                        </div>
                        <div class="md:col-span-2">
                            <p class="mt-6 rounded-lg bg-blue-50 px-3 py-2 text-xs font-medium text-blue-800">
                                First number: <span x-text="label(start)"></span>
                                <template x-if="end">
                                    <span> &nbsp;·&nbsp; Last number: <span x-text="label(end)"></span></span>
                                </template>
                            </p>
                        </div>
                    </div>

                    <h2 class="{{ $section }}">BIR Authority</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="{{ $label }}">ATP / Permit to Use No.</label>
                            <input type="text" name="bir_authority_no" maxlength="60"
                                value="{{ old('bir_authority_no', $series->bir_authority_no) }}"
                                class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Date Issued</label>
                            <input type="date" name="authority_date"
                                value="{{ old('authority_date', $dateVal($series->authority_date)) }}"
                                class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Valid Until</label>
                            <input type="date" name="valid_until"
                                value="{{ old('valid_until', $dateVal($series->valid_until)) }}"
                                class="{{ $input }}">
                            <p class="mt-1 text-[11px] text-gray-400">Posting is blocked after this date.</p>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-6">
                        <label class="flex items-center gap-2 text-xs text-gray-700">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $series->is_active))>
                            Active
                        </label>
                        <label class="flex items-center gap-2 text-xs text-gray-700"
                            @if ($locked || $isFirstForType) title="{{ $isFirstForType ? 'The first series of a document type is the default automatically.' : 'Locked series keep their default flag.' }}" @endif>
                            <input type="checkbox" name="is_default" value="1" @checked($defaultChecked)
                                @disabled($locked || $isFirstForType)>
                            Default series for this document type
                        </label>
                    </div>
                    @if ($isFirstForType)
                        <p class="mt-1 text-[11px] text-gray-400">
                            This is the first series for the selected document type — it will be marked as default
                            automatically.
                        </p>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <script>
        function seriesForm(data) {
            return {
                ...data,
                label(n) {
                    return (this.prefix || '') + String(n || 0).padStart(this.padding || 1, '0');
                },
            };
        }
    </script>
@endsection
