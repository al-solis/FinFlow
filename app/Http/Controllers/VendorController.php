<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\SystemSettings;
use App\Models\vendor;
use App\Models\VendorCategory;
use App\Models\currency;
use App\Models\term;
use App\Models\tax_group;
use App\Models\tax_master;
use App\Models\payment_method;
use App\Models\main_account;
use App\Models\vendor_bank_account;
use App\Models\country;
use App\Models\vendor_attachment;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = vendor::with('category')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('legal_name', 'like', "%{$search}%")
                        ->orWhere('tax_id', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category'), function ($q) use ($request) {
                $q->where('vendor_category_id', $request->category);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('is_active', $request->status);
            });

        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');
        $allowedSorts = ['code', 'name', 'created_at'];

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        }

        $vendors = $query->paginate(config('app.paginate'))->withQueryString();

        $vendorCategories = VendorCategory::where('status', 1)->get();
        // dd($vendors);
        return view('ap.vendor.index', compact('vendors', 'vendorCategories'));
    }

    public function create()
    {
        $vendors = vendor::all();
        $vendorCategories = VendorCategory::where('status', 1)->get();

        $currencies = currency::where('status', 1)
            ->get();

        $paymentTerms = term::where('status', 1)->get();

        $taxGroups = tax_group::where('status', 1)->get();

        $withholdingTaxes = tax_master::where('status', 1)
            ->where('tax_type_id', '!=', 1)
            ->get();

        $vatTaxes = tax_master::where('status', 1)
            ->where('tax_type_id', '=', 1)
            ->get();

        $paymentMethods = payment_method::where('status', 1)->get();

        $apAccounts = main_account::where('status', 1)
            ->where('account_type_id', 2)
            ->get();

        $countries = country::where('status', 1)->get();

        return view('ap.vendor.create', compact(
            'vendors',
            'vendorCategories',
            'currencies',
            'paymentTerms',
            'taxGroups',
            'withholdingTaxes',
            'vatTaxes',
            'paymentMethods',
            'apAccounts',
            'countries'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'vendor_code' => 'required|string|max:50|unique:vendors,code',
            'vendor_name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
            'vendor_category_id' => 'required|exists:vendor_categories,id',
            'industry' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:30',
            'tax_branch_code' => 'nullable|string|max:10',
            'registration_no' => 'nullable|string|max:100',
            'bir_rdo_code' => 'nullable|string|max:10',
            'tax_group_id' => 'nullable|exists:tax_groups,id',
            'address1' => 'nullable|string|max:255',
            'address2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'contact_person' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
            'email' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'website' => 'nullable|string|max:100',
            'contact_notes' => 'nullable|string',
            'currency_id' => 'nullable|exists:currencies,id',
            'payment_term_id' => 'nullable|exists:terms,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'ap_account_id' => 'nullable|exists:main_accounts,id',
            // Bank validation
            'banks' => 'nullable|array',
            'banks.*.bank_name' => 'nullable|string|max:255',
            'banks.*.branch' => 'nullable|string|max:255',
            'banks.*.account_name' => 'nullable|string|max:255',
            'banks.*.account_number' => 'nullable|string|max:50',
            'banks.*.swift_code' => 'nullable|string|max:20',
            'banks.*.currency_id' => 'nullable|exists:currencies,id',
            'attachments.*' => 'nullable|file|max:3072|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip', // 3072 KB = 3MB
            'attachments' => 'nullable|array|max:5', // Max 5 files
            [
                'attachments.*.max' => 'Each file must not exceed 3MB in size.',
                'attachments.*.mimes' => 'Only PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, and ZIP files are allowed.',
                'attachments.max' => 'You can upload a maximum of 5 files.',
            ]
        ]);

        $vendor = vendor::create([
            'code' => $request->vendor_code,
            'name' => $request->vendor_name,
            'legal_name' => $request->legal_name,
            'vendor_category_id' => $request->vendor_category_id,
            'industry' => $request->industry,
            'tax_id' => $request->tax_id,
            'tax_branch_code' => $request->tax_branch_code,
            'registration_no' => $request->registration_no,
            'bir_rdo_code' => $request->bir_rdo_code,
            'tax_group_id' => $request->tax_group_id,
            'address1' => $request->address1,
            'address2' => $request->address2,
            'city' => $request->city,
            'province' => $request->province,
            'country' => $request->country,
            'zip_code' => $request->zip_code,
            'contact_person' => $request->contact_person,
            'position' => $request->position,
            'email' => $request->email,
            'phone' => $request->phone,
            'mobile' => $request->mobile,
            'website' => $request->website,
            'contact_notes' => $request->contact_notes,
            'currency_id' => $request->currency_id,
            'payment_term_id' => $request->payment_term_id,
            'payment_method_id' => $request->payment_method_id,
            'ap_account_id' => $request->ap_account_id,
            'credit_limit' => $request->credit_limit,
            'requires_po' => $request->requires_po,
            'lead_time' => $request->lead_time,
            'preferred_vendor' => $request->preferred_vendor,
            'is_active' => $request->is_active,
            'is_blacklisted' => $request->is_blacklisted,
            'blacklist_reason' => $request->blacklist_reason,
            'remarks' => $request->remarks,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),

        ]);

        // Add bank accounts
        if ($request->has('banks')) {
            foreach ($request->banks as $bankData) {
                // Skip empty rows
                if (empty($bankData['bank_name']) && empty($bankData['account_number'])) {
                    continue;
                }

                vendor_bank_account::create([
                    'vendor_id' => $vendor->id,
                    'bank_name' => $bankData['bank_name'] ?? null,
                    'branch' => $bankData['branch'] ?? null,
                    'account_name' => $bankData['account_name'] ?? null,
                    'account_number' => $bankData['account_number'] ?? null,
                    'swift_code' => $bankData['swift_code'] ?? null,
                    'currency_id' => $bankData['currency_id'] ?? null,
                    'is_primary' => isset($bankData['is_primary']) && $bankData['is_primary'] == 1,
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                ]);
            }
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    // Create folder with vendor code
                    $folderPath = 'files/vendor/' . $vendor->code;
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs($folderPath, $filename, 'public');

                    vendor_attachment::create([
                        'vendor_id' => $vendor->id,
                        'filename' => $filename,
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                    ]);
                }
            }
        }

        return redirect()->route('ap.vendors')->with('success', __('Vendor created successfully.'));
    }

    public function edit($id)
    {
        $vendor = vendor::with(['category', 'paymentTerm', 'bankAccounts', 'attachments'])->findOrFail($id);

        // Get all the dropdown data
        $vendorCategories = VendorCategory::all();
        $currencies = currency::all();
        $paymentTerms = term::all();
        $paymentMethods = payment_method::all();
        $apAccounts = $apAccounts = main_account::where('status', 1)
            ->where('account_type_id', 2)
            ->get();
        $countries = country::all();
        $taxGroups = tax_group::all();
        $withholdingTaxes = tax_master::where('status', 1)
            ->where('tax_type_id', '!=', 1)
            ->get();
        $vatTaxes = tax_master::where('status', 1)
            ->where('tax_type_id', '=', 1)
            ->get();

        $settings = SystemSettings::get();

        return view('ap.vendor.create', compact(
            'vendor',
            'vendorCategories',
            'currencies',
            'paymentTerms',
            'paymentMethods',
            'apAccounts',
            'countries',
            'taxGroups',
            'withholdingTaxes',
            'vatTaxes',
            'settings'
        ));
    }

    public function update(Request $request, $id)
    {
        $vendor = vendor::findOrFail($id);

        $request->validate([
            'vendor_code' => 'required|string|max:50|unique:vendors,code,' . $vendor->id,
            'vendor_name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'vendor_category_id' => 'required|exists:vendor_categories,id',
            'industry' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:30',
            'tax_branch_code' => 'nullable|string|max:10',
            'registration_no' => 'nullable|string|max:100',
            'bir_rdo_code' => 'nullable|string|max:10',
            'tax_group_id' => 'nullable|exists:tax_groups,id',
            'address1' => 'nullable|string|max:255',
            'address2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'contact_person' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
            'email' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'website' => 'nullable|string|max:100',
            'contact_notes' => 'nullable|string',
            'currency_id' => 'nullable|exists:currencies,id',
            'payment_term_id' => 'nullable|exists:terms,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'ap_account_id' => 'nullable|exists:main_accounts,id',
            'credit_limit' => 'nullable|numeric|min:0',
            'requires_po' => 'nullable|boolean',
            'lead_time' => 'nullable|integer|min:0',
            'preferred_vendor' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'is_blacklisted' => 'nullable|boolean',
            'blacklist_reason' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            // Bank validation
            'attachments.*' => 'nullable|file|max:3072|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip', // 3072 KB = 3MB
            'attachments' => 'nullable|array|max:5', // Max 5 files
        ]);



        $vendor->update([
            'code' => $request->vendor_code,
            'name' => $request->vendor_name,
            'legal_name' => $request->legal_name,
            'vendor_category_id' => $request->vendor_category_id,
            'industry' => $request->industry,
            'tax_id' => $request->tax_id,
            'tax_branch_code' => $request->tax_branch_code,
            'registration_no' => $request->registration_no,
            'bir_rdo_code' => $request->bir_rdo_code,
            'tax_group_id' => $request->tax_group_id,
            'address1' => $request->address1,
            'address2' => $request->address2,
            'city' => $request->city,
            'province' => $request->province,
            'country' => $request->country,
            'zip_code' => $request->zip_code,
            'contact_person' => $request->contact_person,
            'position' => $request->position,
            'email' => $request->email,
            'phone' => $request->phone,
            'mobile' => $request->mobile,
            'website' => $request->website,
            'contact_notes' => $request->contact_notes,
            'currency_id' => $request->currency_id,
            'payment_term_id' => $request->payment_term_id,
            'payment_method_id' => $request->payment_method_id,
            'ap_account_id' => $request->ap_account_id,
            'credit_limit' => $request->credit_limit,
            'requires_po' => $request->requires_po,
            'lead_time' => $request->lead_time,
            'preferred_vendor' => $request->preferred_vendor,
            'is_active' => $request->is_active,
            'is_blacklisted' => $request->is_blacklisted ?? false,
            'blacklist_reason' => $request->blacklist_reason,
            'remarks' => $request->remarks,
            'updated_by' => Auth::id(),
            'updated_at' => now()
        ]);

        if ($request->has('banks')) {

            $vendor->bankAccounts()->delete(); // Delete existing bank accounts

            foreach ($request->banks as $bankData) {
                // Skip empty rows
                if (empty($bankData['bank_name']) && empty($bankData['account_number'])) {
                    continue;
                }

                vendor_bank_account::create([
                    'vendor_id' => $vendor->id,
                    'bank_name' => $bankData['bank_name'] ?? null,
                    'branch' => $bankData['branch'] ?? null,
                    'account_name' => $bankData['account_name'] ?? null,
                    'account_number' => $bankData['account_number'] ?? null,
                    'swift_code' => $bankData['swift_code'] ?? null,
                    'currency_id' => $bankData['currency_id'] ?? null,
                    'is_primary' => isset($bankData['is_primary']) && $bankData['is_primary'] == 1,
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                ]);
            }
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    // Create folder with vendor code
                    $folderPath = 'files/vendor/' . $vendor->code;
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs($folderPath, $filename, 'public');

                    vendor_attachment::create([
                        'vendor_id' => $vendor->id,
                        'filename' => $filename,
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                    ]);
                }
            }
        }

        return redirect()->route('ap.vendors')->with('success', __('Vendor updated successfully.'));
    }

    public function deleteAttachment($vendorId, $attachmentId)
    {
        try {
            $attachment = vendor_attachment::findOrFail($attachmentId);

            // Verify attachment belongs to vendor
            if ($attachment->vendor_id != $vendorId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attachment does not belong to this vendor.'
                ], 403);
            }

            // Delete file from storage
            if (Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }

            // Delete database record
            $attachment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Attachment deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting attachment: ' . $e->getMessage()
            ], 500);
        }
    }
}
