@extends('dashboard')

@section('title', 'Financial Reports')

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="mt-5 mb-5">
            <h1 class="text-2xl font-bold text-gray-800">Financial Reports</h1>
            <p class="mt-1 text-sm text-gray-500">Generate and view financial reports</p>
        </div>

        <!-- Report Selection -->
        <div class="mb-5 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Report Type</label>
                    <select name="report_type" onchange="this.form.submit()"
                        class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs min-w-[200px]">
                        @foreach ($reportTypes as $value => $label)
                            <option value="{{ $value }}" @selected($selectedReport == $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($selectedReport == 'tax_report')
                    <!-- Tax Report Filters -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" name="start_date"
                            value="{{ $startDate ?? now()->startOfMonth()->toDateString() }}"
                            class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" name="end_date" value="{{ $endDate ?? now()->toDateString() }}"
                            class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Tax Type</label>
                        <select name="tax_type_id" class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                            <option value="">All Types</option>
                            @foreach ($taxTypes ?? [] as $type)
                                <option value="{{ $type->id }}" @selected(($selectedTaxType ?? '') == $type->id)>
                                    {{ $type->code }} — {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @elseif ($selectedReport != 'balance_sheet')
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">From</label>
                        <input type="date" name="from" value="{{ $from }}"
                            class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">To</label>
                        <input type="date" name="to" value="{{ $to }}"
                            class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                    </div>
                @else
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">As of Date</label>
                        <input type="date" name="as_of" value="{{ $asOf ?? $to }}"
                            class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                    </div>
                @endif
                <button type="submit"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-xs font-medium text-white hover:bg-blue-700">
                    Generate
                </button>
            </form>
        </div>

        <!-- Report Content -->
        @if ($selectedReport == 'trial_balance')
            @include('reports.partials.trial_balance', [
                'rows' => $rows ?? [],
                'totalDebit' => $totalDebit ?? 0,
                'totalCredit' => $totalCredit ?? 0,
                'isBalanced' => $isBalanced ?? true,
                'from' => $from,
                'to' => $to,
            ])
        @elseif ($selectedReport == 'balance_sheet')
            @include('reports.partials.balance_sheet', [
                'assets' => $assets ?? [],
                'liabilities' => $liabilities ?? [],
                'equity' => $equity ?? [],
                'totalAssets' => $totalAssets ?? 0,
                'totalLiabilities' => $totalLiabilities ?? 0,
                'totalEquity' => $totalEquity ?? 0,
                'asOf' => $asOf ?? $to,
                'isBalanced' => $isBalanced ?? true,
            ])
        @elseif ($selectedReport == 'income_statement')
            @include('reports.partials.income_statement', [
                'revenue' => $revenue ?? [],
                'expenses' => $expenses ?? [],
                'cogs' => $cogs ?? [],
                'otherIncome' => $otherIncome ?? [],
                'otherExpenses' => $otherExpenses ?? [],
                'totalRevenue' => $totalRevenue ?? 0,
                'totalExpenses' => $totalExpenses ?? 0,
                'totalCogs' => $totalCogs ?? 0,
                'totalOtherIncome' => $totalOtherIncome ?? 0,
                'totalOtherExpenses' => $totalOtherExpenses ?? 0,
                'grossProfit' => $grossProfit ?? 0,
                'operatingIncome' => $operatingIncome ?? 0,
                'netIncome' => $netIncome ?? 0,
                'from' => $from,
                'to' => $to,
            ])
        @elseif ($selectedReport == 'tax_report')
            @include('reports.partials.tax_report', [
                'summary' => $summary ?? collect(),
                'taxTransactions' => $taxTransactions ?? collect(),
                'apInvoices' => $apInvoices ?? collect(),
                'totals' => $totals ?? [
                    'total_taxable' => 0,
                    'total_tax_amount' => 0,
                    'total_gl_balance' => 0,
                    'total_transactions' => 0,
                ],
                'startDate' => $startDate ?? now()->startOfMonth()->toDateString(),
                'endDate' => $endDate ?? now()->toDateString(),
            ])
        @elseif ($selectedReport == 'cash_flow')
            @include('reports.partials.cash_flow', [
                'cashMovements' => $cashMovements ?? collect(),
                'dailyMovements' => $dailyMovements ?? collect(),
                'operatingActivities' => $operatingActivities ?? collect(),
                'investingActivities' => $investingActivities ?? collect(),
                'financingActivities' => $financingActivities ?? collect(),
                'netOperating' => $netOperating ?? 0,
                'netInvesting' => $netInvesting ?? 0,
                'netFinancing' => $netFinancing ?? 0,
                'openingCash' => $openingCash ?? 0,
                'closingCash' => $closingCash ?? 0,
                'totalNetChange' => $totalNetChange ?? 0,
                'currencySymbol' => $currencySymbol ?? '₱',
                'from' => $from,
                'to' => $to,
            ])
        @endif
    </div>
@endsection
