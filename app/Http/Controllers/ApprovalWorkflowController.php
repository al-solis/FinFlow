<?php

namespace App\Http\Controllers;

use App\Services\SystemSettings;
use App\Models\approval_workflow;
use App\Models\approval_workflow_step;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\role;
use App\Models\user;

class ApprovalWorkflowController extends Controller
{
    private function getOrganizationId()
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }
    public function index(Request $request)
    {
        $workflows = approval_workflow::query()
            ->withCount('steps')
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

    // public function edit(approval_workflow $approval_workflow)
    // {
    //     $approval_workflow->load('steps');

    //     return view('appw.approval_workflows.create', [
    //         'workflow' => $approval_workflow,
    //         'moduleOptions' => approval_workflow::moduleOptions(),
    //         'roles' => role::orderBy('name')->get(),
    //         'users' => user::orderBy('last_name')->get(),
    //     ]);
    // }
    public function edit(approval_workflow $approval_workflow)
    {
        $approval_workflow->load('steps');

        $steps = $approval_workflow->steps
            ->sortBy('step_no')
            ->map(function ($step) {
                return [
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
        if ($approval_workflow->transactions()->exists()) {
            return back()->with('error', 'This workflow already has transactions and cannot be deleted. Deactivate it instead.');
        }

        $approval_workflow->delete();

        return redirect()
            ->route('appw.approval_workflows.index')
            ->with('success', 'Approval workflow deleted.');
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
        $workflow->steps()->delete();

        foreach (array_values($steps) as $index => $step) {
            approval_workflow_step::create([
                'approval_workflow_id' => $workflow->id,
                'step_no' => $index + 1,
                'step_name' => $step['step_name'],
                'approver_type' => $step['approver_type'],
                'role_id' => $step['approver_type'] === 'role' ? $step['role_id'] : null,
                'user_id' => $step['approver_type'] === 'user' ? $step['user_id'] : null,
                'can_edit_chart_of_account' => (bool) ($step['can_edit_chart_of_account'] ?? false),
                'can_edit_tax' => (bool) ($step['can_edit_tax'] ?? false),
                'can_edit_amount' => (bool) ($step['can_edit_amount'] ?? false),
                'can_return_to_requester' => (bool) ($step['can_return_to_requester'] ?? true),
                'is_final_approval' => $index === count($steps) - 1, // last row is always the final approver
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        }
    }
}
