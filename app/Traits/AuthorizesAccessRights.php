<?php

namespace App\Traits;

use App\Constants\Modules;
use App\Services\AuthorizationService;
use Illuminate\Support\Facades\Auth;

trait AuthorizesAccessRights
{
    protected ?AuthorizationService $authService = null;

    /**
     * Get the authorization service instance
     */
    protected function auth(): AuthorizationService
    {
        if (!$this->authService) {
            $this->authService = new AuthorizationService(Auth::user());
        }
        return $this->authService;
    }

    // ==================== PERMISSION CHECKS ====================

    protected function hasPermission(string $moduleCode, string $permission, ?string $subModuleCode = null): bool
    {
        return $this->auth()->hasPermission($moduleCode, $permission, $subModuleCode);
    }

    protected function canCreate(string $moduleCode, ?string $subModuleCode = null): bool
    {
        return $this->auth()->canCreate($moduleCode, $subModuleCode);
    }

    protected function canRead(string $moduleCode, ?string $subModuleCode = null): bool
    {
        return $this->auth()->canRead($moduleCode, $subModuleCode);
    }

    protected function canUpdate(string $moduleCode, ?string $subModuleCode = null): bool
    {
        return $this->auth()->canUpdate($moduleCode, $subModuleCode);
    }

    protected function canDelete(string $moduleCode, ?string $subModuleCode = null): bool
    {
        return $this->auth()->canDelete($moduleCode, $subModuleCode);
    }

    // ==================== AUTHORIZATION ====================

    protected function authorizePermission(string $moduleCode, string $permission, ?string $subModuleCode = null): void
    {
        $this->auth()->authorize($moduleCode, $permission, $subModuleCode);
    }

    protected function authorizeCreate(string $moduleCode, ?string $subModuleCode = null): void
    {
        $this->auth()->authorizeCreate($moduleCode, $subModuleCode);
    }

    protected function authorizeRead(string $moduleCode, ?string $subModuleCode = null): void
    {
        $this->auth()->authorizeRead($moduleCode, $subModuleCode);
    }

    protected function authorizeUpdate(string $moduleCode, ?string $subModuleCode = null): void
    {
        $this->auth()->authorizeUpdate($moduleCode, $subModuleCode);
    }

    protected function authorizeDelete(string $moduleCode, ?string $subModuleCode = null): void
    {
        $this->auth()->authorizeDelete($moduleCode, $subModuleCode);
    }

    // ==================== GET PERMISSIONS ====================

    protected function getPermissions(string $moduleCode, ?string $subModuleCode = null): array
    {
        return $this->auth()->getPermissions($moduleCode, $subModuleCode);
    }

    // ==================== OWNERSHIP CHECKS ====================

    protected function ownsModel($model, string $userIdField = 'created_by'): bool
    {
        return $this->auth()->ownsModel($model, $userIdField);
    }

    // ==================== ADMIN CHECK ====================

    protected function isAdmin(): bool
    {
        return $this->auth()->isAdmin();
    }

    // ==================== MODULE-SPECIFIC HELPERS ====================

    // AP Module
    protected function canAccessRfd(): bool
    {
        return $this->canRead(Modules::AP, Modules::AP_RFD);
    }

    protected function authorizeRfd(): void
    {
        $this->authorizeRead(Modules::AP, Modules::AP_RFD);
    }

    protected function authorizeRfdCreate(): void
    {
        $this->authorizeCreate(Modules::AP, Modules::AP_RFD);
    }

    protected function authorizeRfdUpdate(): void
    {
        $this->authorizeUpdate(Modules::AP, Modules::AP_RFD);
    }

    protected function authorizeRfdDelete(): void
    {
        $this->authorizeDelete(Modules::AP, Modules::AP_RFD);
    }

    // CM Module
    protected function canAccessCashAdvance(): bool
    {
        return $this->canRead(Modules::CM, Modules::CM_CA);
    }

    protected function authorizeCashAdvance(): void
    {
        $this->authorizeRead(Modules::CM, Modules::CM_CA);
    }

    protected function authorizeCashAdvanceCreate(): void
    {
        $this->authorizeCreate(Modules::CM, Modules::CM_CA);
    }

    protected function authorizeCashAdvanceUpdate(): void
    {
        $this->authorizeUpdate(Modules::CM, Modules::CM_CA);
    }

    protected function authorizeLiquidation(): void
    {
        $this->authorizeUpdate(Modules::CM, Modules::CM_LIQ);
    }

    protected function authorizeRefund(): void
    {
        $this->authorizeUpdate(Modules::CM, Modules::CM_REF);
    }

    protected function authorizeReimbursement(): void
    {
        $this->authorizeUpdate(Modules::CM, Modules::CM_REIM);
    }

    // GL Module
    protected function authorizeJournalEntry(): void
    {
        $this->authorizeRead(Modules::GL, Modules::GL_JOURNAL);
    }

    protected function authorizeJournalEntryCreate(): void
    {
        $this->authorizeCreate(Modules::GL, Modules::GL_JOURNAL);
    }

    protected function authorizeJournalEntryUpdate(): void
    {
        $this->authorizeUpdate(Modules::GL, Modules::GL_JOURNAL);
    }
}