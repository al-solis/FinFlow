<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\payment_method;


class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $searchstatus = $request->input('searchstatus');
        $searchchannel = $request->input('searchchannel');
        $query = payment_method::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($searchchannel !== null) {
            $query->where('payment_channel', $searchchannel);
        }

        if ($searchstatus !== null) {
            $query->where('status', $searchstatus);
        }

        $paymentMethods = $query->paginate(config('app.paginate'))
            ->appends(['search' => $search, 'searchstatus' => $searchstatus]);

        return view('admin.payment_method.index', compact('paymentMethods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|max:20|unique:payment_methods,code',
            'name' => 'required|max:100',
            'description' => 'nullable|max:255',
            'requires_bank' => 'required|boolean',
            'requires_check' => 'required|boolean',
            'requires_reference_no' => 'required|boolean',
            'allow_partial_payment' => 'required|boolean',
            'payment_channel' => 'required|integer|in:1,2,3,4,5,6,7,8',
            'status' => 'required|integer|in:0,1',
        ]);

        payment_method::create([
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
            'requires_bank' => $request->requires_bank,
            'requires_check' => $request->requires_check,
            'requires_reference_no' => $request->requires_reference_no,
            'allow_partial_payment' => $request->allow_partial_payment,
            'payment_channel' => $request->payment_channel,
            'status' => $request->status,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        return redirect()->route('admin.method')->with('success', 'Payment method created successfully.');
    }
}
