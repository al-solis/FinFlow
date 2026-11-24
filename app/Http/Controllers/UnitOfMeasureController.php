<?php

namespace App\Http\Controllers;

use App\Constants\Modules;
use App\Http\Controllers\Controller;
use App\Models\unit_of_measure;
use App\Services\AuthorizationService;
use App\Services\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UnitOfMeasureController extends Controller
{
    protected function authz(): AuthorizationService
    {
        return new AuthorizationService(Auth::user());
    }

    public function index(Request $request)
    {
        $authz = $this->authz();
        $authz->authorizeRead(Modules::MASTER, Modules::MASTER_UOM);

        $orgId = SystemSettings::getOrganizationId();
        $base = unit_of_measure::where('organization_id', $orgId);

        $uoms = (clone $base)
            ->with('baseUom')
            ->withCount(['derivedUoms', 'items', 'purchaseItems', 'salesItems'])
            ->when($request->filled('searchname'), function ($q) use ($request) {
                $s = '%' . $request->searchname . '%';
                $q->where(fn($w) => $w->where('code', 'like', $s)->orWhere('name', 'like', $s));
            })
            ->when($request->searchtype === 'base', fn($q) => $q->whereNull('base_uom_id'))
            ->when($request->searchtype === 'derived', fn($q) => $q->whereNotNull('base_uom_id'))
            ->when($request->filled('searchstatus'), fn($q) => $q->where('status', (int) $request->searchstatus))
            ->orderByRaw('COALESCE(base_uom_id, id)')
            ->orderByRaw('CASE WHEN base_uom_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('code')
            ->paginate(config('app.paginate'))
            ->withQueryString();

        return view('master.uom.index', [
            'uoms' => $uoms,
            'totalCount' => (clone $base)->count(),
            'baseCount' => (clone $base)->whereNull('base_uom_id')->count(),
            'derivedCount' => (clone $base)->whereNotNull('base_uom_id')->count(),
            'canCreate' => $authz->canCreate(Modules::MASTER, Modules::MASTER_UOM),
            'canUpdate' => $authz->canUpdate(Modules::MASTER, Modules::MASTER_UOM),
            'canDelete' => $authz->canDelete(Modules::MASTER, Modules::MASTER_UOM),
        ]);
    }

    public function create()
    {
        $this->authz()->authorizeCreate(Modules::MASTER, Modules::MASTER_UOM);

        return view('master.uom.form', $this->formData(new unit_of_measure(['status' => 1, 'factor' => 1]), false));
    }

    public function store(Request $request)
    {
        $this->authz()->authorizeCreate(Modules::MASTER, Modules::MASTER_UOM);

        $data = $this->normalize($request->validate($this->rules($request, null, false)));
        $data['organization_id'] = SystemSettings::getOrganizationId();
        $data['created_by'] = $data['updated_by'] = Auth::id();

        $uom = unit_of_measure::create($data);

        return redirect()->route('master.uom')->with('success', "Unit {$uom->code} created.");
    }

    public function edit(unit_of_measure $uom)
    {
        $this->authz()->authorizeUpdate(Modules::MASTER, Modules::MASTER_UOM);
        $this->assertOwnOrganization($uom);

        return view('master.uom.form', $this->formData($uom, $uom->isInUse()));
    }

    public function update(Request $request, unit_of_measure $uom)
    {
        $this->authz()->authorizeUpdate(Modules::MASTER, Modules::MASTER_UOM);
        $this->assertOwnOrganization($uom);

        $locked = $uom->isInUse();
        $data = $request->validate($this->rules($request, $uom, $locked));

        // Once a unit is referenced, its conversion is frozen so old documents stay meaningful.
        $data = $locked ? $data : $this->normalize($data);

        if ((int) $data['status'] === 0 && $uom->derivedUoms()->where('status', 1)->exists()) {
            throw ValidationException::withMessages([
                'status' => 'Deactivate the units that convert to ' . $uom->code . ' first.',
            ]);
        }

        $data['updated_by'] = Auth::id();
        $uom->update($data);

        return redirect()->route('master.uom')->with('success', "Unit {$uom->code} updated.");
    }

    public function destroy(unit_of_measure $uom)
    {
        $this->authz()->authorizeDelete(Modules::MASTER, Modules::MASTER_UOM);
        $this->assertOwnOrganization($uom);

        if ($uom->isInUse()) {
            return redirect()->route('master.uom')
                ->with('error', "Unit {$uom->code} is used by items, other units, or invoice lines. Set it to Inactive instead of deleting.");
        }

        $code = $uom->code;
        $uom->delete();

        return redirect()->route('master.uom')->with('success', "Unit {$code} deleted.");
    }

    // ------------------------------------------------------------------

    protected function assertOwnOrganization(unit_of_measure $uom): void
    {
        abort_if((int) $uom->organization_id !== (int) SystemSettings::getOrganizationId(), 404);
    }

    protected function normalize(array $data): array
    {
        if (empty($data['base_uom_id'])) {
            $data['base_uom_id'] = null;
            $data['factor'] = 1;
        }

        return $data;
    }

    protected function rules(Request $request, ?unit_of_measure $uom, bool $locked): array
    {
        $orgId = SystemSettings::getOrganizationId();

        $rules = [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('unit_of_measures', 'code')->where('organization_id', $orgId)->ignore($uom?->id)
            ],
            'name' => 'required|string|max:100',
            'status' => ['required', Rule::in([0, 1])],
        ];

        if (!$locked) {
            // A base must exist in this organization and must itself be a base unit (no chains)
            $baseRule = [
                'nullable',
                Rule::exists('unit_of_measures', 'id')->where(fn($q) => $q->where('organization_id', $orgId)->whereNull('base_uom_id')),
            ];
            if ($uom?->exists) {
                $baseRule[] = Rule::notIn([$uom->id]);
            }

            $rules['base_uom_id'] = $baseRule;
            $rules['factor'] = [Rule::requiredIf($request->filled('base_uom_id')), 'nullable', 'numeric', 'gt:0', 'max:999999999'];
        }

        return $rules;
    }

    protected function formData(unit_of_measure $uom, bool $locked): array
    {
        $orgId = SystemSettings::getOrganizationId();

        $bases = unit_of_measure::where('organization_id', $orgId)
            ->whereNull('base_uom_id')
            ->where('status', 1)
            ->when($uom->exists, fn($q) => $q->where('id', '!=', $uom->id))
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return [
            'uom' => $uom,
            'locked' => $locked,
            'bases' => $bases,
        ];
    }
}
