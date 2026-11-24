<?php

namespace App\Services;

use App\Constants\Modules;
use App\Models\access_right;
use App\Models\module;
use App\Models\sub_module;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthorizationService
{
    protected ?User $user = null;
    protected ?int $organizationId = null;
    protected array $cachedRights = [];
    protected array $moduleCache = [];

    /**
     * Explicit map of Modules constant => sub_modules.route_name.
     * Extend this whenever a screen's route key differs from its code.
     */
    private const CODE_TO_ROUTE_NAME = [
        Modules::GL_CHART => 'gl.chart',
        Modules::GL_SEGMENTS => 'gl.segments',
        Modules::GL_STRUCTURE => 'gl.structure',
        Modules::GL_FISCAL => 'gl.fiscal',
        Modules::GL_PERIODS => 'gl.periods',
        Modules::GL_JOURNAL => 'gl.journal',
        Modules::GL_TRIAL => 'gl.trial',
        Modules::GL_GR => 'gl.gr',
        Modules::AP_VENDORS => 'ap.vendors',
        Modules::AP_CATEGORIES => 'ap.categories',
        Modules::AP_RFD => 'ap.rfd',
        Modules::AR_CUST => 'ar.cust',
        Modules::AR_CATG => 'ar.catg',
        Modules::AR_SER => 'ar.ser',
        Modules::AR_INV => 'ar.inv',
        Modules::AR_COL => 'ar.col',
        Modules::AR_CMEMO => 'ar.cmemo',
        Modules::AR_DMEMO => 'ar.dmemo',
        Modules::TAX_TY => 'tax.ty',
        Modules::TAX_TF => 'tax.tf',
        Modules::TAX_TM => 'tax.tm',
        Modules::ADMIN_ORG => 'admin.org',
        Modules::ADMIN_TERMS => 'admin.terms',
        Modules::ADMIN_METHOD => 'admin.method',
        Modules::ADMIN_CURRENCY => 'admin.currency',
        Modules::ADMIN_USER => 'admin.user',
        Modules::ADMIN_ROLE => 'admin.role',
        Modules::ADMIN_ACCESS => 'admin.access',
    ];

    public function __construct(?User $user = null)
    {
        $this->setUser($user ?? Auth::user());
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        $this->organizationId = $this->resolveOrganizationId();
        $this->cachedRights = [];
        return $this;
    }

    /**
     * Correct org lookup — settings table stores organization_id, not the PK.
     */
    protected function resolveOrganizationId(): ?int
    {
        $settings = SystemSettings::get();

        if (!$settings) {
            return null;
        }

        return $settings->organization_id ?? $settings->id ?? null;
    }

    protected function getRoleId(): ?int
    {
        return $this->user?->role_id;
    }

    public function isAdmin(): bool
    {
        return $this->getRoleId() === 1;
    }

    public function getModuleId(string $moduleCode): ?int
    {
        if (array_key_exists($moduleCode, $this->moduleCache)) {
            return $this->moduleCache[$moduleCode];
        }

        $module = module::where('code', $moduleCode)->where('is_active', true)->first();

        return $this->moduleCache[$moduleCode] = $module?->id;
    }

    /**
     * Resolve a sub-module ID from either its `code` or its `route_name`.
     */
    public function getSubModuleId(int $moduleId, string $subModuleCode): ?int
    {
        $cacheKey = $moduleId . '-' . $subModuleCode;
        if (array_key_exists($cacheKey, $this->moduleCache)) {
            return $this->moduleCache[$cacheKey];
        }

        $subModule = sub_module::where('module_id', $moduleId)
            ->where('is_active', true)
            ->where(function ($q) use ($subModuleCode) {
                // Accept either the raw constant ('AR-CMEMO') or the derived
                // route form ('ar.cmemo') in case legacy data stored that.
                $routeForm = strtolower(str_replace('-', '.', $subModuleCode));
                $q->where('code', $subModuleCode)
                    ->orWhere('code', $routeForm);
            })
            ->orderBy('id')
            ->first();

        return $this->moduleCache[$cacheKey] = $subModule?->id;
    }

    public function hasPermission(string $moduleCode, string $permission, ?string $subModuleCode = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        if (!$this->user || !$this->organizationId) {
            return false;
        }

        $moduleId = $this->getModuleId($moduleCode);
        if (!$moduleId) {
            return false;
        }

        $subModuleId = null;
        if ($subModuleCode) {
            $subModuleId = $this->getSubModuleId($moduleId, $subModuleCode);
            if (!$subModuleId) {
                // Sub-module cannot be resolved -> DENY. Do NOT fall back to module-level.
                return false;
            }
        }

        $cacheKey = $moduleId . '-' . ($subModuleId ?? 'null') . '-' . $permission;
        if (array_key_exists($cacheKey, $this->cachedRights)) {
            return $this->cachedRights[$cacheKey];
        }

        $right = access_right::query()
            ->where('role_id', $this->getRoleId())
            ->where('organization_id', $this->organizationId)
            ->where('module_id', $moduleId)
            ->when(
                $subModuleId,
                fn($q) => $q->where('sub_module_id', $subModuleId),
                fn($q) => $q->whereNull('sub_module_id')
            )
            ->orderByRaw('CASE WHEN sub_module_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('id')
            ->first();

        return $this->cachedRights[$cacheKey] = (bool) ($right && $right->{$permission});
    }

    public function getPermissions(string $moduleCode, ?string $subModuleCode = null): array
    {
        if ($this->isAdmin()) {
            return ['can_create' => true, 'can_read' => true, 'can_update' => true, 'can_delete' => true];
        }
        if (!$this->user || !$this->organizationId) {
            return $this->getDefaultPermissions();
        }

        $moduleId = $this->getModuleId($moduleCode);
        if (!$moduleId) {
            return $this->getDefaultPermissions();
        }

        $subModuleId = null;
        if ($subModuleCode) {
            $subModuleId = $this->getSubModuleId($moduleId, $subModuleCode);
            if (!$subModuleId) {
                return $this->getDefaultPermissions();
            }
        }

        $right = access_right::query()
            ->where('role_id', $this->getRoleId())
            ->where('organization_id', $this->organizationId)
            ->where('module_id', $moduleId)
            ->when(
                $subModuleId,
                fn($q) => $q->where('sub_module_id', $subModuleId),
                fn($q) => $q->whereNull('sub_module_id')
            )
            ->orderByRaw('CASE WHEN sub_module_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('id')
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
    protected function getDefaultPermissions(): array
    {
        return [
            'can_create' => false,
            'can_read' => false,
            'can_update' => false,
            'can_delete' => false,
        ];
    }

    // ==================== CONVENIENCE ====================

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

    // ==================== AUTHORIZATION ====================

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

    public function ownsModel($model, string $userIdField = 'created_by'): bool
    {
        if (!$this->user || !$model) {
            return false;
        }
        return (int) $model->{$userIdField} === (int) $this->user->id;
    }

    public function getAccessibleSubModules(string $moduleCode): array
    {
        $moduleId = $this->getModuleId($moduleCode);
        if (!$moduleId) {
            return [];
        }

        return sub_module::where('module_id', $moduleId)
            ->where('is_active', true)
            ->get()
            ->filter(function ($subModule) use ($moduleCode) {
                $p = $this->getPermissions($moduleCode, $subModule->code);
                return $p['can_read'] || $p['can_create'] || $p['can_update'] || $p['can_delete'];
            })
            ->values()
            ->all();
    }
}