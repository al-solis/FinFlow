<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\bank_account;
use App\Models\currency;
use App\Models\chart_of_account;

class BankAccountController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $currencies = currency::where('status', 1)->get();
        $chartOfAccounts = chart_of_account::query()
            ->with(['mainAccount', 'accountType', 'accountCategory'])
            ->whereHas('structure', function ($q) {
                $q->where('status', 1);
            })
            ->where('status', 1)
            ->where('is_posting', true)
            ->whereHas('accountType', function ($q) {
                $q->where('code', 'ASSET');
            })
            ->whereHas('accountCategory', function ($q) {
                $q->where('description', 'like', '%Current Assets%');
            })
            ->orderBy('account_code')
            ->get();

        $query = bank_account::with(['currency', 'chartOfAccount']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                    ->orWhere('name', 'LIKE', "%{$search}%")
                    ->orWhere('account_name', 'LIKE', "%{$search}%")
                    ->orWhere('account_number', 'LIKE', "%{$search}%");
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $bankAccounts = $query->paginate(config('app.paginate', 10))
            ->appends([
                'search' => $search,
                'status' => $status,
            ]);

        return view('bank_accounts.index', compact('bankAccounts'));
    }
}
