@extends('dashboard')
@section('title', 'Bank Master')
@section('content')
    <div class="mx-auto max-w-5xl">
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
            $isEdit = isset($bankAccount) && $bankAccount->exists;
            $formAction = $isEdit ? route('bm.bank.update', $bankAccount->id) : route('bm.bank.store');
        @endphp

        <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data">
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
                                    class="bi bi-bank2" viewBox="0 0 16 16">
                                    <path
                                        d="M8.277.084a.5.5 0 0 0-.554 0l-7.5 5A.5.5 0 0 0 .5 6h1.875v7H1.5a.5.5 0 0 0 0 1h13a.5.5 0 1 0 0-1h-.875V6H15.5a.5.5 0 0 0 .277-.916zM12.375 6v7h-1.25V6zm-2.5 0v7h-1.25V6zm-2.5 0v7h-1.25V6zm-2.5 0v7h-1.25V6zM8 4a1 1 0 1 1 0-2 1 1 0 0 1 0 2M.5 15a.5.5 0 0 0 0 1h15a.5.5 0 1 0 0-1z" />
                                </svg>
                                Bank Master
                            </h1>
                            <p class="mt-1 text-sm text-blue-100">
                                {{ isset($bankAccount) && $bankAccount->exists ? 'Update bank account information' : 'Maintain bank account information used throughout the Accounts Payable module.' }}
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('bm.bank') }}"
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
                                {{ isset($bankAccount) && $bankAccount->exists ? 'Update Bank Account' : 'Save Bank Account' }}
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Form Fields -->
                <div class="px-8 py-6">
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2 lg:grid-cols-4">
                        <!-- Bank Code -->
                        <div>
                            <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                Bank Code
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="bank_code" name="bank_code"
                                value="{{ old('bank_code', $bankAccount->code ?? '') }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                placeholder="e.g. B001" required>
                        </div>

                        <!-- Bank Name -->
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                Bank Name
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="bank_name" name="bank_name"
                                value="{{ old('bank_name', $bankAccount->name ?? '') }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                placeholder="e.g. Bank of the Philippines" required>
                        </div>
                        <!-- Status -->
                        <div>
                            <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                Status
                                <span class="text-red-500">*</span>
                            </label>
                            <select id="status" name="status"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <!-- Account Name -->
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                Account Name
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="account_name" name="account_name"
                                value="{{ old('account_name', $bankAccount->account_name ?? '') }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                placeholder = "e.g. Juan Dela Cruz, Maria Clara, etc." required>
                        </div>

                        <!-- Account Number -->
                        <div class="lg:col-span-1">
                            <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                Account Number
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="account_number" name="account_number"
                                onkeypress="return event.charCode >= 48 && event.charCode <= 57 || event.charCode === 45"
                                oninput="this.value = this.value.replace(/[^0-9-]/g, '')"
                                onpaste="return event.clipboardData.getData('text').match(/^[0-9-]*$/) !== null"
                                value="{{ old('account_number', $bankAccount->account_number ?? '') }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                placeholder = "e.g. 00-125487-12" required>
                        </div>

                        <!-- Branch -->
                        <div class="lg:col-span-1">
                            <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                Branch
                            </label>
                            <input type="text" id="branch" name="branch"
                                value="{{ old('branch', $bankAccount->branch ?? '') }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                placeholder = "e.g. Imus Branch">
                        </div>

                        <!-- Currency -->
                        <div class="lg:col-span-1">
                            <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                Currency
                                <span class="text-red-500">*</span>
                            </label>
                            <select id="currency_id" name="currency_id"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                required>
                                <option value="">-- Select Currency --</option>
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->id }}"
                                        {{ old('currency_id', $bankAccount->currency_id ?? '') == $currency->id ? ' selected' : '' }}>
                                        {{ $currency->code }} - {{ $currency->name }}
                                        @if (!empty($currency->description))
                                            - {{ $currency->description }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Bank Account Type -->
                        <div class="lg:col-span-1">
                            <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                Bank Account Type
                                <span class="text-red-500">*</span>
                            </label>
                            <select id="account_type" name="account_type"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                required>
                                <option value="1"
                                    {{ old('account_type', $bankAccount->account_type ?? '') == '1' ? ' selected' : '' }}>
                                    Savings</option>
                                <option value="2"
                                    {{ old('account_type', $bankAccount->account_type ?? '') == '2' ? ' selected' : '' }}>
                                    Current</option>
                                <option value="3"
                                    {{ old('account_type', $bankAccount->account_type ?? '') == '3' ? ' selected' : '' }}>
                                    Time Deposit</option>
                                <option value="4"
                                    {{ old('account_type', $bankAccount->account_type ?? '') == '4' ? ' selected' : '' }}>
                                    Money Market</option>
                                <option value="5"
                                    {{ old('account_type', $bankAccount->account_type ?? '') == '5' ? ' selected' : '' }}>
                                    Other</option>
                            </select>
                        </div>

                        <!-- GL Account -->
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                GL Account
                            </label>
                            <select id="chart_of_account_id" name="chart_of_account_id"
                                class="select2 bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                <option value="">-- Select GL Account --</option>
                                @foreach ($chartOfAccounts as $chartOfAccount)
                                    <option value="{{ $chartOfAccount->id }}"
                                        {{ old('chart_of_account_id', $bankAccount->chart_of_account_id ?? '') == $chartOfAccount->id ? ' selected' : '' }}>
                                        {{ $chartOfAccount->account_code }} - {{ $chartOfAccount->account_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>


                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            $('#chart_of_account_id').select2({
                placeholder: "Select GL Account",
                allowClear: true,
                width: '100%'
            });
        });

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
