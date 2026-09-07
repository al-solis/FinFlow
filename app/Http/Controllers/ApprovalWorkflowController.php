<?php

namespace App\Http\Controllers;

use App\Constants\Modules;
use App\Services\AuthorizationService;
use App\Services\SystemSettings;
use App\Models\approval_workflow;
use App\Models\approval_workflow_step;
use App\Traits\AuthorizesAccessRights;
use App\Traits\WithSystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\role;
use App\Models\user;
use App\Models\approval_transaction_history;

class ApprovalWorkflowController extends Controller
{
    use AuthorizesAccessRights;
    use WithSystemSettings;

    private function getOrganizationId()
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }
    public function index(Request $request)
    {
        $this->authorizeRead(Modules::APPW, Modules::APPW_APPR);

        $workflows = approval_workflow::query()
            ->withCount('steps')
            ->whereHas('steps', function ($query) {
                $query->where('is_active', true);
            })
            ->when($request->filled('searchmodule'), fn($q) => $q->where('module_code', $request->searchmodule))
            ->when($request->filled('searchname'), fn($q) => $q->where('name', 'like', '%' . $request->searchname . '%'))
            ->when($request->filled('searchstatus'), fn($q) => $q->where('is_active', $request->searchstatus === 'active'))
            ->where('organization_id', $this->getOrganizationId())
            ->orderBy('module_code')
            ->orderBy('name')
            ->paginate(config('app.paginate'))
            ->withQueryString();

        return view('appw.approval_workflows.index', [
            'workflows' => $workflows,
            'moduleOptions' => approval_workflow::moduleOptions(),
        ]);
    }

    // public function create()
    // {
    //     return view('appw.approval_workflows.create', [
    //         'moduleOptions' => approval_workflow::moduleOptions(),
    //         'roles' => role::orderBy('name')->get(),
    //         'users' => user::orderBy('last_name')->get(),
    //     ]);
    // }
    public function create()
    {
        $this->authorizeCreate(Modules::APPW, Modules::APPW_APPR);

        return view('appw.approval_workflows.create', [
            'workflow' => null,
            'steps' => [
                [
                    'step_name' => '',
                    'approver_type' => 'role',
                    'role_id' => '',
                    'user_id' => '',
                    'can_edit_chart_of_account' => false,
                    'can_edit_tax' => false,
                    'can_edit_amount' => false,
                    'can_return_to_requester' => true,
                    'is_final_approval' => true,
                ],
            ],
            'moduleOptions' => approval_workflow::moduleOptions(),
            'roles' => role::orderBy('name')->get(),
            'users' => user::orderBy('last_name')->get(),
        ]);
    }
    public function store(Request $request)
    {
        $this->authorizeCreate(Modules::APPW, Modules::APPW_APPR);

        $validated = $this->validateWorkflow($request);

        DB::transaction(function () use ($validated) {
            $workflow = approval_workflow::create([
                ...$validated['workflow'],
                'organization_id' => $this->getOrganizationId(),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $this->syncSteps($workflow, $validated['steps']);
        });

        return redirect()
            ->route('appw.appr')
            ->with('success', 'Approval workflow [' . $request->name . '] created.');
    }

    public function edit(approval_workflow $approval_workflow)
    {
        $this->authorizeUpdate(Modules::APPW, Modules::APPW_APPR);

        $approval_workflow->load([
            'steps' => function ($query) {
                $query->where('is_active', true);
            }
        ]);

        $steps = $approval_workflow->steps
            ->map(function ($step) {
                return [
                    'id' => $step->id,
                    'step_name' => $step->step_name,
                    'approver_type' => $step->approver_type,
                    'role_id' => $step->role_id ?? '',
                    'user_id' => $step->user_id ?? '',
                    'can_edit_chart_of_account' => (bool) $step->can_edit_chart_of_account,
                    'can_edit_tax' => (bool) $step->can_edit_tax,
                    'can_edit_amount' => (bool) $step->can_edit_amount,
                    'can_return_to_requester' => (bool) $step->can_return_to_requester,
                    'is_final_approval' => (bool) $step->is_final_approval,
                ];
            })
            ->values()
            ->toArray();

        // If no active steps exist, provide a default empty step
        if (empty($steps)) {
            $steps = [
                [
                    'step_name' => '',
                    'approver_type' => 'role',
                    'role_id' => '',
                    'user_id' => '',
                    'can_edit_chart_of_account' => false,
                    'can_edit_tax' => false,
                    'can_edit_amount' => false,
                    'can_return_to_requester' => true,
                    'is_final_approval' => true,
                ],
            ];
        }

        return view('appw.approval_workflows.create', [
            'workflow' => $approval_workflow,
            'steps' => $steps,
            'moduleOptions' => approval_workflow::moduleOptions(),
            'roles' => role::orderBy('name')->get(),
            'users' => user::orderBy('last_name')->get(),
        ]);
    }

    public function update(Request $request, approval_workflow $approval_workflow)
    {
        $this->authorizeUpdate(Modules::APPW, Modules::APPW_APPR);

        $validated = $this->validateWorkflow($request);

        DB::transaction(function () use ($validated, $approval_workflow) {
            $approval_workflow->update([
                ...$validated['workflow'],
                'updated_by' => Auth::id(),
            ]);

            $this->syncSteps($approval_workflow, $validated['steps']);
        });

        return redirect()
            ->route('appw.appr')
            ->with('success', 'Approval workflow [' . $approval_workflow->name . '] updated.');
    }

    public function destroy(approval_workflow $approval_workflow)
    {
        $this->authorizeDelete(Modules::APPW, Modules::APPW_APPR);

        if ($approval_workflow->transactions()->exists()) {
            return back()->with('error', 'This workflow already has transactions and cannot be deleted. Deactivate it instead.');
        }

        $approval_workflow->delete();

        return redirect()
            ->route('appw.approval_workflows.index')
            ->with('success', 'Approval workflow deleted.');
    }

    public function toggleStatus(approval_workflow $approval_workflow)
    {
        $this->authorizeUpdate(Modules::APPW, Modules::APPW_APPR);

        $approval_workflow->update([
            'is_active' => !$approval_workflow->is_active,
            'updated_by' => Auth::id(),
        ]);

        $status = $approval_workflow->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Workflow [{$approval_workflow->name}] {$status}.");
    }

    private function validateWorkflow(Request $request): array
    {
        $data = $request->validate([
            'module_code' => ['required', 'string', Rule::in(array_keys(approval_workflow::moduleOptions()))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'gte:min_amount'],
            'is_active' => ['nullable', 'boolean'],

            'steps' => ['required', 'array', 'min:1'],
            'steps.*.id' => ['nullable', 'integer'],
            'steps.*.step_name' => ['required', 'string', 'max:255'],
            'steps.*.approver_type' => ['required', Rule::in(['role', 'user'])],
            'steps.*.role_id' => ['nullable', 'required_if:steps.*.approver_type,role', 'integer'],
            'steps.*.user_id' => ['nullable', 'required_if:steps.*.approver_type,user', 'integer'],
            'steps.*.can_edit_chart_of_account' => ['nullable', 'boolean'],
            'steps.*.can_edit_tax' => ['nullable', 'boolean'],
            'steps.*.can_edit_amount' => ['nullable', 'boolean'],
            'steps.*.can_return_to_requester' => ['nullable', 'boolean'],
            'steps.*.is_final_approval' => ['nullable', 'boolean'],
        ]);

        return [
            'workflow' => [
                'module_code' => $data['module_code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'min_amount' => $data['min_amount'] ?? null,
                'max_amount' => $data['max_amount'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ],
            'steps' => $data['steps'],
        ];
    }

    private function syncSteps(approval_workflow $workflow, array $steps): void
    {
        $existingSteps = $workflow->steps()->get()->keyBy('id'); // ALL steps, active + inactive

        // Phase 1: park every existing step at a temporary negative step_no. This clears
        // the unique(approval_workflow_id, step_no) space so phase 2 can never collide
        // mid-loop — e.g. when the user swaps the order of two steps.
        $tempOffset = 1;
        foreach ($existingSteps as $step) {
            $step->update(['step_no' => -$tempOffset]);
            $tempOffset++;
        }

        $submittedIds = [];

        foreach (array_values($steps) as $index => $stepData) {
            $stepNo = $index + 1;
            $isFinal = $index === count($steps) - 1;
            $stepId = !empty($stepData['id'] ?? null) ? (int) $stepData['id'] : null;

            $payload = [
                'approval_workflow_id' => $workflow->id,
                'step_no' => $stepNo,
                'step_name' => $stepData['step_name'],
                'approver_type' => $stepData['approver_type'],
                'role_id' => $stepData['approver_type'] === 'role' ? $stepData['role_id'] : null,
                'user_id' => $stepData['approver_type'] === 'user' ? $stepData['user_id'] : null,
                'can_edit_chart_of_account' => (bool) ($stepData['can_edit_chart_of_account'] ?? false),
                'can_edit_tax' => (bool) ($stepData['can_edit_tax'] ?? false),
                'can_edit_amount' => (bool) ($stepData['can_edit_amount'] ?? false),
                'can_return_to_requester' => (bool) ($stepData['can_return_to_requester'] ?? true),
                'is_final_approval' => $isFinal,
                'is_active' => true,
                'updated_by' => Auth::id(),
            ];

            if ($stepId && $existingSteps->has($stepId)) {
                // Update the SAME row this step has always been — its history stays accurate
                $existingSteps->get($stepId)->update($payload);
                $submittedIds[] = $stepId;
            } else {
                // Brand-new step (added via "+ Add another step")
                $payload['created_by'] = Auth::id();
                $new = approval_workflow_step::create($payload);
                $submittedIds[] = $new->id;
            }
        }

        // Anything still parked at a temporary negative step_no wasn't in the submitted
        // form — the user removed it.
        $idsToRemove = $existingSteps->keys()->diff($submittedIds);

        foreach ($idsToRemove as $id) {
            $step = $existingSteps->get($id);
            $hasStepHistory = approval_transaction_history::where('approval_workflow_step_id', $step->id)->exists();

            if ($hasStepHistory) {
                // Part of the audit trail — never delete, just deactivate.
                $step->update(['is_active' => false, 'updated_by' => Auth::id()]);
            } else {
                $step->delete();
            }
        }
    }

}
