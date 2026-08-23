<?php

namespace App\Traits;

use App\Models\access_right;
use App\Models\module;
use App\Models\sub_module;
use Illuminate\Support\Facades\Auth;
use App\Services\SystemSettings;

trait AuthorizesAccessRights
{
    public function getOrganizationId(): ?int
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }
    /**
     * Check if user has access to a specific module and sub-module
     */
    protected function hasAccess(int $moduleId, int $subModuleId, string $permission): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Super admin or specific role can bypass (optional)
        // if ($user->role_id == 1) { // Assuming role_id 1 is super admin
        //     return true;
        // }

        // Check if user has access rights
        $accessRight = access_right::where('organization_id', $this->getOrganizationId())
            ->where('role_id', $user->role_id)
            ->where('module_id', $moduleId)
            ->where('sub_module_id', $subModuleId)
            ->first();

        if (!$accessRight) {
            return false;
        }

        // Check specific permission
        return match ($permission) {
            'create' => (bool) $accessRight->can_create,
            'read' => (bool) $accessRight->can_read,
            'update' => (bool) $accessRight->can_update,
            'delete' => (bool) $accessRight->can_delete,
            default => false,
        };
    }

    /**
     * Authorize user for RFD module access
     */
    protected function authorizeRfd(string $permission): void
    {
        $moduleId = 2; // Accounts Payable
        $subModuleId = 11; // Request for Disbursement

        if (!$this->hasAccess($moduleId, $subModuleId, $permission)) {
            abort(403, 'You do not have permission to perform this action.');
        }
    }

    /**
     * Authorize for viewing RFD
     */
    protected function authorizeRfdRead(): void
    {
        $this->authorizeRfd('read');
    }

    /**
     * Authorize for creating RFD
     */
    protected function authorizeRfdCreate(): void
    {
        $this->authorizeRfd('create');
    }

    /**
     * Authorize for updating RFD
     */
    protected function authorizeRfdUpdate(): void
    {
        $this->authorizeRfd('update');
    }

    /**
     * Authorize for deleting RFD
     */
    protected function authorizeRfdDelete(): void
    {
        $this->authorizeRfd('delete');
    }

    /**
     * Get user's access rights for RFD
     */
    protected function getRfdAccessRights(): ?array
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        // Super admin bypass
        if ($user->role_id == 1) {
            return [
                'can_create' => true,
                'can_read' => true,
                'can_update' => true,
                'can_delete' => true,
            ];
        }

        $accessRight = access_right::where('organization_id', $this->getOrganizationId())
            ->where('role_id', $user->role_id)
            ->where('module_id', 2) // Accounts Payable
            ->where('sub_module_id', 11) // Request for Disbursement
            ->first();

        if (!$accessRight) {
            return null;
        }

        return [
            'can_create' => (bool) $accessRight->can_create,
            'can_read' => (bool) $accessRight->can_read,
            'can_update' => (bool) $accessRight->can_update,
            'can_delete' => (bool) $accessRight->can_delete,
        ];
    }

    /**
     * Authorize specific action on a model instance
     */
    protected function authorizeRfdAction(string $action, $model = null): void
    {
        $rights = $this->getRfdAccessRights();

        if (!$rights) {
            abort(403, 'You do not have access to this module.');
        }

        $canPerform = match ($action) {
            'view', 'read' => $rights['can_read'],
            'create' => $rights['can_create'],
            'update', 'edit' => $rights['can_update'],
            'delete', 'destroy' => $rights['can_delete'],
            default => false,
        };

        if (!$canPerform) {
            abort(403, "You do not have permission to {$action} this request.");
        }
    }
}