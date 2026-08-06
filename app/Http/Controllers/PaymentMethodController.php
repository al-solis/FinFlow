<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Services\SystemSettings;
use App\Models\payment_method;

class PaymentMethodController extends Controller
{
    private function getOrganizationId()
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }

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

        $paymentMethods = $query->where('organization_id', $this->getOrganizationId())
            ->paginate(config('app.paginate'))
            ->appends(['search' => $search, 'searchstatus' => $searchstatus]);

        return view('admin.payment_method.index', compact('paymentMethods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => [
                'required',
                'max:20',
                Rule::unique('payment_methods', 'code')->where(function ($query) {
                    return $query->where('organization_id', $this->getOrganizationId());
                }),
            ],
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
            'organization_id' => $this->getOrganizationId(),
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

    public function update(Request $request, payment_method $paymentMethod)
    {
        $request->validate([
            'edit_code' => [
                'required',
                'max:20',
                Rule::unique('payment_methods', 'code')->where(function ($query) use ($paymentMethod) {
                    return $query->where('organization_id', $this->getOrganizationId());
                })->ignore($paymentMethod->id),
            ],
            'edit_name' => 'required|max:100',
            'edit_description' => 'nullable|max:255',
            'edit_requires_bank' => 'required|boolean',
            'edit_requires_check' => 'required|boolean',
            'edit_requires_reference_no' => 'required|boolean',
            'edit_allow_partial_payment' => 'required|boolean',
            'edit_payment_channel' => 'required|integer|in:1,2,3,4,5,6,7,8',
            'edit_status' => 'required|integer|in:0,1',
        ]);

        $paymentMethod->update([
            'code' => $request->edit_code,
            'name' => $request->edit_name,
            'description' => $request->edit_description,
            'requires_bank' => $request->edit_requires_bank,
            'requires_check' => $request->edit_requires_check,
            'requires_reference_no' => $request->edit_requires_reference_no,
            'allow_partial_payment' => $request->edit_allow_partial_payment,
            'payment_channel' => $request->edit_payment_channel,
            'status' => $request->edit_status,
            'updated_by' => Auth::id(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.method')->with('success', 'Payment method updated successfully.');
    }

}
