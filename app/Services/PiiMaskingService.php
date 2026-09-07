<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Constants\Modules;

class PiiMaskingService
{
    /**
     * PII field types and their masking rules
     */
    public const PII_TYPES = [
        'full_name' => 'full_name',
        'first_name' => 'first_name',
        'last_name' => 'last_name',
        'email' => 'email',
        'phone' => 'phone',
        'mobile' => 'mobile',
        'address' => 'address',
        'tax_id' => 'tax_id',
        'employee_id' => 'employee_id',
        'bank_account' => 'bank_account',
        'passport' => 'passport',
        'birthdate' => 'birthdate',
        'gender' => 'gender',
    ];

    /**
     * Masking levels
     */
    public const MASK_LEVEL_NONE = 'none';        // Full access (admin or write access on this module)
    public const MASK_LEVEL_PARTIAL = 'partial';  // Partial masking (read access only)
    public const MASK_LEVEL_FULL = 'full';        // Full masking (no access)

    /**
     * PII Modules mapping - defines which modules contain PII data
     * and what permissions are required for each level
     */
    public const PII_MODULES = [
        'AP' => [
            'sub_modules' => [
                'AP_VENDORS' => [
                    'fields' => [
                        'name' => 'full_name',
                        'legal_name' => 'full_name',
                        'contact_person' => 'full_name',
                        'email' => 'email',
                        'phone' => 'phone',
                        'mobile' => 'mobile',
                        'tax_id' => 'tax_id',
                        'address1' => 'address',
                        'address2' => 'address',
                    ]
                ],
                'AP_CATEGORIES' => [
                    'fields' => [
                        'name' => 'full_name',
                        'description' => 'address',
                    ]
                ],
            ]
        ],
        'ADMIN' => [
            'sub_modules' => [
                'ADMIN_USER' => [
                    'fields' => [
                        'first_name' => 'first_name',
                        'last_name' => 'last_name',
                        'middle_name' => 'full_name',
                        'email' => 'email',
                        'employee_id' => 'employee_id',
                    ]
                ],
                'ADMIN_ROLE' => [
                    'fields' => [
                        'name' => 'full_name',
                    ]
                ],
                'ADMIN_ORG' => [
                    'fields' => [
                        'name' => 'full_name',
                        'legal_name' => 'full_name',
                        'contact_person' => 'full_name',
                        'email' => 'email',
                        'phone' => 'phone',
                    ]
                ],
            ]
        ],
        'CM' => [
            'sub_modules' => [
                'CM_CA' => [
                    'fields' => [
                        'employee_id' => 'employee_id',
                        'purpose' => 'address',
                    ]
                ],
                'CM_LIQ' => [
                    'fields' => [
                        'remarks' => 'address',
                    ]
                ],
                'CM_REF' => [
                    'fields' => [
                        'purpose' => 'address',
                    ]
                ],
                'CM_REIM' => [
                    'fields' => [
                        'purpose' => 'address',
                    ]
                ],
            ]
        ],
        'BM' => [
            'sub_modules' => [
                'BM_BANK' => [
                    'fields' => [
                        'account_number' => 'bank_account',
                        'account_name' => 'full_name',
                    ]
                ],
            ]
        ],
    ];

    protected ?User $user = null;
    protected ?AuthorizationService $authService = null;
    protected string $maskLevel = self::MASK_LEVEL_FULL;
    protected ?string $currentModule = null;
    protected ?string $currentSubModule = null;

    public function __construct(?User $user = null)
    {
        $this->user = $user ?? Auth::user();
        $this->authService = new AuthorizationService($this->user);
        $this->maskLevel = self::MASK_LEVEL_FULL;
    }

    /**
     * Set the current module context for PII masking
     */
    public function setContext(string $module, ?string $subModule = null): self
    {
        $this->currentModule = $module;
        $this->currentSubModule = $subModule;
        $this->determineMaskLevel();
        return $this;
    }

    /**
     * Determine the mask level based on user's permissions for the current module
     */
    protected function determineMaskLevel(): void
    {
        if (!$this->user) {
            $this->maskLevel = self::MASK_LEVEL_FULL;
            return;
        }

        // Admin users get full access
        if ($this->user->isAdmin()) {
            $this->maskLevel = self::MASK_LEVEL_NONE;
            return;
        }

        // If no module context is set, check if user has ANY write access globally
        // This is the fallback behavior
        if (!$this->currentModule) {
            $this->maskLevel = $this->hasGlobalWriteAccess() ? self::MASK_LEVEL_NONE : self::MASK_LEVEL_PARTIAL;
            return;
        }

        // Check permissions for the current module
        $module = $this->currentModule;
        $subModule = $this->currentSubModule;

        // Check if user has write access on this specific module/sub-module
        if ($this->hasWriteAccess($module, $subModule)) {
            $this->maskLevel = self::MASK_LEVEL_NONE;
            return;
        }

        // Check if user has read access on this specific module/sub-module
        if ($this->hasReadAccess($module, $subModule)) {
            $this->maskLevel = self::MASK_LEVEL_PARTIAL;
            return;
        }

        // Default: full masking
        $this->maskLevel = self::MASK_LEVEL_FULL;
    }

    /**
     * Check if user has write access on a specific module
     */
    protected function hasWriteAccess(string $module, ?string $subModule = null): bool
    {
        // Check sub-module level first
        if ($subModule) {
            if ($this->authService->canCreate($module, $subModule)) {
                return true;
            }
            if ($this->authService->canUpdate($module, $subModule)) {
                return true;
            }
            if ($this->authService->canDelete($module, $subModule)) {
                return true;
            }
        }

        // Check module level
        if ($this->authService->canCreate($module)) {
            return true;
        }
        if ($this->authService->canUpdate($module)) {
            return true;
        }
        if ($this->authService->canDelete($module)) {
            return true;
        }

        return false;
    }

    /**
     * Check if user has read access on a specific module
     */
    protected function hasReadAccess(string $module, ?string $subModule = null): bool
    {
        // Check sub-module level first
        if ($subModule) {
            if ($this->authService->canRead($module, $subModule)) {
                return true;
            }
        }

        // Check module level
        if ($this->authService->canRead($module)) {
            return true;
        }

        return false;
    }

    /**
     * Check if user has global write access (for fallback)
     */
    protected function hasGlobalWriteAccess(): bool
    {
        $modules = ['AP', 'ADMIN', 'CM', 'GL', 'BM', 'TAX'];
        foreach ($modules as $module) {
            if (
                $this->authService->canCreate($module) ||
                $this->authService->canUpdate($module) ||
                $this->authService->canDelete($module)
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get current mask level
     */
    public function getMaskLevel(): string
    {
        return $this->maskLevel;
    }

    /**
     * Check if user has full access for the current context
     */
    public function hasFullAccess(): bool
    {
        return $this->maskLevel === self::MASK_LEVEL_NONE;
    }

    /**
     * Check if user has partial access for the current context
     */
    public function hasPartialAccess(): bool
    {
        return $this->maskLevel === self::MASK_LEVEL_PARTIAL;
    }

    /**
     * Check if user has read-only access for the current context
     */
    public function hasReadOnlyAccess(): bool
    {
        return $this->maskLevel === self::MASK_LEVEL_PARTIAL ||
            $this->maskLevel === self::MASK_LEVEL_FULL;
    }

    /**
     * Mask a value based on PII type and current module context
     */
    public function mask($value, string $piiType): string
    {
        if (empty($value)) {
            return '';
        }

        // Full access - show original value
        if ($this->hasFullAccess()) {
            return (string) $value;
        }

        // Partial access - apply partial masking
        if ($this->hasPartialAccess()) {
            return $this->applyPartialMask($value, $piiType);
        }

        // No access - full masking
        return $this->applyFullMask($value, $piiType);
    }

    /**
     * Get PII fields for a specific module/sub-module
     */
    public function getPiiFields(string $module, ?string $subModule = null): array
    {
        if (!isset(self::PII_MODULES[$module])) {
            return [];
        }

        if ($subModule && isset(self::PII_MODULES[$module]['sub_modules'][$subModule])) {
            return self::PII_MODULES[$module]['sub_modules'][$subModule]['fields'];
        }

        // Return all fields for the module if sub-module not found
        $allFields = [];
        foreach (self::PII_MODULES[$module]['sub_modules'] as $sub => $data) {
            $allFields = array_merge($allFields, $data['fields']);
        }
        return $allFields;
    }

    // ==================== MASKING METHODS ====================

    /**
     * Apply partial masking based on PII type
     */
    protected function applyPartialMask($value, string $piiType): string
    {
        $value = (string) $value;

        switch ($piiType) {
            case self::PII_TYPES['full_name']:
                return $this->maskName($value, 2);
            case self::PII_TYPES['first_name']:
                return $this->maskName($value, 1);
            case self::PII_TYPES['last_name']:
                return $this->maskName($value, 1);
            case self::PII_TYPES['email']:
                return $this->maskEmail($value);
            case self::PII_TYPES['phone']:
            case self::PII_TYPES['mobile']:
                return $this->maskPhone($value);
            case self::PII_TYPES['address']:
                return $this->maskAddress($value);
            case self::PII_TYPES['tax_id']:
                return $this->maskTaxId($value);
            case self::PII_TYPES['employee_id']:
                return $this->maskEmployeeId($value);
            case self::PII_TYPES['bank_account']:
                return $this->maskBankAccount($value);
            case self::PII_TYPES['passport']:
                return $this->maskPassport($value);
            case self::PII_TYPES['birthdate']:
                return $this->maskBirthdate($value);
            case self::PII_TYPES['gender']:
                return $this->maskGender($value);
            default:
                return $this->maskGeneric($value);
        }
    }

    /**
     * Apply full masking
     */
    protected function applyFullMask($value, string $piiType): string
    {
        $value = (string) $value;

        switch ($piiType) {
            case self::PII_TYPES['full_name']:
            case self::PII_TYPES['first_name']:
            case self::PII_TYPES['last_name']:
                return $this->maskName($value, 0);
            case self::PII_TYPES['email']:
                return str_repeat('*', strlen($value));
            case self::PII_TYPES['phone']:
            case self::PII_TYPES['mobile']:
                return str_repeat('*', strlen($value));
            case self::PII_TYPES['address']:
                return str_repeat('*', strlen($value));
            case self::PII_TYPES['tax_id']:
                return str_repeat('*', strlen($value));
            case self::PII_TYPES['employee_id']:
                return str_repeat('*', strlen($value));
            case self::PII_TYPES['bank_account']:
                return str_repeat('*', strlen($value));
            case self::PII_TYPES['passport']:
                return str_repeat('*', strlen($value));
            case self::PII_TYPES['birthdate']:
                return str_repeat('*', 10);
            case self::PII_TYPES['gender']:
                return str_repeat('*', strlen($value));
            default:
                return str_repeat('*', strlen($value));
        }
    }

    /**
     * Mask a name - show first N characters
     */
    protected function maskName(string $name, int $visibleChars = 1): string
    {
        if (empty($name)) {
            return '';
        }

        $name = trim($name);
        $length = strlen($name);

        if ($visibleChars <= 0) {
            return str_repeat('*', $length);
        }

        if ($length <= $visibleChars) {
            return $name;
        }

        $visible = substr($name, 0, $visibleChars);
        $masked = str_repeat('*', $length - $visibleChars);

        return $visible . $masked;
    }

    /**
     * Mask email - show first character and domain
     */
    protected function maskEmail(string $email): string
    {
        if (empty($email) || !str_contains($email, '@')) {
            return str_repeat('*', strlen($email));
        }

        $parts = explode('@', $email);
        $username = $parts[0];
        $domain = $parts[1] ?? '';

        if (strlen($username) <= 2) {
            $maskedUsername = $username[0] . str_repeat('*', strlen($username) - 1);
        } else {
            $maskedUsername = $username[0] . str_repeat('*', strlen($username) - 2) . substr($username, -1);
        }

        return $maskedUsername . '@' . $domain;
    }

    /**
     * Mask phone number - show last 4 digits
     */
    protected function maskPhone(string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);
        $length = strlen($phone);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        $visible = substr($phone, -4);
        $masked = str_repeat('*', $length - 4);

        return $masked . $visible;
    }

    /**
     * Mask address - show only first few characters
     */
    protected function maskAddress(string $address): string
    {
        if (empty($address)) {
            return '';
        }

        $lines = explode("\n", $address);
        $firstLine = $lines[0] ?? '';

        if (strlen($firstLine) <= 10) {
            return str_repeat('*', strlen($firstLine));
        }

        return substr($firstLine, 0, 3) . str_repeat('*', strlen($firstLine) - 6) . substr($firstLine, -3);
    }

    /**
     * Mask Tax ID - show last 4 digits
     */
    protected function maskTaxId(string $taxId): string
    {
        if (empty($taxId)) {
            return '';
        }

        $taxId = preg_replace('/[^0-9-]/', '', $taxId);
        $length = strlen($taxId);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        $visible = substr($taxId, -4);
        $masked = str_repeat('*', $length - 4);

        return $masked . $visible;
    }

    /**
     * Mask Employee ID - show last 4 digits
     */
    protected function maskEmployeeId(string $employeeId): string
    {
        if (empty($employeeId)) {
            return '';
        }

        $length = strlen($employeeId);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        $visible = substr($employeeId, -4);
        $masked = str_repeat('*', $length - 4);

        return $masked . $visible;
    }

    /**
     * Mask Bank Account - show last 4 digits
     */
    protected function maskBankAccount(string $account): string
    {
        if (empty($account)) {
            return '';
        }

        $account = preg_replace('/[^0-9]/', '', $account);
        $length = strlen($account);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        $visible = substr($account, -4);
        $masked = str_repeat('*', $length - 4);

        return $masked . $visible;
    }

    /**
     * Mask Passport - show last 4 digits
     */
    protected function maskPassport(string $passport): string
    {
        if (empty($passport)) {
            return '';
        }

        $length = strlen($passport);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        $visible = substr($passport, -4);
        $masked = str_repeat('*', $length - 4);

        return $masked . $visible;
    }

    /**
     * Mask Birthdate - show day only
     */
    protected function maskBirthdate(string $birthdate): string
    {
        if (empty($birthdate)) {
            return '';
        }

        try {
            $date = \Carbon\Carbon::parse($birthdate);
            return '****-**-' . $date->format('d');
        } catch (\Exception $e) {
            return str_repeat('*', 10);
        }
    }

    /**
     * Mask Gender - show first letter only
     */
    protected function maskGender(string $gender): string
    {
        if (empty($gender)) {
            return '';
        }

        return strtoupper(substr($gender, 0, 1)) . str_repeat('*', strlen($gender) - 1);
    }

    /**
     * Generic masking for unknown types
     */
    protected function maskGeneric(string $value): string
    {
        $length = strlen($value);

        if ($length <= 2) {
            return str_repeat('*', $length);
        }

        return $value[0] . str_repeat('*', $length - 2) . substr($value, -1);
    }

    /**
     * Mask a collection of items with PII fields for the current context
     */
    public function maskCollection($collection, array $fieldsWithTypes): array
    {
        if ($this->hasFullAccess()) {
            return $collection->toArray();
        }

        return $collection->map(function ($item) use ($fieldsWithTypes) {
            foreach ($fieldsWithTypes as $field => $piiType) {
                if (isset($item->{$field})) {
                    $item->{$field} = $this->mask($item->{$field}, $piiType);
                }
            }
            return $item;
        })->toArray();
    }

    /**
     * Mask an array item
     */
    public function maskArray(array $data, array $fieldsWithTypes): array
    {
        if ($this->hasFullAccess()) {
            return $data;
        }

        foreach ($fieldsWithTypes as $field => $piiType) {
            if (isset($data[$field])) {
                $data[$field] = $this->mask($data[$field], $piiType);
            }
        }

        return $data;
    }

    /**
     * Mask a single model's PII fields
     */
    public function maskModel($model, array $fieldsWithTypes)
    {
        if (!$model) {
            return $model;
        }

        if ($this->hasFullAccess()) {
            return $model;
        }

        foreach ($fieldsWithTypes as $field => $piiType) {
            if (isset($model->{$field})) {
                $model->{$field} = $this->mask($model->{$field}, $piiType);
            }
        }

        return $model;
    }

    /**
     * Get the masked value for display
     */
    public static function display($value, string $piiType): string
    {
        $masker = app('pii.masker');
        return $masker->mask($value, $piiType);
    }

    /**
     * Get the mask level label for display
     */
    public function getMaskLevelLabel(): string
    {
        return match ($this->maskLevel) {
            self::MASK_LEVEL_NONE => 'Full Access',
            self::MASK_LEVEL_PARTIAL => 'Limited Access (PII Masked)',
            self::MASK_LEVEL_FULL => 'Restricted Access',
            default => 'Unknown',
        };
    }

    /**
     * Get the mask level color for display
     */
    public function getMaskLevelColor(): string
    {
        return match ($this->maskLevel) {
            self::MASK_LEVEL_NONE => 'green',
            self::MASK_LEVEL_PARTIAL => 'yellow',
            self::MASK_LEVEL_FULL => 'red',
            default => 'gray',
        };
    }

    /**
     * Get the mask level badge HTML
     */
    public function getMaskLevelBadge(): string
    {
        $label = $this->getMaskLevelLabel();
        $color = $this->getMaskLevelColor();

        return sprintf(
            '<span class="inline-flex items-center rounded-full bg-%s-100 px-2.5 py-0.5 text-xs font-medium text-%s-800">
                <span class="h-1.5 w-1.5 rounded-full bg-%s-500 mr-1"></span>
                %s
            </span>',
            $color,
            $color,
            $color,
            $label
        );
    }

    /**
     * Debug method to check permission status for current context
     */
    public function debugPermissions(): array
    {
        return [
            'user_id' => $this->user?->id,
            'role_id' => $this->user?->role_id,
            'is_admin' => $this->user?->isAdmin(),
            'current_module' => $this->currentModule,
            'current_sub_module' => $this->currentSubModule,
            'mask_level' => $this->maskLevel,
            'mask_level_label' => $this->getMaskLevelLabel(),
            'mask_level_color' => $this->getMaskLevelColor(),
            'mask_level_badge' => $this->getMaskLevelBadge(),
        ];
    }
}