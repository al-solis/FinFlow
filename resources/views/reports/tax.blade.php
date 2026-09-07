@extends('dashboard')

@section('content')
    <div class="container-fluid py-4">
        <h1 class="h3 mb-4">Tax Summary & Detail Report</h1>

        <!-- Filter Form -->
        <form method="GET" action="{{ route('fin.tax') }}" class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="col-md-2 align-self-end">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>

        <!-- Tax Summary Table -->
        <div class="card mb-4">
            <div class="card-header font-weight-bold">Tax Code Summary</div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Tax Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Rate (%)</th>
                            <th>GL Account</th>
                            <th class="text-end">Tax Base</th>
                            <th class="text-end">Tax Amount</th>
                            <th class="text-end">GL Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summary as $row)
                            <tr>
                                <td><strong>{{ $row['code'] }}</strong></td>
                                <td>{{ $row['name'] }}</td>
                                <td>{{ $row['type'] }}</td>
                                <td>{{ number_format($row['rate'], 2) }}%</td>
                                <td>{{ $row['gl_account'] }}</td>
                                <td class="text-end">₱{{ number_format($row['taxable_amount'], 2) }}</td>
                                <td class="text-end">₱{{ number_format($row['tax_amount'], 2) }}</td>
                                <td class="text-end">₱{{ number_format($row['gl_balance'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No tax setup found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- AP Transaction Details -->
        <div class="card">
            <div class="card-header font-weight-bold">AP Invoices Tax Details</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Date</th>
                            <th>Vendor</th>
                            <th class="text-end">Gross Amount</th>
                            <th class="text-end">Tax Amount</th>
                            <th class="text-end">Net Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($apInvoices as $inv)
                            <tr>
                                <td>{{ $inv->invoice_no }}</td>
                                <td>{{ $inv->invoice_date }}</td>
                                <td>{{ $inv->vendor->name ?? 'N/A' }}</td>
                                <td class="text-end">₱{{ number_format($inv->gross_amount, 2) }}</td>
                                <td class="text-end">₱{{ number_format($inv->tax_amount, 2) }}</td>
                                <td class="text-end">₱{{ number_format($inv->net_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No transaction data found for selected
                                    period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
