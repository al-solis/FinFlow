<?php

namespace App\Http\Controllers;

use App\Constants\Modules;
use App\Services\SystemSettings;
use App\Models\ap_invoice;
use App\Models\ap_invoice_line;
use App\Models\rfd_detail_tax;
use App\Models\gl_journal_line;
use App\Models\tax_master;
use App\Models\tax_type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TaxReportController extends Controller
{
    use \App\Traits\AuthorizesAccessRights;
    use \App\Traits\WithSystemSettings;

    protected function getOrganizationId()
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }

    public function index(Request $request)
    {
        $this->authorizeRead(Modules::FIN, Modules::FIN_TAXR);

        $organizationId = $this->getOrganizationId();

        // Get filter values
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $taxTypeId = $request->input('tax_type_id');

        // Get tax types for filter dropdown
        $taxTypes = tax_type::where('organization_id', $organizationId)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        // 1. Fetch Tax Masters for mapping
        $taxMastersQuery = tax_master::with(['taxType', 'taxFormula', 'glAccount'])
            ->where('organization_id', $organizationId)
            ->where('status', 1);

        if ($taxTypeId) {
            $taxMastersQuery->where('tax_type_id', $taxTypeId);
        }

        $taxMasters = $taxMastersQuery->orderBy('code')->get();

        // Collect GL Account IDs associated with tax configurations
        $taxGlAccountIds = $taxMasters->pluck('gl_account_id')->filter()->unique()->toArray();

        // 2. Get RFD Detail Taxes (Source of truth for tax on invoices)
        $rfdTaxes = rfd_detail_tax::with([
            'rfdDetail.rfdHeader',
            'rfdDetail.rfdHeader.currency',
            'rfdDetail.vendor',
            'tax'
        ])
            ->whereHas('rfdDetail.rfdHeader', function ($q) use ($organizationId, $startDate, $endDate) {
                $q->where('organization_id', $organizationId)
                    ->where('approval_status', '2') // Approved RFDs only
                    ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->get();

        // 3. Get AP Invoices for reference
        $apInvoices = ap_invoice::with(['vendor:id,name'])
            ->where('organization_id', $organizationId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->orderBy('created_at', 'desc')
            ->get();

        // 4. Build Tax Summary from RFD Tax Details
        $summary = $taxMasters->map(function ($tax) use ($rfdTaxes) {
            // Filter tax details for this tax master
            $taxDetails = $rfdTaxes->filter(function ($detail) use ($tax) {
                return $detail->tax_id == $tax->id;
            });

            $totalTaxable = $taxDetails->sum('taxable_amount');
            $totalTaxAmount = $taxDetails->sum('tax_amount');

            // Calculate GL balance from posted journals
            $glBalance = $this->getTaxGlBalance($tax->id, $tax->gl_account_id);

            return [
                'code' => $tax->code,
                'name' => $tax->name,
                'type' => $tax->taxType->name ?? 'N/A',
                'rate' => (float) $tax->rate ?? 0,
                'operation' => $tax->taxFormula->operation ?? 'Add',
                'gl_account' => $tax->glAccount->getFormattedAccountCodeAttribute() ?? 'Not Assigned',
                'gl_account_name' => $tax->glAccount->account_name ?? 'Not Assigned',
                'taxable_amount' => $totalTaxable,
                'tax_amount' => $totalTaxAmount,
                'gl_balance' => $glBalance,
                'transaction_count' => $taxDetails->count(),
            ];
        });

        // 5. Build Detailed Tax Transactions
        $taxTransactions = $rfdTaxes->map(function ($detail) {
            $rfd = $detail->rfdDetail->rfdHeader ?? null;
            return [
                'rfd_id' => $rfd->id ?? null,
                'rfd_reference' => $rfd ? 'RFD-' . str_pad($rfd->id, 6, '0', STR_PAD_LEFT) : 'N/A',
                'date' => $rfd->created_at ?? null,
                'vendor_name' => $detail->rfdDetail->vendor->name ?? 'N/A',
                'tax_code' => $detail->tax->code ?? 'N/A',
                'tax_name' => $detail->tax->name ?? 'N/A',
                'taxable_amount' => $detail->taxable_amount,
                'tax_amount' => $detail->tax_amount,
                'description' => $detail->rfdDetail->description ?? '',
            ];
        });

        // Calculate totals
        $totals = [
            'total_taxable' => $summary->sum('taxable_amount'),
            'total_tax_amount' => $summary->sum('tax_amount'),
            'total_gl_balance' => $summary->sum('gl_balance'),
            'total_transactions' => $rfdTaxes->count(),
        ];

        return view('reports.tax', $this->withSystemSettings([
            'summary' => $summary,
            'taxTransactions' => $taxTransactions,
            'apInvoices' => $apInvoices,
            'taxTypes' => $taxTypes,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedTaxType' => $taxTypeId,
            'totals' => $totals,
        ]));
    }

    /**
     * Get GL balance for a tax account from posted journals
     */
    protected function getTaxGlBalance($taxId, $glAccountId)
    {
        if (!$glAccountId) {
            return 0;
        }

        $organizationId = $this->getOrganizationId();

        $result = gl_journal_line::where('gl_account_id', $glAccountId)
            ->whereHas('journal', function ($q) use ($organizationId) {
                $q->where('organization_id', $organizationId)
                    ->where('status', 'posted');
            })
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        return ((float) ($result->total_debit ?? 0)) - ((float) ($result->total_credit ?? 0));
    }

    public function generate(Request $request)
    {
        $this->authorizeRead(Modules::FIN, Modules::FIN_TAXR);

        $organizationId = $this->getOrganizationId();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $taxTypeId = $request->input('tax_type_id');

        // Get tax masters
        $taxMastersQuery = tax_master::with(['taxType', 'taxFormula', 'glAccount'])
            ->where('organization_id', $organizationId)
            ->where('status', 1);

        if ($taxTypeId) {
            $taxMastersQuery->where('tax_type_id', $taxTypeId);
        }

        $taxMasters = $taxMastersQuery->get();

        // Get RFD Tax Details
        $rfdTaxes = rfd_detail_tax::with([
            'rfdDetail.rfdHeader',
            'rfdDetail.vendor',
            'tax'
        ])
            ->whereHas('rfdDetail.rfdHeader', function ($q) use ($organizationId, $startDate, $endDate) {
                $q->where('organization_id', $organizationId)
                    ->where('approval_status', '2')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->get();

        // Build summary
        $summary = $taxMasters->map(function ($tax) use ($rfdTaxes) {
            $taxDetails = $rfdTaxes->filter(function ($detail) use ($tax) {
                return $detail->tax_id == $tax->id;
            });

            return [
                'code' => $tax->code,
                'name' => $tax->name,
                'type' => $tax->taxType->name ?? 'N/A',
                'rate' => (float) $tax->rate ?? 0,
                'gl_account' => $tax->glAccount->account_name ?? 'Not Assigned',
                'taxable_amount' => $taxDetails->sum('taxable_amount'),
                'tax_amount' => $taxDetails->sum('tax_amount'),
                'gl_balance' => $this->getTaxGlBalance($tax->id, $tax->gl_account_id),
            ];
        });

        return response()->json([
            'status' => 'success',
            'filter' => [
                'organization_id' => $organizationId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'summary' => $summary,
            'transaction_details' => $rfdTaxes->map(function ($detail) {
                $rfd = $detail->rfdDetail->rfdHeader ?? null;
                return [
                    'rfd_reference' => $rfd ? 'RFD-' . str_pad($rfd->id, 6, '0', STR_PAD_LEFT) : 'N/A',
                    'date' => $rfd->created_at->format('Y-m-d') ?? null,
                    'vendor' => $detail->rfdDetail->vendor->name ?? 'N/A',
                    'tax_code' => $detail->tax->code ?? 'N/A',
                    'taxable_amount' => round($detail->taxable_amount, 2),
                    'tax_amount' => round($detail->tax_amount, 2),
                ];
            }),
        ]);
    }

    public function export(Request $request)
    {
        $this->authorizeRead(Modules::FIN, Modules::FIN_TAXR);

        $organizationId = $this->getOrganizationId();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $taxTypeId = $request->input('tax_type_id');
        $format = $request->input('format', 'csv');

        // Get data
        $taxMasters = tax_master::with(['taxType', 'taxFormula', 'glAccount'])
            ->where('organization_id', $organizationId)
            ->where('status', 1)
            ->when($taxTypeId, function ($q) use ($taxTypeId) {
                $q->where('tax_type_id', $taxTypeId);
            })
            ->get();

        $rfdTaxes = rfd_detail_tax::with([
            'rfdDetail.rfdHeader',
            'rfdDetail.vendor',
            'tax'
        ])
            ->whereHas('rfdDetail.rfdHeader', function ($q) use ($organizationId, $startDate, $endDate) {
                $q->where('organization_id', $organizationId)
                    ->where('approval_status', '2')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->get();

        $filename = 'tax_report_' . date('Y-m-d') . '.csv';

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Headers - Summary
        fputcsv($handle, ['TAX SUMMARY REPORT']);
        fputcsv($handle, ['Period:', $startDate, 'to', $endDate]);
        fputcsv($handle, []);
        fputcsv($handle, [
            'Tax Code',
            'Tax Name',
            'Type',
            'Rate (%)',
            'GL Account',
            'Taxable Amount',
            'Tax Amount',
            'GL Balance',
            'Transactions'
        ]);

        // Summary Data
        foreach ($taxMasters as $tax) {
            $taxDetails = $rfdTaxes->filter(function ($detail) use ($tax) {
                return $detail->tax_id == $tax->id;
            });

            fputcsv($handle, [
                $tax->code,
                $tax->name,
                $tax->taxType->name ?? 'N/A',
                number_format($tax->rate ?? 0, 2),
                $tax->glAccount->account_name ?? 'Not Assigned',
                number_format($taxDetails->sum('taxable_amount'), 2),
                number_format($taxDetails->sum('tax_amount'), 2),
                number_format($this->getTaxGlBalance($tax->id, $tax->gl_account_id), 2),
                $taxDetails->count(),
            ]);
        }

        fputcsv($handle, []);
        fputcsv($handle, ['DETAILED TRANSACTIONS']);
        fputcsv($handle, []);
        fputcsv($handle, [
            'RFD #',
            'Date',
            'Vendor',
            'Tax Code',
            'Tax Name',
            'Taxable Amount',
            'Tax Amount'
        ]);

        // Transaction Details
        foreach ($rfdTaxes as $detail) {
            $rfd = $detail->rfdDetail->rfdHeader ?? null;
            fputcsv($handle, [
                $rfd ? 'RFD-' . str_pad($rfd->id, 6, '0', STR_PAD_LEFT) : 'N/A',
                $rfd->created_at->format('Y-m-d') ?? '',
                $detail->rfdDetail->vendor->name ?? 'N/A',
                $detail->tax->code ?? 'N/A',
                $detail->tax->name ?? 'N/A',
                number_format($detail->taxable_amount, 2),
                number_format($detail->tax_amount, 2),
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}