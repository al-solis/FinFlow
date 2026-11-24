<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1. Activate the AR module (seeded as inactive)
        DB::table('modules')->where('code', 'AR')->update(['is_active' => true, 'updated_at' => now()]);
        $moduleId = DB::table('modules')->where('code', 'AR')->value('id');

        $subs = [
            ['AR-CUST', 'Customer Master', 'Customer records used across Accounts Receivable', 'Maintenance', 1, 'fa fa-users', 'customer.png'],
            ['AR-CATG', 'Customer Categories', 'Customer classification', 'Maintenance', 2, 'fa fa-list', 'customer-category.png'],
            ['AR-SER', 'Invoice Series', 'BIR-registered document number series', 'Maintenance', 3, 'fa fa-file-text', 'invoice-series.png'],
            ['AR-INV', 'Sales Invoice', 'Billing and sales invoices', '', 4, 'fa fa-file-invoice', 'sales-invoice.png'],
            ['AR-COL', 'Collections', 'Receiving payments from customers', '', 5, 'fa fa-money-bill', 'collections.png'],
            ['AR-WHT', 'BIR 2307 Register', 'Creditable withholding tax certificates', '', 6, 'fa fa-file-alt', 'bir.png'],
        ];

        foreach ($subs as [$code, $name, $desc, $group, $seq, $icon, $img]) {
            if (DB::table('sub_modules')->where('code', $code)->exists()) {
                continue;
            }
            $id = DB::table('sub_modules')->insertGetId([
                'module_id' => $moduleId,
                'code' => $code,
                'name' => $name,
                'description' => $desc,
                'group' => $group,
                'icon' => $icon,
                'img' => $img,
                'src' => '',
                'sequence' => $seq,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Full access for the Administrator role, same as the original access_rights seed
            DB::table('access_rights')->insert([
                'organization_id' => 1,
                'role_id' => 1,
                'module_id' => $moduleId,
                'sub_module_id' => $id,
                'can_create' => true,
                'can_read' => true,
                'can_update' => true,
                'can_delete' => true,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Creditable withholding tax masters (customer withholds from us => an ASSET).
        //    gl_account_id is left NULL on purpose: point them at your "Creditable Withholding Tax" account.
        $cwtType = DB::table('tax_types')->where('code', 'CWT')->value('id');
        if (!$cwtType) {
            $cwtType = DB::table('tax_types')->insertGetId([
                'organization_id' => 1,
                'code' => 'CWT',
                'name' => 'Creditable Withholding Tax',
                'description' => 'Tax withheld by customers from our income; claimed against income tax (BIR Form 2307).',
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $formulaId = DB::table('tax_formulas')->where('code', 'EWT')->value('id'); // Percentage / Gross / Deduct

        foreach ([['CWT1', '1% Creditable Withholding Tax', 1.00], ['CWT2', '2% Creditable Withholding Tax', 2.00], ['CWT5', '5% Creditable Withholding Tax', 5.00]] as [$code, $name, $rate]) {
            if (DB::table('tax_masters')->where('code', $code)->exists()) {
                continue;
            }
            DB::table('tax_masters')->insert([
                'organization_id' => 1,
                'code' => $code,
                'name' => $name,
                'tax_type_id' => $cwtType,
                'tax_formula_id' => $formulaId,
                'rate' => $rate,
                'fixed_amount' => null,
                'gl_account_id' => null,
                'recoverable' => true,
                'priority' => 1,
                'effective_from' => now(),
                'effective_to' => null,
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $ids = DB::table('tax_types')->where('code', 'CWT')->pluck('id');
        DB::table('tax_masters')->whereIn('tax_type_id', $ids)->delete();
        DB::table('tax_types')->where('code', 'CWT')->delete();

        $ids = DB::table('sub_modules')->whereIn('code', ['AR-CUST', 'AR-CATG', 'AR-SER', 'AR-INV', 'AR-COL', 'AR-WHT'])->pluck('id');
        DB::table('access_rights')->whereIn('sub_module_id', $ids)->delete();
        DB::table('sub_modules')->whereIn('id', $ids)->delete();
        DB::table('modules')->where('code', 'AR')->update(['is_active' => false]);
    }
};
