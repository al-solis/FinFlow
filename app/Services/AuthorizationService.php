<?php

namespace App\Services;

use App\Constants\Modules;
use App\Models\access_right;
use App\Models\module;
use App\Models\User;
use App\Services\SystemSettings;
use Illuminate\Support\Facades\Auth;

class AuthorizationService
{
    protected ?User $user = null;
    protected ?int $organizationId = null;
    protected array $cachedRights = [];
    protected array $moduleCache = [];

    public function __construct(?User $user = null)
    {
        if ($user) {
            $this->setUser($user);
        } else {
            $this->setUser(Auth::user());
        }
    }

    /**
     * Set the user for authorization checks
     */
    public function setUser(?User $user): self
    {
        $this->user = $user;
        $this->organizationId = $this->getOrganizationId();
        $this->cachedRights = [];
        return $this;
    }

    /**
     * Get organization ID from system settings
     */
    protected function getOrganizationId(): ?int
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }

    /**
     * Get user's role ID
     */
    protected function getRoleId(): ?int
    {
        return $this->user?->role_id;
    }

    /**
     * Check if user is admin (role_id = 1)
     */
    public function isAdmin(): bool
    {
        return $this->getRoleId() === 1;
    }

    /**
     * Get module ID by code
     */
    public function getModuleId(string $moduleCode): ?int
    {
        if (isset($this->moduleCache[$moduleCode])) {
            return $this->moduleCache[$moduleCode];
        }

        $module = module::where('code', $moduleCode)->where('is_active', true)->first();
        $this->moduleCache[$moduleCode] = $module?->id;
        return $this->moduleCache[$moduleCode];
    }

    /**
     * Get sub-module ID by code
     */
    public function getSubModuleId(int $moduleId, string $subModuleCode): ?int
    {
        $cacheKey = $moduleId . '-' . $subModuleCode;
        if (isset($this->moduleCache[$cacheKey])) {
            return $this->moduleCache[$cacheKey];
        }

        $subModule = \App\Models\sub_module::where('module_id', $moduleId)
            ->where('code', $subModuleCode)
            ->where('is_active', true)
            ->first();

        $this->moduleCache[$cacheKey] = $subModule?->id;
        return $this->moduleCache[$cacheKey];
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $moduleCode, string $permission, ?string $subModuleCode = null): bool
    {
        // Admin bypass
        if ($this->isAdmin()) {
            return true;
        }

        if (!$this->user) {
            return false;
        }

        $moduleId = $this->getModuleId($moduleCode);
        if (!$moduleId) {
            return false;
        }

        $subModuleId = null;
        if ($subModuleCode) {
            $subModuleId = $this->getSubModuleId($moduleId, $subModuleCode);
            // If sub-module doesn't exist, check at module level
            if (!$subModuleId) {
                return $this->hasPermission($moduleCode, $permission, null);
            }
        }

        // Check cache
        $cacheKey = $moduleId . '-' . ($subModuleId ?? 'null') . '-' . $permission;
        if (isset($this->cachedRights[$cacheKey])) {
            return $this->cachedRights[$cacheKey];
        }

        // Query database
        $right = access_right::where('organization_id', $this->organizationId)
            ->where('role_id', $this->getRoleId())
            ->where('module_id', $moduleId)
            ->when($subModuleId, function ($q) use ($subModuleId) {
                return $q->where('sub_module_id', $subModuleId);
            }, function ($q) {
                return $q->whereNull('sub_module_id');
            })
            ->first();

        $hasPermission = $right && (bool) $right->{$permission};

        // Cache result
        $this->cachedRights[$cacheKey] = $hasPermission;

        return $hasPermission;
    }

    /**
     * Get all permissions for a module
     */
    public function getPermissions(string $moduleCode, ?string $subModuleCode = null): array
    {
        if ($this->isAdmin()) {
            return [
                'can_create' => true,
                'can_read' => true,
                'can_update' => true,
                'can_delete' => true,
            ];
        }

        $moduleId = $this->getModuleId($moduleCode);
        if (!$moduleId) {
            return $this->getDefaultPermissions();
        }

        $subModuleId = null;
        if ($subModuleCode) {
            $subModuleId = $this->getSubModuleId($moduleId, $subModuleCode);
        }

        $right = access_right::where('organization_id', $this->organizationId)
            ->where('role_id', $this->getRoleId())
            ->where('module_id', $moduleId)
            ->when($subModuleId, function ($q) use ($subModuleId) {
                return $q->where('sub_module_id', $subModuleId);
            }, function ($q) {
                return $q->whereNull('sub_module_id');
            })
            ->first();

        if (!$right) {
            return $this->getDefaultPermissions();
        }

        return [
            'can_create' => (bool) $right->can_create,
            'can_read' => (bool) $right->can_read,
            'can_update' => (bool) $right->can_update,
            'can_delete' => (bool) $right->can_delete,
        ];
    }

    /**
     * Get default permissions (all false)
     */
    protected function getDefaultPermissions(): array
    {
        return [
            'can_create' => false,
            'can_read' => false,
            'can_update' => false,
            'can_delete' => false,
        ];
    }

    // ==================== CONVENIENCE METHODS ====================

    public function canCreate(string $moduleCode, ?string $subModuleCode = null): bool
    {
        return $this->hasPermission($moduleCode, 'can_create', $subModuleCode);
    }

    public function canRead(string $moduleCode, ?string $subModuleCode = null): bool
    {
        return $this->hasPermission($moduleCode, 'can_read', $subModuleCode);
    }

    public function canUpdate(string $moduleCode, ?string $subModuleCode = null): bool
    {
        return $this->hasPermission($moduleCode, 'can_update', $subModuleCode);
    }

    public function canDelete(string $moduleCode, ?string $subModuleCode = null): bool
    {
        return $this->hasPermission($moduleCode, 'can_delete', $subModuleCode);
    }

    // ==================== AUTHORIZATION METHODS ====================

    public function authorize(string $moduleCode, string $permission, ?string $subModuleCode = null): void
    {
        if (!$this->hasPermission($moduleCode, $permission, $subModuleCode)) {
            $moduleLabel = Modules::getModuleLabel($moduleCode);
            $subModuleLabel = $subModuleCode ? ' / ' . Modules::getSubModuleLabel($subModuleCode) : '';
            $displayPermission = str_replace('can_', '', $permission);
            abort(403, "You don't have permission to {$displayPermission} on {$moduleLabel}{$subModuleLabel}.");
        }
    }

    public function authorizeCreate(string $moduleCode, ?string $subModuleCode = null): void
    {
        $this->authorize($moduleCode, 'can_create', $subModuleCode);
    }

    public function authorizeRead(string $moduleCode, ?string $subModuleCode = null): void
    {
        $this->authorize($moduleCode, 'can_read', $subModuleCode);
    }

    public function authorizeUpdate(string $moduleCode, ?string $subModuleCode = null): void
    {
        $this->authorize($moduleCode, 'can_update', $subModuleCode);
    }

    public function authorizeDelete(string $moduleCode, ?string $subModuleCode = null): void
    {
        $this->authorize($moduleCode, 'can_delete', $subModuleCode);
    }

    /**
     * Check if user owns a model
     */
    public function ownsModel($model, string $userIdField = 'created_by'): bool
    {
        if (!$this->user) {
            return false;
        }
        return $model && $model->{$userIdField} === $this->user->id;
    }

    /**
     * Get all accessible sub-modules for a module
     */
    public function getAccessibleSubModules(string $moduleCode): array
    {
        $moduleId = $this->getModuleId($moduleCode);
        if (!$moduleId) {
            return [];
        }

        $subModules = \App\Models\sub_module::where('module_id', $moduleId)
            ->where('is_active', true)
            ->get();

        $accessible = [];
        foreach ($subModules as $subModule) {
            $permissions = $this->getPermissions($moduleCode, $subModule->code);
            if (
                $permissions['can_read'] || $permissions['can_create'] ||
                $permissions['can_update'] || $permissions['can_delete']
            ) {
                $accessible[] = $subModule;
            }
        }

        return $accessible;
    }
}