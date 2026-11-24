<?php

namespace App\Http\Controllers;

use App\Constants\Modules;
use App\Http\Controllers\Controller;
use App\Models\invoice_series;
use App\Models\sales_invoice;
use App\Services\AuthorizationService;
use App\Services\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvoiceSeriesController extends Controller
{
    public const TYPES = [
        'sales_invoice' => 'Sales Invoice',
        'credit_memo' => 'Credit Memo',
        'debit_memo' => 'Debit Memo',
        'collection_receipt' => 'Collection Receipt',
    ];

    protected function authz(): AuthorizationService
    {
        return new AuthorizationService(Auth::user());
    }

    public function index(Request $request)
    {
        $authz = $this->authz();
        $authz->authorizeRead(Modules::AR, Modules::AR_SER);

        $orgId = SystemSettings::getOrganizationId();
        $base = invoice_series::where('organization_id', $orgId);

        $series = (clone $base)
            ->when($request->filled('searchtype'), fn($q) => $q->where('document_type', $request->searchtype))
            ->when($request->filled('searchstatus'), fn($q) => $q->where('is_active', $request->searchstatus === '1'))
            ->orderBy('document_type')->orderByDesc('is_default')->orderBy('code')
            ->paginate(config('app.paginate'))
            ->withQueryString();

        $active = (clone $base)->where('is_active', true)->get();

        return view('ar.series.index', [
            'series' => $series,
            'types' => self::TYPES,
            'activeCount' => $active->count(),
            'expiringCount' => $active->filter(fn($s) => $s->expiresWithin(30) || $s->isExpired())->count(),
            'lowCount' => $active->filter(fn($s) => $s->isLow())->count(),
            'canCreate' => $authz->canCreate(Modules::AR, Modules::AR_SER),
            'canUpdate' => $authz->canUpdate(Modules::AR, Modules::AR_SER),
            'canDelete' => $authz->canDelete(Modules::AR, Modules::AR_SER),
        ]);
    }

    public function create()
    {
        $this->authz()->authorizeCreate(Modules::AR, Modules::AR_SER);

        $orgId = SystemSettings::getOrganizationId();

        // FIX: the "Default" checkbox is only meaningful when at least one series
        // of that document type already exists. We surface this to the form so it
        // can show the checkbox disabled on the very first series (which will be
        // auto-defaulted by store()).
        $docTypesWithSeries = invoice_series::where('organization_id', $orgId)
            ->distinct()->pluck('document_type')->all();

        return view('ar.series.form', [
            'series' => new invoice_series([
                'document_type' => 'sales_invoice',
                'series_start' => 1,
                'next_number' => 1,
                'padding' => 6,
                'is_active' => true,
                'is_default' => false,
            ]),
            'types' => self::TYPES,
            'locked' => false,
            'docTypesWithSeries' => $docTypesWithSeries,
        ]);
    }

    public function store(Request $request)
    {
        $this->authz()->authorizeCreate(Modules::AR, Modules::AR_SER);

        $data = $request->validate($this->rules($request, null, false));
        $data['organization_id'] = SystemSettings::getOrganizationId();
        $data['next_number'] = $data['series_start'];
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');
        $data['created_by'] = $data['updated_by'] = Auth::id();

        $series = DB::transaction(function () use ($data) {
            // The first series of a document type is the default automatically.
            $hasOthers = invoice_series::where('organization_id', $data['organization_id'])
                ->where('document_type', $data['document_type'])->exists();
            if (!$hasOthers) {
                $data['is_default'] = true;
            }

            $series = invoice_series::create($data);
            $this->enforceSingleDefault($series);

            return $series;
        });

        return redirect()->route('ar.ser')->with('success', "Series {$series->code} created.");
    }

    public function edit(invoice_series $series)
    {
        $this->authz()->authorizeUpdate(Modules::AR, Modules::AR_SER);
        $this->assertOwnOrganization($series);

        $orgId = SystemSettings::getOrganizationId();
        $docTypesWithSeries = invoice_series::where('organization_id', $orgId)
            ->distinct()->pluck('document_type')->all();

        return view('ar.series.form', [
            'series' => $series,
            'types' => self::TYPES,
            'locked' => $series->isLocked(),
            'docTypesWithSeries' => $docTypesWithSeries,
        ]);
    }

    public function update(Request $request, invoice_series $series)
    {
        $this->authz()->authorizeUpdate(Modules::AR, Modules::AR_SER);
        $this->assertOwnOrganization($series);

        $locked = $series->isLocked();
        $data = $request->validate($this->rules($request, $series, $locked));

        // FIX: is_default / is_active are no longer blindly read from the request.
        // A locked series keeps its default flag (changing it would reroute future
        // numbers through a range that already has issued documents). is_active can
        // still be toggled on a locked series (deactivation is allowed), but
        // deactivating the current default is blocked below.
        if (!$locked) {
            $data['is_default'] = $request->boolean('is_default');
        }
        $data['is_active'] = $request->boolean('is_active');
        $data['updated_by'] = Auth::id();

        // FIX: block deactivating the default series without promoting another.
        $wouldBeInactiveDefault = $series->is_default && !$data['is_active'];
        if ($wouldBeInactiveDefault) {
            $otherDefaults = invoice_series::where('organization_id', $series->organization_id)
                ->where('document_type', $series->document_type)
                ->where('id', '!=', $series->id)
                ->where('is_default', true)
                ->where('is_active', true)
                ->exists();

            if (!$otherDefaults) {
                throw ValidationException::withMessages([
                    'is_active' => "Series {$series->code} is the default for {$this->typeLabel($series->document_type)}. Promote another active series to default first, then deactivate this one.",
                ]);
            }
        }

        DB::transaction(function () use ($series, $data) {
            $series->update($data);
            $this->enforceSingleDefault($series);
        });

        return redirect()->route('ar.ser')->with('success', "Series {$series->code} updated.");
    }

    public function destroy(invoice_series $series)
    {
        $this->authz()->authorizeDelete(Modules::AR, Modules::AR_SER);
        $this->assertOwnOrganization($series);

        if ($series->isLocked() || sales_invoice::where('invoice_series_id', $series->id)->exists()) {
            return redirect()->route('ar.ser')
                ->with('error', "Series {$series->code} has already issued numbers. Deactivate it instead of deleting; issued numbers must stay on record.");
        }

        $code = $series->code;
        $series->delete();

        return redirect()->route('ar.ser')->with('success', "Series {$code} deleted.");
    }

    // ------------------------------------------------------------------

    protected function assertOwnOrganization(invoice_series $series): void
    {
        abort_if((int) $series->organization_id !== (int) SystemSettings::getOrganizationId(), 404);
    }

    protected function typeLabel(string $type): string
    {
        return self::TYPES[$type] ?? $type;
    }

    /** Only one default per organization + document type (issue() picks the default first). */
    protected function enforceSingleDefault(invoice_series $series): void
    {
        if ($series->is_default && $series->is_active) {
            invoice_series::where('organization_id', $series->organization_id)
                ->where('document_type', $series->document_type)
                ->where('id', '!=', $series->id)
                ->update(['is_default' => false]);
        } elseif ($series->is_default && !$series->is_active) {
            // an inactive series cannot be the default
            $series->update(['is_default' => false]);
        }
    }

    protected function rules(Request $request, ?invoice_series $series, bool $locked): array
    {
        $orgId = SystemSettings::getOrganizationId();

        $rules = [
            'tax_branch_code' => 'nullable|string|max:10',
            'bir_authority_no' => 'nullable|string|max:60',
            'authority_date' => 'nullable|date',
            'valid_until' => array_filter([
                'nullable',
                'date',
                $request->filled('authority_date') ? 'after_or_equal:authority_date' : null,
            ]),
        ];

        if ($locked) {
            // Numbers already issued: the range can be extended but never cut below
            // what was issued. Clamp the floor to 1 so a series that has not yet
            // been issued (edge case) cannot be set to a nonsensical end of 0.
            $rules['series_end'] = [
                'nullable',
                'integer',
                'min:' . max((int) $series->next_number - 1, 1),
            ];

            return $rules;
        }

        return $rules + [
            'document_type' => ['required', Rule::in(array_keys(self::TYPES))],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('invoice_series', 'code')
                    ->where(fn($q) => $q->where('organization_id', $orgId)
                        ->where('document_type', $request->input('document_type')))
                    ->ignore($series?->id),
            ],
            'prefix' => 'nullable|string|max:20',
            'series_start' => 'required|integer|min:1',
            'series_end' => 'nullable|integer|gte:series_start',
            'padding' => 'required|integer|between:1,12',
        ];
    }

    /**
     * FIX: reject a new series whose (document_type, prefix, [start..end]) overlaps
     * an existing one in the same organization. Two overlapping ranges would emit
     * duplicate BIR document numbers when both are issued from.
     *
     * Only considers series that share the same prefix and document type. A series
     * with a different prefix is a different BIR book and can share the numeric
     * range.
     */
    protected function assertNoRangeOverlap(array $data, ?invoice_series $ignore = null): void
    {
        $prefix = $data['prefix'] ?? '';
        $start = (int) $data['series_start'];
        $end = isset($data['series_end']) && $data['series_end'] !== null && $data['series_end'] !== ''
            ? (int) $data['series_end']
            : null;

        $overlap = invoice_series::where('organization_id', $data['organization_id'])
            ->where('document_type', $data['document_type'])
            ->where(fn($q) => $q->where('prefix', $prefix)->orWhere(fn($w) => $w->whereNull('prefix')->whereRaw('? = ?', ['', ''])))
            ->when($ignore, fn($q) => $q->where('id', '!=', $ignore->id))
            ->where(function ($q) use ($start, $end) {
                // Existing range overlaps [start..end] iff existing.start <= end AND
                // (existing.end IS NULL OR existing.end >= start).
                $q->where('series_start', '<=', $end ?? PHP_INT_MAX)
                    ->where(fn($w) => $w->whereNull('series_end')->orWhere('series_end', '>=', $start));
            })
            ->first();

        if ($overlap) {
            throw ValidationException::withMessages([
                'series_start' => "This range overlaps series {$overlap->code} (" .
                    $overlap->label((int) $overlap->series_start) . " – " .
                    ($overlap->series_end ? $overlap->label((int) $overlap->series_end) : 'no end') .
                    ") for the same document type and prefix.",
            ]);
        }
    }
}