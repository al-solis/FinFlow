<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SystemSettings;
use App\Models\account_structure as AccountStructure;
use App\Models\chart_of_account as ChartOfAccount;
use App\Models\account_type as AccountType;
use App\Models\account_category as AccountCategory;
use App\Models\account_subcategory as AccountSubcategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ChartOfAccountController extends Controller
{
    private function getOrganizationId()
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }

    public function index(AccountStructure $accountStructure, Request $request)
    {
        // Get filter parameters
        $search = $request->input('search');
        $accountTypeId = $request->input('account_type_id');
        $accountCategoryId = $request->input('account_category_id');
        $status = $request->input('status');
        $isPosting = $request->input('is_posting');

        // Build query
        $query = ChartOfAccount::where('organization_id', $this->getOrganizationId())->where('account_structure_id', $accountStructure->id)
            ->with(['accountType', 'accountCategory', 'accountSubcategory', 'segments'])
            ->orderBy('account_code');

        // Apply filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('account_code', 'LIKE', "%{$search}%")
                    ->orWhere('account_name', 'LIKE', "%{$search}%");
            });
        }

        if ($accountTypeId) {
            $query->where('account_type_id', $accountTypeId);
        }

        if ($accountCategoryId) {
            $query->where('account_category_id', $accountCategoryId);
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        if ($isPosting !== null && $isPosting !== '') {
            $query->where('is_posting', $isPosting);
        }

        // Get paginated results
        $chartAccounts = $query->paginate(config('app.paginate', 10))
            ->appends([
                'search' => $search,
                'account_type_id' => $accountTypeId,
                'account_category_id' => $accountCategoryId,
                'status' => $status,
                'is_posting' => $isPosting,
            ]);

        // Get filter options
        $accountTypes = AccountType::where('organization_id', $this->getOrganizationId())->orderBy('code')->get();
        $accountCategories = AccountCategory::where('organization_id', $this->getOrganizationId())->orderBy('description')->get();

        // Get statistics
        $stats = [
            'total' => ChartOfAccount::where('organization_id', $this->getOrganizationId())->where('account_structure_id', $accountStructure->id)->count(),
            'active' => ChartOfAccount::where('organization_id', $this->getOrganizationId())->where('account_structure_id', $accountStructure->id)->where('status', true)->count(),
            'inactive' => ChartOfAccount::where('organization_id', $this->getOrganizationId())->where('account_structure_id', $accountStructure->id)->where('status', false)->count(),
            'posting' => ChartOfAccount::where('organization_id', $this->getOrganizationId())->where('account_structure_id', $accountStructure->id)->where('is_posting', true)->count(),
            'non_posting' => ChartOfAccount::where('organization_id', $this->getOrganizationId())->where('account_structure_id', $accountStructure->id)->where('is_posting', false)->count(),
        ];

        return view('gl.chart.account_structures.glchart', compact(
            'accountStructure',
            'chartAccounts',
            'accountTypes',
            'accountCategories',
            'stats',
            'search',
            'accountTypeId',
            'accountCategoryId',
            'status',
            'isPosting'
        ));
    }

    /**
     * Update a chart of account.
     */
    public function update(Request $request, AccountStructure $accountStructure, ChartOfAccount $chartAccount)
    {
        // Ensure the account belongs to the structure

        // dd($accountStructure, $chartAccount);
        // if ($chartAccount->account_structure_id !== $accountStructure->id) {
        //     abort(404);
        // }

        $request->validate([
            'account_name' => 'required|string|max:255',
            'status' => 'required|boolean',
        ]);

        $chartAccount->update([
            'account_name' => $request->input('account_name'),
            'status' => $request->input('status'),
            'updated_by' => Auth::id(),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Account updated successfully.'
            ]);
        }

        return redirect()->back()->with('success', 'Account updated successfully.');
    }

    /**
     * Export chart of accounts to CSV.
     */
    public function export(AccountStructure $accountStructure)
    {
        $accounts = ChartOfAccount::where('organization_id', $this->getOrganizationId())->where('account_structure_id', $accountStructure->id)
            ->with(['accountType', 'accountCategory', 'accountSubcategory'])
            ->get();

        $filename = "chart_of_accounts_{$accountStructure->code}_{$accountStructure->id}_" . date('Y-m-d') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($accounts) {
            $handle = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Headers
            fputcsv($handle, [
                'Account Code',
                'Account Name',
                'Account Type',
                'Account Category',
                'Account Subcategory',
                'Is Posting',
                'Status',
                'Created At'
            ]);

            // Data
            foreach ($accounts as $account) {
                fputcsv($handle, [
                    $account->account_code,
                    $account->account_name,
                    $account->accountType->description ?? '',
                    $account->accountCategory->description ?? '',
                    $account->accountSubcategory->description ?? '',
                    $account->is_posting ? 'Yes' : 'No',
                    $account->status ? 'Active' : 'Inactive',
                    $account->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Bulk update status for selected accounts.
     */
    public function bulkUpdateStatus(Request $request, AccountStructure $accountStructure)
    {
        $request->validate([
            'account_ids' => 'required|array',
            'account_ids.*' => 'exists:chart_of_accounts,id',
            'status' => 'required|boolean',
        ]);

        $count = ChartOfAccount::where('organization_id', $this->getOrganizationId())->where('account_structure_id', $accountStructure->id)
            ->whereIn('id', $request->input('account_ids'))
            ->update([
                'status' => $request->input('status'),
                'updated_by' => Auth::id(),
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', "{$count} accounts updated successfully.");
    }

}
