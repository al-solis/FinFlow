<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\currency;

class CurrencyController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $searchstatus = $request->input('searchstatus');

        $query = currency::query();
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('symbol', 'like', '%' . $search . '%');
            });
        }
        if ($searchstatus !== null) {
            $query->where('status', $searchstatus);
        }

        $currencies = $query->paginate(config('app.paginate'))
            ->appends(['search' => $search, 'searchstatus' => $searchstatus]);

        return view('admin.currency.index', compact('currencies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:10',
            'status' => 'required|in:0,1',
        ]);

        currency::create([
            'code' => $request->code,
            'name' => $request->name,
            'symbol' => $request->symbol,
            'status' => $request->status,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        return redirect()->route('admin.currency')->with('success', 'Currency created successfully.');
    }

    public function update(Request $request, currency $currency)
    {
        $request->validate([
            'edit_code' => 'required|string|max:20',
            'edit_name' => 'required|string|max:255',
            'edit_symbol' => 'required|string|max:10',
            'edit_status' => 'required|in:0,1',
        ]);

        $currency->update([
            'code' => $request->edit_code,
            'name' => $request->edit_name,
            'symbol' => $request->edit_symbol,
            'status' => $request->edit_status,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        return redirect()->route('admin.currency')->with('success', 'Currency updated successfully.');
    }
}
