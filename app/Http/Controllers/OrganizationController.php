<?php

namespace App\Http\Controllers;

use App\Models\organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\SystemSettings;
use App\Models\currency;

class OrganizationController extends Controller
{
    public function index()
    {
        $organization = organization::where('status', 1)->first();
        $currencies = currency::where('status', 1)->get();
        return view('admin.organization.index', compact('organization', 'currencies'));
    }

    public function update(Request $request)
    {
        // dd($request->all());
        $organization = organization::where('id', $request->id)->firstOrFail();

        $validated = $request->validate([

            'organization_code' => 'required|max:20',

            'name' => 'required|max:150',
            'short_name' => 'nullable|max:50',
            'legal_name' => 'required|max:150',

            'industry' => 'nullable|max:100',
            'business_type' => 'nullable|max:100',

            'tax_id' => 'nullable|max:50',
            'registration_no' => 'nullable|max:100',
            'tax_branch_code' => 'nullable|max:20',
            'bir_rdo_code' => 'nullable|max:20',

            'description' => 'nullable',

            'address' => 'required|max:255',
            'city' => 'required|max:100',
            'province' => 'required|max:100',
            'country' => 'required|max:100',
            'zip_code' => 'required|max:20',

            'contact_person' => 'nullable|max:100',
            'phone' => 'nullable|max:30',
            'mobile' => 'nullable|max:30',

            'email' => 'nullable|email|max:150',
            'website' => 'nullable|url|max:255',

            'currency_id' => 'required|exists:currencies,id',
            'timezone' => 'required|max:60',
            'language' => 'required|max:30',
            'date_format' => 'required|max:20',
            'number_format' => 'required|max:20',

            'decimal_places' => 'required|integer|min:0|max:6',

            'accounting_method' => 'required|in:Accrual,Cash',

            'status' => 'required|boolean',

            'logo' => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048',

        ]);

        if ($request->hasFile('logo')) {

            if ($organization->logo && Storage::disk('public')->exists($organization->logo)) {
                Storage::disk('public')->delete($organization->logo);
            }

            $validated['logo'] = $request
                ->file('logo')
                ->store('organization', 'public');
        }

        $validated['updated_by'] = Auth::id();

        $organization->update($validated);

        SystemSettings::clear();

        return redirect()
            ->route('admin.org')
            ->with('success', 'Organization profile updated successfully.');
    }
}