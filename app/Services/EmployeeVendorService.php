<?php

namespace App\Services;

use App\Models\vendor;
use App\Models\VendorCategory;
use App\Models\User;
use App\Models\chart_of_account;
use App\Models\main_account;
use App\Services\SystemSettings;
use Illuminate\Support\Facades\DB;

class EmployeeVendorService
{
    private function getOrganizationId()
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }

    /**
     * Get or create a vendor record for an employee
     */
    public function getOrCreateEmployeeVendor(User $employee): vendor
    {
        // Check if vendor already exists for this employee
        // $vendor = vendor::where('organization_id', $this->getOrganizationId())
        //     ->where('code', 'EMP-' . str_pad($employee->id, 6, '0', STR_PAD_LEFT))
        //     ->first();
        $vendorCode = user::where('id', $employee->id)->value('vendor_code');
        $vendor = vendor::where('organization_id', $this->getOrganizationId())
            ->where('id', $vendorCode)
            ->first();

        if ($vendor) {
            return $vendor;
        }

        return DB::transaction(function () use ($employee) {
            // Get the Employee category
            $category = VendorCategory::where('code', 'EMP')
                ->orWhere('name', 'Employee')
                ->first();

            if (!$category) {
                throw new \RuntimeException('Employee vendor category not found. Please create a vendor category with code "EMP".');
            }

            // Get default AP account for employees
            $apAccountId = $this->getEmployeeApAccountId();

            // Create vendor record
            $vendor = vendor::create([
                'organization_id' => $this->getOrganizationId(),
                'code' => 'EMP-' . str_pad($employee->id, 6, '0', STR_PAD_LEFT),
                'name' => trim(($employee->last_name ?? '') . ', ' . ($employee->first_name ?? '') . ' ' . ($employee->middle_name ?? '')),
                'legal_name' => $employee->name ?? '',
                'vendor_category_id' => $category->id,
                'email' => $employee->email,
                'contact_person' => $employee->name ?? '',
                'phone' => $employee->phone ?? '',
                'is_active' => true,
                'default_ap_chart_of_account_id' => $apAccountId,
                'created_by' => auth()->id() ?? 1,
                'remarks' => 'Auto-created employee vendor for cash advances',
            ]);

            user::where('id', $employee->id)->update(['vendor_code' => $vendor->id]);

            return $vendor;
        });
    }

    /**
     * Get the default AP account for employees
     */
    protected function getEmployeeApAccountId(): ?int
    {
        // Try to find a specific employee AP account
        $mainAccount = main_account::where('code', 'EMPLOYEE_PAYABLE')
            ->orWhere('description', 'LIKE', '%Employee Payable%')
            ->first();

        if ($mainAccount) {
            // Find the chart of account for this main account
            $chartAccount = chart_of_account::where('main_account_id', $mainAccount->id)
                ->where('organization_id', $this->getOrganizationId())
                ->where('is_posting', true)
                ->where('status', true)
                ->first();

            if ($chartAccount) {
                return $chartAccount->id;
            }
        }

        // Fallback: Use any liability account
        $chartAccount = chart_of_account::where('organization_id', $this->getOrganizationId())
            ->whereHas('accountType', fn($q) => $q->where('code', 'LIABILITY'))
            ->where('is_posting', true)
            ->where('status', true)
            ->first();

        return $chartAccount?->id;
    }

    /**
     * Check if a vendor is an employee
     */
    public function isEmployeeVendor(vendor $vendor): bool
    {
        $category = $vendor->category;
        if (!$category) {
            return false;
        }

        return $category->code === 'EMP' || $category->name === 'Employee';
    }

    /**
     * Get the employee user from a vendor
     */
    public function getEmployeeFromVendor(vendor $vendor): ?User
    {
        if (!$this->isEmployeeVendor($vendor)) {
            return null;
        }

        // Extract employee ID from vendor code (EMP-000123)
        if (preg_match('/EMP-(\d+)/', $vendor->code, $matches)) {
            return User::find($matches[1]);
        }

        // Try to find by email
        return User::where('email', $vendor->email)->first();
    }
}