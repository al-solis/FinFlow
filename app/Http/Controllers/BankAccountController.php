<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SystemSettings;
use App\Models\bank_account;
use App\Models\currency;
use App\Models\chart_of_account;

class BankAccountController extends Controller
{
    protected function getOrganizationId()
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('searchstatus');

        $currencies = currency::where('status', 1)->get();

        $chartOfAccounts = chart_of_account::query()
            ->with(['mainAccount', 'accountType', 'accountCategory'])
            ->whereHas('structure', function ($q) {
                $q->where('organization_id', $this->getOrganizationId())
                    ->where('status', 1);
            })
            ->where('status', 1)
            ->where('is_posting', true)
            ->whereHas('accountType', function ($q) {
                $q->where('organization_id', $this->getOrganizationId())
                    ->where('code', 'ASSET');
            })
            ->whereHas('accountCategory', function ($q) {
                $q
                    ->where('organization_id', $this->getOrganizationId())
                    ->where('description', 'like', '%Current Assets%');
            })
            ->where('organization_id', $this->getOrganizationId())
            ->orderBy('account_code')
            ->get();

        $query = bank_account::where('organization_id', $this->getOrganizationId())->with(['currency', 'chartOfAccount']);

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

        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc');
        $allowedSorts = ['name', 'account_name', 'currency'];

        if (in_array($sort, $allowedSorts)) {
            $sort = 'name';
        }

        switch ($sort) {
            case 'currency':
                $query->join('currencies', 'bank_accounts.currency_id', '=', 'currencies.id')
                    ->orderBy('currencies.name', $direction)
                    ->select('bank_accounts.*');
                break;
            default:
                $query->orderBy($sort, $direction);
                break;
        }

        $bankAccounts = $query->paginate(config('app.paginate', 10))
            ->appends([
                'search' => $search,
                'searchstatus' => $status,
            ])->withQueryString();

        return view('bm.bank.index', compact('bankAccounts'));
    }

    public function create()
    {
        $bankAccount = null;
        $currencies = currency::where('status', 1)->get();
        $chartOfAccounts = chart_of_account::query()
            ->with(['mainAccount', 'accountType', 'accountCategory'])
            ->whereHas('structure', function ($q) {
                $q->where('organization_id', $this->getOrganizationId())
                    ->where('status', 1)
                    ->where('is_default', true);
            })
            ->where('status', 1)
            ->where('is_posting', true)
            ->whereHas('accountType', function ($q) {
                $q
                    ->where('organization_id', $this->getOrganizationId())
                    ->where('code', 'ASSET');
            })
            ->whereHas('accountCategory', function ($q) {
                $q->where('organization_id', $this->getOrganizationId())
                    ->where('description', 'like', '%Current Assets%');
            })
            ->where('organization_id', $this->getOrganizationId())
            ->orderBy('account_code')
            ->get();

        return view('bm.bank.create', compact('bankAccount', 'currencies', 'chartOfAccounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'bank_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('bank_accounts', 'code')->where(function ($query) {
                    return $query->where('organization_id', $this->getOrganizationId());
                })
            ],
            'bank_name' => 'required|string|max:100',
            'account_name' => 'required|string|max:100',
            'account_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('bank_accounts', 'account_number')->where(function ($query) {
                    return $query->where('organization_id', $this->getOrganizationId());
                })
            ],
            'branch' => 'nullable|string|max:100',
            'currency_id' => 'required|exists:currencies,id',
            'account_type' => 'required|integer|in:1,2,3,4,5',
            'chart_of_account_id' => 'nullable|exists:chart_of_accounts,id',
            'status' => 'required|boolean',
        ]);

        bank_account::create([
            'organization_id' => $this->getOrganizationId(),
            'code' => $request->input('bank_code'),
            'name' => $request->input('bank_name'),
            'account_name' => $request->input('account_name'),
            'account_number' => $request->input('account_number'),
            'branch' => $request->input('branch') ?? null,
            'currency_id' => $request->input('currency_id'),
            'account_type' => $request->input('account_type'),
            'chart_of_account_id' => $request->input('chart_of_account_id') ?? null,
            'status' => $request->input('status') ?? 1,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        return redirect()->route('bm.bank')->with('success', 'Bank account created successfully.');
    }

    public function edit($id)
    {
        $bankAccount = bank_account::findOrFail($id);
        $currencies = currency::where('status', 1)->get();

        $chartOfAccounts = chart_of_account::query()
            ->with(['mainAccount', 'accountType', 'accountCategory'])
            ->whereHas('structure', function ($q) {
                $q->where('organization_id', $this->getOrganizationId())
                    ->where('status', 1)
                    ->where('is_default', true);
            })
            ->where('status', 1)
            ->where('is_posting', true)
            ->where('organization_id', $this->getOrganizationId())
            ->whereHas('accountType', function ($q) {
                $q->where('organization_id', $this->getOrganizationId())
                    ->where('code', 'ASSET');
            })
            ->whereHas('accountCategory', function ($q) {
                $q->where('organization_id', $this->getOrganizationId())
                    ->where('description', 'like', '%Current Assets%');
            })
            ->orderBy('account_code')
            ->get();

        return view('bm.bank.create', compact('bankAccount', 'currencies', 'chartOfAccounts'));
    }

    public function update(Request $request, $id)
    {
        $bankAccount = bank_account::findOrFail($id);

        $request->validate([
            'bank_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('bank_accounts', 'code')->where(function ($query) use ($bankAccount) {
                    return $query->where('organization_id', $this->getOrganizationId());
                })->ignore($bankAccount->id),
            ],
            'bank_name' => 'required|string|max:100',
            'account_name' => 'required|string|max:100',
            'account_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('bank_accounts', 'account_number')->where(function ($query) use ($bankAccount) {
                    return $query->where('organization_id', $this->getOrganizationId());
                })->ignore($bankAccount->id),
            ],
            'branch' => 'nullable|string|max:100',
            'currency_id' => 'required|exists:currencies,id',
            'account_type' => 'required|integer|in:1,2,3,4,5',
            'chart_of_account_id' => [
                'nullable',
                'exists:chart_of_accounts,id',
            ],
            'status' => 'required|boolean',
        ]);

        $bankAccount->update([
            'code' => $request->input('bank_code'),
            'name' => $request->input('bank_name'),
            'account_name' => $request->input('account_name'),
            'account_number' => $request->input('account_number'),
            'branch' => $request->input('branch') ?? null,
            'currency_id' => $request->input('currency_id'),
            'account_type' => $request->input('account_type'),
            'chart_of_account_id' => $request->input('chart_of_account_id') ?? null,
            'status' => $request->input('status') ?? 1,
            'updated_by' => Auth::id(),
            'updated_at' => now(),
        ]);

        return redirect()->route('bm.bank')->with('success', 'Bank account updated successfully.');
    }
}
