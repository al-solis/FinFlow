<?php

namespace App\Http\Controllers;

use App\Models\access_right;
use App\Models\module;
use App\Models\role;
use App\Services\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccessRightController extends Controller
{
    private function getOrganizationId()
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }

    public function index(Request $request)
    {
        $roles = role::orderBy('name')->get();

        if ($roles->isEmpty()) {
            return view('admin.access.index', [
                'roles' => $roles,
                'selectedRoleId' => null,
                'modules' => collect(),
                'rightsMap' => collect(),
            ]);
        }

        $selectedRoleId = (int) $request->input('role_id', $roles->first()->id);

        $modules = module::with(['subModules'])
            ->where('is_active', true)
            ->orderBy('sequence')
            ->get();

        // Key: "{module_id}-{sub_module_id|null}" - using 'null' as string for module-level rights
        $rightsMap = access_right::where('organization_id', $this->getOrganizationId())
            ->where('role_id', $selectedRoleId)
            ->get()
            ->keyBy(fn($r) => $r->module_id . '-' . ($r->sub_module_id ?? 'null'));

        return view('admin.access.index', compact('roles', 'selectedRoleId', 'modules', 'rightsMap'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'rights' => 'nullable|array',
        ]);

        $roleId = (int) $validated['role_id'];
        $orgId = $this->getOrganizationId();
        $submitted = $request->input('rights', []);

        DB::transaction(function () use ($submitted, $roleId, $orgId) {
            // Get all existing rights for this role
            $existingRights = access_right::where('organization_id', $orgId)
                ->where('role_id', $roleId)
                ->get()
                ->keyBy(fn($r) => $r->module_id . '-' . ($r->sub_module_id ?? 'null'));

            $processedKeys = [];

            foreach ($submitted as $moduleId => $subModules) {
                foreach ($subModules as $subModuleKey => $flags) {
                    // Convert 'null' string back to null for database
                    $subModuleId = $subModuleKey === 'null' ? null : (int) $subModuleKey;
                    $key = $moduleId . '-' . $subModuleKey;

                    // Skip if flags is empty or not an array
                    if (!is_array($flags)) {
                        continue;
                    }

                    $right = access_right::firstOrNew([
                        'organization_id' => $orgId,
                        'role_id' => $roleId,
                        'module_id' => (int) $moduleId,
                        'sub_module_id' => $subModuleId,
                    ]);

                    $right->can_create = isset($flags['can_create']) && $flags['can_create'] == 1;
                    $right->can_read = isset($flags['can_read']) && $flags['can_read'] == 1;
                    $right->can_update = isset($flags['can_update']) && $flags['can_update'] == 1;
                    $right->can_delete = isset($flags['can_delete']) && $flags['can_delete'] == 1;
                    $right->updated_by = Auth::id();

                    if (!$right->exists) {
                        $right->created_by = Auth::id();
                    }

                    $right->save();
                    $processedKeys[] = $key;
                }
            }

            // Delete rights that no access flags were submitted for (unchecked)
            access_right::where('organization_id', $orgId)
                ->where('role_id', $roleId)
                ->where('can_create', false)
                ->where('can_read', false)
                ->where('can_update', false)
                ->where('can_delete', false)
                ->delete();

            // Delete rights that were not submitted (all unchecked)
            foreach ($existingRights as $key => $right) {
                if (!in_array($key, $processedKeys)) {
                    $right->delete();
                }
            }
        });

        return redirect()->route('admin.access', ['role_id' => $roleId])
            ->with('success', 'Access rights updated.');
    }
}