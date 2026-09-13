<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Constants\Modules;
use App\Traits\WithSystemSettings;
use App\Traits\AuthorizesAccessRights;
use App\Services\SystemSettings;
use App\Models\rfd_header;
use App\Models\rfd_detail;
use App\Models\rfd_detail_tax;
use App\Models\rfd_attachment;
use App\Models\vendor;
use App\Models\term;
use App\Models\payment_method;
use App\Models\currency;
use App\Models\tax_master;
use App\Models\chart_of_account;
use App\Models\approval_transaction;
use App\Services\ApprovalWorkflowService;


class RfdController extends Controller
{
    use AuthorizesAccessRights;
    use WithSystemSettings;

    private function getOrganizationId()
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }

    public function __construct(protected ApprovalWorkflowService $approvals)
    {
    }

    public function index(Request $request)
    {
        $this->authorizeRead(Modules::AP, Modules::AP_RFD);

        $rfds = rfd_header::query()
            ->with(['details.vendor', 'currency', 'attachments'])
            ->when($request->filled('searchvendor'), fn($q) => $q->whereHas(
                'details.vendor',
                fn($q2) => $q2->where('name', 'like', '%' . $request->searchvendor . '%')
            ))
            ->when($request->filled('searchstatus'), fn($q) => $q->where('status', $request->searchstatus))
            ->when($request->filled('searchapproval'), fn($q) => $q->where('approval_status', $request->searchapproval))
            ->when($request->filled('datefrom'), fn($q) => $q->whereDate('request_date', '>=', $request->datefrom))
            ->when($request->filled('dateto'), fn($q) => $q->whereDate('request_date', '<=', $request->dateto))
            ->orderByDesc('id')
            ->paginate(config('app.paginate', 15))
            ->withQueryString();

        return view('ap.rfd.index', compact('rfds'));
    }

    public function create()
    {
        $this->authorizeCreate(Modules::AP, Modules::AP_RFD);
        // dd($this->withSystemSettings());
        return view('ap.rfd.form', $this->withSystemSettings([
            'rfdHeader' => new rfd_header(),
            'lines' => [$this->emptyLine()],
            ...$this->lookups(),
        ]));


    }

    public function edit(rfd_header $rfd)
    {
        $this->authorizeUpdate(Modules::AP, Modules::AP_RFD);

        // dd($this->ownsModel($rfd, 'created_by'), $rfd->created_by, Auth::id());
        if (!$this->isAdmin() && !$this->ownsModel($rfd)) {
            abort(403, 'You can only edit your own RFDs.');
        }

        $this->authorizeDraftEdit($rfd);

        $lines = $rfd->details()->with([
            'taxes' => function ($query) {
                $query->orderBy('line_no');
            }
        ])->get()
            ->map(fn($d) => [
                '_key' => (string) $d->id,
                'id' => $d->id,
                'vendor_id' => $d->vendor_id,
                'description' => $d->description,
                'gl_account_id' => $d->gl_account_id,
                'reference' => $d->reference,
                'quantity' => (float) $d->quantity,
                'unit_price' => (float) $d->unit_price,
                'discount_amount' => (float) $d->discount_amount,
                'total_amount' => (float) $d->total_amount,
                'taxes' => $d->taxes->map(fn($t) => ['tax_id' => $t->tax_id])->values(),
            ])->values()->all();

        if (empty($lines)) {
            $lines = [$this->emptyLine()];
        }

        return view('ap.rfd.form', $this->withSystemSettings([
            'rfdHeader' => $rfd,
            'lines' => $lines,
            ...$this->lookups(),
        ]));
    }

    public function store(Request $request)
    {
        $this->authorizeCreate(Modules::AP, Modules::AP_RFD);

        $data = $this->validateHeader($request);

        DB::transaction(function () use ($request, $data) {
            $rfd = rfd_header::create([
                ...$data,
                'organization_id' => $this->getOrganizationId(),
                'status' => '0',
                'approval_status' => '0',
                'created_by' => Auth::id(),
            ]);

            $this->syncLines($rfd, $request->input('lines', []));
            $this->syncAttachments(
                $rfd,
                $request->file('attachment_files', []),
                $this->buildAttachmentsData($request->input('attachment_descriptions', []))
            );
            $this->recalcHeaderTotals($rfd);

            if ($request->boolean('submit_for_approval')) {
                $this->approvals->submit($rfd->fresh(), 'rfd', (float) $rfd->total_amount, Auth::id());
                $rfd->update(['submitted_at' => now(), 'submitted_by' => Auth::id()]);
            }
        });

        return redirect()->route('ap.rfd')->with('success', 'Request for disbursement saved.');
    }

    public function update(Request $request, rfd_header $rfd)
    {
        $this->authorizeUpdate(Modules::AP, Modules::AP_RFD);

        if (!$this->isAdmin() && !$this->ownsModel($rfd)) {
            abort(403, 'You can only update your own RFDs.');
        }

        $this->authorizeDraftEdit($rfd);

        $data = $this->validateHeader($request);

        DB::transaction(function () use ($request, $rfd, $data) {
            $rfd->update([...$data, 'updated_by' => Auth::id()]);

            $rfd->details()->each(function ($d) {
                $d->taxes()->delete();
                $d->delete();
            });
            $this->syncLines($rfd, $request->input('lines', []));

            $this->syncAttachments(
                $rfd,
                $request->file('attachment_files', []),
                $this->buildAttachmentsData($request->input('attachment_descriptions', []))
            );
            $this->recalcHeaderTotals($rfd);

            if ($request->boolean('submit_for_approval')) {
                $this->approvals->submit($rfd->fresh(), 'rfd', (float) $rfd->total_amount, Auth::id());
                $rfd->update(['submitted_at' => now(), 'submitted_by' => Auth::id()]);
            }
        });

        return redirect()->route('ap.rfd')->with('success', 'Request for disbursement updated.');
    }

    protected function buildAttachmentsData(array $descriptions): array
    {
        $data = [];
        foreach ($descriptions as $index => $description) {
            $data[$index] = ['description' => $description];
        }
        return $data;
    }

    /**
     * Download attachment
     */
    public function downloadAttachment(rfd_attachment $attachment)
    {
        $this->authorizeRead(Modules::AP, Modules::AP_RFD);

        if (!Storage::disk('private')->exists($attachment->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('private')->download(
            $attachment->file_path,
            $attachment->original_filename
        );
    }

    /**
     * Delete attachment - Now using AuthorizesRequests trait
     */
    public function deleteAttachment(rfd_attachment $attachment)
    {
        $this->authorizeDelete(Modules::AP, Modules::AP_RFD);

        DB::transaction(function () use ($attachment) {
            if (Storage::disk('private')->exists($attachment->file_path)) {
                Storage::disk('private')->delete($attachment->file_path);
            }
            $attachment->delete();
        });

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Attachment deleted.');
    }

    public function showApproval(approval_transaction $transaction)
    {
        $step = $transaction->currentStep();
        abort_unless($step, 404);

        $rfd = $transaction->approvable()->with([
            'details.taxes.tax',
            'details.glAccount',
            'details.vendor',
            'term',
            'paymentMethod',
            'currency',
            'attachments',
        ])->first();

        return view('ap.rfd.approve', $this->withSystemSettings([
            'transaction' => $transaction,
            'step' => $step,
            'rfd' => $rfd,
            'taxes' => tax_master::where('organization_id', $this->getOrganizationId())->where('status', 1)->orderBy('name')->get(),
            'glAccounts' => chart_of_account::with('structure')->where('organization_id', $this->getOrganizationId())
                ->whereHas('structure', fn($q) => $q->where('status', 1)
                    ->where('is_default', true))
                ->where('is_posting', true)->where('status', 1)->orderBy('account_code')->get(),
        ]));
    }

    public function approve(Request $request, approval_transaction $transaction)
    {
        $step = $transaction->currentStep();
        abort_unless($step, 404);

        $rfd = $transaction->approvable;

        DB::transaction(function () use ($request, $step, $rfd) {
            foreach ($request->input('lines', []) as $lineId => $lineData) {
                $detail = rfd_detail::where('rfd_header_id', $rfd->id)->findOrFail($lineId);

                if ($step->can_edit_chart_of_account && $request->filled("lines.$lineId.gl_account_id")) {
                    $detail->gl_account_id = $lineData['gl_account_id'];
                }

                if ($step->can_edit_amount && isset($lineData['unit_price'])) {
                    $detail->unit_price = $lineData['unit_price'];
                    $detail->discount_amount = $lineData['discount_amount'] ?? $detail->discount_amount;
                }

                $detail->updated_by = Auth::id();
                $detail->save();

                if ($step->can_edit_tax) {
                    $detail->taxes()->delete();
                    foreach ($lineData['tax_ids'] ?? [] as $taxId) {
                        $this->applyTaxToLine($detail, $taxId);
                    }
                }

                $detail->recalcTotals();
            }

            $this->recalcHeaderTotals($rfd->fresh());
        });

        $this->approvals->approve($transaction->fresh(), Auth::id(), $request->input('remarks'));

        return redirect()->route('approvals.index')->with('success', 'Request approved.');
    }

    public function returnToRequester(Request $request, approval_transaction $transaction)
    {
        $request->validate(['remarks' => 'required|string|max:1000']);
        $this->approvals->returnToRequester($transaction, Auth::id(), $request->input('remarks'));

        return redirect()->route('approvals.index')->with('success', 'Request returned to requester.');
    }

    public function reject(Request $request, approval_transaction $transaction)
    {
        $request->validate(['remarks' => 'required|string|max:1000']);
        $this->approvals->reject($transaction, Auth::id(), $request->input('remarks'));

        return redirect()->route('approvals.index')->with('success', 'Request rejected.');
    }

    // ---- helpers -----------------------------------------------------

    protected function authorizeDraftEdit(rfd_header $rfd): void
    {
        abort_if(
            !in_array($rfd->approval_status, ['0', '4', null]),
            403,
            'Only draft or returned requests can be edited.'
        );
    }

    protected function validateHeader(Request $request): array
    {
        return $request->validate([
            'request_date' => 'required|date',
            'required_date' => 'nullable|date|after_or_equal:request_date',
            'term_id' => 'nullable|exists:terms,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'currency_id' => 'required|exists:currencies,id',
            'exchange_rate' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:2000',
            'lines' => 'required|array|min:1',
            'lines.*.vendor_id' => 'required|exists:vendors,id',
            'lines.*.description' => 'required|string|max:255',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_amount' => 'nullable|numeric|min:0',
            'lines.*.gl_account_id' => 'nullable|exists:chart_of_accounts,id',
            'lines.*.reference' => 'nullable|string|max:50',
            'lines.*.tax_ids' => 'nullable|array',
            'lines.*.tax_ids.*' => 'exists:tax_masters,id',
            'attachment_files.*' => 'nullable|file|max:20480',
            'attachment_descriptions.*' => 'nullable|string|max:255',
        ]);
    }

    protected function syncLines(rfd_header $rfd, array $lines): void
    {
        foreach (array_values($lines) as $i => $line) {
            $quantity = (float) $line['quantity'];
            $unitPrice = (float) $line['unit_price'];
            $discount = (float) ($line['discount_amount'] ?? 0);
            $taxableAmount = ($quantity * $unitPrice) - $discount;

            $detail = rfd_detail::create([
                'rfd_header_id' => $rfd->id,
                'line_no' => $i + 1,
                'item_id' => $line['item_id'] ?? null,
                'vendor_id' => $line['vendor_id'],
                'gl_account_id' => $line['gl_account_id'] ?? null,
                'description' => $line['description'],
                'reference' => $line['reference'] ?? '',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => $discount,
                'taxable_amount' => $taxableAmount,
                'tax_amount' => 0,
                'total_amount' => $taxableAmount,
                'created_by' => Auth::id(),
            ]);

            foreach ($line['tax_ids'] ?? [] as $taxId) {
                if ($taxId === '' || $taxId === null) {
                    continue;
                }
                $this->applyTaxToLine($detail, (int) $taxId);
            }

            $detail->recalcTotals();
        }
    }

    protected function syncAttachments(rfd_header $rfd, array $files, array $attachmentsData): void
    {
        foreach ($attachmentsData as $index => $attachmentData) {
            if (!isset($files[$index]) || !$files[$index]->isValid()) {
                continue;
            }

            $file = $files[$index];
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::uuid() . '.' . $extension;
            $filePath = 'rfd/' . $rfd->id . '/' . $fileName;

            Storage::disk('private')->putFileAs('rfd/' . $rfd->id, $file, $fileName);

            rfd_attachment::create([
                'rfd_header_id' => $rfd->id,
                'file_name' => $fileName,
                'original_filename' => $originalName,
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'description' => $attachmentData['description'] ?? null,
                'uploaded_by' => Auth::id(),
            ]);
        }
    }

    /**
     * Helper to extract file from upload array
     */
    protected function getFileFromUpload(array $files, int $index)
    {
        // Check if files are structured as attachments[index][file]
        if (isset($files[$index]['file']) && $files[$index]['file'] instanceof \Illuminate\Http\UploadedFile) {
            return $files[$index]['file'];
        }

        // Check if files are structured as attachments[index]
        if (isset($files[$index]) && $files[$index] instanceof \Illuminate\Http\UploadedFile) {
            return $files[$index];
        }

        // Check if files are flat array
        if (isset($files[$index]) && is_array($files[$index])) {
            foreach ($files[$index] as $key => $value) {
                if ($value instanceof \Illuminate\Http\UploadedFile) {
                    return $value;
                }
            }
        }

        return null;
    }
    protected function recalcLineTotals(rfd_detail $detail): void
    {
        $taxable = ($detail->quantity * $detail->unit_price) - $detail->discount_amount;
        $tax = 0;

        foreach ($detail->taxes as $detailTax) {
            $taxMaster = tax_master::where('organization_id', $this->getOrganizationId())->find($detailTax->tax_id);
            if ($taxMaster) {
                if ($taxMaster->fixed_amount > 0) {
                    $tax += $taxMaster->fixed_amount;
                } elseif ($taxMaster->rate > 0) {
                    $tax += $taxable * ($taxMaster->rate / 100);
                }
            }
        }

        $detail->taxable_amount = $taxable;
        $detail->tax_amount = $tax;
        $detail->total_amount = $taxable + $tax;
        $detail->save();
    }

    protected function applyTaxToLine(rfd_detail $detail, int $taxId): void
    {
        $tax = tax_master::where('organization_id', $this->getOrganizationId())->find($taxId);
        if (!$tax) {
            return;
        }

        $taxableAmount = $detail->taxable_amount;
        $taxAmount = $tax->fixed_amount > 0
            ? (float) $tax->fixed_amount
            : round($taxableAmount * ((float) $tax->rate / 100), 2);

        rfd_detail_tax::create([
            'rfd_detail_id' => $detail->id,
            'line_no' => $detail->line_no,
            'tax_id' => $tax->id,
            'taxable_amount' => $taxableAmount,
            'tax_amount' => $taxAmount,
            'created_by' => Auth::id(),
        ]);
    }

    protected function recalcHeaderTotals(rfd_header $rfd): void
    {
        $details = $rfd->details()->get();

        $rfd->update([
            'total_amount' => $details->sum('taxable_amount'),
            'total_tax' => $details->sum('tax_amount'),
            'total_discount' => $details->sum('discount_amount'),
            'total_due' => $details->sum('total_amount'),
        ]);
    }

    protected function emptyLine(): array
    {
        return [
            '_key' => (string) str()->uuid(),
            'id' => null,
            'vendor_id' => '',
            'description' => '',
            'gl_account_id' => '',
            'reference' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'discount_amount' => 0,
            'taxes' => [],
        ];
    }

    protected function lookups(): array
    {
        return [
            'vendors' => vendor::with('category')
                ->whereHas('category', fn($q) => $q->where('organization_id', $this->getOrganizationId())
                    ->where('code', '!=', 'EMP')
                    ->where('is_active', 1))
                ->where('organization_id', $this->getOrganizationId())->where('is_active', 1)
                ->orderBy('name')->get(),
            'terms' => term::where('organization_id', $this->getOrganizationId())->where('status', 1)->orderBy('name')->get(),
            'paymentMethods' => payment_method::where('organization_id', $this->getOrganizationId())->where('status', 1)->orderBy('name')->get(),
            'currencies' => currency::where('status', 1)->orderBy('code')->get(),
            'taxes' => tax_master::where('organization_id', $this->getOrganizationId())
                ->whereNotNull('gl_account_id')
                ->where('status', 1)
                ->orderBy('name')->get(),
            'glAccounts' => chart_of_account::with('structure', 'accountType')->where('organization_id', $this->getOrganizationId())
                ->whereHas('structure', fn($q) => $q->where('status', 1)
                    ->where('is_default', true))
                ->whereHas('accountType', fn($q) => $q->where('organization_id', $this->getOrganizationId())
                    ->whereIn('code', ['LIABILITY', 'EXPENSE']))
                ->where('is_posting', true)->where('status', 1)->orderBy('account_code')->get(),
        ];
    }
}