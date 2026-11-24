@extends('dashboard')

@section('title', $uom->exists ? 'Edit Unit of Measure' : 'New Unit of Measure')

@section('content')
    @php
        $isEdit = $uom->exists;
        $formAction = $isEdit ? route('master.uoms.update', $uom->id) : route('master.uoms.store');
        $input = 'mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs disabled:bg-gray-100 disabled:text-gray-500';
        $label = 'block text-xs font-medium text-gray-900';
        $factorValue = old('factor', $uom->base_uom_id ? (float) $uom->factor : 1);
    @endphp

    <div class="mx-auto max-w-2xl">
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

        <form method="POST" action="{{ $formAction }}"
            x-data="uomForm({
                code: @js((string) old('code', $uom->code)),
                baseId: @js((string) old('base_uom_id', $uom->base_uom_id)),
                factor: @js($factorValue),
                bases: @js($bases)
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
                                {{ $isEdit ? 'Edit Unit of Measure' : 'New Unit of Measure' }}</h1>
                            <p class="mt-1 text-sm text-blue-100">Define a unit and how it converts to its base unit.</p>
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('master.uom') }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20 border border-white/20">Back</a>
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50 shadow-lg">
                                {{ $isEdit ? 'Update Unit' : 'Save Unit' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="px-8 py-6">
                    @if ($locked)
                        <div class="mb-4 rounded-lg border border-yellow-300 bg-yellow-50 p-3 text-xs text-yellow-800">
                            This unit is already used by items, other units, or invoice lines, so its base unit and
                            factor are locked to keep existing documents correct. To change a conversion, set this unit
                            to Inactive and create a new one.
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="{{ $label }}">Code <span class="text-red-500">*</span></label>
                            <input type="text" name="code" maxlength="20" required x-model="code"
                                placeholder="e.g., BOX" class="{{ $input }}">
                        </div>
                        <div class="md:col-span-2">
                            <label class="{{ $label }}">Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" maxlength="100" required placeholder="e.g., Box"
                                value="{{ old('name', $uom->name) }}" class="{{ $input }}">
                        </div>

                        <div class="md:col-span-2">
                            <label class="{{ $label }}">Base UOM</label>
                            <select name="base_uom_id" x-model="baseId" @disabled($locked) class="{{ $input }}">
                                <option value="">— None (this is a base unit) —</option>
                                @foreach ($bases as $base)
                                    <option value="{{ $base->id }}">{{ $base->code }} — {{ $base->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400">Leave empty for units like PC, KG, or HR. Only base
                                units can be chosen here.</p>
                        </div>
                        <div>
                            <label class="{{ $label }}">Status <span class="text-red-500">*</span></label>
                            <select name="status" required class="{{ $input }}">
                                <option value="1" @selected((string) old('status', $uom->status) === '1')>Active</option>
                                <option value="0" @selected((string) old('status', $uom->status) === '0')>Inactive</option>
                            </select>
                        </div>

                        <div x-show="baseId" x-cloak class="md:col-span-3">
                            <label class="{{ $label }}">Factor <span class="text-red-500">*</span></label>
                            <input type="number" step="0.000001" min="0.000001" name="factor" x-model="factor"
                                :required="!!baseId" @disabled($locked) class="{{ $input }} md:w-1/3">
                            <p class="mt-2 rounded-lg bg-blue-50 px-3 py-2 text-xs font-medium text-blue-800"
                                x-text="preview()"></p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        function uomForm(init) {
            return {
                ...init,
                preview() {
                    const base = this.bases.find(b => String(b.id) === String(this.baseId));
                    if (!base) return '';
                    return '1 ' + (this.code || '?') + ' = ' + (this.factor || '?') + ' ' + base.code;
                },
            };
        }
    </script>
@endsection
