@extends('dashboard')

@section('title', 'Edit Approval Workflow')

@section('content')
    <div x-data="workflowBuilder()">
        <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
            Financial System &middot; Setup
        </div>
        <h1 class="mt-1 mb-6 text-2xl font-bold text-[#2D3452] dark:text-white">Edit Approval Workflow</h1>

        <form action="{{ route('appw.update', $workflow) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- Workflow details --}}
            <div class="mb-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-4 text-sm font-bold text-[#2D3452] dark:text-white">Workflow details</div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-12">
                    <div class="sm:col-span-4">
                        <label class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Module</label>
                        <select name="module_code" required
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-[#2D3452] focus:ring-[#2D3452] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">Select module&hellip;</option>
                            @foreach ($moduleOptions as $code => $label)
                                <option value="{{ $code }}" @selected($workflow->module_code === $code)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-8">
                        <label class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Workflow name</label>
                        <input type="text" name="name" value="{{ $workflow->name }}" required
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-[#2D3452] focus:ring-[#2D3452] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">
                            Min. amount <span class="text-gray-400">(optional)</span>
                        </label>
                        <input type="number" step="0.01" name="min_amount" value="{{ $workflow->min_amount }}"
                            placeholder="0.00"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-[#2D3452] focus:ring-[#2D3452] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">
                            Max. amount <span class="text-gray-400">(optional)</span>
                        </label>
                        <input type="number" step="0.01" name="max_amount" value="{{ $workflow->max_amount }}"
                            placeholder="No limit"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-[#2D3452] focus:ring-[#2D3452] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="sm:col-span-6">
                        <label class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">
                            Description <span class="text-gray-400">(optional)</span>
                        </label>
                        <input type="text" name="description" value="{{ $workflow->description }}"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-[#2D3452] focus:ring-[#2D3452] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="sm:col-span-12">
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" name="is_active" value="1" @checked($workflow->is_active)
                                class="peer sr-only">
                            <div
                                class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-[#0F9B8E] peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-[#0F9B8E]/30 dark:border-gray-600 dark:bg-gray-700">
                            </div>
                            <span class="ms-3 text-sm font-medium text-gray-900 dark:text-white">Active</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Approval steps --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-4 text-sm font-bold text-[#2D3452] dark:text-white">
                    Approval steps <span class="font-normal text-gray-400">— in order, top to bottom</span>
                </div>

                <div class="relative space-y-4 pl-12">
                    <div class="absolute left-[22px] top-6 bottom-12 w-px bg-gray-200 dark:bg-gray-700"></div>

                    <template x-for="(step, index) in steps" :key="step.uid">
                        <div class="relative">
                            <div class="absolute -left-12 top-3 flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold text-white"
                                :class="index === steps.length - 1 ? 'bg-[#0F9B8E]' : 'bg-[#2D3452]'" x-text="index + 1">
                            </div>

                            <div
                                class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                <div class="mb-2 flex items-start justify-between">
                                    <span class="text-xs font-bold uppercase tracking-wide text-[#0F9B8E]"
                                        x-show="index === steps.length - 1">
                                        Final approver &mdash; releases to processing
                                    </span>
                                    <span></span>
                                    <button type="button" x-show="steps.length > 1" @click="removeStep(index)"
                                        class="text-xs font-semibold text-red-600 hover:text-red-800 dark:text-red-400">
                                        Remove
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-12">
                                    <div class="sm:col-span-5">
                                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Step
                                            name</label>
                                        <input type="text" :name="`steps[${index}][step_name]`" x-model="step.step_name"
                                            required
                                            class="block w-full rounded-lg border border-gray-300 bg-white p-2 text-sm text-gray-900 focus:border-[#2D3452] focus:ring-[#2D3452] dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label
                                            class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Approver
                                            type</label>
                                        <select :name="`steps[${index}][approver_type]`" x-model="step.approver_type"
                                            class="block w-full rounded-lg border border-gray-300 bg-white p-2 text-sm text-gray-900 focus:border-[#2D3452] focus:ring-[#2D3452] dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                            <option value="role">Role</option>
                                            <option value="user">Specific user</option>
                                        </select>
                                    </div>
                                    <div class="sm:col-span-4">
                                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300"
                                            x-text="step.approver_type === 'role' ? 'Role' : 'User ID'"></label>
                                        <template x-if="step.approver_type === 'role'">
                                            <select :name="`steps[${index}][role_id]`" x-model="step.role_id" required
                                                class="block w-full rounded-lg border border-gray-300 bg-white p-2 text-sm text-gray-900 focus:border-[#2D3452] focus:ring-[#2D3452] dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                                <option value="">Select role&hellip;</option>
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                                @endforeach
                                            </select>
                                        </template>
                                        <template x-if="step.approver_type === 'user'">
                                            <input type="number" :name="`steps[${index}][user_id]`" x-model="step.user_id"
                                                required placeholder="User ID"
                                                class="block w-full rounded-lg border border-gray-300 bg-white p-2 text-sm text-gray-900 focus:border-[#2D3452] focus:ring-[#2D3452] dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                        </template>
                                    </div>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2">
                                    <div class="flex items-center">
                                        <input type="checkbox" :name="`steps[${index}][can_edit_chart_of_account]`"
                                            value="1" x-model="step.can_edit_chart_of_account"
                                            class="h-4 w-4 rounded border-gray-300 text-[#2D3452] focus:ring-[#2D3452] dark:border-gray-600 dark:bg-gray-700">
                                        <label class="ms-2 text-sm text-gray-700 dark:text-gray-300">Can edit chart of
                                            account</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="checkbox" :name="`steps[${index}][can_edit_tax]`" value="1"
                                            x-model="step.can_edit_tax"
                                            class="h-4 w-4 rounded border-gray-300 text-[#2D3452] focus:ring-[#2D3452] dark:border-gray-600 dark:bg-gray-700">
                                        <label class="ms-2 text-sm text-gray-700 dark:text-gray-300">Can add/modify
                                            tax</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="checkbox" :name="`steps[${index}][can_edit_amount]`" value="1"
                                            x-model="step.can_edit_amount"
                                            class="h-4 w-4 rounded border-gray-300 text-[#2D3452] focus:ring-[#2D3452] dark:border-gray-600 dark:bg-gray-700">
                                        <label class="ms-2 text-sm text-gray-700 dark:text-gray-300">Can edit
                                            amounts</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="checkbox" :name="`steps[${index}][can_return_to_requester]`"
                                            value="1" x-model="step.can_return_to_requester"
                                            class="h-4 w-4 rounded border-gray-300 text-[#2D3452] focus:ring-[#2D3452] dark:border-gray-600 dark:bg-gray-700">
                                        <label class="ms-2 text-sm text-gray-700 dark:text-gray-300">Can return to
                                            requester</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <button type="button" @click="addStep()"
                        class="block w-full rounded-lg border-2 border-dashed border-gray-300 py-3 text-sm font-semibold text-gray-500 hover:border-[#2D3452] hover:text-[#2D3452] dark:border-gray-600 dark:text-gray-400 dark:hover:border-gray-400 dark:hover:text-gray-200">
                        + Add another step
                    </button>
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <button type="submit"
                    class="rounded-lg bg-[#2D3452] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#232948] focus:outline-none focus:ring-4 focus:ring-[#2D3452]/30">
                    Save changes
                </button>
                <a href="{{ route('approval-workflows.index') }}"
                    class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.5/cdn.min.js" defer></script>
    <script>
        function workflowBuilder() {
            return {
                uidCounter: 1000,
                steps: @json(
                    $workflow->steps->map(fn($s) => [
                            'uid' => $s->id,
                            'step_name' => $s->step_name,
                            'approver_type' => $s->approver_type,
                            'role_id' => $s->role_id,
                            'user_id' => $s->user_id,
                            'can_edit_chart_of_account' => (bool) $s->can_edit_chart_of_account,
                            'can_edit_tax' => (bool) $s->can_edit_tax,
                            'can_edit_amount' => (bool) $s->can_edit_amount,
                            'can_return_to_requester' => (bool) $s->can_return_to_requester,
                        ])),
                addStep() {
                    this.steps.push({
                        uid: this.uidCounter++,
                        step_name: '',
                        approver_type: 'role',
                        role_id: '',
                        user_id: '',
                        can_edit_chart_of_account: false,
                        can_edit_tax: false,
                        can_edit_amount: false,
                        can_return_to_requester: true,
                    });
                },
                removeStep(index) {
                    this.steps.splice(index, 1);
                },
            };
        }
    </script>
@endsection
