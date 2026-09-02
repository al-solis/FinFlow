@extends('dashboard')

@section('title', 'New Approval Workflow')

@section('content')
    <div class="mx-auto max-w-5xl" x-data="workflowBuilder()">
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
                    <button type="button" onclick="closeAlert('success-alert')"
                        class="text-green-600 hover:text-green-800 transition-colors duration-200">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div id="error-alert" class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 shadow-sm">
                <div class="flex items-start justify-between">
                    <div class="flex items-start">
                        <svg class="h-4 w-4 mr-2 text-red-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        <div>
                            <div class="font-semibold text-red-700 text-sm">
                                Please correct the following errors:
                            </div>
                            <ul class="mt-1 list-disc list-inside text-xs text-red-600">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <button type="button" onclick="closeAlert('error-alert')"
                        class="text-red-600 hover:text-red-800 transition-colors duration-200 flex-shrink-0 ml-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        @php
            $isEdit = isset($workflow) && $workflow->exists;
            $formAction = $isEdit ? route('appw.update', $workflow->id) : route('appw.store');
        @endphp

        {{-- <pre class="bg-black text-black p-4 text-xs">
        {{ print_r($steps, true) }}
        </pre> --}}
        <form method="POST" action="{{ $formAction }}">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="mt-5 mb-5 bg-white rounded-2xl shadow-lg border border-gray-200/80 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                    class="bi bi-diagram-2" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd"
                                        d="M6 3.5A1.5 1.5 0 0 1 7.5 2h1A1.5 1.5 0 0 1 10 3.5v1A1.5 1.5 0 0 1 8.5 6v1H11a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-1 0V8h-5v.5a.5.5 0 0 1-1 0v-1A.5.5 0 0 1 5 7h2.5V6A1.5 1.5 0 0 1 6 4.5zM8.5 5a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5zM3 11.5A1.5 1.5 0 0 1 4.5 10h1A1.5 1.5 0 0 1 7 11.5v1A1.5 1.5 0 0 1 5.5 14h-1A1.5 1.5 0 0 1 3 12.5zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zm4.5.5a1.5 1.5 0 0 1 1.5-1.5h1a1.5 1.5 0 0 1 1.5 1.5v1a1.5 1.5 0 0 1-1.5 1.5h-1A1.5 1.5 0 0 1 9 12.5zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5z" />
                                </svg>
                                Approval Workflow
                            </h1>
                            <p class="mt-1 text-sm text-blue-100">
                                {{ $isEdit ? 'Update workflow configuration' : 'Define a new approval process for financial transactions' }}
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('appw.appr') }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20 transition-all duration-200 backdrop-blur-sm border border-white/20">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Back
                            </a>
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50 transition-all duration-200 shadow-lg hover:shadow-xl">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                {{ $isEdit ? 'Update Workflow' : 'Save Workflow' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Workflow Details -->
                <div class="px-8 py-6 border-b border-gray-200">
                    <h2 class="text-sm font-semibold text-gray-700 mb-4">Workflow Details</h2>
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2 lg:grid-cols-4">
                        <!-- Module -->
                        <div>
                            <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                Module
                                <span class="text-red-500">*</span>
                            </label>
                            <select name="module_code" required
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                <option value="">Select module&hellip;</option>
                                @foreach ($moduleOptions as $code => $label)
                                    <option value="{{ $code }}" @selected(old('module_code', $workflow->module_code ?? '') === $code)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Workflow Name -->
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                Workflow Name
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" value="{{ old('name', $workflow->name ?? '') }}" required
                                placeholder="e.g. Standard Disbursement Approval"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                Status
                                <span class="text-red-500">*</span>
                            </label>
                            <select name="is_active"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                <option value="1" @selected(old('is_active', $workflow->is_active ?? true))>Active</option>
                                <option value="0" @selected(old('is_active', $workflow->is_active ?? true) === false)>Inactive</option>
                            </select>
                        </div>

                        <!-- Min Amount -->
                        <div>
                            <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                Min. Amount <span class="text-gray-400">(optional)</span>
                            </label>
                            <input type="number" step="0.01" name="min_amount"
                                value="{{ old('min_amount', $workflow->min_amount ?? '') }}" placeholder="0.00"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                        </div>

                        <!-- Max Amount -->
                        <div>
                            <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                Max. Amount <span class="text-gray-400">(optional)</span>
                            </label>
                            <input type="number" step="0.01" name="max_amount"
                                value="{{ old('max_amount', $workflow->max_amount ?? '') }}" placeholder="No limit"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                        </div>

                        <!-- Description -->
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                Description <span class="text-gray-400">(optional)</span>
                            </label>
                            <input type="text" name="description"
                                value="{{ old('description', $workflow->description ?? '') }}"
                                placeholder="Brief description of this workflow"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                        </div>
                    </div>
                </div>

                <!-- Approval Steps -->
                <div class="px-8 py-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-gray-700">Approval Steps</h2>
                        <span class="text-xs text-gray-500">In order, top to bottom</span>
                    </div>

                    <div class="relative space-y-4 pl-12">
                        <!-- Connecting line behind numbered circles -->
                        <div class="absolute left-[22px] top-6 bottom-12 w-px bg-gray-200 dark:bg-gray-700"></div>

                        <template x-for="(step, index) in steps" :key="step._key">
                            <div class="relative">
                                <div class="absolute -left-12 top-3 flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold text-white"
                                    :class="index === steps.length - 1 ? 'bg-green-600' : 'bg-blue-600'"
                                    x-text="index + 1">
                                </div>

                                <div
                                    class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                    <div class="mb-3 flex items-start justify-between">
                                        <span class="text-xs font-bold uppercase tracking-wide text-green-600"
                                            x-show="index === steps.length - 1">
                                            Final Approver &mdash; releases to processing
                                        </span>
                                        <span x-show="index < steps.length - 1" class="text-xs text-gray-400">
                                            Step <span x-text="index + 1"></span> of <span x-text="steps.length"></span>
                                        </span>
                                        <button type="button" x-show="steps.length > 1" @click="removeStep(index)"
                                            class="text-xs font-semibold text-red-600 hover:text-red-800 dark:text-red-400">
                                            Remove
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                                        <!-- Step Name -->
                                        <div class="md:col-span-5">
                                            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                                                Step Name
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" :name="`steps[${index}][step_name]`"
                                                x-model="step.step_name" required placeholder="e.g. Accounting Review"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                        </div>

                                        <!-- Approver Type -->
                                        <div class="md:col-span-3">
                                            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                                                Approver Type
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <select :name="`steps[${index}][approver_type]`" x-model="step.approver_type"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                                <option value="role">Role</option>
                                                <option value="user">Specific User</option>
                                            </select>
                                        </div>

                                        <!-- Role / User -->
                                        <div class="md:col-span-4">
                                            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300"
                                                x-text="step.approver_type === 'role' ? 'Role' : 'User ID'">
                                            </label>
                                            <template x-if="step.approver_type === 'role'">
                                                <select :name="`steps[${index}][role_id]`" x-model="step.role_id" required
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                                    <option value="">Select role&hellip;</option>
                                                    @foreach ($roles as $role)
                                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                                    @endforeach
                                                </select>
                                            </template>
                                            <template x-if="step.approver_type === 'user'">
                                                <select :name="`steps[${index}][user_id]`" x-model="step.user_id" required
                                                    class="select2 bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                                    <option value="">Select user&hellip;</option>
                                                    @foreach ($users as $user)
                                                        <option value="{{ $user->id }}">{{ $user->last_name }},
                                                            {{ $user->first_name }} {{ $user->middle_name }}</option>
                                                    @endforeach
                                                </select>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Permissions -->
                                    <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2">
                                        <div class="flex items-center">
                                            <input type="checkbox" :name="`steps[${index}][can_edit_chart_of_account]`"
                                                value="1" x-model="step.can_edit_chart_of_account"
                                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
                                            <label class="ms-2 text-xs text-gray-700 dark:text-gray-300">
                                                Can edit chart of account
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="checkbox" :name="`steps[${index}][can_edit_tax]`" value="1"
                                                x-model="step.can_edit_tax"
                                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
                                            <label class="ms-2 text-xs text-gray-700 dark:text-gray-300">
                                                Can add/modify tax
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="checkbox" :name="`steps[${index}][can_edit_amount]`"
                                                value="1" x-model="step.can_edit_amount"
                                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
                                            <label class="ms-2 text-xs text-gray-700 dark:text-gray-300">
                                                Can edit amounts
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="checkbox" :name="`steps[${index}][can_return_to_requester]`"
                                                value="1" x-model="step.can_return_to_requester"
                                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
                                            <label class="ms-2 text-xs text-gray-700 dark:text-gray-300">
                                                Can return to requester
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <button type="button" @click="addStep()"
                            class="block w-full rounded-lg border-2 border-dashed border-gray-300 py-3 text-sm font-semibold text-gray-500 hover:border-blue-500 hover:text-blue-600 dark:border-gray-600 dark:text-gray-400 dark:hover:border-gray-400 dark:hover:text-gray-200 transition-colors">
                            + Add another step
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.5/cdn.min.js" defer></script> --}}
    <script>
        // function workflowBuilder() {
        //     return {
        //         steps: @json($steps),
        //         addStep() {
        //             this.steps.push({
        //                 _key: crypto.randomUUID(),
        //                 step_name: '',
        //                 approver_type: 'role',
        //                 role_id: '',
        //                 user_id: '',
        //                 can_edit_chart_of_account: false,
        //                 can_edit_tax: false,
        //                 can_edit_amount: false,
        //                 can_return_to_requester: true,
        //             });
        //         },
        //         removeStep(index) {
        //             this.steps.splice(index, 1);
        //         },
        //     };
        // }
        function workflowBuilder() {
            return {
                steps: @js($steps ?? []),

                init() {
                    this.steps = this.steps.map(step => ({
                        ...step,
                        _key: crypto.randomUUID(),
                    }));
                },

                addStep() {
                    this.steps.push({
                        _key: crypto.randomUUID(),
                        step_name: '',
                        approver_type: 'role',
                        role_id: '',
                        user_id: '',
                        can_edit_chart_of_account: false,
                        can_edit_tax: false,
                        can_edit_amount: false,
                        can_return_to_requester: true,
                        is_final_approval: false,
                    });
                },

                removeStep(index) {
                    this.steps.splice(index, 1);
                },
            };
        }

        function closeAlert(alertId) {
            const alert = document.getElementById(alertId);
            if (alert) {
                alert.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 300);
            }
        }

        // Auto-close alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('#success-alert, #error-alert');
            alerts.forEach(alert => {
                if (alert) {
                    setTimeout(() => {
                        closeAlert(alert.id);
                    }, 5000);
                }
            });
        });
    </script>
@endsection
